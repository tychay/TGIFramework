# TGIFramework #

**TGIFramework** stands for…

- Tagged Generic Internet Framework: So I could get the open-source approved
- TGIFramework Generic Internet Framework: Be Recursive like a geek
- Terry's Generic Internet Framework: Because I have an ego?
- Thank God It’s a Framework: Because that's what the world needs, another architectural framework! ;-)

Originally, it was the parts of the [Tagged website][tagged] that were generic enough to be used by any project requiring scalability where the operational control of the servers is under the application builder.

When I joined Tagged in 2007, Tagged was built using OPAL (Oracle PHP Apache Linux) and had over 30 million registered users delivering around 20 million page views per day.  The servers would fail under load. When I left Tagged in 2009, it was still built under OPAL but with a completely rewritten architecture. It had over 80 million registered users delivering over 250 million page views per day (around half a billion dynamic requests/day due to Ajax). It was in the top 100 websites in the world according to Alexa and the #3 social network in the United States (according to Hitwise, Nielsen, and others).

It served this with half the number of front-facing web servers that we had in 2007 where most of the code was built on TGIFramework.

### TGIFramework does not replace 95% of the frameworks out there ###

TGIFramework is **not** is an enterprise framework designed around database independence. It’s a consumer-facing one built around stability, scalability, speed, and security.

It assumes certain implementations that are true of all large scale websites (a database backend, a separate real-time event-driving processing outside PHP, memcached installed, APC enabled, etc.). Also, templating abstraction is minimized because of both heavy dependence on Ajax APIs and expected passing familiarity of all engineers with PHP.

Therefore it does not replace 95% of the web-frameworks out there.

### TGIFramework does not compete with the other 5% either ###

The code is currently not easy to use as-is.

It's database code is an ad-hoc mess (because Tagged was Oracle and most are on MySQL or no-SQL).

It isn’t even the actual code used by Tagged (since the abstraction at Tagged is not complete). It will not contain much social networking/virality code besides the parts common to all Web 2.0 applications. Also, parts of the Tagged codebase dependent to the old architecture are not included. In this way you could use TGIFramework to build a Web 2.0 product, but not really to build Tagged—trust me, this is a Good Thing™.

Therefore, it doesn’t replace the other 5% of web frameworks remaining from the above. ;-)

### Why use TGIFramework ###

Think of it as a code sample of how to built a consumer facing web product that scaled to the level of Tagged.

If you are a Tagged employee (or were). Think of it as a way of building a website while leveraging your knowledge of Tagged. (Tagged has had over a dozen interns working there in the year I left.) It’s the part of Tagged you can privately and commercially use, under a generous quid-pro-quo license.

The general philosophy is that of a library-based framework—the original name (~2004) for this code was BLX: Beginner's Library and Extensions—as opposed to a real framework. The prioritization is based around stability first (it should run), scalability next (it should be able to be scaled horizontally), then speed (it should move bottlenecks out of band and out of PHP), and finally security (it should be permissive as possible, with the ability to dynamically recover from error cases being the priority).

### Licensing ###

The code license is currently GNU LesserGPL. This allows you to use it for closed source commercial products (just use a different namespace for any extension you write on it), but requires you to commit changes you make to the internal workings.

You can always override classes and depend through a design pattern if you need to (maybe someday Tagged will do the same, but I doubt it).

If you want/need me to change the licensing, tell me why and I'll probably do it if the reason is good. I just figured LGPL is the best quid pro quo for anyone wanting to build anything commercial on this codebase.

[tagged]: http://www.tagged.com/
