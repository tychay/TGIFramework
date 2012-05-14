TGIFramework
============

**TGIFramework** stands for…

- Tagged Generic Internet Framework: So I could get the open-source approved
- TGIFramework Generic Internet Framework: Be Recursive like a geek
- Terry's Generic Internet Framework: Because I have an ego?
- Thank God It’s a Framework: Because that's what the world needs, another architectural framework! ;-)

Originally, it was the parts of the [Tagged website][tagged] that were generic
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
(a database backend, a separate real-time event-driving processing outside PHP,
memcached installed (on its own tier), APC enabled, etc.). Also, templating
abstraction is minimized because of both heavy dependence on Ajax APIs and
expected passing familiarity of all engineers with PHP.

Therefore it does not replace 95% of the web-frameworks out there.

### TGIFramework does not compete with the other 5% either ###

The code is currently not easy to use as-is.

It's database code is an ad-hoc mess (because Tagged was Oracle and most are on
MySQL or no-SQL).

It isn’t even the actual code used by Tagged (since the abstraction at Tagged
was not complete). It will not contain much social networking/virality code
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

1. [Download][download tgif] and uncompress TGIFramework or
   `$ git clone git://github.com/tychay/TGIFramework.git`
2. Go to the root of the directory
3. `$ ./bs/bootstrap.sh` (YMMV on whether this works without modifications)
4. Add `samples/www` so that your website can access it (I assume Apache if
   with an `.htaccess` file, if using something else, you must port the file)
5. Access it. It now should work!
6. `samples/` can be trolled for a basic outline of how such a application
   configuration and such should work. Normally the framework directory should
   live inside your application, but your app can be put inside tgiframewrok
   like `samples/` is.

How to build your own app using TGIFramework
--------------------------------------------

1. The web server expects some stuff (emulated using `.htaccess` in samples)

	- `php_value auto_prepend_file *your_apps_prepend.php*`
	- `php_value unserialize_callback_func '__autoload'` (to get classes to load properly)
	- `php_value runkit.superglobal '_TAG'` (to make $_TAG superglobal accessable everywhere)

2. Either copy `prepend.php` or reference it. If the latter, be sure to set
   `APP_DIR` and configure the directory for `class/`, `config/` and `inc/` as
   done in `samples/`
3. Don't make your application directory your web root. Instead you point the
   web root to a `www` directory inside your application. For security reasons.

Licensing
---------

The code license is currently GNU LesserGPL. This allows you to use it for
closed source commercial products (just use a different namespace for any
extension you write on it), but requires you to make available changes you make
to the internal workings.

You can always override classes and depend through a design pattern if you need
to (maybe someday Tagged will do the same, but I doubt it).

If you want/need different licensing, tell me why and I'll probably give you a different license if the reason is good. I just figured LGPL is the best quid pro quo for anyone wanting to build anything commercial on this codebase.

[tagged]: http://www.tagged.com/
[download tgif]: https://github.com/tychay/TGIFramework
