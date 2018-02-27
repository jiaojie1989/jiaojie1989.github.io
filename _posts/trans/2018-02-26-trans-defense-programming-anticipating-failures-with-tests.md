---
layout: post
title: Laravel - 利用Real-time Facade缩短测试中Mock类的代码
categories: [Testing, Laravel, PHP]
description: 使用PHPUnit测试的过程中，当我们Mock一个依赖对象的时候，代码就会非常复杂；Laravel 5.4以上的版本提供的Real-time Facade类，利用这种设计模式可以缩短相关的测试代码，进行预期测试。
keywords: Laravel, Testing, Facade
---

# Inspiration

这篇文章启发自Laravel News最近的一篇文章[Defense Programming: Anticipating Failures with Tests](https://laravel-news.com/defense-programming-anticipating-failures-tests)。

翻译下来应该是这样的：《防御式编程：参与到失败的测试中去》。看了看整篇文章，大概讲的就是一种测试第三方依赖的方法，而Laravel框架自5.5版本后有一种[Real-time Facade](https://laravel.com/docs/5.5/facades#real-time-facades)，可以更加简化此类测试在Laravel中的写法。

# Doc Abstract

随着现代企业技术的分工，大而全的应用和项目不复存在。“微服务”化的服务分拆、所谓“服务治理”、各种高大上的架构，大部分都基于Unix最原始的哲学“程序应该只关注一个目标，并尽可能把它做好”。

所以，当我们新开发一个功能的时候，很可能就会用到另外的服务。比如Laravel News这个网站，它的首页上面有些工作信息是来自于LaraJobs这个第三方网站的，而当LaraJobs这个网站服务不可用或停掉了之后，Laravel News这个网站的首页会发生些什么呢？

所以我们需要写一些服务失败时候的测试用例，以便我们更好的应对我们的功能。

Laravel中可以使用*real-time facades*对预期失败的服务进行测试，文中对“获取文章列表”这一Http服务进行举例。

假设“获取文章列表”这一服务为简单的Http服务，那么作为调用方，文中是通过注册一个单独的服务项进行的。

{% highlight php %}
<?php

namespace App\Contracts;

interface ArticleRepository
{
    public function get();
}
{% endhighlight %}

{% highlight php %}
<?php

namespace App\Repositories;

use GuzzleHttp\Client;

class ApiArticleRepository implements ArticleRepository
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get($id)
    {
        return $this->client->get('posts', ['query' => ['id' => $id]]);
    }
}
{% endhighlight %}

通过注册ServiceProvider到容器中来保证单例：

{% highlight php %}
<?php

namespace App\Providers;

use GuzzleHttp\Client;

class RepositroyServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function register()
    {
        $this->app->singleton('PostRepository',
                function() {
            return new \App\Repositories\ApiArticleRepository(new Client([
                'base_uri' => config('api.url')
            ]));
        });
    }

}
{% endhighlight %}

应用中使用这个服务的例子如下：

{% highlight php %}
<?php

namespace App\Http\Controllers;

use App\Repositories\ApiArticleRepository as Repository;

class RegisterController extends Controller
{
    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function view($id)
    {
        return view('artice.view', ['article' => $this->repository->get($id)]);
    }
}
{% endhighlight %}

那么，如何编写测试用例来测试失败的情况呢？

应该注意到，我们应该测试`$this->repository->get($id)`这段代码在Http请求出错时候的情况