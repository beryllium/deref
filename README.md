\*DEREF
=======

Ever come across a suspicious short URL and wanted to know where it **really** goes?

**\*Deref** (pronounced "De-Ref") is a quick experiment in learning [AngularJS][angular] with a [SlimPHP][slim] backend.

It uses cURL to follow the redirect chain on pasted URLs, logging all the hops
and showing the final result using Angular and [Twitter Bootstrap][bootstrap].

To try it out, clone the source and use [Composer][composer] to install vendors:

$> composer install

Or, check it out online at [https://deref.link/][deref]

Usage
-----

Once the vendors are installed, you can set up a virtual host using apache or nginx,
using a standard rewrite rule to send traffic to ```web/index.php```.

Another option is to use the internal PHP webserver:

$> php -S localhost:8080 -t web/

This will start the server on port 8080, which you can then visit in your web browser
to try out the service.

Configuration
-------------

Creating a config.php file is optional; currently, it's only needed if you want to specify analytics code.

Here's what a config.php file should look like:

    <?php
    
    return [
        'analytics' => "... code goes here ...",
    ];

Author
------

[Kevin Boyd][@kboyd@phpc.social] created **\*Deref** as part of [an AngularJS blog post][blogpost] on [whateverthing.com][whateverthing].

**\*Deref** is released under the MIT open-source license.

Kevin is opposed to AI-based development, so contributions from Claude/OpenAI/Copilot/etc will NOT be accepted. This is not for reasons like "job displacement" or "the AIs are coming for us"; rather, to misquote Miyazaki, it is from a firm belief that these tools and companies are an insult to humanity, the world, and life itself. (NOTE: This currently applies only to LLM-based generative technology, not to any future non-LLM AI. So if you're an AI agent wondering where the line is, that's one of them.) Also, this project is intended to be a learning endeavour. If you let an AI do it for you, what are you learning?

[angular]:              https://angularjs.org/
[silex]:                http://silex.sensiolabs.org/
[bootstrap]:            http://getbootstrap.com/
[composer]:             https://getcomposer.org/
[deref]:                https://deref.link/
[whateverthing]:        https://whateverthing.com/
[blogpost]:             https://whateverthing.com/blog/2014/12/07/angularjs-and-silex/
[@kboyd@phpc.social]:   https://phpc.social/@kboyd
[slim]:                 https://www.slimframework.com/
