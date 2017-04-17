---
layout: post
title: Learn Ruby The Hard Way - 2
categories: [Ruby, Programming Language]
description: Arguments
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

### STDIN

标准输入，`stdin``stdout``stderr`三个标准io中的input。

### gets

如果没有附加参数，直接调用的gets相当于标准输入的gets；相反，如有附加参数，gets相当于从以附加参数为文件名的文件中读取输入。

{% highlight ruby %}
# run `ruby test.rb`
puts gets.chomp # input 'hello\n'
# output 'hello'

# run `ruby test.rb test` with no such file exist
puts gets.chomp # if no file called 'test' exists
# test.rb:1:in `gets': No such file or directory @ rb_sysopen - test (Errno::ENOENT)

# run `ruby test.rb test`
puts STDIN.gets.chomp # input'hello\n'
# output 'hello'
{% endhighlight %}

