<?php

return [
    'server' => [
        'main' => [
            // 协议类名称
            'server_class' => 'App\Server\Tcp',
            // 进程名
            'process_name' => 'tcp_server',
            // pid路径
            'run_path'     => '/tmp',
            // 指定用户
            'user'         => 'nginx',
            // mode debug test product
            'mode'         => 'test',
            // 监听的端口号
            'listen' => [
                'host' => '127.0.0.1',
                'port' => 9002
            ],
        ],
        'server' => [
            'worker_num' => 1,                 // worker进程数
            'max_request' => 1000,             // worker 最大请求数量
            'max_connection' => 1000,          // server 最大连接数量
            'heartbeat_check_interval' => 30,  // 心跳检测时间
            'heartbeat_idle_time' => 90,       // 最大idle时间
            //open_eof_check = true            // 开启包尾检测
            //package_eof = "\r\n"             // 包尾结束符
            'open_cpu_affinity' => 1,
            'open_tcp_nodelay'  => 1,
            'dispatch_mode' => 2,                // 转发模式
            'daemonize' => 0,                    // 守护进程
            'log_file' => "logs/tcp_server.log"  // 系统日志
            //task_worker_num = 2                // task worker 开启的线程数量
            //task_max_request                   // task worker 最大请求数量
        ]
    ]

];
