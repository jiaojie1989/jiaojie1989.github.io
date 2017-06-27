---
layout: post
title: PHP匿名函数(Closure) 
categories: [PHP]
description: Something about PHP closure.
keywords: PHP, Closure
---
## 缘由

这几天看了Martin Sikora写的[《PHP Reactive Programming》](https://www.amazon.com/PHP-Reactive-Programming-Martin-Sikora/dp/1786462877)的第一章*Introduction to Reactive Programming*，其中对*函数式编程*举例时的PHP代码把匿名函数玩出了花，所以下午看了下[关于Closure的文档](http://php.net/manual/en/class.closure.php)。

## 函数式编程

函数式编程(Functional Programming)是一种*编程范式*([Programming Paradigm](https://en.wikipedia.org/wiki/Programming_paradigm))。

其要点有三：

* 消除函数副作用(Eliminating side effects)

由于非局部变量改变或跳出函数体的控制语句，而造成的函数*变量不满足交换率的作用。

* 变量不变性(Avoiding mutable data)

不改变非局部变量的状态，并且对于同样的输入，会造成相同的输出。

* 函数作为程序基本数据类型(First-class citizens and higher-order functions)

函数可以作为函数参数、被赋值和作为函数返回值。

## PHP匿名函数(Closure)

当我们提起[PHP匿名函数](http://php.net/manual/en/functions.anonymous.php)的时候，一般指的是下面这样的代码。

{% highlight php %}
<?php
$greet = function($name)
{
    printf("Hello %s\r\n", $name);
};

$greet('World');
$greet('PHP');
{% endhighlight %}

其中，`$greet`其实是一个[Closure](http://php.net/manual/en/class.closure.php)对象。

文档中对[Closure](http://php.net/manual/en/class.closure.php)类进行了详细的描述，PHP 7.1版本之前，主要包含三个方法`bind`,`bindTo`,`call`，这三个方法的区别主要是在于匿名函数对象中`$this`的使用。

{% highlight php %}
<?php
class Closure {
    /* Methods */
    private __construct ( void )
    public static Closure bind ( Closure $closure , object $newthis [, mixed $newscope = "static" ] )
    public Closure bindTo ( object $newthis [, mixed $newscope = "static" ] )
    public mixed call ( object $newthis [, mixed $... ] )
    public static Closure fromCallable ( callable $callable )
}
{% endhighlight %}

### bind/bindTo

两个方法实现的功能类似，都是复制一个匿名函数对象，指定其`$this`对象和类作用域。

以下仅就非静态方法`bindTo`进行说明。

`$newthis`指的是新的匿名函数对象中`$this`所调用的对象，而`$newscope`则会确定`$this`中成员的可见性。

#### 默认参数`static`

使用默认参数`static`的情况如下：

{% highlight php %}
<?php

class Foo {
    private $_foo = 1;
}

class Baz extends Foo {
    private $_baz = 2;
}

$func = function() {
    var_dump($this->_foo);
    var_dump($this->_baz);
};

$funcFoo = $func->bindTo(new Foo(), 'static');
$funcFoo();
{% endhighlight %}

这时候报错`PHP Fatal error:  Cannot access private property Foo::$_foo `，显然`static`无法访问private属性`_foo`。

#### 类名或者对象

如果我们指定Baz对象为`$this`，并将可见性设置为Baz的可见性。

{% highlight php %}
<?php

class Foo {
    private $_foo = 1;
}

class Baz extends Foo {
    private $_baz = 2;
}

$func = function() {
    var_dump($this->_foo);
    var_dump($this->_baz);
};

$funcBaz = $func->bindTo(new Baz(), Baz::class);
$funcBaz();
{% endhighlight %}

这时候测试通过，新的`$funcBaz`中的`$this`可以访问Foo和Baz的私有属性。

当我们指定Baz对象为`$this`，而将可见性设置为Foo的可见性时。

{% highlight php %}
<?php

class Foo {
    private $_foo = 1;
}

class Baz extends Foo {
    private $_baz = 2;
}

$func = function() {
    var_dump($this->_foo);
    var_dump($this->_baz);
};

$funcBaz = $func->bindTo(new Baz(), Foo::class);
$funcBaz();
{% endhighlight %}

程序会报错`PHP Fatal error:  Cannot access private property Baz::$_baz`，这时候就无法访问Baz的私有属性了。

### call

这个方法临时指定一个对象作为`$this`，并用剩余参数调用匿名函数，返回值为匿名函数的返回值。

{% highlight php %}
<?php

class Foo {
    private $val;
    public function __construct($val) {
        $this->val = $val;
    }
    public function getVal() {
        return $this->val;
    }
}

$func = function() {
    var_dump($this->getVal());
};

$func->call(new Foo(1));// int(1)
$func->call(new Foo(2));// int(2)

{% endhighlight %}

### 其他

这个类在PHP版本升级7之后有一些变动：

* `$newscope`在7.0.0版本后不能使用PHP内置的类
* 7.1.0版本后新增加`Closure::fromCallable ( callable $callable )`方法，用以从`callable`构造一个匿名函数并检查，如果不可call，则会抛出`TypeError`