<?php namespace App\Helper;

use Monolog\Logger;
use Noodlehaus\Config;
use App\Package\Signature\Token;
use App\Package\Signature\Request;
use App\Package\Signature\Auth;
use App\Package\Signature\Guards\CheckKey;
use App\Package\Signature\Guards\CheckVersion;
use App\Package\Signature\Guards\CheckSignature;
use App\Package\Signature\Exceptions\SignatureException;

Class Common{

    protected static $random;

    public static function getRandom(){
        $conf   = Config::load(CONFPATH.'ws_app.ini');
        self::$random = $conf->get('random_key');
    }

    public static function getMicroTime(){
        $time = microtime(true)*10000;
        $time = substr($time,5,7);
        return $time;
    }

    public static function getKey($pid){
        switch($pid){
            case 'loveChild':
                return '3ae93b4d4580604f602fae4453197b07';
            case 'weChat':
                return '8f25edf0';
            default:
                return '';
        }
    }

}
