---
layout: post
title: Thrift
categories: [thrift]
description: This is a general view of thrift framework.
keywords: thrift
---
# 缘由

入职滴滴大概三周了，这边的工作与在渣浪有很多的不同．这边的很多基础设施都是完善的，比如日志＼报警＼框架等．

这些还好，最大的变化是这边有了一些所谓`服务治理`相关的东西．

最早在ziroom的时候，php团队主要和java团队通过soap这种古老的webservice进行通信，也提供各种各样的http接口供各种java服务和app客户端使用；

sina财经主要是php编写的，系统之间的通信也是用http这样的接口进行的，某些核心系统HQ比较特殊，和其他系统用websocket进行通信．

这边组内大概是一种`微服务化`的架构，各个子系统一直在进行拆拆拆工作，它们之间主要通过http接口进行通信；而didi提供的基础服务主要是用thrift framework进行服务治理的，专快系统也使用Kafka作为队列推送给其他系统进行消费．

Kafka这种东西后面再说，这里先讲讲Thrift.

# Thrift

## 什么是Thrift

如果上网查询的话，很多人会说Thrift是一个协议（protocol），didi内部wiki的很多接口文档也直接在`协议`一栏填写了Thrift．

但这样是不对的，引用[stackoverflow](https://stackoverflow.com/questions/38088324/thrift-can-use-http-but-it-is-a-binary-communication-protocol)上面的一句话：

    First, Wikipedia is not an authoritative resource, so don't bet your whatever on it.

    No, Thrift is not a binary communication protocol. Thrift is not a protocol at all.

    Thrift is a framework that offers the ability to serialize into and communicate over 
    various protocols and transports, which include HTTP and binary, but are by no means limited to that.
    
Thrift是一套RPC通信框架，根据[github](https://github.com/apache/thrift)上面的`Readme`，实现了类似下面这副图的功能：

![thrift layers](https://github.com/apache/thrift/raw/master/doc/images/thrift-layers.png)

Thrift提供了上图最左侧的各种层级（操作系统＼编程语言＼底层传输协议＼数据封装＼数据协议＼服务器和客户端）的一个Option集合，使用Thrift框架主要是将系统使用的各种Case挑选出来，拼装成为数据Rpc调用的底层（通过gen实现），与上层业务逻辑无关，也适配各种编程语言．

## 什么情况用Thrift

Thrift跨语言，跨平台，是多个异构系统揉合成一体的一个中间件；它提供的Binary/Tcp/Buffered传输方式也在一定程度上减小了数据大小，节省了带宽．

官方提供的gen code也可以根据简单易懂的规范格式快速生产出中间件代码．

## 什么时候不用Thrift

事实上，Thrift也是一种RPC，和SOAP＼Restful＼HTTP Api这些也没有本质的区别．

主要的缺点也是服务端如果改变Api，那么所有使用的C端都要改变，版本不兼容；另外Binary传输也不利于数据排查．

# 举个🌰

Thrift最方便的就是能够自动生成各种语言的code，业务逻辑层面只需要关心调用逻辑即可，无需关注过于复杂的连接和协议代码．

要使用Thrift去生成代码，首先要安装这么一个工具[官网link](http://thrift.apache.org/)，按照Guide中的说明进行安装；当然，Ubuntu可以用`apt install thrift`，Mac可以用`brew install thrift`来进行预编译好的二进制安装．

安装完成之后，我们需要按照Thrift的语法书写一个文件，后面的各种语言的代码都是根据这个文件生成的，一般这种文件的后缀是`.thrift`．

这里推荐一本开源书[<Thrift: The Missing Guide>](http://diwakergupta.github.io/thrift-missing-guide/)，里面有一些介绍．

下面是我生成测试例子时候使用的idl文件：

{% highlight thrift %}
namespace java site.jiaojie.test.thrift.demo
namespace php Jiaojie.Thrift.Test

service  HelloWorldService {
      string sayHello(1:string username)
}
{% endhighlight %}

按照文档中的说明，我们使用`thrift --gen <language> hello.thrift`生成对应编程语言的库文件．

Rpc的话，那么肯定分为Server和Client两端，下面用java实现一套简单的服务端。

## Server

生成的java库文件如下所示：

![gen java]({{site.baseurl}}/images/thrift/Screenshot_2017-11-01_18-33-13.png)

我们建立一个Maven项目，引入`org.apache.thrift`的`libthrift`包，将生成的文件丢到相应的位置．

新建立一个实现`sayHello`方法的类`HelloWorldImpl.java`，具体代码如下:

{% highlight java %}
package site.jiaojie.test.thrift.demo;

import org.apache.thrift.TException;

/**
 *
 * @author jiaojie <jiaojie@didichuxing.com thomasjiao@vip.qq.com>
 */
public class HelloWorldImpl implements HelloWorldService.Iface {

    public HelloWorldImpl() {
    }

    @Override
    public String sayHello(String username) throws TException {
        String output = "hello, " + username;
        System.out.println(output);
        return output;
    }

}
{% endhighlight %}

然后再实现我们运行的main class，`HelloWorldServer.java`:

{% highlight java %}
package site.jiaojie.test.thrift.demo;

import org.apache.thrift.TProcessor;
import org.apache.thrift.protocol.TBinaryProtocol;
import org.apache.thrift.server.TServer;
import org.apache.thrift.server.TSimpleServer;
import org.apache.thrift.server.TThreadPoolServer;
import org.apache.thrift.transport.TServerSocket;
import org.apache.thrift.protocol.TJSONProtocol;

/**
 *
 * @author jiaojie <jiaojie@didichuxing.com thomasjiao@vip.qq.com>
 */
public class HelloServiceServer {

    public static final int SERVER_PORT = 8090;

    public void startServer() {
        try {
            System.out.println("HelloWorld TSimpleServer start ....");

            TProcessor tprocessor = new HelloWorldService.Processor<HelloWorldService.Iface>(new HelloWorldImpl());
            TServerSocket serverTransport = new TServerSocket(SERVER_PORT);
//            TServer.Args tArgs = new TServer.Args(serverTransport);
            TThreadPoolServer.Args tArgs = new TThreadPoolServer.Args(serverTransport);
            tArgs.processor(tprocessor);
            tArgs.protocolFactory(new TBinaryProtocol.Factory());
//            TServer server = new TSimpleServer(tArgs);
            TServer server = new TThreadPoolServer(tArgs);
            server.serve();
        } catch (Exception e) {
            System.out.println("Server start error!!!");
            e.printStackTrace();
        }
    }

    public static void main(String[] args) {
        HelloServiceServer server = new HelloServiceServer();
        server.startServer();
    }

}
{% endhighlight %}

这样就完成了Server的编写．

在编译过程中，遇到了一点小插曲

    SLF4J: Failed to load class "org.slf4j.impl.StaticLoggerBinder".

根据[一篇博客](http://www.cnblogs.com/FocusIN/p/5853009.html)里面的信息，我们添加了`slf4j-nop`包进行解决．

## Client

C端当然要用万能的PHP进行编写了，首先引入`apache/thrift`包，再按指定目录放入thrift生成的php文件，最后使用的`composer.json`文件如下：

{% highlight json %}
{
    "name": "jiaojie/test",
    "description": "Description of project test.",
    "authors": [
        {
            "name": "jiaojie",
            "email": "thomasjiao@vip.qq.com"
        }
    ],
    "require": {
        "apache/thrift": "0.9.3"
    },
    "autoload": {
        "psr-4": {
            "Jiaojie\\": "src"
        },
        "classmap": [
            "src/Thrift"
        ]
    }
}
{% endhighlight %}

C端程序的编写要注意和Server端的协议一致，我在Server端采用了Socket+Binary的方式，所以C端也要采用相同的方式建立连接：

{% highlight php %}
<?php

/*
 * Copyright (C) 2017 Didi
 *  
 *  
 * 
 * This script is firstly created at 2017-10-31.
 * 
 * To see more infomation,
 *    visit our official website http://home.didichuxing.com/.
 */

use Jiaojie\Thrift\Test\HelloWorldServiceClient as Client;
use Thrift\Transport\TCurlClient;
use Thrift\Transport\TSocket;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\TFramedTransport;

require "vendor/autoload.php";

$client = new Client($input = new TBinaryProtocol($transport = new TBufferedTransport($sock = new TSocket("127.0.0.1", 8090, false, "var_dump"))));

$transport->open();

$output = $client->sayHello(rand());
$transport->close();
{% endhighlight %}

# 小结

这样就完成了一个简单的thrift示例．Didi的很多核心系统和基础设施都是通过这种Framework进行服务治理的，大概就这样^-^