<?php namespace App\Package\Handler;

use Psr\Log\LogLevel;
use Noodlehaus\Config;

class Apple extends Client
{
    protected $actions = ['login','heartbeat','download','upload','command','info','updateWifi','error'];
    protected $mq_prefix = 'nosun/';
    public function __construct(){
        parent::__construct();
    }

}
