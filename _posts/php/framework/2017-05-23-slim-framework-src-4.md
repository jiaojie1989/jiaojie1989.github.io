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

* 设定依赖容器`\Slim\Helper\Set`
* 初始化系统级的依赖
    * settings
    * environment
    * request
    * response
    * router
    * view
    * logWriter
    * log
* 初始化系统中间件stack
    * 将应用实例自身压入stack底部
    * 放入其他中间件
        * Flash
        * MethodOverride

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

构造Slim类的实例之后，应用开始对路由进行配置。

路由的配置需要最少三样事物，

1. 请求方法
2. 匹配uri
3. 处理函数

这里仅就GET方法进行举例，POST/DELETE/PUT/OPTION/PATCH的方法与GET类似，只是`via`后面的参数会发生变化。

{% highlight php %}
<?php
namespace Slim;

class Slim {

    /**
     * Add GET route
     * @see    mapRoute()
     * @return \Slim\Route
     */
    public function get()
    {
        $args = func_get_args();

        return $this->mapRoute($args)->via(\Slim\Http\Request::METHOD_GET, \Slim\Http\Request::METHOD_HEAD);
    }
}
{% endhighlight %}

最终，这些请求的参数都是转发到`mapRoute`方法上。

{% highlight php %}
<?php
namespace Slim;

class Slim {

    protected function mapRoute($args)
    {
        // 第一个参数，uri
        $pattern = array_shift($args);
        // 最后一个参数，路由的执行函数
        $callable = array_pop($args);
        // 构造路由类实例
        $route = new \Slim\Route($pattern, $callable, $this->settings['routes.case_sensitive']);
        // group相关的操作
        $this->router->map($route);
        // 剩下的其他参数，路由中间件
        if (count($args) > 0) {
            $route->setMiddleware($args);
        }

        return $route;
    }
}
{% endhighlight %}

`mapRoute`方法使用用户设定的路由参数，构造一个`Slim\Route`实例，并返回给前置的设定方法，设定该路由所适配的HTTP请求。

其中group部分的实现很有意思，由于Slim在设置路由组的时候可以嵌套，比如下述结构：

{% highlight php %}
<?php
$app = new \Slim\Slim();

// API group
$app->group('/api', function () use ($app) {

    // Library group
    $app->group('/library', function () use ($app) {

        // Get book with ID
        $app->get('/books/:id', function ($id) {

        });

        // Update book with ID
        $app->put('/books/:id', function ($id) {

        });

        // Delete book with ID
        $app->delete('/books/:id', function ($id) {

        });

    });

});
{% endhighlight %}

路由组group实现了一个先进后出的stack结构。

{% highlight php %}
<?php
namespace Slim;

class Slim {

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback all Route
     * declarations in the callback will be prepended by the group(s)
     * that it is in
     *
     * Accepts the same parameters as a standard route so:
     * (pattern, middleware1, middleware2, ..., $callback)
     */
    public function group()
    {
        $args = func_get_args();
        $pattern = array_shift($args);
        $callable = array_pop($args);
        // 将group的pattern和路由中间件存到router的group组中
        $this->router->pushGroup($pattern, $args);
        if (is_callable($callable)) {
            call_user_func($callable);
        }
        $this->router->popGroup();
    }
}
{% endhighlight %}

Router类的实现参见下述代码，如果存在多级的group，那么`Router::$routeGroups`会保存这些多级的group。

{% highlight php %}
<?php
namespace Slim;

class Router {

    /**
     * @var array Array containing all route groups
     */
    protected $routeGroups;

    /**
     * Add a route group to the array
     * @param  string     $group      The group pattern (ie. "/books/:id")
     * @param  array|null $middleware Optional parameter array of middleware
     * @return int        The index of the new group
     */
    public function pushGroup($group, $middleware = array())
    {
        return array_push($this->routeGroups, array($group => $middleware));
    }

    /**
     * Removes the last route group from the array
     * @return bool    True if successful, else False
     */
    public function popGroup()
    {
        return (array_pop($this->routeGroups) !== null);
    }
}
{% endhighlight %}

单个路由在调用`mapRoute`的时候，会从`Router`中调用`map`方法，这个方法就是向单个的路由添加当前存在`routeGroups`的路由组的pattern和路由中间件。

{% highlight php %}
<?php
namespace Slim;

class Router {

    /**
     * Add a route object to the router
     * @param  \Slim\Route     $route      The Slim Route
     */
    public function map(\Slim\Route $route)
    {
        list($groupPattern, $groupMiddleware) = $this->processGroups();

        $route->setPattern($groupPattern . $route->getPattern()); // 合并group的pattern和自己的pattern
        $this->routes[] = $route;


        foreach ($groupMiddleware as $middleware) {
            $route->setMiddleware($middleware);
        }
    }

    /**
     * A helper function for processing the group's pattern and middleware
     * @return array Returns an array with the elements: pattern, middlewareArr
     */
    protected function processGroups()
    {
        $pattern = "";
        $middleware = array();
        foreach ($this->routeGroups as $group) {
            $k = key($group);
            $pattern .= $k; // 合并pattern
            if (is_array($group[$k])) {
                $middleware = array_merge($middleware, $group[$k]); // 合并路由中间件
            }
        }
        return array($pattern, $middleware); // 返回所属group(s)的pattern和路由中间件
    }
}
{% endhighlight %}

