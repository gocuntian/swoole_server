<?php namespace App\Server;

use Nosun\Swoole\Server\Socket\Base;
use Noodlehaus\Config;
use App\Model\Device;
use App\Model\DeviceRedis;
use App\Package\Package;
use App\Package\Handler\DeviceFactory;
use App\Helper\Common;
use App\Client\WebSocketClient;
use App\Model\ChatModel;

class Tcp extends Base {

    public $conf; // object
    public $db;
    public $redis;
    protected $access_log;
    protected $error_log;
    protected $task_log;
    protected $random_key;
    protected $ws_client;
    protected $chatModel;

    public function __construct(){
        parent::__construct();
        $this->conf       = Config::load(CONFPATH . 'tcp_app.ini');
        $this->access_log = $this->access_log = $this->conf->get('main.access_log');
        $this->error_log  = $this->conf->get('main.error_log');
        $this->task_log   = $this->conf->get('main.task_log');
        $this->random_key = $this->conf->get('main.random_key');
        $this->db         = new Device($this->conf->get('mysql'));
        $this->redis      = new DeviceRedis();
        $this->ws_client  = new WebSocketClient();
        $this->chatModel  = new ChatModel();
    }

    public function onStart($server,$workerId){
//        $this->server->tick(100000, function ($workerId) {
//            $this->db->checkConnection();
//        });
    }

    public function onConnect($server, $fd, $fromId){
        if(isset($this->access_log)){
            $this->log("Client[$fd@$fromId] Connected",$this->access_log);
        }
    }

    public function onTask($server, $taskId, $fromId, $data){
        $job = $data['job'];
        if(isset($this->task_log)){
            $this->log("onTask: [PID={$server->worker_pid}]: task_id=$taskId, received ".$job.PHP_EOL,$this->task_log);
        }

    }

    public function onFinish($server, $taskId, $data){
        if(isset($this->task_log)){
            $this->log("Task#$taskId finished,"."$data".PHP_EOL,$this->task_log);
        }
    }

    public function onReceiveU($server,$fd, $fromId, $data){


    }

    public function onReceive($server,$fd, $fromId, $data){
        $d_arr = Package::divide($data);
        foreach($d_arr as $package){
            if(!empty($package)){
                $json_d = Package::decode($package);
                if($json_d == false){
                    if(isset($this->error_log)){
                        $this->log("Error:BadPackage Worker#{$server->worker_pid} Client[$fd@$fromId]: received: $package",$this->error_log);
                    }
                    $this->forbidden($fd);
                    return;
                }else{
                    if(isset($this->access_log)){
                        $this->log("Worker#{$server->worker_pid} Client[$fd@$fromId]: received: $package",$this->access_log);
                    }
                }

                switch ($json_d['action']){
                    case 'login':
                        $this->doLogin($server,$fd,$fromId,$json_d);
                        break;
                    case 'heartbeat':
                        $this->doHeartBeat($server,$fd,$fromId,$json_d);
                        break;
                    case 'upload':
                        $this->doUpload($server,$fd,$fromId,$json_d);
                        break;
                    case 'sendCmd':
                        $this->doCommand($server,$fd,$fromId,$json_d);
                        break;
                    case 'task':
                        $this->doTask($server,$fd,$fromId,$json_d);
                        break;
                    case 'chat':
                        $this->doChat($server,$fd,$fromId,$json_d);
                        break;
                    case 'error':
                        $this->doError($server,$fd,$fromId,$json_d);
                        break;
                    default: // badCmd
                        $this->doBadAction($server,$fd, $fromId,$json_d);
                        break;
                }
            }
        }
    }

    public function onClose($server, $fd, $fromId){
        $mac = $this->redis->getFd($fd);

        if($mac){
            $this->setOffline($mac,$fd);
        }

        if(isset($this->access_log)){
            $this->log("Client[$fd@$fromId] closed",$this->access_log);
        }

    }

    /*
     * cmd functions
     *
     */

    public function doLogin($server,$fd,$fromId,$data){
        $mac  = $data['mac'];
        if(empty($mac) || empty($data['data'])){
            $this->forbidden($fd);
            if(isset($this->error_log)){
                $this->log("Error:LoginArgumentError Worker#{$server->worker_pid} Client[$fd@$fromId]: received: ".json_encode($data),$this->access_log);
            }
            return;
        }

        $pid  = $data['data']['pid'];
        $pkey = $data['data']['pkey'];
        $did  = $data['data']['did'];
        $sv   = $data['data']['sv'];
        $pv   = $data['data']['pv'];
        $fd_info = $server->connection_info($fd);
        $ip   = $fd_info['remote_ip'];

        if(Common::checkKey($pid,$this->random_key,$pkey)== false){
            $this->forbidden($fd);
            if(isset($this->error_log)){
                $this->log("Error:CheckKeyError Worker#{$server->worker_pid} Client[$fd@$fromId]: received: ".json_encode($data),$this->access_log);
            }
            return;
        }

        $DeviceRes  = $this->db->getDevice(array('mac'=>$mac));
        //register
        if(false == $DeviceRes ){
            $status = 1;
            $this->db->addDevice(array(
                'mac' => $mac,
                'pv' => $pv,
                'sv' => $sv,
                'pid' => $pid,
                'did' => $did,
                'state' => 0,
                'add_time' => time(),
                'update_time' => time()
                )
            );
        }
        else{ //login
            $status = 2;
            if($DeviceRes[0]['pv'] != $pv || $DeviceRes[0]['sv'] != $sv){
                $this->db->setDevice(
                    array(
                    'pv' => $pv,
                    'sv' => $sv,
                    'update_time' =>time()
                    ),
                    array('mac'=>$mac)
                );
            }
        }

        $this->setOnline($mac,$fd,array(
            'online' => 1,
            'pv' => $pv,
            'sv' => $sv,
            'pid'=> $pid,
            'ip' => $ip
        ));

        $download = array(
            'sn'=> $data['sn'],
            'action'=> $data['action'],
            'ret'=> 200,
            'heart'=> (int)$this->conf->get('main.heart')
        );

        $download = json_encode($download)."\n";
        $server->send($fd, $download);

        $log = array(
            'mac'    => $data['mac'],
            'action' => $data['action'],
            'status' => $status,
            'info'   => json_encode($data),
            'time'   => date('Y-m-d H:i:s', time())
        );

        $this->db->addLog($log);

    }

