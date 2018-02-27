---
layout: post
title: Laravel - 利用Real-time Facade缩短测试中Mock类的代码
categories: [Testing, Laravel, PHP]
description: 使用PHPUnit测试的过程中，当我们Mock一个依赖对象的时候，代码就会非常复杂；Laravel 5.4以上的版本提供的Real-time Facade类，利用这种设计模式可以缩短相关的测试代码，进行预期测试。
keywords: Laravel, Testing, Facade
---

# Inspiration

这篇文章启发自Laravel News最近的一篇文章[Defense Programming: Anticipating Failures with Tests](https://laravel-news.com/defense-programming-anticipating-failures-tests)。

翻译下来应该是这样的：《防御式编程：参与到失败的测试中去》。看了看整篇文章，大概讲的就是一种测试第三方依赖的方法，而Laravel框架自5.5版本后有一种[Real-time Facade](https://laravel.com/docs/5.5/facades#real-time-facades)，可以更加简化此类测试在Laravel中的写法。