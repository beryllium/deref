\*DEREF
=======

Ever come across a suspicious short URL and wanted to know where it **really** goes?

**\*Deref** (pronounced "De-Ref") is a quick experiment in learning [AngularJS][angular] with a [Silex][silex] PHP backend.

It uses cURL to follow the redirect chain on pasted URLs, logging all the hops
and showing the final result using Angular and [Twitter Bootstrap][bootstrap].

To try it out, clone the source and use [Composer][composer] to install vendors:

$> composer install

Or, check it out online at [http://deref.link/][deref]

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

[Kevin Boyd][@beryllium9] created **\*Deref** as part of [an AngularJS blog post][blogpost] on [whateverthing.com][whateverthing].

**\*Deref** is released under the MIT open-source license.

[angular]:       https://angularjs.org/
[silex]:         http://silex.sensiolabs.org/
[bootstrap]:     http://getbootstrap.com/
[composer]:      https://getcomposer.org/
[deref]:         http://deref.link/
[whateverthing]: http://whateverthing.com/
[blogpost]:      http://whateverthing.com/blog/2014/12/07/angularjs-and-silex/
[@beryllium9]:   http://twitter.com/beryllium9