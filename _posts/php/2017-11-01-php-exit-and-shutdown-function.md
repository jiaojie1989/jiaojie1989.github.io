---
layout: post
title: PHP - exit and shutdown function
categories: [PHP]
description: Is it still run your registed shutdown function on exit?
keywords: PHP 
---
# 缘由

下午看业务代码的时候发现系统在输出`json`格式的数据的时候，使用了`exit`：

{% highlight php %}
<?php
// bla bla bla ...
class Demo {
    protected function showSucc($errno = 0, $errmsg = '',$data=NULL,$flag=FALSE)
    {
        header('Content-Type:application/json');
        $out['errno'] = $errno;
        $out['errmsg'] = !empty($errmsg)?$errmsg:'SUCCESS';
        $out['data'] = $data;

        echo json_encode($out);

        if($flag)
        {
            $commonLog = $this->CommonLogModel->getLog();
            $commonLog['in'] = $commonLog['request'] = $_REQUEST;
            $commonLog['out'] = $commonLog['return'] = $out;
            $commonLog['errno'] = $out['errno'];
            $commonLog['msg'] = $out['errmsg'];
            $commonLog['errmsg'] = 'REQUEST_INFO';

            $filter = isset($commonLog['filter']) ? $commonLog['filter'] : array('company_id'=>'', 'out_order_id'=>'', 'order_id'=>'', 'phone'=>'');
            if(!empty($this->memberInfo))
            {
                $filter['company_id'] = $this->memberInfo['company_id'];
                $filter['phone']      = $this->memberInfo['phone'];
            }
            log_notice('showSucc', $commonLog, $filter);
//            log_monitor('showSucc', $commonLog, $filter);
        }

        exit;
    }
}
{% endhighlight %}

在`exit`之前，有一个记录日志的方法`log_monitor`，根据之前看的这套框架，系统的日志代码最终的`save`方法是注册在`register_shutdown_function`里面的，即程序退出时执行．

那么这个日志会不会上报上去呢？

# Find out

俗话说，外事不决问狗狗．但是，PHP的问题呢，我们看[`manual`](http://php.net/manual/)就足够了．

打开关于[`exit`](http://php.net/manual/en/function.exit.php)的页面，一句话赫然入目：

    Terminates execution of the script. Shutdown functions and 
    object destructors will always be executed even if exit is called.

A-ao，原来`exit`是会调用注册的`shutdown functions`的，同时呢，也会调用各种对象的`__destruct`方法．

其实，`exit`就是一个平滑的退出过程吧，[`register_shutdown_function`](http://php.net/manual/en/function.register-shutdown-function.php)里面有一个Note:

    Shutdown functions will not be executed if the process 
    is killed with a SIGTERM or SIGKILL signal. While you cannot 
    intercept a SIGKILL, you can use pcntl_signal() to install 
    a handler for a SIGTERM which uses exit() to end cleanly.

大概意思是，当你使用信号SIGKILL和SIGTERM进行关闭的时候，注册的shutdown function不会被执行，当然SIGTERM可以用可以使用信号绑定到exit上面进行平滑的关闭．

# 其他想法

Manual上面一般都是关于正常情况的讨论，那么异常情况呢？

比如出现了Fatal Error呢？

于是有了下面这个栗子：

{% highlight php %}
<?php

function foo()
{
    var_dump(microtime(true));
}

register_shutdown_function("foo");

var_dump(microtime(true));

baz();

exit;
{% endhighlight %}

显然，上面的栗子中`baz()`这个函数是不存在的，那么这一行就会出现Fatal Error．

这种情况下，其实shutdown function也生效了，是不是很惊讶. ToT