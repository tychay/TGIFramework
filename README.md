TGIFramework
============

**TGIFramework** stands for…

- Tagged Generic Internet Framework: So I could get the open-source approved
- TGIFramework Generic Internet Framework: Be Recursive like a geek
- Terry's Generic Internet Framework: Because I have an ego?
- Thank God It’s a Framework: Because that's what the world needs, another architectural framework! ;-)

Originally, it was the parts of the [Tagged website](http://www.tagged.com/ "Tagged") that were generic
enough to be used by any project requiring scalability where the operational
control of the servers is under the application builder.

When I joined Tagged in 2007, Tagged was built using OPAL (Oracle PHP Apache
Linux) and had over 30 million registered users delivering around 20 million
page views per day.  The servers would fail under load. When I left Tagged in
2009, it was still built under OPAL but with a completely rewritten
architecture. It had over 80 million registered users delivering over 250
million page views per day (around half a billion dynamic requests/day due to
Ajax). It was in the top 100 websites in the world according to Alexa and
the #3 social network in the United States (according to Hitwise, Nielsen, and
others).

It did this with half the number of front-facing web servers that we had in
2007 where most of the code was re-built on TGIFramework.

### TGIFramework does not replace 95% of the frameworks out there ###

TGIFramework is **not** is an enterprise framework designed around database
independence. It’s a consumer-facing one built around stability, scalability,
speed, and security.

It assumes certain implementations that are true of all large scale websites
(complete operational control of the infrastructure, a database backend,
a separate real-time event-driving processing outside PHP, memcached installed
on a separate tier, Opcode caching and APCu enabled, etc.). Also, templating
abstraction is minimized because of both heavy dependence on Ajax APIs and
expected passing familiarity of all engineers with PHP.

Therefore it does not replace 95% of the web-frameworks out there.

### TGIFramework does not compete with the other 5% either ###

The code is currently not easy to use as-is.

It's database code is an ad-hoc mess (because Tagged was Oracle and most are on
MySQL or no-SQL).

It isn’t even the actual code used by Tagged (since the abstraction at Tagged
was not complete). It will not contain much social networking/viral growth code
besides the parts common to all Web 2.0 applications. Also, parts of the Tagged
codebase dependent to the old architecture are not included. In this way you
could use TGIFramework to build a Web 2.0 product, but not really to build
Tagged—trust me, this is a Good Thing™.

Therefore, it doesn’t replace the other 5% of web frameworks remaining from the
above. ;-)

### Why use TGIFramework ###

Think of it as a code sample of how to built a consumer facing web product that
scaled to the level of Tagged.

If you are a Tagged employee (or were). Think of it as a way of building a
website while leveraging your knowledge of Tagged. (Tagged has had over a dozen
interns working there in the year I left.) It’s the part of Tagged you can
privately and commercially use, under a generous quid-pro-quo license.

The general philosophy is that of a library-based framework—the original name
(~2004) for this code was BLX: Beginner's Library and Extensions—as opposed to
a real framework. The prioritization was/is based around the prioritization of
the *four-s's of web architecture*:

- **stability** first (the first priority is it should work);
- **scalability** next (it should be able to be scaled horizontally, shardable);
- then **speed** (it should move bottlenecks out of band and out of PHP); and
- finally **security** (it should be permissive as possible, with the ability
  to dynamically recover from error cases being the priority).

How to install TGIFramework
---------------------------

Because you need operational control of the environment on which TGIFramework
works to even run, since 2006, it is best to get one set up in a virtual
environment like Amazon Web Services. Fortunately, since the introduction
of vagrant and VirtualBox it is easy to build out a development version using
a couple of commands.

