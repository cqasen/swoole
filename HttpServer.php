<?php

/**
 * Swoole Http Server
 * Class HttpServer
 */
class HttpServer
{
    /**
     * @var swoole_http_server
     */
    protected $server;

    /**
     * @var string
     */
    protected $host = '0.0.0.0';
    /**
     * @var int
     */
    protected $port = 9501;
    /**
     * @var int
     */
    protected $mode = SWOOLE_PROCESS;

    /**
     * 主进程路径
     * @var string
     */
    protected $masterPidPath = './masterPid.txt';

    /**
     * 构造方法
     * Server constructor.
     */
    public function __construct()
    {

    }

    /**
     *
     */
    public function start()
    {

        $this->server = new swoole_http_server($this->host, $this->port, $this->mode);

        $this->server->set([
            'worker_num' => 8,
//            'heartbeat_check_interval' => 5,
//            'heartbeat_idle_time'      => 10,
        ]);

        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->server->on('WorkerError', [$this, 'onWorkerError']);
        $this->server->on('Connect', [$this, 'onConnect']);
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->on('Shutdown', [$this, 'onShutdown']);
        $this->server->on('close', [$this, 'onClose']);

        $this->server->start();
    }

    /**
     * 停止
     */
    public function stop()
    {
        $masterPid = file_get_contents($this->masterPidPath);
        $masterPid && posix_kill($masterPid, SIGSTOP);
        file_put_contents($this->masterPidPath, '');
    }

    /**
     * 重启
     */
    public function reload()
    {
        $masterPid = file_get_contents($this->masterPidPath);
        $masterPid && posix_kill($masterPid, SIGUSR1);
        file_put_contents($this->masterPidPath, '');
        $this->server->reload();
    }

    public function help()
    {

    }


    /**
     * @param swoole_http_server $server
     */
    public function onStart(swoole_http_server $server)
    {
        $autoloadFunctions = spl_autoload_functions();
        if ($autoloadFunctions) {
            foreach ($autoloadFunctions as $function) {
                spl_autoload_unregister($function);
            }
        }

        $this->setProcessTitle('master_process');
    }

    /**
     * 有新的连接进入时，在worker进程中回调。
     * @param swoole_server $server
     * @param integer $fd 是连接的文件描述符，发送数据/关闭连接时需要此参数
     * @param integer $reactorId 来自哪个Reactor线程
     */
    public function onConnect(swoole_server $server, $fd, $reactorId)
    {
        var_dump(sprintf('connect[%d]', $fd));
    }

    /**
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     */
    public function onRequest(swoole_http_request $request, swoole_http_response $response)
    {
        $_GET = [];
        if ($request->get) {
            $_GET = $request->get;
        }

        $_POST = [];
        if ($request->post) {
            $_POST = $request->post;
        }

        $_COOKIE = [];
        if ($request->cookie) {
            $_COOKIE = $request->cookie;
        }

        $_FILES = [];
        if ($request->files) {
            $_FILES = $request->files;
        }

        $_SERVER = [];
        $_SERVER = array_merge($request->header, $request->server);

        var_dump($request->rawContent());

        $begTime = $_SERVER['request_time_float'];


        $str = sprintf('Request[%d]', $request->fd);

        var_dump(App::$say);


        $endTime = microtime(true);

        var_dump($endTime - $begTime);


        $response->end($str);
    }

    /**
     * 当管理进程启动时调用它
     * @param swoole_server $server
     */
    public function onManagerStart(swoole_server $server)
    {
        $this->setProcessTitle('manager_process');
        file_put_contents($this->masterPidPath, $server->master_pid);
        var_dump('ManagerStart');
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。
     * @param swoole_server $server
     * @param integer $workerId 进程的编号
     */
    public function onWorkerStart(swoole_server $server, $workerId)
    {
        $this->setProcessTitle('worker_process');
        var_dump(sprintf('WorkerStart[%d]', $workerId));

        spl_autoload_register(function ($class) {
            $className = $class . '.php';
            if (file_exists($className)) {
                require_once $className;
            }
        });

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        clearstatcache();
    }

    /**
     * 当worker/task_worker进程发生异常后会在Manager进程内回调此函数
     * @param swoole_server $server
     * @param integer workerId 是异常进程的编号
     * @param integer $workerPid 是异常进程的ID
     * @param integer $exitCode 退出的状态码，范围是 1 ～255
     * @param integer $signal 进程退出的信号
     */
    public function onWorkerError(swoole_server $server, $workerId, $workerPid, $exitCode, $signal)
    {

    }

    /**
     * 此事件在worker进程终止时发生。在此函数中可以回收worker进程申请的各类资源。
     * @param swoole_server $server
     * @param integer $workerId 进程的编号
     */
    public function onWorkerStop(swoole_server $server, $workerId)
    {

    }


    /**
     * 设置进程名称
     * @param $title
     */
    protected function setProcessTitle($title)
    {
        if (PHP_OS === 'Darwin') {
            return;
        }
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        } elseif (function_exists('swoole_set_process_name')) {
            swoole_set_process_name($title);
        }
    }

    /**
     * 此事件在Server正常结束时发生
     * @param swoole_server $server
     */
    public function onShutdown(swoole_server $server)
    {
        var_dump('onShutdown');
        $server->shutdown();
    }

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数。
     * @param swoole_server $server
     * @param integer $fd 是连接的文件描述符
     * @param integer $reactorId 来自那个reactor线程
     */
    public function onClose(swoole_server $server, $fd, $reactorId)
    {
        var_dump(sprintf('Close[%d]', $fd));
    }
}
