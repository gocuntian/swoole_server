<?php namespace App\Package\Handler;

use Nosun\Swoole\Client\Mqtt;

abstract class Client
{

    protected $server;
    protected $logger;
    protected $redis;
    protected $actions = ['login','heartbeat','download','upload','command','get','updateWifi','error'];
    protected $mq_prefix = 'nosun/';

    public function __construct(){}

    public function init($server,$redis,$logger){
        $this->server = $server;
        $this->redis  = $redis;
        $this->logger = $logger;
    }

    public function doCheck($fd,$data){
        //return true;
        if ($this->checkAction($data['action']) == false){
            $this->logger->error('bad action');
            return false;
        };

        return true;
    }

    protected function checkAction($action){
        return in_array($action,$this->actions);
    }

    protected function checkBind($key,$val){
        return $this->redis->isBind($key,$val);
    }

    public function doLogin($fd,$data){
        $mac    = $data['mac'];
        if (empty($mac)){
            $this->forbidden($fd,$mac);
        }else{
            $this->setOnline($mac,$fd,$data['data']);
            $download = array(
                'sn'     => $data['sn'],
                'action' => $data['action'],
                'ret'    => 200,
                'heart'  => $this->getHeartbeat()
            );
            $download = json_encode($download)."\n";
            $this->server->send($fd, $download);
        }
    }

    public function doUpload($fd,$data){
        $this->updateState($data);
        $message = json_encode($data);
        $this->pushMsgToApp($data['mac'],$message);
        $this->doReturn($fd,$data);
    }

    protected function updateState($data){
        $list = $data['data'];
        $list['updated'] = time();
        $this->redis->setDeviceData($data['mac'],$list);
    }

    protected function getHeartbeat(){
        return $period = 60;
    }

    // 下发到 device
    public function doCommand($data){
        $mac = $data['mac'];
        if($mac){
            $fd = $this->redis->getMac($mac);
            if($fd){
                $download = json_encode($data['data'], JSON_UNESCAPED_SLASHES);
                $this->server->send($fd,$download."\n");
            }
        }
    }

    // just return
    public function doReturn($fd,$data){
        $download = array(
            "sn"     => $data['sn'],
            "action" => $data['action'],
            'ret'    => 200
        );
        $download = json_encode($download)."\n";
        $this->server->send($fd, $download);
    }

    public function doUpdateWifi($data){
        $upload = array(
            "sn"     => $data['sn'],
            "action" => $data['action'],
            'ret'    => $data['ret']
        );
        $upload = json_encode($upload)."\n";
        $this->pushMsgToApp($data['mac'],$upload); // push message to app
    }

    public function doError($data){
        $upload = array(
            "sn"     => $data['sn'],
            "action" => $data['action'],
            'code'   => $data['code']
        );
        $upload = json_encode($upload)."\n";
        $this->pushMsgToApp($data['mac'],$upload); // push message to app
    }

    public function forbidden($fd,$message){
        $this->logger->error($message);
        $this->closeConnection($fd);
    }

    protected function closeConnection($fd){
        $this->server->close($fd);
    }

    public function pushMsgToApp($mac,$msg){
        $mq = new Mqtt();
        $mq->publish($this->mq_prefix.$mac, $msg, 1, 0);
    }

    public function setOffline($mac,$fd){

        // 下线控制权只交给最新的和mac对应的fd
        $chk_fd = $this->redis->getMac($mac);
        if ($fd == $chk_fd){
            $this->redis->setDevice($mac,array('online'=>0));
            $msg = json_encode(array("sn"=>  $this->getSn(),"mac"=>$mac,"online" => 0))."\n";
            $this->pushMsgToApp($mac,$msg);
        }
        $this->redis->DelFd($fd);
    }

    protected function setOnline($mac,$fd,$data){
        $this->redis->setMac($mac,$fd);
        $this->redis->setFd($fd,$mac);
        $this->redis->setDevice($mac,array_merge($data,array('online' =>1)));
        $msg = json_encode(array("sn"=> $this->getSn(),"mac"=>$mac,"online" => 1))."\n";
        $this->pushMsgToApp($mac,$msg);
    }

    function getSn(){
        $time = microtime(true)*10000;
        $time = substr($time,5,7);
        return $time;
    }

}
