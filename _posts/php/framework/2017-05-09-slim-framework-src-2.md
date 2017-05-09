---
layout: post
title: Slim Framework - 2
categories: [Framework, PHP]
description: Slim vendor sturcture and its main class analyzation.
keywords: Framework, PHP
---
# Structure

Slim 3.x较2.x删减了一些功能，这里就2.6.3版本进行一些分析。

## Directory Structure

下面所示为安装完成之后Slim核心文件的目录结构。

{% highlight bash %}
vendor/slim/slim/Slim/
                    ├── Environment.php
                    ├── Exception
                    │   ├── Pass.php
                    │   └── Stop.php
                    ├── Helper
                    │   └── Set.php
                    ├── Http
                    │   ├── Cookies.php
                    │   ├── Headers.php
                    │   ├── Request.php
                    │   ├── Response.php
                    │   └── Util.php
                    ├── Log.php
                    ├── LogWriter.php
                    ├── Middleware
                    │   ├── ContentTypes.php
                    │   ├── Flash.php
                    │   ├── MethodOverride.php
                    │   ├── PrettyExceptions.php
                    │   └── SessionCookie.php
                    ├── Middleware.php
                    ├── Route.php
                    ├── Router.php
                    ├── Slim.php
                    └── View.php
{% endhighlight %}

