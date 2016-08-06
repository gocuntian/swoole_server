<?php namespace Nosun\Swoole\Client;

use Noodlehaus\Config;

class Socket
{

    public $client;

    public function __construct($config='socket.php')
    {
        $conf = Config::load(CONFPATH.$config);
        $this->type = constant($conf->get('type'));
        $host = $conf->get('host');
        $port = $conf->get('port');
        $this->client = new \swoole_client($this->type);
        $this->connect($host,$port);
    }

    public function connect($host,$port){

        if ($this->client->connect($host, $port)) {
            return true;
        }
        return false;
    }


    public function send($msg)
    {
        $result = $this->client->send($msg);
        return $result;
    }
}