This has been done for you in a [separate project](https://github.com/tychay/tgif_vagrant).
If you follow the instructions there exactly, you will have built
[a sample web site](https://github.com/tychay/tgiframework_sample)
in TGIFramework. To create a different site, create a new project directory
with a different name and model its tree structure after the sample site
and update the `Vagrantfile` to build out that version.

### How to build your own app using TGIFramework ###

If you already have infrastructure with the correct packages installed,
then you can manually install it.

[Download](https://github.com/tychay/TGIFramework/archive/master.zip "download archive of TGIFramework") and uncompress TGIFramework or
```shell
$ git clone git://github.com/tychay/TGIFramework.git tgif
```

Run composer on TGIFramework
```shell
$ cd tgif
$ composer install
```

In your own project, be sure to load something that defines the application 
symbol and includes the Composer prepend script on any pages that use 
TGIFramework.
(Note to previous: runkit superglobals were removed because runkit is no
longer maintained; `auto_prepend_file` was removed to work better with
fastcgi pools; `unserialize_callback_function` is no longer needed since
the introduction of `spl_autoload`.)

```php
<?php
// A symbol is used in order to allow multiple TGIFramework apps to run on
// the same machne and not overwrite each others shared memory caches.
// Either manually call it or define a PHP SYMBOL_FILE that returns it. 
// There is a shell script /bin/generate_global_version.php that can create 
// one for you.
$symbol = 'SYM';
require_once "/path/to/tgif/vendor/autoload.php";
?>
```

Licensing
---------

The code license is currently GNU LesserGPL. This allows you to use it for
closed source commercial products (just put your code in the separate project
directory), but requires you to make available changes you make to the
framework itself.

You can always override classes and depend through a design pattern if you need
to (maybe someday Tagged will do the same, but I doubt it).

If you want/need different licensing, tell me why and I'll probably give you a different license if the reason is good. I just figured LGPL is the best quid pro quo for anyone wanting to build anything commercial on this codebase.

Programming Notes
-----------------

### [PHP Framework Interoperability](http://www.php-fig.org/) ###

TGIFramework was written long before this existed (in it's earliest incarnations it
was written before PHP 5 was released!). Because of that, support for PSR is a
work-in-progress.

Here is the status:

#### [PSR-0](http://www.php-fig.org/psr/psr-0/ "Autoloading Standard") ####

While it is deprecated, the current iteration of TGIFramework is compliant
with PSR-0 with the vendor name of `tgif`. Currently it creates its own
`spl_autoload` function due to an artifact of needing to backward support
and bootstrap existing spaghetti code at Tagged. (This is likely to change
later.)

#### [PSR-1](http://www.php-fig.org/psr/psr-1/ "Basic Coding Standard") ####

Compliant with the following exceptions:

- Free energy scripts can theortically violate "side effects" _by design_.
- Class names are (currently) all lower case instead of `StudlyCaps` because of
  broken beta PHP5 compatibility with object instantiation. (This will be fixed later.)
- Class method names use underscores instead of `camelCase` due to the old way
  of using classes as a form of function namespacing which is still used throughout the
  code. This will not change due to non-English-speaking readability and similarity to
  php built-in functions.

#### [PSR-2](http://www.php-fig.org/psr/psr-2/ "Coding Style Guide") ####

- Not all pure-PHP files have the closing `?>` tag omitted. This is in the
  process of being fixed.
- Method names _SHOULD NOT_ be prefixed is ignored because it's a short-sighted
  recommendation. That's probably why it's not a _MUST_.
- Not all method names have been autdited for visibility declaration or
  ordering (`abstract`/`final` _access_ `static`)
- Some expressions that evaluate to a truth condition violate the standard of
  spaces around parenthesis. I'm keeping it because it's 100x more readable
  when mixing function calls with truth conditions.
- For loop truth conditions do not always have spaces where they should. This
  is in the process of being fixed.

#### [PSR-3](http://www.php-fig.org/psr/psr-3/ "Logger Interface") ####

Currently this is not supported. I'll have to look at it and add support.

#### [PSR-4](http://www.php-fig.org/psr/psr-2/ "Autoloader") ####

Namespacing did not exist when Tagged was written or when TGIFramework was
first open-sourced. When I migrate to using it, PSR-0 will shift to PSR-4
since [the latter is better](http://www.sitepoint.com/battle-autoloaders-psr-0-vs-psr-4/ "Battle of the Autoloaders: PSR-0 vs. PSR-4—Sitepoint").

#### [PSR-7](http://www.php-fig.org/psr/psr-7/ "HTTP Message Interfaces") ####

This standard is opaque to me. I'll support it when I hit it in the real-world.

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/tychay/tgiframework/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

