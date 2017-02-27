---
layout: post
title: Laravel命令‘queue:restart’带来的启发
categories: [PHP, Laravel]
description: PHPUnit的简单使用
keywords: laravel
---

由于项目的后端cron部署在运维的机器上，登录一次需要验证密码和动态PIN，上去kill队列的消费进程不太方便，看了下[Laravel的文档](http://www.golaravel.com/laravel/docs/5.0/queues/#daemon-queue-worker),上面有个重启队列的命令`queue:restart`，本地执行就可以使得执行cron的机器进行队列任务重启，于是看了下实现。

执行`queue:restart`命令时，代码是这么运行的：
{% highlight php %}
<?php namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class RestartCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:restart';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Restart queue worker daemons after their current job";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
                // 这里，对‘illuminate:queue:restart’这个key设定了当前的时间戳
		$this->laravel['cache']->forever('illuminate:queue:restart', time());

		$this->info('Broadcasting queue restart signal.');
	}

}

{% endhighlight %}

对缓存中的`illuminate:queue:restart`这个key设定当前的时间戳为其值。

下述`vendor/laravel/framework/src/Illuminate/Queue/Worker.php`文件中的代码表明这个key中的时间戳是怎么利用的：
{% highlight php %}
<?php namespace Illuminate\Queue;

use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Debug\ExceptionHandler;

class Worker {

        // ... some code

    	/**
	 * Listen to the given queue in a loop.
	 *
	 * @param  string  $connectionName
	 * @param  string  $queue
	 * @param  int     $delay
	 * @param  int     $memory
	 * @param  int     $sleep
	 * @param  int     $maxTries
	 * @return array
	 */
	public function daemon($connectionName, $queue = null, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
	{
		$lastRestart = $this->getTimestampOfLastQueueRestart();

		while (true)
		{
			if ($this->daemonShouldRun())
			{
				$this->runNextJobForDaemon(
					$connectionName, $queue, $delay, $sleep, $maxTries
				);
			}
			else
			{
				$this->sleep($sleep);
			}

			if ($this->memoryExceeded($memory) || $this->queueShouldRestart($lastRestart))
			{
				$this->stop();
			}
		}
	}

	/**
	 * Determine if the queue worker should restart.
	 *
	 * @param  int|null  $lastRestart
	 * @return bool
	 */
	protected function queueShouldRestart($lastRestart)
	{
		return $this->getTimestampOfLastQueueRestart() != $lastRestart;
	}

        /**
	 * Get the last queue restart timestamp, or null.
	 *
	 * @return int|null
	 */
	protected function getTimestampOfLastQueueRestart()
	{
		if ($this->cache)
		{
			return $this->cache->get('illuminate:queue:restart');
		}
	}

        // ... some code
        
}
{% endhighlight %}

`daemon`方法执行队列中拿到的事件，每次执行完一个事件之后，程序对缓存中`illuminate:queue:restart`中的时间戳进行判断，如果和`daemon`方法启动时的时间戳不同，那么说明发生了变化，程序需要重启。
