---
layout: post
title: Slim Framework - 3
categories: [Framework, PHP]
description: Code clips.
keywords: Framework, PHP
---
# Code clips

## 容器和注入

Slim中依赖容器的注入主要有如下几种操作方式。

### 简单注入
{% highlight php %}
<?php
$app = new \Slim\Slim();
$app->foo = 'bar';
{% endhighlight %}

这种注入适合对简单对象的操作，如`number`/`string`/`array`等基本数据结构。

其实现首先使用`Slim.php`中的魔术方法`__set`，然后调用了依赖容器`Helper/Set.php`中的`set`方法，将数据存储到容器的`data`属性中。

### 资源定位器

如下的代码将Slim作为了一个资源的提供者，将资源构造的方法以闭包的形式注入到Slim的依赖容器中，然后以KV方式通过`Slim`的`__get`方法(依赖容器`Set`的`get`方法)进行获取。

注入的闭包在被请求时，会被调用并返回闭包的返回值。

{% highlight php %}
<?php
$app = new \Slim\Slim();

// 定义一个创建 UUID 的方法
$app->uuid = function(){
    return exec('uuidgen');
};

// 获取一个新的 UUID
$uuid_1 = $app->uuid;
$uuid_2 = $app->uuid;

// 断言两者不同
assert($uuid_1 !== $uuid_2);
{% endhighlight %}

这一部分的实现如下。
{% highlight php %}
<?php
namespace Slim\Helper;

class Set implements \ArrayAccess, \Countable, \IteratorAggregate
{
    // ...

    /**
     * Get data value with key
     * @param  string $key     The data key
     * @param  mixed  $default The value to return if data key does not exist
     * @return mixed           The data value, or the default value
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            // 魔术方法`__invoke`
            $isInvokable = is_object($this->data[$this->normalizeKey($key)]) && method_exists($this->data[$this->normalizeKey($key)], '__invoke');
            // 传入$this，也就是容器Set自身
            return $isInvokable ? $this->data[$this->normalizeKey($key)]($this) : $this->data[$this->normalizeKey($key)];
        }

        return $default;
    }

    // ...
}
{% endhighlight %}

### 单例资源

这里的单例资源指的是每次请求是一样的资源。资源定位器一栏中生成UUID的代码示例如果调用两次`$app->uuid`会返回不同的UUID值，单例资源就是解决这个问题的。

{% highlight php %}
<?php
$app = new \Slim\Slim();

// 定义一个 stdClass
$app->container->singleton('std', function(){
    $obj = new \stdClass;
    $obj = microtime(true);
    return $obj;            
});

// 获取资源
$std_1 = $app->std;
$std_2 = $app->std;

// 断言两者是同一个实例
assert(true === (spl_object_hash($std_1) === spl_object_hash($std_2)));
{% endhighlight %}

它的实现是利用了PHP匿名函数中的`static`修饰符，实现如下。
{% highlight php %}
<?php
namespace Slim\Helper;

class Set implements \ArrayAccess, \Countable, \IteratorAggregate
{
    // ...

    /**
     * Ensure a value or object will remain globally unique
     * @param  string   $key   The value or object name
     * @param  \Closure $value The closure that defines the object
     * @return mixed
     */
    public function singleton($key, $value)
    {
        $this->set($key, function ($c) use ($value) {
            // 静态修饰符，执行结果存储到这里了
            static $object;

            if (null === $object) {
                $object = $value($c);
            }

            return $object;
        });
    }

    // ...
}
{% endhighlight %}


