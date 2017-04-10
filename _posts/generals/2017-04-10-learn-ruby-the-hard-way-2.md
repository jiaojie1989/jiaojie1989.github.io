---
layout: post
title: Learn Ruby The Hard Way - 2
categories: [Ruby, Programming Language]
description: About Input and Interaction
keywords: Ruby
---
## Arguments

### Script Name

Ruby命令行执行情况下，脚本文件名用`$0`获取。

{% highlight ruby %}
puts "The script name is called: #{$0}"
{% endhighlight %}

### ARGV

Ruby用`ARGV`来获取传入的参数。

获取参数的过程称为`解包(unpack)`

{% highlight ruby %}
# run `ruby test.php a b c`

puts ARGV # ['a', 'b', 'c']

a, b, c = ARGV # unpack 解包

puts a, b, c # 'a', 'b', 'c'
{% endhighlight %}

