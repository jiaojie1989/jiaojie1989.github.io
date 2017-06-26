---
layout: post
title: 面试所考察的点，以及一道关于正则的题目
categories: [jquery]
description: Points that interviews concern, and a topic of regexp.
keywords: regexp, interview
---
# 缘由

上周末听到一个有关面试的点，然后想了一下。

市面上的JD一般按工作年限进行区分：

* 1-3年

考察重点一般是想象力，智力层次的题目会更多一些，拿来做储备和潜力股的。

* 3-5年

考察重点一般是语言层面和解决实际问题，这种人招聘过来是拿来就要用的。

* 5年以上

考察重点一般是系统层面的设计和分析，拿来做资深工程师或者专家的。

# 一道题目

>输入一串整形数字（字符），按照西方国家习惯每三位加个逗号分割。

这里仅就正则如何做这个题目进行编码，不用正则的方法这里就不说了。

考察点主要是贪婪非贪婪匹配。

{% highlight php %}
<?php

require_once "../vendor/autoload.php";

$input = "12345678";

$regex = "#^(?<first>([0-9]{1,3})??)(?<others>([0-9]{3})*)$#";

$callback = function($match) {
    $arr = str_split($match["others"], 3);
    return empty($match["first"]) ? (implode(",", $arr)) : ("" . $match["first"] . "," . implode(",", $arr));
};

$output = preg_replace_callback($regex, $callback, $input);

var_dump($output);
{% endhighlight %}