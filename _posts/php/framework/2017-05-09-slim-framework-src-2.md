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

## Important Classes

### Slim.php

框架的核心文件，配置、路由、中间件、请求与响应、日志、错误收集等插件都包含在其中。

这个文件还涵盖了Slim框架执行的流程。

### Helper/Set.php

应用容器，存储于Slim类实例中。

实质上是一个实现了数组/迭代器的类，能够按照K-V方式存储框架的各个组件信息。

### Environment.php

配置信息的存储器，在Slim类实例的Container中，单例方式存在。

### Middleware.php

中间件抽象类。

Slim框架主要实现了Rack协议的一种版本，中间件在应用生命周期中可以拦截、修改、分析包括环境变量、Http请求、响应在内的各种信息体。

中间件的实质是一个堆栈，底部是Slim类的实例，上层均为Middleware抽象类的实现。

Slim实例应用在初始化后，运行`run()`方法处理Http请求，其中会按照堆栈方式调用Middleware的`call()`方法，最底层调用Slim实例的`call()`方法。

### Route.php
### Router.php
### Http/Request.php
### Http/Response.php
### Exception

# Highlight Points


