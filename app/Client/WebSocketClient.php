<?php namespace App\Client;

use Nosun\Swoole\Client\WebSocket;
use Noodlehaus\Config;

class WebSocketClient {

    protected $client;

    public function __construct($config = 'websocket.php'){
        $conf = Config::load(CONFPATH.$config);
        $host = $conf->get('websocket.host');
        $port = $conf->get('websocket.port');
        $this->client = new Websocket($host,$port);
        if(!$this->client->connect())
        {
            echo "connect to server failed.\n";
            exit;
        }
    }

    public function send($data){
        $this->client->send($data);
    }

    public function close(){
        $this->client->disconnect();
    }
}