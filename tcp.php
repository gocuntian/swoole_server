<?php

require_once 'bootstrap.php';

$container = Container::getInstance();

$container->share('conf', function(){
   // 加载配置文件，生成全局配置文件对象
   return new \Noodlehaus\Config(CONF_PATH);
});

$container->add('Redis',function() use($container) {
   return new \Nosun\Swoole\Client\Redis($container->get('conf')->get('redis'));
});

// 定义网络层 UDP、TCP
$server = new Nosun\Swoole\Manager\TcpBox($container->get('conf')->get('server'));

// 启动Server
$server->run();