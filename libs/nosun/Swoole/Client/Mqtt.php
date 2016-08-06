<?php  namespace Nosun\Swoole\Client;

use Noodlehaus\Config;

class Mqtt {

    protected $mq;

    public function __construct($config='mqtt.php',$host='127.0.0.1',$port='1883'){

        $conf = Config::load(CONFPATH.$config);
        $host = $conf['host']?$conf['host']:$host;
        $port = $conf['port']?$conf['port']:$port;
        $this->mq     = new \Mosquitto\client();
        $this->mq->connect($host, $port, 5);

    }

    public function publish($topic,$msg,$qos,$retain){
        $this->mq->publish($topic, $msg, $qos, $retain);
    }

    public function close(){
        $this->mq->disconnect();
    }
}
