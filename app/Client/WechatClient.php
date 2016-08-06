<?php namespace App\Client;

use Noodlehaus\Config;

class WechatClient {

    protected static $_instance;
    protected static $conf;

    public static function getInstance(){
        if(self::$_instance == null){
            self::$conf = Config::load(CONFPATH.'app.php');
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public static function send(array $message){
        $url     = self::$conf['app.wx_msg_api'];
        if(empty($message['message'] or empty($message['uid']))){
            return false;
        }
        $result = self::sendRequest($url,$message);
        return $result;
    }

    private static function sendRequest($url,$post) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $tmpInfo = curl_exec($curl);
        curl_close($curl);
        return $tmpInfo;
    }

}
