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
puts "There are #{10} types of people." # 带数字的
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
puts "%d %s %.2f" % [12, 23, 123.124124] # 数字
{% endhighlight %}

与Python的不同在于，Python中多个变量是用`()`圆括号表示多个变量
{% highlight python %}
print "%s %s !" % ("Hello", "World")
{% endhighlight %}

### 格式化输出的高级用法

可以指定一个变量为规定好的格式，然后进行格式化输出，一般日志应该是这样指定格式的。
{% highlight ruby %}
formatter = "%s %s %s %s"
puts formatter % [true, false, true, false]
puts formatter % [1, 2, 3, 4]
{% endhighlight %}

### 字符串连接

Ruby的字符串连接用法类似Python中的字符串连接，用`+`号进行连接。
{% highlight ruby %}
puts "Hello" + " " + "World"
puts "." * 10
{% endhighlight %}

### Heredoc

Ruby中Heredoc是由`<<`进行界定的。
{% highlight ruby %}
puts <<HEREDOC
I'm a programmer.
HEREDOC
{% endhighlight %}

但是，这样的写法不利于对齐，于是Ruby 2.3中增加了一种新的写法`<<~`。
{% highlight ruby %}
def hello
    puts <<-HEREDOC
        I'm a programmer. 
    HEREDOC  # 这时候输出会多出空格
end

def world
    puts <<~HEREDOC.
        I'm a programmer.
    HEREDOC # 据说这时候完美去掉空格
end
{% endhighlight %}

具体各种可以参见下面的栗子。
{% highlight ruby %}
def hello
	puts <<HEREDOC
		I know I know
		You will like it.
HEREDOC # 默认的方式

	puts <<-HEREDOC
		I know I know
		You will like it.
	HEREDOC # 可以关键词对齐的方式

	puts <<~HEREDOC
		I know I know
		You will like it.
	HEREDOC # 内容去除缩进的方式
end

hello
{% endhighlight %}

##### 其他

Heredoc的用法非常灵活，具体可以参见[Ruby China](http://ruby-china.org/topics/28501)的写法。