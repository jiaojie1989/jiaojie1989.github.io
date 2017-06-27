---
layout: post
title: PHP Closure
categories: [PHP]
description: Something about PHP closures.
keywords: PHP, Closure
---
## 缘由

这几天看了Martin Sikora写的[《PHP Reactive Programming》](https://www.amazon.com/PHP-Reactive-Programming-Martin-Sikora/dp/1786462877)的第一章*Introduction to Reactive Programming*，其中对*函数式编程*举例时的PHP代码把匿名函数玩出了花，所以下午看了下[关于Closure的文档](http://php.net/manual/en/class.closure.php)。

## 函数式编程

函数式编程(Functional Programming)是一种*编程范式*([Programming Paradigm](https://en.wikipedia.org/wiki/Programming_paradigm))。

其要点有三：

* 消除函数副作用(Eliminating side effects)

由于非局部变量改变或跳出函数体的控制语句，而造成的函数*变量不满足交换率的作用。

* 变量不变性(Avoiding mutable data)

不改变非局部变量的状态，并且对于同样的输入，会造成相同的输出。

* 函数作为程序基本数据类型(First-class citizens and higher-order functions)

函数可以作为函数参数、被赋值和作为函数返回值。

## PHP匿名函数