\*DEREF
=======

Ever come across a suspicious short URL and wanted to know where it **really** goes?

**\*DEREF** (pronounced "De-Ref") is a quick experiment in learning AngularJS with a Silex-based backend.

It uses cURL to follow the redirect chain on pasted URLs, logging all the hops
and showing the final result using Angular and Bootstrap.

To try it out, clone the source and use Composer to install vendors:

$> composer install

Usage
-----

Once the vendors are installed, you can set up a virtual host using apache or nginx,
using a standard rewrite rule to send traffic to ```web/index.php```.

Another option is to use the internal PHP webserver:

$> php -S localhost:8080 -t web/

This will start the server on port 8080, which you can then visit in your web browser
to try out the service.

