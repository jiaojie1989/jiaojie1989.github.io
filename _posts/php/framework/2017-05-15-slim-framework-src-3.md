---
layout: post
title: Slim Framework - 3
categories: [Framework, PHP]
description: Slim framework code clips.
keywords: Framework, PHP
---
# DI和IoC

现代框架一般用到了`依赖注入`和`控制反转`的设计思想，了解这两种设计模式对理解框架的结构有很大帮助。

## DI

DI即`依赖注入`，那么什么是`依赖`呢？

`依赖`是一种关系，例如一种处理类Foo对象的类Baz，那么对于Baz而言，Foo就是它的`依赖`。

考虑以下两个代码段：

{% highlight php %}
<?php
class Foo {}

class Baz {
    protected $foo;

    public function __construct() {
        $this->foo = new Foo();
    }
}
{% endhighlight %}

{% highlight php %}
<?php
class Foo {}

class Baz {
    protected $foo;

    public function __construct(Foo $foo) {
        $this->foo = $foo;
    }
}
{% endhighlight %}

第一个代码段里面Foo的实例仅存在于Baz的实例中，两者之间是强依赖关系；而第二段代码中，将Foo的实例`注入`到Baz实例中，做到了Foo和Baz的解耦。

## IoC

IoC即`控制反转`，
