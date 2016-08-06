<?php namespace Nosun\Swoole\Server\WebSocket;

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

    public function onOpen($server, $request)
    {

    }

    public function onMessage($server, $frame)
    {

    }

    public function onClose($server, $fd)
    {

    }
}