<?php namespace App\Helper;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;
use Noodlehaus\Config;

class LoggerHelper
{
    private static $_instance;

    public static function getInstance(){
        if(!(self::$_instance instanceof Logger)){
            $conf = Config::load(CONFPATH . 'app.php');
             self::$_instance = new Logger(
                $conf->get('app.log_path'),
                $conf->get('app.logLevel'),
                array ('flushFrequency' => 1)
            );
        }
        return self::$_instance;
    }
}