### Run App

Slim实例在完成路由设置后，就可以进入运行阶段了。

{% highlight php %}
<?php
namespace Slim;

class Slim {

    /**
     * Run
     *
     * This method invokes the middleware stack, including the core Slim application;
     * the result is an array of HTTP status, header, and body. These three items
     * are returned to the HTTP client.
     */
    public function run()
    {
        set_error_handler(array('\Slim\Slim', 'handleErrors'));

        //Apply final outer middleware layers
        if ($this->config('debug')) {
            //Apply pretty exceptions only in debug to avoid accidental information leakage in production
            $this->add(new \Slim\Middleware\PrettyExceptions());
        }

        //Invoke middleware and application stack
        $this->middleware[0]->call();

        //Fetch status, header, and body
        list($status, $headers, $body) = $this->response->finalize();

        // Serialize cookies (with optional encryption)
        \Slim\Http\Util::serializeCookies($headers, $this->response->cookies, $this->settings);

        //Send headers
        if (headers_sent() === false) {
            //Send status
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', \Slim\Http\Response::getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/%s %s', $this->config('http.version'), \Slim\Http\Response::getMessageForCode($status)));
            }

            //Send headers
            foreach ($headers as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal) {
                    header("$name: $hVal", false);
                }
            }
        }

        //Send body, but only if it isn't a HEAD request
        if (!$this->request->isHead()) {
            echo $body;
        }

        $this->applyHook('slim.after');

        restore_error_handler();
    }
}
{% endhighlight %}

核心部分大概只有一行`$this->middleware[0]->call();`，执行中间件stack的代码。由于Slim应用实例自身位于stack底部，所以最终执行的是`Slim::call()`。

{% highlight php %}
<?php
namespace Slim;

class Slim {

    /**
     * Call
     *
     * This method finds and iterates all route objects that match the current request URI.
     */
    public function call()
    {
        try {
            // 设置相关的flash session数据
            if (isset($this->environment['slim.flash'])) {
                $this->view()->setData('flash', $this->environment['slim.flash']);
            }
            // 执行某个时间节点的hook
            $this->applyHook('slim.before');
            // 开启output buffer
            ob_start();
            $this->applyHook('slim.before.router');
            // 先设置未分发到具体路由上
            $dispatched = false;
            // 找到匹配当前uri的路由(s)
            $matchedRoutes = $this->router->getMatchedRoutes($this->request->getMethod(), $this->request->getResourceUri());
            // 对匹配路由(s)执行分发，执行各路由预先设定的闭包函数
            foreach ($matchedRoutes as $route) {
                try {
                    $this->applyHook('slim.before.dispatch');
                    // 这里，执行的是设置路由的时候传入的闭包
                    // 除非返回false，否则返回的都是true
                    $dispatched = $route->dispatch();
                    $this->applyHook('slim.after.dispatch');
                    // 判断分发完成在这里，跳出循环
                    if ($dispatched) {
                        break;
                    }
                // 当然，闭包里面丢出一个Pass异常可以跳过此闭包逻辑，执行下一个匹配路由
                } catch (\Slim\Exception\Pass $e) {
                    continue;
                }
            }
            // 如果没分发出去，那么肯定就是404了
            if (!$dispatched) {
                $this->notFound();
            }
            $this->applyHook('slim.after.router');
            // 丢出Stop异常，路由部分结束
            $this->stop();
        } catch (\Slim\Exception\Stop $e) { // 正常的执行结果
            // 把刚才输出的东西都丢到Response里面
            $this->response()->write(ob_get_clean());
        } catch (\Exception $e) { // 啊啊啊，遇到错误的执行结果了
            if ($this->config('debug')) {
                // 这个时候，Slim::run()刚刚塞进去的PrettyExceptions中间件就有用了
                ob_end_clean();
                throw $e;
            } else {
                try {
                    // 不debug的时候的输出
                    $this->response()->write(ob_get_clean());
                    $this->error($e);
                } catch (\Slim\Exception\Stop $e) {
                    // Do nothing
                }
            }
        }
    }
}
{% endhighlight %}

这段代码基本按照注释的流程执行的，其中重要的是以下三点
* 寻找匹配路由
* 路由分发
* 输出响应

#### Get matched routes

`Router`是Slim框架里解析路由的类，其实例的`getMatchedRoutes()`方法接受两个参数：Http Method和请求Uri。

{% highlight php %}
<?php
namespace Slim;

class Router {

