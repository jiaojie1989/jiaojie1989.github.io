---
layout: post
title: 在禁用pid/pcntl/posix/exec系列函数的机器上部署单例cron任务
categories: [PHP]
description: 部署cron的机器上禁用了getmypid,posix_*,pcntl_*系列的函数，但是要保证某个任务同时只存在一个，纯PHP方法实现
keywords: PHP 
---
# 缘由

下午需要部署一组消费redis zset类型队列的任务：
由于zset的zrangebyscore并不完全按照添加顺序进行输出，所以为了保证数据的有序性，需要对同时消费的进程进行数量限制（同时消费队列的进程不得超过1个）。

于是仿照服务进程的方式，写了一段将进程process id放置到xxx.pid文件中进行锁定的脚本。

{% highlight php %}
class balabala {

    // ...

    public function index()
    {
        $pid     = getmypid();
        $pidFile = self::PID_FILE;
        if (file_exists($pidFile)) {
            $pid    = file_get_contents($pidFile);
            $output = shell_exec("ps aux | grep -v grep | grep {$pid} | grep php | grep index_prod | grep balabala");
            if (!empty($output)) {
                exit(0);
            }
        }
        file_put_contents($pidFile, $pid);
        for ($i = 0; $i < 100000; $i++) {
            // bla bla
        }
        unlink($pidFile);
    }

    // ...

}
{% endhighlight %}

后面觉得文件可能被不明程序干掉，然后换成了redis进行process id的存储。

{% highlight php %}
class balabala {

    // ...

    public function index()
    {
        if ($this->redis->exists(self::PID_KEY)) {
            retry_loop:$pid    = $this->redis->get(self::PID_KEY);
            $output = shell_exec("ps aux | grep -v grep | grep {$pid} | grep php | grep index_prod | grep AsyncBatchMemberImport");
            if (!empty($output)) {
                exit(0);
            } else {
                $this->redis->del(self::PID_KEY);
            }
        }
        $ret = $this->redis->set(self::PID_KEY, getmypid(), array('nx'));
        if (false === $ret) {
            goto retry_loop;
        }
        for ($i = 0; $i < 100000; $i++) {
            // bla bla
        }
        $this->redis->del(self::PID_KEY);
    }

    // ...

}
{% endhighlight %}

运行起来之后，发现了一个奇怪的问题，getmypid返回的结果是null！

这并不科学，查了下PHP文档，其中是这么说的：

    Description:
    Gets the current PHP process ID.
    Return Values:
    Returns the current PHP process ID, or FALSE on error.

返回值为PHP进程pid，或者在发生错误时返回false，并没有null这样一个返回值。

那么是不是设置的问题呢？

果不其然，php.ini中disable_functions包含了这个函数。

同时被禁用的还有很多posix、shell相关的函数:

    apache_note,apache_setenv,phpinfo,checkdnsrr,chgrp,chown,chroot,closelog,debugger_off
    debugger_on,define_sys,define_syslog_variables,diskfreespace,disk_free_space,disk_total_space,dl
    error_log,ftp_connect,ftp_get,ftp_login,ftp_pasv,getmxrr,getmypid,getmyuid,_getppid,getservbyname
    getservbyport,highlight_file,ini_alter,ini_restore,ini_set,leak,listen,openlog,passthru
    pclose,pcntl_alarm,pcntl_exec,pcntl_fork,pcntl_get_last_error,pcntl_getpriority,pcntl_setpriority
    pcntl_signal,pcntl_signal_dispatch,pcntl_sigprocmask,pcntl_sigtimedwait,pcntl_sigwaitinfo,pcntl_strerror
    pcntl_wait,pcntl_waitpid,pcntl_wexitstatus,pcntl_wifexited,pcntl_wifsignaled,pcntl_wifstopped,pcntl_wstopsig
    pcntl_wtermsig,pfsockopen,php_uname,popen,popepassthru,posix,posix_ctermid,posix_getcwd,posix_getegid,posix_geteuid
    posix_getgid,posix_getgrgid,posix_getgrnam,posix_getgroups,posix_get_last_error,posix_getlogin,posix_getpgid
    posix_getpgrp,posix_getpid,posix_getppid,posix_getpwnam,posix_getpwuid,posix_getrlimit,posix_getsid
    posix_getuid,posix_isatty,posix_kill,posix_mkfifo,posix_setegid,posix_seteuid,posix_setgid,posix_setpgid
    posix_setsid,posix_setuid,posix_strerror,posix_times,posix_ttyname,posix_uname,proc_close,proc_get_status
    proc_nice,proc_terminate,putenv,readlink,scandir,shell_exec,show_sourcymlink,sys_getloadavg,syslog
    url_exec,eval,system,passthru

某些侵入性的探针会使用这些函数。
如果是在web项目上禁用这些函数，还算正常，不过cron上面禁用这些，感觉就有些不对了。

# 解决问题

当然，如果写shell脚本限制进程数的话，这个问题很好解决，下面说说用php解决的一些想法。

pid在这里只是作为一个进程的标识，如果这个标识不能用，那么我们能否换一个标识呢？

既然我们的任务是运行在cli环境下的，那么我们每次生成一个随机的字符串作为唯一任务标识能否解决这个问题呢？

通过狗狗搜索，我们找到了这样一篇文章[shell实例浅谈之三产生随机数七种方法](http://blog.csdn.net/taiyang1987912/article/details/39997303)

    cat /dev/urandom | head -n 10 | md5sum | head -c 10 

我们在部署cron时，添加上述脚本作为参数，作为启动php脚本的选项，作为唯一标识使用。

    php script.php `cat /dev/urandom | head -n 10 | md5sum | head -c 10`

同时代码改成下面的样子：

{% highlight php %}
class balabala {

    // ...

    public function index($uniqueId = 0)
    {
        $this->uniqueId = $uniqueId;
        if ($this->redis->exists(self::PID_KEY)) {
            $pid = $this->redis->get(self::PID_KEY);
            log_warning('ERP_IMPORT_ASYNC_INFO', "UniqueId {$pid} Still RUNNING");
            exit(0);
        }
        $ret = $this->redis->set(self::PID_KEY, $uniqueId, array('nx'));
        if (false === $ret) {
            log_warning('ERP_IMPORT_ASYNC_INFO', "Set PID FAILED, WILL RETRY, Current PID " . $uniqueId);
            exit(0);
        }
        $this->redis->expire(self::PID_KEY, 360);
        for ($i = 0; $i < 1000; $i++) {
            // bla bla
        }
        $this->redis->del(self::PID_KEY);
    }

    // ...

}
{% endhighlight %}

# 其他想法

如果我们把unique id生成器作为一种类似systemd的启动工具，项目中又集成了针对unique id的日志或项目数据，这时候进行trace和监控应该会变得极其容易。

类似一种分布式监控的样子吧。