<?php namespace App\Server;

use Nosun\Swoole\Server\Socket\Base;
use Noodlehaus\Config;
use App\Helper\LoggerHelper;
use App\Model\Device;
use App\Package\Package;
use App\Package\Handler\ClientFactory;

define('SUCCESS',200);
define('ARGUMENT_ERROR',400);
define('SIGNATURE_ERROR',401);
define('FORBIDDEN',403);
define('NOT_EXIST',404);
define('SERVER_ERROR',500);
define('OTHER_ERROR',505);

class Tcp extends Base {

    public $conf;
    protected $redis;
    protected $logger;

    public function __construct(){
        parent::__construct();
        $this->conf   = Config::load(CONFPATH . 'app.php');
        $this->redis  = new Device();
        $this->logger = LoggerHelper::getInstance();
    }

    public function onStart($server,$workerId){
        $this->server = $server;
    }

    public function onTask($server, $taskId, $fromId, $data){}
    public function onFinish($server, $taskId, $data){}
    public function onOpen($server, $req){}
    public function onShutdown($server, $workerId){}

    public function onConnect($server, $fd, $fromId){
        $this->logger->info("Client[$fd@$fromId] Connected");
    }

    public function onReceive($server,$fd, $fromId, $data){

        $d_arr = Package::divide($data);

        foreach($d_arr as $package){
            if(!empty($package)){
                $this->logger->info("Worker#{$server->worker_pid} Client[$fd@$fromId]: received: $data");

                $json_d = Package::decode($package);
                if($json_d == false){
                    $this->forbidden($fd,'PackageError');
                    return;
                }

                try {
                    $from = $json_d['pid'];
                    $client = ClientFactory::createApp($from);
                    $client->init($this->server, $this->redis, $this->logger);

                    if (false == $client->doCheck($fd,$json_d)) {
                        $client->forbidden($fd,'bad Action');
                    }

                    $action = 'do' . ucfirst($json_d['cmd']);
                    $client->$action($fd, $json_d);

                } catch (\Exception $e){
                    $this->logger->error($e->getMessage().'error_package:'.$data);
                }
            }
        }
    }

    public function onClose($server, $fd, $fromId){
        $this->logger->info("Client[$fd@$fromId] closed");
        $mac = $this->redis->getFd($fd);
        if($mac){
            $pid = $this->redis->getDeviceAttr($mac,'pid');
            if($pid){
                $factory = new ClientFactory();
                $client = $factory->createApp($pid);
                $client->setOffline($mac,$fd);
            }
        }
    }

    // close the connect and do something
    protected function forbidden($fd,$message){
        $this->logger->error($message);
        $this->closeConnection($fd);
    }

    protected function closeConnection($fd){
        $this->server->close($fd);
    }

}