---
layout: post
title: 跨域？跨域！
categories: [Http]
description: About Cross Domain 
keywords: Browser
---

最近做的用户反馈系统最终决定采用H5的方式进行开发，由于我们的前端一般是制作静态页，发布到某浪的静态池进行访问，所以这就导致了静态页地址和接口地址不在同一域名下。

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