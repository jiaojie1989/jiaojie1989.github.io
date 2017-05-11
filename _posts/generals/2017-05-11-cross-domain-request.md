---
layout: post
title: 跨域？跨域！
categories: [Http]
description: About Cross Domain 
keywords: Browser
---

# 起因

最近做的用户反馈系统最终决定采用H5的方式进行开发，由于我们的前端一般是制作静态页，发布到某浪的静态池进行访问，这就导致了静态页地址和接口地址不在同一域名下。

# 技术方案

## JSONP

最简单的解决方案就是前端使用`JSONP`技术，但是`JSONP`只能进行`GET`请求，接口需要的提交表单和上传图片的功能是需要`POST`的，因此不完全满足需求。

## CORS

以下部分引用自阮一峰老师的[跨域资源共享 CORS 详解](http://www.ruanyifeng.com/blog/2016/04/cors.html)。

### 简介

CORS是一个W3C标准，全称是"跨域资源共享"（Cross-origin resource sharing）。

它允许浏览器向跨源服务器，发出`XMLHttpRequest`请求，从而克服了AJAX只能同源使用的限制。

浏览器将CORS请求分为两种：简单请求和非简单请求。

### 简单请求 simple request

#### 满足条件

简单请求必须满足如下的几个条件，否则就是非简单请求

* 请求方法是下述方法之一
    * GET
    * POST
    * HEAD
* HTTP头信息不得超出以下字段
    * Accept
    * Accept-Language
    * Content-Language
    * Last-Event-ID
    * Content-Type：只限以下三个值
        * `application/x-www-form-urlencoded`
        * `multipart/form-data`
        * `text/plain`

#### 基本流程

对于简单请求，浏览器直接发出CORS请求。具体来说，就是在头信息之中，增加一个`Origin`字段。

下面是一个例子，浏览器发现这次跨源AJAX请求是简单请求，就自动在头信息之中，添加一个`Origin`字段。

{% highlight http %}
GET /cors HTTP/1.1
Origin: http://api.bob.com
Host: api.alice.com
Accept-Language: en-US
Connection: keep-alive
User-Agent: Mozilla/5.0...
{% endhighlight %}

上面的头信息中，`Origin`字段用来说明，本次请求来自哪个源（协议 + 域名 + 端口）。服务器根据这个值，决定是否同意这次请求。

如果Origin指定的源，不在许可范围内，服务器会返回一个正常的HTTP回应。浏览器发现，这个回应的头信息没有包含`Access-Control-Allow-Origin`字段（详见下文），就知道出错了，从而抛出一个错误，被`XMLHttpRequest`的`onerror`回调函数捕获。注意，这种错误无法通过状态码识别，因为HTTP回应的状态码有可能是200。

如果`Origin`指定的域名在许可范围内，服务器返回的响应，会多出几个头信息字段。

{% highlight http %}
Access-Control-Allow-Origin: http://api.bob.com
Access-Control-Allow-Credentials: true
Access-Control-Expose-Headers: FooBar
Content-Type: text/html; charset=utf-8
{% endhighlight %}

上面的头信息之中，有三个与CORS请求相关的字段，都以Access-Control-开头。

1. `Access-Control-Allow-Origin`

    该字段是必须的。
    它的值要么是请求时Origin字段的值，要么是一个*，表示接受任意域名的请求。

2. `Access-Control-Allow-Credentials`

    该字段可选。
    它的值是一个布尔值，表示是否允许发送Cookie。默认情况下，Cookie不包括在CORS请求之中。设为true，即表示服务器明确许可，Cookie可以包含在请求中，一起发给服务器。这个值也只能设为true，如果服务器不要浏览器发送Cookie，删除该字段即可。

3. `Access-Control-Expose-Headers`

    该字段可选。
    CORS请求时，XMLHttpRequest对象的getResponseHeader()方法只能拿到6个基本字段：Cache-Control、Content-Language、Content-Type、Expires、Last-Modified、Pragma。如果想拿到其他字段，就必须在Access-Control-Expose-Headers里面指定。上面的例子指定，getResponseHeader('FooBar')可以返回FooBar字段的值。

#### Cookies

默认情况下，简单请求发出请求信息时不传送`Cookie`，也就是其实服务器返回的`Access-Control-Allow-Credentials`其实没有意义。

这时候需要设定客户端AJAX请求的`withCredentials`属性为`true`。

{% highlight javascript %}
    var xhr = new XMLHttpRequest();
    xhr.withCredentials = true;
{% endhighlight %}

一般原生情况下的JS会比较少，下面给出[JQuery](http://api.jquery.com/jquery.ajax/)的例子。

{% highlight javascript %}
    $.ajax({
       url: a_cross_domain_url,
       xhrFields: {
          withCredentials: true
       }
    });
{% endhighlight %}

### 非简单请求 not-so-simple request

#### 预检请求

非简单请求是那种对服务器有特殊要求的请求，比如请求方法是`PUT`或`DELETE`，或者`Content-Type`字段的类型是`application/json`，或者特殊的请求头部。

非简单请求的CORS请求，会在正式通信之前，增加一次HTTP查询请求，称为"预检"请求（preflight）。

浏览器先询问服务器，当前网页所在的域名是否在服务器的许可名单之中，以及可以使用哪些HTTP动词和头信息字段。只有得到肯定答复，浏览器才会发出正式的`XMLHttpRequest`请求，否则就报错。

"预检"请求用的请求方法是`OPTIONS`，表示这个请求是用来询问的。头信息里面，关键字段是`Origin`，表示请求来自哪个源。以下是一个“预检”的例子。

{% highlight http %}
    OPTIONS /cors HTTP/1.1
    Origin: http://api.bob.com
    Access-Control-Request-Method: PUT
    Access-Control-Request-Headers: X-Custom-Header
    Host: api.alice.com
    Accept-Language: en-US
    Connection: keep-alive
    User-Agent: Mozilla/5.0...
{% endhighlight %}

1. `Access-Control-Request-Method`
    该字段是必须的，用来列出浏览器的CORS请求会用到哪些HTTP方法，上例是`PUT`。
2. `Access-Control-Request-Headers`
    该字段是一个逗号分隔的字符串，指定浏览器CORS请求会额外发送的头信息字段，上例是`X-Custom-Header`。

服务器收到"预检"请求以后，检查了`Origin`、`Access-Control-Request-Method`和`Access-Control-Request-Headers`字段以后，确认允许跨源请求，就可以做出回应。

{% highlight http %}
    HTTP/1.1 200 OK
    Date: Mon, 01 Dec 2008 01:15:39 GMT
    Server: Apache/2.0.61 (Unix)
    Access-Control-Allow-Origin: http://api.bob.com
    Access-Control-Allow-Methods: GET, POST, PUT
    Access-Control-Allow-Headers: X-Custom-Header
    Content-Type: text/html; charset=utf-8
    Content-Encoding: gzip
    Content-Length: 0
    Keep-Alive: timeout=2, max=100
    Connection: Keep-Alive
    Content-Type: text/plain
{% endhighlight %}

除了`Access-Control-Allow-Origin`之外，响应中其他字段的含义如下。

1. `Access-Control-Allow-Methods`
    该字段必需，它的值是逗号分隔的一个字符串，表明服务器支持的所有跨域请求的方法。注意，返回的是所有支持的方法，而不单是浏览器请求的那个方法。这是为了避免多次"预检"请求。
2. `Access-Control-Allow-Headers`
    如果浏览器请求包括Access-Control-Request-Headers字段，则Access-Control-Allow-Headers字段是必需的。它也是一个逗号分隔的字符串，表明服务器支持的所有头信息字段，不限于浏览器在"预检"中请求的字段。
3. `Access-Control-Allow-Credentials`
    该字段与简单请求时的含义相同。
4. `Access-Control-Max-Age`
    该字段可选，用来指定本次预检请求的有效期，单位为秒。上面结果中，有效期是20天（1728000秒），即允许缓存该条回应1728000秒（即20天），在此期间，不用发出另一条预检请求。
    
    **But，cmxz这样说**

    *Access-Control-Max-Age字段在webkit的浏览器上默认最大时间为5分钟，若设置超过5分钟则不会生效，代码见https://cs.chromium.org/chromium/src/third_party/WebKit/Source/core/loader/CrossOriginPreflightResultCache.cpp?rcl=1399481969&l=44
    另外，chrome实际上有bug，即使Access-Control-Max-Age设置为600，下次请求时依然会发送options请求，此bug在2012年被提出，但至今未修复。具体见 https://bugs.chromium.org/p/chromium/issues/detail?id=131368*

#### Cookie

原则上，非简单请求也应该支持Cookie，这一部分没有测试。

### 开发方案

#### 应用内开发

可以采用Rack中间件的方式控制相关的流程。

#### 服务器层面

这一部分摘自[junyi.me博客](http://junyi.me/blog/s17.html)。

从Apache和Nginx的服务器配置层面解决此类问题。

{% highlight apache %}
# ----------------------------------------------------------------------
# Allow loading of external fonts
# ----------------------------------------------------------------------
<FilesMatch "\.(ttf|otf|eot|woff)$">
    <IfModule mod_headers.c>
        SetEnvIf Origin "http(s)?://(www\.)?(google.com|staging.google.com|development.google.com|otherdomain.net|dev02.otherdomain.net)$" AccessControlAllowOrigin=$0
        Header add Access-Control-Allow-Origin %{AccessControlAllowOrigin}e env=AccessControlAllowOrigin
    </IfModule>
</FilesMatch>
{% endhighlight %}

{% highlight nginx %}
#
# Wide-open CORS config for nginx
#

# allow origin list
set $ACAO 'http://www.test.com http://user.test.com';

# set single origin
if ($http_origin ~* ^https?://(www|user)\.test\.com$) {
    set $ACAO $http_origin;
}

if ($request_method = 'OPTIONS') {
    add_header 'Access-Control-Allow-Origin' '$ACAO';
    #
    # Om nom nom cookies
    #
    add_header 'Access-Control-Allow-Credentials' 'true';
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
    #
    # Custom headers and headers various browsers *should* be OK with but aren't
    #
    add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
    #
    # Tell client that this pre-flight info is valid for 20 days
    #
    add_header 'Access-Control-Max-Age' 1728000;
    add_header 'Content-Type' 'text/plain charset=UTF-8';
    add_header 'Content-Length' 0;
    return 204;
}
if ($request_method = 'POST') {
    add_header 'Access-Control-Allow-Origin' '$ACAO';
    add_header 'Access-Control-Allow-Credentials' 'true';
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
    add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
}
if ($request_method = 'GET') {
    add_header 'Access-Control-Allow-Origin' '$ACAO';
    add_header 'Access-Control-Allow-Credentials' 'true';
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
    add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
}
{% endhighlight %}

### 支持

目前几乎所有主流浏览器和WebView都支持CORS技术，具体可以参见[各浏览器对于CORS的支持情况](http://caniuse.com/#feat=cors)。

# 解决

最终，我们将H5静态页放置到动态平台，把请求和接口放到一个域名下。

ToT

所以，采用新的技术还需要一部分**权力**...

# 参考资料
* http://www.ruanyifeng.com/blog/2016/04/cors.html
* http://www.cnblogs.com/yuzhongwusan/p/3677955.html
* http://junyi.me/blog/s17.html
* http://caniuse.com/#feat=cors
* https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Access_control_CORS
* https://www.w3.org/TR/cors/

