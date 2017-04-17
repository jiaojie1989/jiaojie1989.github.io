---
layout: post
title: Learn Ruby The Hard Way - 3
categories: [Ruby, Programming Language]
description: Files
keywords: Ruby
---

## Files

### 一些基本方法

Ruby中文件操作相关的方法都在`File`里面，具体如下

* open - 打开文件
* close - 关闭文件
* read - 读取文件内容
* readline - 读取文件中一行
* truncate - 截短文件（默认是清空）
* write - 写入文件

{% highlight ruby %}
filename = ARGV.first
script = $0

puts "We're going to erase #{filename}."
puts "If you don't want that, hit CTRL-C (^C)."
puts "If you do want that, hit RETURN."

print "? "
STDIN.gets

puts "Opening the file..."
target = File.open(filename, 'w')

puts "Truncating the file.  Goodbye!"
target.truncate(target.size)

puts "Now I'm going to ask you for three lines."

print "line 1: "; line1 = STDIN.gets.chomp()
print "line 2: "; line2 = STDIN.gets.chomp()
print "line 3: "; line3 = STDIN.gets.chomp()

puts "I'm going to write these to the file."

target.write(line1)
target.write("\n")
target.write(line2)
target.write("\n")
target.write(line3)
target.write("\n")

puts "And finally, we close it."
target.close()
{% endhighlight %}

### 更多的方法

* exists? - 文件是否存在
* zero? - 是否是个空文件

{% highlight ruby %}
{% endhighlight %}
