---
layout: post
title: Laravel中配置缓存以及使用memcached扩展遇到的坑
categories: [PHP, Laravel, Memcached]
description: 填坑系列
keywords: PHP, Laravel，Memcached
---

## 缘由

#### 起因

最近遇到了南方电信idc访问北方redis从库速度慢的情况，公司动态平台无法根据南北机房配置不同的redis环境变量，所以由领导决定，直接采用动态平台默认提供的memcache服务器进行缓存配置。

#### Laravel

Laravel做配置缓存相对简单，运行`php artisan config:cache`就可以生成一个`config_cache.php`的文件，里面包含需要的所有配置信息，所以线上发布的时候，我们会在线下生成缓存文件，然后上传分发到动态平台的前端机。

#### Memcached变量

动态平台前端机在fpm的配置文件中给予了变量`SINASRV_MEMCACHED_SERVERS`，使用`$_SERVER["SINASRV_MEMCACHED_SERVERS"]`可以获取单台机器的配置，大抵是这个样子：`10.13.32.21:7801 10.13.32.22:7801 10.13.32.105:7801`。

## 坑1

Laravel中Memcache缓存配置是类似一个数组的样子，如下：

{% highlight php %}
<?php
return [
    'stores' => [
        'memcached' => [
            'driver' => 'memcached',
            'servers' =>
            [
                [
                    'host' => '127.0.0.1', 'port' => 11211, 'weight' => 100
                ],
            ],
        ],
    ],
];
{% endhighlight %}

我们这里需要的配置项是需要从`$_SERVER`变量中动态获取的，每个机房的值不可控。

由于配置文件最终走的是我们缓存的`config_cache.php`文件，该文件是框架生成缓存之后使用函数`var_export`导出来的，所以考虑这里使用一个对象进行缓存。

{% highlight php %}
<?php
return [
    'stores' => [
        'memcached' => [
            'driver' => 'memcached',
            'servers' =>
            [
                new \Sina\Config\Cache\MemcacheCollection(),
            ],
        ],
    ],
];
{% endhighlight %}

类需要实现`__set_state`静态方法，并且返回值类似原始配置中的数组样式，最终的代码如下所示：

{% highlight php %}
<?php

/*
 * Copyright (C) 2017 SINA Corporation
 *  
 *  
 * 
 * This script is firstly created at 2017-03-06.
 * 
 * To see more infomation,
 *    visit our official website http://app.finance.sina.com.cn/.
 */

namespace Sina\Config\Cache;

use Iterator;
use ArrayAccess;

/**
 * Description of MemcacheCollection
 *
 * @encoding UTF-8 
 * @author jiaojie <jiaojie@staff.sina.com.cn> 
 * @since 2017-03-06 16:58 (CST) 
 * @version 0.1
 * @description 
 */
class MemcacheCollection implements Iterator, ArrayAccess
{

    protected $container = [];
    protected $position = 0;
    protected static $reserved = "10.13.32.21:7801 10.13.32.22:7801 10.13.32.105:7801 10.13.32.106:7801 10.13.32.147:7801";

    public function __construct($string = "")
    {
        $this->fillContainer($string);
    }
    
    public function getContainer() {
        return $this->container;
    }

    protected function fillContainer($string = "")
    {
        if (empty($string)) {
            $string = self::$reserved;
        }
        $arr = explode(" ", $string);
        foreach ($arr as $k => $v) {
            list($this->container[$k]["host"], $this->container[$k]["port"]) = explode(":", $v);
            $this->container[$k]["weight"] = 0;
        }
    }

    public static function __set_state($arr)
    {
        $string = env("SINASRV_MEMCACHED_SERVERS");
        $setting = new self($string);
        return $setting->getContainer();
    }

    public function current()
    {
        return $this->container[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return isset($this->container[$this->position]);
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->container[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->container[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->container[$offset]);
        }
    }

}
{% endhighlight %}

由于框架中对memcache缓存的配置项加载时强制了`array`，所以需要对此处进行修改：

{% highlight php %}
<?php namespace Illuminate\Cache;

use Memcached;
use RuntimeException;

class MemcachedConnector {

	/**
	 * Create a new Memcached connection.
	 *
	 * @param  array  $servers
	 * @return \Memcached
	 *
	 * @throws \RuntimeException
	 */
	public function connect(array $servers) { // 去掉array
            // bla bla
        }
}
{% endhighlight %}

如此，动态加载配置项就完成了。

正式环境上线后没遇到问题，此时放心地回家了。

## 坑2

今天上午在测试环境代码的时候，出现了新的问题：

{% highlight bash%}
exception 'ErrorException' with message 'Memcached::get(): could not unserialize value, no igbinary support' in /data1/projects/test/app.finance.sina.com.cn/vendor/laravel/framework/src/Illuminate/Cache/MemcachedStore.php:42
Stack trace:
#0 [internal function]: Illuminate\Foundation\Bootstrap\HandleExceptions->handleError(2, 'Memcached::get(...', '/data1/projects...', 42, Array)
#1 /data1/projects/test/app.finance.sina.com.cn/vendor/laravel/framework/src/Illuminate/Cache/MemcachedStore.php(42): Memcached->get('app.finance.sin...')
{% endhighlight %}

经过google查询，看到了[一篇相关的文章（可能要翻墙）](http://kingfff.blogspot.jp/2016/09/php-memcache-igbinary-serialize.html)

{% highlight bash %}
原來是因為，當程式將物件傳入memcache時，我們所使用的memcached會自動將物件做serialize。

memcached Runtime Configuration中提到，memcached有個memcached.serializer參數可控制寫入memcache的格式，目前有以下四種
json
json_array
php
igbinary
預設採用igbinary。但是，當主機上沒有安裝igbinary時，則會改用php模式（standard PHP serializer）。

知道可能的原因，就好處理了～比對了log，以及主機上所安裝的套件，果然是因為該系統的眾多主機中，有台主機沒有安裝Igbinary。因此，當該主機的程式由memcache取回資料時，因無法使用Igbinary而發生錯誤。

最後提一下，原本是打算將該主機上安裝Igbinary以解決這問題。但是考慮未來打算升級到PHP7，而Igbinary目前僅相容PHP5(Compatible with PHP 5.2 – 5.6)。因此，最後統一改為使用php模式（standard PHP serializer）來解決。
{% endhighlight %}

然后重新编译了memcached扩展，重启fpm，一切搞定。