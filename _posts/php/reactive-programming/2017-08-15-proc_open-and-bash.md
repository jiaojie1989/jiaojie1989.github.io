---
layout: post
title: 从proc_open()打开的两个进程说起
categories: [PHP]
description: 默认sh和proc_*系列函数的关系
keywords: PHP
---
## 起因

最近工作不太紧张，于是看起了《PHP Reactive Programming》这本书，其中举了个Chat Server的例子。

这是一个Cli App，默认启动一个Server Manager，根据Stdin的输入进行Chat Server的创建，Server Manager建立一个Unix Socket Server，其创建的Chat Server通过连接Socket Server与Server Manager进行状态通信；而Chat Server监听服务器的某个指定端口，创建一个WebSocket Server；其他客户端通过连接这个WebSocket Server进行Chat动作，而Chat Server会实时将在线、聊天数量上报给Server Manager。

用的当然是书中提到的Reactive Programming，使用了PHP的stream族函数进行非阻塞编程。

其中，由于之前对黄旭说过的socketpair比较感兴趣，所以详细看了一下Server Manager创建Chat Server的部分。

这一部分采用了[symfony/process](https://packagist.org/packages/symfony/process)组件，对Chat Server的创建并不是通过`fork`的方式进行的，而是通过`proc_open`进行的。

之后，参照[proc_open()](http://php.net/manual/en/function.proc-open.php)文档中给的例子，在不同的机器上实验了一下，这就试出了毛病。

## 问题

{% highlight php %}
<?php
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
);

$cwd = '/tmp';
$env = array('some_option' => 'aeiou');

$process = proc_open('sleep 100', $descriptorspec, $pipes, $cwd, $env);

if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt

    fwrite($pipes[0], '<?php print_r($_ENV); ?>');
    fclose($pipes[0]);

    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);

    echo "command returned $return_value\n";
}
{% endhighlight %}

出现问题的操作系统是去年年底新装的`Xubuntu 16.04/PHP 5.6`，执行`php sleep.php`父进程下面竟然出现了两个进程。

![proc-ubuntu]({{site.baseurl}}/images/reactive-programming/proc_u.png)

然后，换到测试机器上面(`CentOS 6/PHP 5.5`)，执行相同的代码，父进程下面只挂着一个`sleep 100`的子进程，这个是正确的。

![proc-centos]({{site.baseurl}}/images/reactive-programming/proc_c.png)

## 排查

初始以为是PHP版本造成的问题，然后Ubuntu和CentOS都换用PHP 7.0版本执行代码，然后结果相同，所以排除PHP版本造成的问题。

那么会不会是服务器版本造成的问题呢？

我想了一下，Ubuntu上面多出的一个进程是`sh -c`的进程，那么我们手工执行一下`sh -c 'sleep 100'`会发生什么呢？

于是在Ubuntu上面得到下面的结果，仍然是两个进程：

![proc_sh-ubuntu]({{site.baseurl}}/images/reactive-programming/sleep_u.png)

然而在CentOS上面，得到结果却只有一个`sleep`的进程：

![proc_sh-centos]({{site.baseurl}}/images/reactive-programming/sleep_c.png)

那么，会不会是`sh`的问题呢？

## 解决

突然想起来，很久很久以前安装Ubuntu操作系统的时候，`sh`默认的都是`dash`，一般都要改成`bash`，会不会是`dash`造成的呢？

于是，在Ubuntu上面手工执行`bash -c 'sleep 100'`和`dash -c 'sleep 100'`，结果分别如下：

#### bash
![proc_sh-bash]({{site.baseurl}}/images/reactive-programming/bash.png)

#### dash
![proc_sh-dash]({{site.baseurl}}/images/reactive-programming/dash.png)

然后查看`sh`，果然是软链接到`dash`上面了。

![proc_sh-sh]({{site.baseurl}}/images/reactive-programming/sh.png)

将`sh`的软链接改到`bash`上面，PHP代码按照预期的结果执行下去了～

## 小结

PHP的`proc_open`系列函数在Linux操作系统中应该是通过系统默认的`sh -c`命令进行执行的，要把不同的`sh`带来的结果考虑进去。