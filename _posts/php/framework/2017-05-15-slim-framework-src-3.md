---
layout: post
title: Slim Framework - 3
categories: [Framework, PHP]
description: Dependency Injection & Inversion of Control
keywords: Framework, PHP
---
# DI和IoC

现代框架一般用到了`依赖注入``控制反转`的设计思想，了解这两种设计模式对理解框架的结构有很大帮助。

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

IoC即`控制反转`。

传统的开发工作中，我们会对每一个业务对象进行构造，并且在程序中对所构造的对象进行管理和控制；IoC则意味着这一部分工作在IoC容器中进行，所有的业务对象由容器进行控制。

所谓`反转`，就是对传统设计中对`依赖`部分的`反转`；传统设计中，我们会主动控制依赖对象，而这里会由IoC容器进行创建和注入依赖。

虽然有**DI是IoC的一种实现方式**的说法，但是目前业界大概也都是只用这一种实现方式。

我觉得引自[开涛的博客-跟我学Spring3-IoC基础](http://jinnianshilongnian.iteye.com/blog/1413846)中的两幅图能够很明白地说明`反转`带来的改变。

![pic1](http://sishuok.com/forum/upload/2012/2/19/a02c1e3154ef4be3f15fb91275a26494__1.JPG)

![pic2](http://sishuok.com/forum/upload/2012/2/19/6fdf1048726cc2edcac4fca685f050ac__2.JPG)

# 参考资料

* http://blog.csdn.net/bestone0213/article/details/47424255
* http://www.jianshu.com/p/002542f9c854
* https://www.zhihu.com/question/25392984?sort=created
* http://jinnianshilongnian.iteye.com/blog/1413846