    /**
     * Return route objects that match the given HTTP method and URI
     * @param  string               $httpMethod   The HTTP method to match against
     * @param  string               $resourceUri  The resource URI to match against
     * @param  bool                 $reload       Should matching routes be re-parsed?
     * @return array[\Slim\Route]
     */
    public function getMatchedRoutes($httpMethod, $resourceUri, $reload = false)
    {
        if ($reload || is_null($this->matchedRoutes)) {
            $this->matchedRoutes = array(); // 初始化匹配路由s
            foreach ($this->routes as $route) { // 遍历路由列表（fifo）
                if (!$route->supportsHttpMethod($httpMethod) && !$route->supportsHttpMethod("ANY")) {
                    continue;
                }

                if ($route->matches($resourceUri)) {
                    $this->matchedRoutes[] = $route;
                }
            }
        }

        return $this->matchedRoutes;
    }
}
{% endhighlight %}

对于每个Request而言，其Uri和请求方法都是唯一的。

`getMatchedRoutes()`方法将遍历Router实例中的`routes`属性，即应用设定的每一个路由；对于路由列表里面的每一个路由，首先判断是否支持Request的请求方法，然后判断相应的Uri是否匹配。

判断Uri是否匹配使用了类`Route`的`matches()`方法，主要原理是将设定的路由转换成一个正则表达式，然后进行正则匹配。
其中，如果遇到变量的标识`:`和变量组标识`+`会调用`matchesCallback()`进行特殊替换。

{% highlight php %}
<?php
namespace Slim;

class Route {

    /**
     * Matches URI?
     *
     * Parse this route's pattern, and then compare it to an HTTP resource URI
     * This method was modeled after the techniques demonstrated by Dan Sosedoff at:
     *
     * http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
     *
     * @param  string $resourceUri A Request URI
     * @return bool
     */
    public function matches($resourceUri)
    {
        //Convert URL params into regex patterns, construct a regex for this route, init params
        $patternAsRegex = preg_replace_callback(
            '#:([\w]+)\+?#',
            array($this, 'matchesCallback'),
            str_replace(')', ')?', (string)$this->pattern)
        );
        if (substr($this->pattern, -1) === '/') {
            $patternAsRegex .= '?';
        }

        $regex = '#^' . $patternAsRegex . '$#';

        if ($this->caseSensitive === false) {
            $regex .= 'i';
        }

        //Cache URL params' names and values if this route matches the current HTTP request
        if (!preg_match($regex, $resourceUri, $paramValues)) {
            return false;
        }
        foreach ($this->paramNames as $name) {
            if (isset($paramValues[$name])) {
                if (isset($this->paramNamesPath[$name])) {
                    $this->params[$name] = explode('/', urldecode($paramValues[$name]));
                } else {
                    $this->params[$name] = urldecode($paramValues[$name]);
                }
            }
        }

        return true;
    }

    /**
     * Convert a URL parameter (e.g. ":id", ":id+") into a regular expression
     * @param  array $m URL parameters
     * @return string       Regular expression for URL parameter
     */
    protected function matchesCallback($m)
    {
        $this->paramNames[] = $m[1];
        if (isset($this->conditions[$m[1]])) {
            return '(?P<' . $m[1] . '>' . $this->conditions[$m[1]] . ')';
        }
        if (substr($m[0], -1) === '+') {
            $this->paramNamesPath[$m[1]] = 1;

            return '(?P<' . $m[1] . '>.+)';
        }

        return '(?P<' . $m[1] . '>[^/]+)';
    }
}
{% endhighlight %}

#### Dispatch request

这一部分的代码主要就是分发Request信息到匹配到的路由上，并执行绑定的路由中间件和闭包函数。

{% highlight php %}
<?php
namespace Slim;

class Route {
    
    /**
     * Dispatch route
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @return bool
     */
    public function dispatch()
    {
        foreach ($this->middleware as $mw) {
            call_user_func_array($mw, array($this)); // 路由中间件闭包，会向内传递本Route的实例
        }

        $return = call_user_func_array($this->getCallable(), array_values($this->getParams()));
        return ($return === false) ? false : true;
    }
}
{% endhighlight %}

#### Output response

Slim实例将output buffer中的数据使用`ob_get_clean`收集完成，并将其放入到Response的实例中。

{% highlight php %}
<?php
namespace Slim;

class Response {
    
    /**
     * Append HTTP response body
     * @param  string   $body       Content to append to the current HTTP response body
     * @param  bool     $replace    Overwrite existing response body?
     * @return string               The updated HTTP response body
     */
    public function write($body, $replace = false)
    {
        if ($replace) {
            $this->body = $body;
        } else {
            $this->body .= (string)$body;
        }
        $this->length = strlen($this->body);

        return $this->body;
    }

    /**
     * Finalize
     *
     * This prepares this response and returns an array
     * of [status, headers, body]. This array is passed to outer middleware
     * if available or directly to the Slim run method.
     *
     * @return array[int status, array headers, string body]
     */
    public function finalize()
    {
        // Prepare response
        if (in_array($this->status, array(204, 304))) {
            $this->headers->remove('Content-Type');
            $this->headers->remove('Content-Length');
            $this->setBody('');
        }

        return array($this->status, $this->headers, $this->body);
    }
}
{% endhighlight %}

之后在`Slim::run()`方法中，通过调用`Response::finalize()`方法将生成的响应状态、头部、内容返回给Slim实例，然后再输出出去。

这样，一个完整的请求流程就完成了。