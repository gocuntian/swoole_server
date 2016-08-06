<?php namespace Nosun\Swoole\Manager;

// 此处必须引入Protocol，用于核对应用层是否符合
use Nosun\Swoole\Server\WebSocket\Protocol;

class WebSocketBox extends Box
{
    protected $serverType = 'swoole_websocket_server_ssl';

    protected function addListener(){
    
        $this->sw->addlistener('10.24.191.119', 9002, SWOOLE_SOCK_TCP);
        $this->sw->addlistener('10.24.191.119', 9003, SWOOLE_SOCK_TCP);

    }

    // create swoole_websocket_server,set server,set callback function
    protected function addCallback() {
        $this->sw->on('Open', function($server,$fd){
            $this->protocol->onOpen($server, $fd);
        });

        $this->sw->on('Message', function($server,$frame){
            $this->protocol->onMessage($server,$frame);
        });

        $this->sw->on('Close', function($server, $fd)
        {
            $this->protocol->onClose($server, $fd);
        });
    }
}
