<?php

require_once 'bootstrap.php';

// 加载配置文件，生成全局配置文件对象
$conf = new \Noodlehaus\Config(CONFPATH);

define('SERVER_MODE',$conf->get('server.main.mode'));

// 定义网络层 UDP、TCP
$server = new Nosun\Swoole\Manager\WebSocketBox($conf->get('server'));

// 启动Server
$server->run();