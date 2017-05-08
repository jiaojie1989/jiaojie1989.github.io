---
layout: post
title: On Feedback System 用户反馈系统的设计
categories: [Product System]
description: On Feedback System
keywords: Product System
---
## 用户反馈系统

### 缘由

最近由于某浪wap用户反馈系统不再维护，且反馈页面样式文件404，于是pm们找上门来要求开发一套新的反馈系统。

### 方案调研

虽然pm给出了一个简单的原型图，但是我们还是需要调研一下市面上都有什么样的反馈系统。

#### 邮件反馈

因为手边只有Android的机器，所以点开了某浪最常用的"某浪口袋"App。

反馈界面点击后会跳转到发送邮件界面，自动生成半封邮件。

* 新浪口袋用户反馈

![koudai 1]({{site.baseurl}}/images/on-feedback/koudai-1.png)

![koudai 2]({{site.baseurl}}/images/on-feedback/koudai-2.png)

这种方案一般会发送邮件到邮件组，问题在于容易造成垃圾邮件泛滥和处理不及时。

#### 对话/聊天/论坛式反馈

典型的比如Bug跟踪系统。

* PHP Bug系统

![php bug]({{site.baseurl}}/images/on-feedback/bugsys.png)

这种方案扩展开来就是类似工单系统，优点是分类详细、责任明确，缺点很明显，需要大量的维护工作和大量客服人员的支持。

#### 用户提交图片/信息到系统的反馈

这种就是我们要做的用户反馈系统，大部分App都是采用这种方案进行处理。

这种App反馈有做原生的也有做成H5页面的，典型的原生的反馈类似qq、淘宝的App,利用H5的目前只观察到UC浏览器(当然WebApp一定是H5)是这样的。

* QQ用户反馈

![qq]({{site.baseurl}}/images/on-feedback/qq.png)

* UC浏览器用户反馈

![qq]({{site.baseurl}}/images/on-feedback/uc.png)

### 方案设计

鉴于我们pm产品设计、移动端同事开发的不确定性，我们还是先设计接口吧。

#### 技术问题

##### CRSF/SQL INJECTION/XSS

无论是H5还是原生App的反馈页面，都需要调用接口，那么对接口安全方面的防范就尤其重要。

常见的Xss和SQL注入我们通过框架级别的ORM进行解决，CRSF只能通过验证Referer和Crsf-Token方式解决。

所以需要一个提供Token的接口。

#### Emoji

由于是移动端的反馈，很多用户会输入Emoji表情，这时候数据的存储和展示就成为了问题。

MySQL utf8mb4字符集可以存储Emoji表情，而大多数现代浏览器也是支持Emoji表情的展现的，类似下图所示。

![emoji]({{site.baseurl}}/images/on-feedback/emoji.png)

如果浏览器不支持，也可以采用Twitter的替换方案[twemoji](https://github.com/twitter/twemoji)。

#### 图片上传和展示

为了以防pm后期增加回复图片上传的功能，我们需要在后台增加两种方案：单纯的图片放大和图片的上传展示。

[zoom.js](https://github.com/hakimel/zoom.js)能够简单地实现图片的放大功能，而[bootstrap-fileinput](https://github.com/kartik-v/bootstrap-fileinput/)也提供了相当完善的图片上传的例子。

#### 缩略图

接口原则上只提供图片上传的地址，但是在移动端不论是H5还是原生都存在展示的流量问题，所以用户上传的图片需要进行压缩处理。

这一部分通过PHP的扩展就可以解决。

#### JSON

接口输出一般采用JSON数据格式，PHP自带的`json_encode()`函数在默认的情况下，会对多字节字符进行`\u`这样的unicode转码，emoji会被专程两个`\u`。

`JSON_UNESCAPED_UNICODE`常量在5.4之后可以使用，对多字节字符不进行转义，直接可以输出emoji字符。

如果不进行设置，在浏览器层面目前的观测结果也是可以直接展示emoji字符的。

#### 接口设计

宗上，我们的用户反馈系统大概需要支持用户上传图片、文字，确认用户的身份，以及产品回复用户途径。

对外接口端初步设计了4个接口：

* crsf token接口
* 上传图片接口
* 提交反馈接口
* 反馈列表接口

### 数据设计

### 中间件设计

### 功能扩展设计