    public function doHeartBeat($server,$fd,$fromId,$data){
        $download = array(
            'sn'=> $data['sn'],
            'action'=> $data['action'],
            'heart'=> (int)$this->conf->get('main.heart'),
        );
        $download = json_encode($download)."\n";
        $server->send($fd, $download);
    }

    public function doUpload($server,$fd,$fromId,$data){
        $this->doReturn($server,$fd,$data);
        $pid = $this->redis->getDeviceAttr($data['mac'],'pid');
        if($pid){
            $factory = new DeviceFactory();
            $device = $factory->createProduct($pid);
            $device->upload($data);
        }
    }

    public function doChat($server,$fd,$fromId,$data){
        $client = $data['client'];

        switch($client){
            case 'wx':
                $mac = $data['data']['to'];
                $fd = $this->redis->getMac($mac);
                if($fd){
                    $download = json_encode($data, JSON_UNESCAPED_SLASHES);
                    $this->server->send($fd,$download."\n");
                }
                break;
            case 'pc':
                $this->sendToWx(json_encode($data, JSON_UNESCAPED_SLASHES));
                break;
            default:
                break;
        }

        //$this->chatModel->addChat($client,$data,$send);
    }

    public function sendToWx($data){
        $this->ws_client->send($data);
    }

    /*
     * 1. command is like: {"action":"sendCmd","sn":4,"data":{"from":5,"to":"AABBCCDDEE","cmd":["getLog"]}}
     * 2. check if the PC is online, if online,send it ,else log error
     * 3.
     *
     *
     *
     */
    public function doCommand($server,$fd,$fromId,$data){
        $mac = $data['data']['to'];
        if($mac){
            $fd = $this->redis->getMac($mac);
            if($fd){
                $download = json_encode($data, JSON_UNESCAPED_SLASHES);
                $this->server->send($fd,$download."\n");
            }
        }
    }

    public function doReturn($server,$fd,$data){
        $download = array(
            "sn"  => $data['sn'],
            "action" => $data['action'],
            'ret' => 200
        );
        $download = json_encode($download)."\n";
        $server->send($fd, $download);
    }

    public function doError($server,$fd,$fromId,$data){
        $this->doReturn($server,$fd,$data);
        $upload = array(
            "sn" => $data['sn'],
            "action" => $data['action'],
            'code' => $data['code']
        );
        // send to ssdb log;
    }

    public function doBadAction($server,$fd,$fromId,$data){
        $download = array(
            'error' => 'badAction'
        );
        $download = json_encode($download)."\n";
        $server->send($fd, $download);
        if(isset($this->error_log)){
            $this->log("Error:BadAction Worker#{$server->worker_pid} Client[$fd@$fromId]: received: ".json_encode($data),$this->access_log);
        }
    }

    // close the connect and do something
    public function forbidden($fd){
        if($this->conf->get('main.mode') != 'work'){
           return;
        }else{
            $this->server->close($fd);
        }
    }

    public function pushMsg($mac,$msg){


    }

    private function setOffline($mac,$fd){
        // 下线控制权只交给最新的和mac对应的fd
        $chk_fd = $this->redis->getMac($mac);
        if ($fd == $chk_fd){
            $this->redis->setDevice($mac,array('online'=>0));
            $msg = json_encode(array("sn"=> Common::getMicroTime(),"mac"=>$mac,"online" => 0))."\n";
            $this->pushMsg($mac,$msg);
        }
        $this->redis->DelFd($fd);
    }

    private function setOnline($mac,$fd,$data){
        $this->redis->setMac($mac,$fd);
        $this->redis->setFd($fd,$mac);
        $this->redis->setDevice($mac,$data);
        $msg = json_encode(array("sn"=> Common::getMicroTime(),"mac"=>$mac,"online" => 1))."\n";
        $this->pushMsg($mac,$msg);
    }

    public  function getServer($pid){
        return $this->db->getTcpServer($pid);
    }

    function ascToHex($string){
        $code =bin2hex($string)."\n";
        $str = '';
        for($i=0;$i<strlen($code);$i=$i+2){
            $str.= substr($code,$i,2).' ';
        }
        echo $str;
    }

    public function doTask($server,$fd,$fromId,$data){
        // 需要增加key 来保证安全性
        $server->task($data, 0);
        $server->send($fd, 'ok');
    }
}
