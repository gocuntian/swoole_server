<?php namespace Nosun\Swoole\Server\Socket;

class Base implements Protocol
{

    public $server;

    function __construct()
    {
        $this->init();
    }

    public function init()
    {
        // some code
    }

    /**
     * 打印Log信息
     * @param $msg
     *
     */
    public function log($msg, $path)
    {
        $log = "[" . date("Y-m-d G:i:s") . " " . floor(microtime() * 1000) . "]" . $msg;

        if (!$path || !is_writable($path)) {
            $file = fopen($path, 'w');
            fwrite($file, $msg);
            fclose($file);
        }
        error_log($log . PHP_EOL, 3, $path);
        echo $log . "\n";
    }


    public function onStart($serv, $workerId)
    {

    }

    public function onReceive($server, $clientId, $fromId, $data)
    {

    }

    public function onConnect($server, $fd, $fromId)
    {

    }

    public function onShutdown($serv, $workerId)
    {

    }

    public function onClose($server, $fd, $fromId)
    {

    }

    public function onTask($serv, $taskId, $fromId, $data)
    {

    }

    public function onFinish($serv, $taskId, $data)
    {

    }
}