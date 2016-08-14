<?php namespace App\Client;

use SSDB\SimpleClient;
use Noodlehaus\Config;

class SSDBClient
{
    private static $_instance;

    public static function getInstance(){
        if(!(self::$_instance instanceof SimpleClient )){
            $conf = Config::load(CONF_PATH.'ssdb.php');
            try{
                self::$_instance = new SimpleClient($conf->get('ssdb.host'), $conf->get('ssdb.port'));
            }catch(\Exception $e){
                echo ("ssdb connnection error:" . ' ' . $e->getMessage());
            }
        }
        return self::$_instance;
    }

}