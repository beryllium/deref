<?php

namespace Deref;

use Beryllium\Cache\Cache;
use Deref\Exceptions\CommunicationException;
use Deref\Exceptions\InvalidUrlException;
use Deref\Exceptions\TooManyRedirectsException;
use Psr\Log\LoggerAwareTrait;

use function \in_array;
use function \strlen;

class Deref
{
    use LoggerAwareTrait;

    protected $maxHops;
    protected $userAgent;  // used for fixing an issue with facebook redirecting to "/unsupported-browser"

    /**
     * @var Cache
     */
    private $cache = null;

    public function __construct(int $maxHops = 10, string $userAgent = 'deref')
    {
        $this->maxHops   = $maxHops;
        $this->userAgent = $userAgent;
    }

    /**
     * Follow all the redirects of a URL and return an array containing all results.
     *
     * This is a recursive method that calls itself until the redirect chain is exhausted or else
     * the max recursion depth is reached (>10 redirects, in this case)
     *
     * @param  string $url                  URL to check
     * @param  int    $depth                (internal) Current recursion depth (starts at 0)
     * @param  bool   $checkMeta            Whether to check for html-based meta tag redirects
     *
     * @return array                        An array of URL matches
     * @throws TooManyRedirectsException    If recursion depth is exceeded
     * @throws InvalidUrlException          If URL fails validation
     * @throws CommunicationException       If a Curl error happens
     */
    public function getRedirectLog($url, $depth = 0, $checkMeta = false)
    {
        if ($depth > $this->maxHops) {
            throw new TooManyRedirectsException('Too Many Redirects');
        }

        $url  = $this->filterUrl($url);

        // Check for a recent copy in the cache and return one, if found
        $cachedResult = $this->getCachedResult($url);
        if ($cachedResult) {
            return $cachedResult;
        }

        $curl = $this->getCurlClient($url);

        $startTime = microtime(true);
        $result    = curl_exec($curl);
        $redirect  = curl_getinfo($curl, CURLINFO_REDIRECT_URL);

        $logInfo = [
            'hop'       => $depth + 1,
            'timeTaken' => microtime(true) - $startTime,
            'url'       => $url,
        ];

        if (!$result) {
            $logInfo['curlError'] = curl_error($curl);
            $this->logger->notice('Communication failed', $logInfo);

            throw new CommunicationException($logInfo['curlError']);
        }

        $this->logger->debug('fetched hop ' . $depth, $logInfo);

        // If this is an HTTP 301/302 redirect response, that means we've got to go deeper
        // Recurse with a $depth + 1 to make sure we don't go too deep
        if ($redirect) {
            $fetchedResult = array_merge([$url], $this->getRedirectLog($redirect, $depth + 1, $checkMeta));
            $this->cacheResult($url, $fetchedResult);

            return $fetchedResult;
        }

        // If this is an HTTP 200 response, we must fetch the body (at least the first few KB)
        // and scan for a meta redirect URL
        $metaRedirect = $this->checkMetaRedirectUrl($url, $checkMeta);
        if ($metaRedirect) {
            $this->logger->debug('found meta redirect URL', ['url' => $metaRedirect]);

            $fetchedResult = array_merge([$url], $this->getRedirectLog($metaRedirect, $depth + 1, $checkMeta));
            $this->cacheResult($url, $fetchedResult);

            return $fetchedResult;
        }

        $this->cacheResult($url, [$url]);

        return [$url];
    }

    /**
     * Examine a URL for HTML "meta" redirects
     *
     * @param string $url       The URL to inspect; ideally, we should already know that this is an HTTP 200 URL
     * @param bool   $checkMeta Whether to check for the meta redirect tag
     *
     * @return bool|string       False if there is no meta redirect, otherwise the URL
     * @throws CommunicationException
     */
    public function checkMetaRedirectUrl($url, $checkMeta = true)
    {
        if (!$checkMeta) {
            return false;
        }

        $data        = fopen('php://memory', 'w+b');
        $curlOptions = [];

        // enable transferring the body data back
        $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        $curlOptions[CURLOPT_NOBODY]         = false;

        // this needs to write to a tmp file so that timed-out content can be read
        $curlOptions[CURLOPT_FILE] = $data;

        $curl = $this->getCurlClient($url, $curlOptions);

        $startTime = microtime(true);
        curl_exec($curl);

        rewind($data);
        $result = stream_get_contents($data);

        fclose($data);

        $logInfo = [
            'hop'       => 'http-200 hop',
            'timeTaken' => microtime(true) - $startTime,
            'url'       => $url,
            'bytes'     => strlen($result),
        ];

        if (!$result) {
            $logInfo['curlError'] = curl_error($curl);
            $this->logger->notice('Communication failed', $logInfo);

            throw new CommunicationException($logInfo['curlError']);
        }

        $this->logger->debug('fetched http-200', $logInfo);

        // tidy HTML fragment to ensure it can be parsed
        // extract metadata and look for a redirect URL

        try {
            $crawler = new \Symfony\Component\DomCrawler\Crawler($result);
            $content = $crawler->filter('meta[http-equiv=refresh]')->attr('content');
        } catch (\InvalidArgumentException $e) {
        }

        if ($content ?? false) {
            $this->logger->debug('found meta redirect tag', ['meta-content' => $content]);
            $elements   = explode(';', $content, 2);
            $contentUrl = $elements[1] ?? false;
        }

        if ($contentUrl ?? false) {
            $elements    = explode('=', $contentUrl, 2);
            $redirectUrl = $elements[1] ?? false;
        }

        return $redirectUrl ?? false;
    }

    /**
     * Filter URLs to ensure they are http:// or https://
     * If a URL does not provide a scheme, it will be given http://
     *
     * @param  string $url          URL to filter
     *
     * @return string               Filtered URL
     * @throws InvalidUrlException  If the URL is unusable
     */
    public function filterUrl($url)
    {
        // Ensure the URL is OK to work with
        $url_scheme = parse_url($url, PHP_URL_SCHEME);
        if (in_array($url_scheme, ['http', 'https'])) {
            return $url;
        }

        if (!$url_scheme && 'http' === parse_url('http://' . $url, PHP_URL_SCHEME)) {
            return 'http://' . $url;
        }

        throw new InvalidUrlException('Invalid URL encountered in redirect chain');
    }

    protected function getCurlClient($url, array $options = [])
    {
        $curl = curl_init($url);
        $opts = $options + [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER         => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_NOBODY         => true,
            CURLOPT_USERAGENT      => $this->userAgent,
        ];

        // Only allow valid SSL/TLS hosts; don't talk to broken hosts
        // This could cause some consternation in the real world, due to the complexity of SSL configuration on servers
        // (both those running the code, and those being talked to by the code)
        if (parse_url($url, PHP_URL_SCHEME) === 'https') {
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
            $opts[CURLOPT_SSL_VERIFYHOST] = 2;
        }

        curl_setopt_array($curl, $opts);

        return $curl;
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    protected function cacheResult($url, $result)
    {
        if (!$this->cache) {
            return;
        }

        $this->cache->set('url-' . md5($url), $result);
    }

    protected function getCachedResult($url)
    {
        if (!$this->cache) {
            return null;
        }

        return $this->cache->get('url-' . md5($url));
    }
}