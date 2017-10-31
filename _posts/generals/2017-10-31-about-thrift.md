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

