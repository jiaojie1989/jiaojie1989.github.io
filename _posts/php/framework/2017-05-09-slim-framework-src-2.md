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

配置、环境信息的存储器，在Slim类实例的Container中，单例方式存在。

### Middleware.php

中间件抽象类。

Slim框架主要实现了[Rack协议](https://blog.engineyard.com/2015/understanding-rack-apps-and-middleware)的一种版本，中间件在应用生命周期中可以拦截、修改、分析包括环境变量、Http请求、响应在内的各种信息体。

中间件的实质是一个堆栈，底部是Slim类的实例，上层均为Middleware抽象类的实现。

Slim实例应用在初始化后，运行`run()`方法处理Http请求，其中会按照堆栈方式调用Middleware的`call()`方法，最底层调用Slim实例的`call()`方法。

框架中给出了Middleware的几个实现：

* ContentTypes
* Flash
* MethodOverride
* PrettyExceptions
* SessionCookie

### Route.php

路由类，每一条路由都是的一个Route实例。

Slim应用实例在设定路由的时候会调用自身的`mapRoute()`方法，使用传递过来的路由参数构造一个全新的Route实例，然后置入路由处理实例Router的路由数组中，这个数组是**FIFO**的。

### Router.php

路由处理类，存储于Slim实例的容器中，单例方式存在。

Router主要存储应用设定的多个路由信息，然后对当前的访问URL进行匹配，给出相应的匹配路由。

### Http/Request.php

请求类，存储于Slim实例的容器中，单例方式存在。

Request实例包含Http Request的全部信息(环境变量、Cookies、Headers)，在请求此项目时直接由Environment单例构造生成。

### Http/Response.php

响应类，存储于Slim实例的容器中，单例方式存在。

Response实例是服务器Slim应用实例对Http请求的处理结果，执行匹配成功的Route的业务逻辑代码之后，再经由各中间件进行处理，最终返回给用户的Http响应。

### Exception

异常扩展，Slim扩展的两个异常主要用于流程控制。

* Pass
* Stop
