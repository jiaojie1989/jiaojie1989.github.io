---
layout: post
title: Slim Framework - 4
categories: [Framework, PHP]
description: Slim app runtime & its flow diagram.
keywords: Framework, PHP
---
## App Runtime

一个Slim应用由类`Slim\Slim`的一个实例贯穿始终。

{% highlight php %}
<?php
require "vendor/autoload.php";

// 构造Slim运行实例
$app = new \Slim\Slim();

// 设置路由
$app->get('/hello/:name', function ($name) use($app) {
    echo "Hello, {$name} !";
});

// 执行应用
$app->run();
{% endhighlight %}

### Construct A Slim Application

在构造一个`Slim\Slim`实例的时候，我们做了如下的操作。

* IoC容器

{% highlight php %}
<?php
namespace Slim;

class Slim
{
    /**
     * Constructor
     * @param  array $userSettings Associative array of application settings
     */
    public function __construct(array $userSettings = array())
    {
        // 构造依赖容器
        $this->container = new \Slim\Helper\Set();
        
        // 用户配置项
        $this->container['settings'] = array_merge(static::getDefaultSettings(), $userSettings);

        // 环境初始值
        $this->container->singleton('environment', function ($c) {
            return \Slim\Environment::getInstance();
        });

        // 原始请求Request
        $this->container->singleton('request', function ($c) {
            return new \Slim\Http\Request($c['environment']);
        });

        // 响应Response
        $this->container->singleton('response', function ($c) {
            return new \Slim\Http\Response();
        });

        // 路由处理器Router
        $this->container->singleton('router', function ($c) {
            return new \Slim\Router();
        });

        // 模板处理器View
        $this->container->singleton('view', function ($c) {
            $viewClass = $c['settings']['view'];
            $templatesPath = $c['settings']['templates.path'];

            $view = ($viewClass instanceOf \Slim\View) ? $viewClass : new $viewClass;
            $view->setTemplatesDirectory($templatesPath);
            return $view;
        });

        // 日志Writer
        $this->container->singleton('logWriter', function ($c) {
            $logWriter = $c['settings']['log.writer'];

            return is_object($logWriter) ? $logWriter : new \Slim\LogWriter($c['environment']['slim.errors']);
        });

        // 日志
        $this->container->singleton('log', function ($c) {
            $log = new \Slim\Log($c['logWriter']);
            $log->setEnabled($c['settings']['log.enabled']);
            $log->setLevel($c['settings']['log.level']);
            $env = $c['environment'];
            $env['slim.log'] = $log;

            return $log;
        });

        // 系统运行模式
        $this->container['mode'] = function ($c) {
            $mode = $c['settings']['mode'];

            if (isset($_ENV['SLIM_MODE'])) {
                $mode = $_ENV['SLIM_MODE'];
            } else {
                $envMode = getenv('SLIM_MODE');
                if ($envMode !== false) {
                    $mode = $envMode;
                }
            }

            return $mode;
        };

        // 系统中间件堆栈
        $this->middleware = array($this); // 底部压入Slim实例自身
        $this->add(new \Slim\Middleware\Flash());
        $this->add(new \Slim\Middleware\MethodOverride());

        // Make default if first instance
        if (is_null(static::getInstance())) {
            $this->setName('default');
        }
    }
}
{% endhighlight %}

### Set Routes' List for Future Request Dispatch

