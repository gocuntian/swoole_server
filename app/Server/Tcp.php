<?php namespace App\Server;

use Nosun\Swoole\Server\Socket\Base;
use Noodlehaus\Config;
use App\Helper\LoggerHelper;
use App\Model\Device;
use App\Package\Package;
use App\Package\Handler\ClientFactory;
use SSDB\Exception;

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
        $this->conf   = Config::load(CONF_PATH . 'app.php');
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

                if($json_d['action'] == 'heartbeat'){
                    $this->doHeartbeat($fd,$json_d);
                    return;
                }

                try {
                    $pid = isset($json_d['pid']) ? $json_d['pid'] : '';
                    if(empty($pid)){
                        $this->forbidden($fd,'No pid');
                    }
                    $client = ClientFactory::createApp($pid);
                    $client->init($this->server, $this->redis, $this->logger);

                    if (false == $client->doCheck($fd,$json_d)) {
                        $client->forbidden($fd,'bad Action');
                    }

                    $action = 'do' . ucfirst($json_d['action']);
                    $client->$action($fd, $json_d);

                } catch (\Exception $e){
                    $this->logger->error($e->getMessage().'error_package:'.$data);
                }
            }
        }
    }

    public function onClose($server, $fd, $fromId){
        $mac = $this->redis->getFd($fd);
        $this->logger->info("Client[$fd@$fromId], mac: $mac");
        if($mac){
            $pid = $this->redis->getDeviceAttr($mac,'pid');
            if($pid){
                $factory = new ClientFactory();
                $client = $factory->createApp($pid);
                $client->init($this->server, $this->redis, $this->logger);
                $client->setOffline($mac,$fd);
            }
        }
    }

    public function doHeartbeat($fd,$data){
        $download = array(
            'sn'     => $data['sn'],
            'action' => $data['action'],
        );
        $download = json_encode($download)."\n";
        $this->server->send($fd, $download);
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