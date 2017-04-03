---
layout: post
title: Learn Ruby The Hard Way - 1
categories: [Ruby, Programming Language]
description: Ruby
keywords: Ruby
---
## Strings And Printings 字符串与输出

### 简单的输出

Ruby中的简单输出类似Python的`print`，使用`puts`进行输出。

{% highlight ruby %}
puts "Hello World!"
puts "Hello Again"
puts "I like typing this."
puts "This is fun."
puts 'Yay! Printing.'
puts "I'd much rather you 'not'."
puts 'I "said" do not touch this.'
{% endhighlight %}

### 带变量的输出

类似PHP中`{$foo}`这样的输出方式

##### PHP
{% highlight php %}
<?php
$foo = "World";
echo "Hello {$foo} !";
{% endhighlight %}

##### Ruby
{% highlight ruby %}
foo="World"
puts "Hello #{foo} !"
{% endhighlight %}

### 带有`,`号的输出

Ruby中`puts`输出可以用`,`号分割，输出结果为各段换行显示。
{% highlight ruby %}
puts "Hello", "World", "!"
# Output as follows:
#Hello
#World
#!
{% endhighlight %}

### 格式化输出

类似Python(`%`)、PHP(`sprintf`)，Ruby也可以进行占位符类似的格式化输出。
{% highlight ruby %}
hello = "Hello"
world = "World"

puts "%s World !" % hello # 单个变量
puts "%s %s !" % [hello, world] # 多个变量
{% endhighlight %}

与Python的不同在于，Python中多个变量是用`()`圆括号表示多个变量
{% highlight python %}
print "%s %s !" % ("Hello", "World")
{% endhighlight %}