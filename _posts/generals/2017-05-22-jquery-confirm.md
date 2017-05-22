---
layout: post
title: 个推、jquery选择器以及jquery-confirm的一个坑
categories: [jquery]
description: Problems when using jquery selector and jquery-confirm plugin.
keywords: jquery, jquery-confirm
---

最近在做一个后台的个性化推送项目，主要是通过数据部门接口获取有关性别、地域、财经关键字的列表，然后由编辑人员在编辑Push消息的时候对标签进行复选，然后进行进行Push。

开始的时候，我认为流程是这样的：

![version 1]({{site.baseurl}}/images/jquery-confirm/v1.png)

结果，上周三晚上给出的数据平台的接口的关键字列表竟然给了常量，而且筛选设备也是先筛选后下载的两步接口实现，那么流程又改成了下面这样：

![version 2]({{site.baseurl}}/images/jquery-confirm/v2.png)

做的过程遇到了一下几个前端的坑，下面说下这几个问题

# JQuery选择器的特殊字符

做后台的时候图省事儿，直接用了[zofe/rapyd-laravel](https://github.com/zofe/rapyd-laravel)这个脚手架，然后把个性推荐的tags统一用了数组的形式，比如`features[sex]`,`features[pr]`这样的命名规则。

然后就发现jquery选择器选择不到这个元素了，渲染出来的Html如下所示

{% highlight html %}
<div class="form-group clearfix" id="fg_features[keywords]">
    <label for="features[keywords]" class="col-sm-2 control-label">个性化推送(关键词)</label>
    <div class="col-sm-10" id="div_features[keywords]">
        <input name="features[keywords][]" type="checkbox" value="y_1F">财经_消费&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_19">财经_理财&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_1D">财经_国际财经&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_JEv">财经_意见领袖&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_14">财经_期货&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_z_11">财经_股票_美股&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_13">财经_外汇&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_16">财经_银行&nbsp;&nbsp;
        <input name="features[keywords][]" type="checkbox" value="y_z_10">财经_股票_港股&nbsp;&nbsp;
    </div>
</div>
{% endhighlight %}

然后，狗狗和百度都搜了一下，原来jquery选择器对某些特殊字符需要再转义一次才行，参照[JQuery的选择器对控件ID含有特殊字符的解决方法](http://blog.csdn.net/z1729734271/article/details/52192035)，然后把代码里面的HRERDOC改了一下，问题解决。

{% highlight php %}
<?php
$heredoc = <<<JS
var features = ""
$("#div_features\\\[keywords\\\] input").each(function() {
    if (true === this.checked) {
        features += "" + this.value + ","
    }
})
JS;
{% endhighlight %}

# jquery-confirm引起的滚动条神秘消失事件

开发的时候，用的是22寸显示器进行开发，没有占满整个屏幕，所以滚动条是没有的，如下图所示。

![screenshot 1]({{site.baseurl}}/images/jquery-confirm/s1.png)

![screenshot 1-1]({{site.baseurl}}/images/jquery-confirm/s1-1.png)

然而，在14寸笔记本上测试的时候，confirm框框消失之后，右侧滚动条也消失了

![screenshot 2]({{site.baseurl}}/images/jquery-confirm/s2.png)

![screenshot 3]({{site.baseurl}}/images/jquery-confirm/s3.png)

![screenshot 4]({{site.baseurl}}/images/jquery-confirm/s4.png)

之后按照[jquery-confirm](http://craftpip.github.io/jquery-confirm/)的文档参数调了半个小时，没调出结果，想到可能与scroll相关，然后用狗狗搜了一下，竟然在这篇文章[HTML页面的垂直滚动条不见了](http://www.myexception.cn/HTML-CSS/772866.html)里面找到了可能的思路，`F12`打开console一看，果然给body加上了一个属性。

![screenshot 5]({{site.baseurl}}/images/jquery-confirm/s5.png)

然后，由于jconfirm后面的数字是随机变化的，所以参照[如何使用jQuery批量移除class](https://segmentfault.com/q/1010000002469953)设定了全局的jconfim属性。

{% highlight javascript %}
jconfirm.defaults = {
    onClose: function() {
                $('body').removeClass(function(index,className){
                    // Ref. https://segmentfault.com/q/1010000002469953 http://www.myexception.cn/HTML-CSS/772866.html
                    var arr = className.split(/\s+/);
                    for (var i = 0; i < arr.length; i++) {
                        if(arr[i].indexOf('jconfirm') === -1){
                            arr.splice(i,1)
                            i--
                        }
                    }
                    return arr.join(' ')
                })
            }
}
{% endhighlight %}

这样的jquery-confirm在多次调用的情况下右边滚动条会恢复，而首次调用则不会恢复，那么我们触发式手工调用一次来解决这个问题。

{% highlight javascript %}
$("#__check_button").confirm({
    title: '检查推送URL参数？',
    content: '此行为将检测推送url对应获取正文的内容。',
    buttons: {
        cancel: {
            text: '取消',
            btnClass: 'btn-default',
            keys: ['esc'],
            action: function(){
                $.alert('已取消检查URL参数操作！');
            }
        },
        confirm: {
            text: '确认',
            btnClass: 'btn-warning',
            keys: ['enter', 'shift'],
            action: function(){
                var url = $("#extra\\\[url\\\]").val()
                if (undefined === url) {
                    $.alert("undefined url")
                } else {
                    if ("" === url) {
                        $.alert("Url参数未填写")
                    } else {
                        $.confirm({
                            title: '正文内容',
                            content: function () {
                                var self = this
                                return $.ajax({
                                    url: '/toutiao/content',
                                    data: {url: url},
                                    method: 'get'
                                }).done(function (response) {
                                    var title = response.result.data[0].title
                                    var content = response.result.data[0].content
                                    if (undefined === title) {
                                        self.setContent("正文内容获取失败，请检查Url参数")
                                    } else {
                                        self.setContent("<b>" + title + "</b>")
                                        self.setContentAppend('<br> ' + content)
                                        self.setTitle(title);
                                    }
                                }).fail(function(){
                                    self.setContent("正文内容获取失败，请检查Url参数")
                                })
                            },
                            buttons: {
                                cancel: {
                                    text: 'close',
                                    btnClass: 'btn-default',
                                    keys: ['esc'],
                                    action: function(){
                                        $.alert("正文接口调用完成。") // 这个手工式触发，解决滚动条问题
                                    }
                                }
                            }
                        })
                    }
                }
            }
        }
    }
})
{% endhighlight %}

ToT，忙碌的一天就这么过去了......