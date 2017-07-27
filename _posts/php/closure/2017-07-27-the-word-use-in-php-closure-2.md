---
layout: post
title: PHP basic types' behavior in use section of closure
categories: [PHP]
description: Fixes of last introduction of closure.
keywords: PHP, Closure
---

前天写的关于Closure中use的部分有点问题，use中传递php基本数据类型时，绝大多数情况都是传递的copy。

这个大多数情况包括boolean/integer/float/string和array这样的类型。

针对object的类型，传递的是引用，不需要加`&`符号。

针对null或者未初始化的变量，加`&`符号是保留一个对该命名的引用，之后这个名称下的所有变化都会涵盖到变量中。

针对resource的类型，根据[文档](http://php.net/manual/en/language.types.intro.php)，resource本身就是对外部资源的一个引用，而它的状态也是全局唯一的，所以这个也不需要加`&`符号。