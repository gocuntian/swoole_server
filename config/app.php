<?php
/*
; LogLevel::EMERGENCY => 0,
; LogLevel::ALERT     => 1,
; LogLevel::CRITICAL  => 2,
; LogLevel::ERROR     => 3,
; LogLevel::WARNING   => 4,
; LogLevel::NOTICE    => 5,
; LogLevel::INFO      => 6,
; LogLevel::DEBUG     => 7
*/

return [

    'app' => [
            'logLevel'   => "debug",
            'log_path'   => "/var/log",
            'heart'      => 60,
            'random_key' => '',
            'wx_msg_api' => '',
            'app_id'     => '',
            'app_key'    => ''
        ]
];

