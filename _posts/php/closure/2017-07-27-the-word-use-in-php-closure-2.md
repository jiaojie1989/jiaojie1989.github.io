---
layout: post
title: Keyword use & Referance in PHP Closure
categories: [PHP]
description: Keyword `use`, and referances usage in PHP Language.
keywords: PHP, Closure
---
## 缘由

《PHP Reactive Programming》中有一个例子：
{% highlight php %}
<?php
use Rx\Observable; 
use Rx\Scheduler\EventLoopScheduler; 
use React\EventLoop\StreamSelectLoop; 

$loop = new StreamSelectLoop(); 
$scheduler = new EventLoopScheduler($loop); 

$disposable = Observable::range(1, 5) 
    ->subscribeCallback(function($val) use (&$disposable) { 
        echo "$val\n"; 
        if ($val == 3) { 
            $disposable->dispose(); 
        } 
    }, null, null, $scheduler); 

$scheduler->start();
{% endhighlight %}

其中对$disposable对象中传递了一个closure，use的对象是带引用符号的`$disposable`，我尝试把引用符号去掉，然而程序报错`PHP Notice:  Undefined variable: disposable`。

突然想起来之前做的一个形成cms菜单栏的方法，要求实现一个closure自身的递归，当时查到的解决方案就是使用`&`符号进行调用。

{% highlight php %}
<?php
            $retChild = function($menu) use (&$retChild, $userMenu) {
                switch ($menu->depth) {
                    case 0:
                        $ret = "";
                        foreach ($menu->children as $child) {
                            $ret .= $retChild($child);
                        }
                        return $ret;
                    case 1:
                        $ret = <<<STR
<li class="header">{$menu->title}</li>
<li class="treeview">
STR;
                        $childRet = false;
                        foreach ($menu->children as $child) {
                            $childStr = $retChild($child);
                            if (!empty($childStr)) {
                                $ret .= $childStr . '</li><li class="treeview">' . "\n";
                                $childRet = true;
                            }
                        }
                        if (true === $childRet) {
                            $ret .= <<<STR
</li>
STR;
                            return $ret;
                        }
                        return "";
                    default:
                        if (empty($menu->icon)) {
                            $icon = "fa fa-link";
                        } else {
                            $icon = $menu->icon;
                        }
                        $ret = <<<STR
<a href="#"><i class='{$icon}'></i> <span>{$menu->title}</span> <i class="fa fa-angle-left pull-right"></i></a>
<ul class="treeview-menu">
STR;
                        $retChildStr = "";
                        if (!in_array($menu->id, array_keys($userMenu))) {
                            continue;
                        }
                        foreach ($menu->children as $child) {
                            if (!in_array($menu->id, array_keys($userMenu))) {
                                continue;
                            }
                            $childStr = $retChild($child);
                            if (!empty($childStr)) {
                                $retChildStr .= '<li class="treeview">' . $childStr . '</li>';
                            }
                        }
                        if (!empty($retChildStr)) {
                            $ret .= $retChildStr . "\n";
                        }

                        $retRowStr = "";
                        $routes = $menu->routesColl;
                        if (empty($routes) || $routes->isEmpty()) {
                            $retRowStr = "";
                        } else {
                            foreach ($routes as $route) {
                                $uri = $route->uri;
                                $descLong = $route->descLong;
                                $descShort = $route->descShort;
                                if ($route->monitorShow) {
                                    $retRowStr .= <<<STR
    <li><a href="/{$uri}" desc="{$descLong}">{$descShort}</a></li>
STR;
                                }
                            }
                        }
                        if (!empty($retRowStr)) {
                            $ret .= $retRowStr;
                        }

                        if (empty($retRowStr) && empty($retChildStr)) {
                            return "";
                        }

                        return $ret . '</ul>';
                }
            };
{% endhighlight %}

## 解释

凡是关于PHP的东西，一般都可以从文档上找到，即使文档中没有，那么下面的user notes里面也会存在。

[匿名函数](http://php.net/manual/en/functions.anonymous.php)下面的[Hayley Watson](http://php.net/manual/en/functions.anonymous.php#100545)评论中就是针对这种情况举的一个例子，例子用Fibonacci数列进行示意。

{% highlight php %}
<?php
$fib = function($n)use(&$fib)
{
    if($n == 0 || $n == 1) return 1;
    return $fib($n - 1) + $fib($n - 2);
};

echo $fib(10);
{% endhighlight %}

php的匿名函数中，如果`use`一个基本数据类型（非对象）的时候，传递的是当时此数据的快照；而`use`一个对象的时候，与函数参数中传递该对象相同，都是引用。

这一点可以参照[mail at mkharitonov dot net](http://php.net/manual/en/functions.anonymous.php#114433)里面的注释。

{% highlight php %}
<?php
$aaa = 111;
$func = function() use($aaa){ print $aaa; };
$aaa = 222;
$func(); // Outputs "111"
{% endhighlight %}

{% highlight php %}
<?php
$aaa = 111;
$func = function() use(&$aaa){ print $aaa; };
$aaa = 222;
$func(); // Outputs "222"
{% endhighlight %}

{% highlight php %}
<?php
$aaa = 111;
$func = function() use(&$aaa){ print $aaa; };
$aaa = 222;
$func(); // Outputs "222"
{% endhighlight %}

{% highlight php %}
<?php
class Foo
{

    public $foo = 'default';

}

$foo = new Foo;

$func = function() use ($foo) {
    echo $foo->foo . "\n";
};

$foo->foo = 'changable';
$func();// 输出 "changable"
{% endhighlight %}