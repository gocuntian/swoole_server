<?php namespace App\Server;

use Nosun\Swoole\Server\WebSocket\Base;
use Noodlehaus\Config;
use App\Model\RedisModel;
use App\Model\SSDBModel;
use App\Package\Package;
use App\Helper\Common;
use App\Helper\LoggerHelper;
use Psr\Log\LogLevel;
use App\Package\Handler\ClientFactory;

define('SUCCESS',200);
define('ARGUMENT_ERROR',400);
define('SIGNATURE_ERROR',401);
define('FORBIDDEN',403);
define('NOT_EXIST',404);
define('SERVER_ERROR',500);
define('OTHER_ERROR',505);

class WebSocket extends Base {

    public $conf;
    protected $task_log;
    protected $random_key;
    protected $redis_model;
    protected $ssdb_model;
    protected $logger;
    protected $errorLogger;

    public function __construct(){
        parent::__construct();
        $this->conf       = Config::load(CONFPATH . 'app.php');
        $this->task_log   = $this->conf->get('app.task_log');
        $this->random_key = $this->conf->get('app.random_key');
        $this->redis_model= new RedisModel();
        $this->ssdb_model = new SSDBModel();
        $this->logger     = LoggerHelper::getInstance();
    }

    public function onStart($server,$workerId){$this->server = $server;}
    public function onTask($server, $taskId, $fromId, $data){}
    public function onFinish($server, $taskId, $data){}
    public function onOpen($server, $req){}
    public function onShutdown($server, $workerId){}

    public function onClose($server, $fd){
        $clients = $this->redis_model->getNoticer($fd);
        if(count($clients['to']) > 0){
            $this->sendNotice($clients,'offline');
        }

        $this->redis_model->setOffline($fd);
        $this->logger->info("Client[$fd] closed");
    }

    public function sendNotice($clients,$event){
        $message = array(
            'sn'  => rand(999,9999),
            'action' => 'notice',
            'from' => 'server',
            'data' => array(
                'event' => $event,
                'value' => $clients['who'],
                'time'  => time()
            )
        );

        $this->logger->info(json_encode($message));
        $this->logger->info(json_encode($clients));

        foreach($clients['to'] as $client){
            if(isset($client['online']) && $client['online'] ==1){
                   $this->push($client['fd'],$message);
            }
        }
    }

    public function onMessage($server,$frame)
    {
        $message = "Worker#{$server->worker_pid} Client[$frame->fd]: received: $frame->data";
        $this->logger->info($message);

        $d_arr = Package::divide($frame->data);
        foreach ($d_arr as $package) {
            if (!empty($package)) {
                $json_d = Package::decode($package);

                if ($json_d == false) {
                    $this->forbidden($frame, 'PackageError');
                    return;
                }

                if($json_d['action'] == 'heartbeat'){
                    $this->doHeartbeat($frame,$json_d);
                }else{
                    try {
                        $from = $json_d['from'];
                        $client = ClientFactory::createApp($from);
                        $client->init($this->server, $this->redis_model, $this->ssdb_model, $this->logger);
                        if (true == $client->doCheck($frame,$json_d)) {
                            $action = 'do' . ucfirst($json_d['action']);
                            $client->$action($frame, $json_d);
                        } else {
                            $client->forbidden($frame,'bad Action');
                        }
                    } catch (\Exception $e){
                        $this->logger->error($e->getMessage().'error_package:'.$frame->data);
                    }
                }
            }
        }
    }

    public function forbidden($frame,$message){
        $this->logger->error($message);
        $this->closeConnection($frame->fd);
    }

    protected function closeConnection($fd){
        $this->server->close($fd);
    }


    public function doHeartbeat($frame,$data){
        $fd = $frame->fd;
        $download = array(
            'sn'     => $data['sn'],
            'action' => $data['action'],
        );
        $this->push($fd,$download);
    }

    public function push($fd,$data){
        return $this->server->push($fd, json_encode($data));
    }

    protected function getHeartbeat(){
        return$this->conf->get('app.heart',60);
    }
}
