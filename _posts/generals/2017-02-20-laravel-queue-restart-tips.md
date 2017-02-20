---
layout: post
title: Laravel命令‘queue:restart’带来的启发
categories: [PHP, Laravel]
description: PHPUnit的简单使用
keywords: laravel
---

由于项目的后端cron部署在运维的机器上，登录一次需要验证密码和动态PIN，上去kill队列的消费进程不太方便，看了下[Laravel的文档](http://www.golaravel.com/laravel/docs/5.0/queues/#daemon-queue-worker),上面有个重启队列的命令`queue:restart`，本地执行就可以使得执行cron的机器进行队列任务重启，于是看了下实现。

