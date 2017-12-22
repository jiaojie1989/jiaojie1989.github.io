---
layout: post
title: MySQL大数据分页、遍历
categories: [MySQL, Pagination]
description: MySQL pagination with big data, traverse.
keywords: MySQL, Pagination
---
# 起因

公司业务有一项限额开关在一张表中用两个字段表示，通常情况下，这两个字段的设置是一样的；
但是由于前人遗留下来的代码逻辑bug，导致某种业务情形下，两个字段的value会不一致，这就会影响所谓的企业下单打车。

代码层面的bug已然修复，但是过去的一个月之内，究竟有多少人通过这样的接口配置了不一样的数据呢？

这个就需要扫表进行查询和修复了。

# 扫表遇到的问题

由于表的数据量巨大，一次性的取出所有数据并进行查询不太现实，于是采用sql中分页的方法进行批量查询，然后逐个进行问题排查。

使用的sql类似下面这个样子：

{% highlight sql %}
SELECT * FROM test where status = 1 limit start, 1000
{% endhighlight %}

每1000个作为一个分页，然后逐步进行排查。

程序运行中出现了执行到30多页的时候，程序卡死的情况，然后找出执行的sql，直接连接mysql客户端进行查询，查出结果的时间竟然高达5s。

这时候突然想起来，mysql分页时间消耗会随着start值的增大而急剧增加。

# 解决办法

假定表明为test，拥有一个数字的非自增的PK为id，其他字段大概有20个，表数据大约为50w条。

## 正常分页sql及执行情况

假设执行查找某种where条件下从第30w开始，取10row的任务。

正常的sql应该是这么写的：

{% highlight sql %}
select * from test where status = 1 limit 300000, 10;
---
10 rows in set (0.49 sec)
{% endhighlight %}

## 优化方案

之前sina的dba进行过一次培训，大数据的分页可以优先取出PK，然后将表中数据按照PK条件进行查找。

类似下面的sql:

{% highlight sql %}
select * from test a , 
    (select id from test where status = 1 limit 300000, 10) b 
    where a.id  = b.id;
---
10 rows in set (0.06 sec)
{% endhighlight %}

这样的写法其实也可以写作inner join，

{% highlight sql %}
select * from test 
    inner join (select id from test  limit 300000, 10) t2 
    using (id);
---
10 rows in set (0.06 sec)
{% endhighlight %}

# ToT

然而，这只是如果是业务接口分页时的解决方案，我们需要的是快速地扫描完整个表。

于是想到单独利用PK或者UNI进行大小判断是否可以解决这个问题。

{% highlight sql %}
select * from test 
    where status = 1 and id > xxx
    order by id asc
    limit 10
---
10 rows in set (0.00 sec)
{% endhighlight %}

xxx的值由程序进行控制，把上次sql搜索结果中最大的放进下次sql中，这样就能达到一个快速扫表的状态。