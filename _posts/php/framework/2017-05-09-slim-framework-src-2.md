---
layout: post
title: Slim Framework - 2
categories: [Framework, PHP]
description: Learn design patterns from PHP Framework Source Codes.
keywords: Framework, PHP
---
# Generals

## 框架简介

[Slim Framework](https://www.slimframework.com/)是一个轻型PHP框架，适用于轻型的WebApp应用和Api的开发。

## 版本情况

下述列表是[Slim各个版本](https://packagist.org/packages/slim/slim)和PHP的兼容情况。

|Slim Framework Version|PHP Version|PHPUnit Support|Extra|
|:--:|:--:|:--:|:--:|
|1.x|>=5.2.0|Unknown|End of Life|
|2.x|>=5.3.0|Support|Maintaining|
|3.x|>=5.5.0|4.x Support|Maintaining|
|4.x|>=5.6.0|5.7.x/6.x Support|Developping|

## 文档

2.x的文档地址为[http://docs.slimframework.com/](http://docs.slimframework.com/)。
3.x的文档地址为[https://www.slimframework.com/docs/](https://www.slimframework.com/docs/)。

# Examples

## Installation

Slim框架通过[Composer](https://getcomposer.org/)进行安装。

{% highlight bash %}
# 安装Slim 2.x版本
composer require slim/slim:~2.0 -vvv
{% endhighlight %}

## Simple Usage

{% highlight php %}
<?php

require "vendor/autoload.php";

$app = new \Slim\Slim([
    "debug" => true,
    "mode" => "development",
        ]);
$app->get('/hello/:name', function ($name) {
    echo "Hello, " . $name;
});
$app->run();
{% endhighlight %}
