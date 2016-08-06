<?php namespace App\Package\Handler;

abstract class Client
{

    protected $server;
    protected $logger;
    protected $redis;
    protected $actions = ['login','heartbeat','download','upload','command','get','updateWifi','error'];

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

        if ($this->checkConnection($fd,$data) == false){
            $this->closeConnection($fd);
            $this->logger->error('bad fd');
            return false;
        }

        return true;
    }

    protected function checkAction($action){
        return in_array($action,$this->actions);
    }

    protected function checkConnection($fd,$data){
        return true;
    }

    protected function checkBind($key,$val){
        return $this->redis->isBind($key,$val);
    }

    protected function doLogin($server,$fd,$data){
        $mac  = $data['mac'];
        $pv   = $data['pv'];
        $sv   = $data['sv']?$data['sv']:$data['hfver'];
        if($data['data']){
            $u_data = $this->JsonArrayToArray($data['data']);
            $vid    = $u_data['vid'];
            $pid    = $u_data['pid'];
            $mv     = $u_data['mv']?$u_data['mv']:$u_data['usrver'];
        }else{
            $pid = $vid = $mv = 0;
        }

        $status = 0;

        if (empty ($pid) || empty($mac)){
            $this->forbidden($fd,$mac);
        }else{
            // 缓存
            // $device_attr = $this->redis->getDevice($mac);
            // if(empty($device_attr)){

            $DeviceRes  = $this->db->getDevice(array('device_mac'=>$mac));
            $product_id = $this->db->getProductId($pid);
            $wifi       = $this->getWifiInfo($sv);
            //register
            if(false == $DeviceRes ){
                $status = 1;
                $this->db->addDevice(array(
                        'device_mac' => $mac,
                        'device_protocol_ver' => $pv,
                        'device_wifi_firmware_version' => $wifi['fv'],
                        'device_wifi_version' => $wifi['wv'],
                        'device_mcu_version' => $mv,
                        'product_id' => $product_id,
                        'add_time' => time(),
                        'update_time' => time()
                    )
                );
            }
            else{ //login
                $status = 2;
                $device_attr = $this->redis->getDevice($mac);
                if($device_attr['pv'] != $pv || $device_attr['sv'] != $sv || $device_attr['mv'] != $mv){
                    $this->db->setDevice(
                        array(
                            'device_protocol_ver' => $pv,
                            'device_wifi_firmware_version' => $wifi['fv'],
                            'device_wifi_version' => $wifi['wv'],
                            'device_mcu_version' => $mv,
                            'update_time' =>time()
                        ),
                        array('device_mac'=>$mac)
                    );
                }
            }

            $this->setOnline($mac,$fd,array(
                'online' => 1,
                'pv' => $pv,
                'mv' => $mv,
                'sv' => $sv,
                'pid'=> $pid
            ));

            $download = array(
                'sn'=> $data['sn'],
                'cmd'=> $data['cmd'],
                'ret'=> 200,
                'heart'=> (int)$this->conf->get('main.heart')
            );

            $download = json_encode($download)."\n";
            $server->send($fd, $download);
            usleep(10);
            $this->doGetInfo($server,$fd,$data['sn']);
        }
        $log = array(
            'mac'    => $data['mac'],
            'action' => $data['cmd'],
            'status' => $status,
            'info'   => json_encode($data),
            'time'   => date('Y-m-d H:i:s', time())
        );
        $this->db->addLog($log);

    }

    public function doGetInfo($server,$fd,$data){
        $download = array(
            'sn'=> $data['sn'] + 1,
            'cmd'=> 'info',
        );
        if(empty($fd)){
            $fd = $this->redis->getMac($data['mac']);
        }
        $download = json_encode($download)."\n";
        $server->send($fd, $download);
    }

    public function doUpload($data){
        $pid = $this->redis->getDeviceAttr($data['mac'],'pid');
        if($pid){
            $factory = new ProductFactory();
            $product = $factory->createProduct($pid);
            $product -> upload($data);
        }
    }

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

    public function doReturn($server,$fd,$data){
        $download = array(
            "sn"  => $data['sn'],
            "cmd" => $data['cmd'],
            'ret' => 200
        );
        $download = json_encode($download)."\n";
        $server->send($fd, $download);
    }

    public function doUpdateWifi($data){
        $upload = array(
            "sn" => $data['sn'],
            "cmd" => $data['cmd'],
            'ret' => $data['ret']
        );
        $upload = json_encode($upload)."\n";
        $this->pushMsgToApp($data['mac'],$upload); // push message to app
    }

    public function doError($data){
        $upload = array(
            "sn" => $data['sn'],
            "cmd" => $data['cmd'],
            'code' => $data['code']
        );
        $upload = json_encode($upload)."\n";
        $this->pushMsgToApp($data['mac'],$upload); // push message to app
    }

    public function doHeartbeat($fd,$data){
        $download = array(
            'sn'=> $data['sn'],
            'cmd'=> $data['cmd'],
            'heart'=> (int)$this->conf->get('main.heart'),
        );
        $download = json_encode($download)."\n";
        $this->server->send($fd, $download);
    }

    public function forbidden($frame,$message){
        $this->logger->error($message);
        $this->closeConnection($frame->fd);
    }

    protected function closeConnection($fd){
        $this->server->close($fd);
    }


    protected function push($fd,$data){
        return $this->server->push($fd, json_encode($data));
    }


    protected function getHeartbeat(){
        global $conf;
        return $period = $conf->get('server.main.heart',60);
    }

    public function pushMsgToApp($mac,$msg){
        $mq = new Mqtt();
        $mq->publish($this->conf->get('mqtt.prefix').$mac, $msg, 1, 0);
    }

    public function setOffline($mac,$fd){
        // 下线控制权只交给最新的和mac对应的fd
        $chk_fd = $this->redis->getMac($mac);
        if ($fd == $chk_fd){
            $this->redis->setDevice($mac,array('online'=>0));
            $msg = json_encode(array("sn"=>  $this->getMicroTime(),"mac"=>$mac,"device_online" => 0))."\n";
            $this->pushMsgToApp($mac,$msg);
        }
        $this->redis->DelFd($fd);
    }

    private function setOnline($mac,$fd,$data){
        $this->redis->setMac($mac,$fd);
        $this->redis->setFd($fd,$mac);
        $this->redis->setDevice($mac,$data);
        $msg = json_encode(array("sn"=> $this->getMicroTime(),"mac"=>$mac,"device_online" => 1))."\n";
        $this->pushMsgToApp($mac,$msg);
    }

    function getMicroTime(){
        $time = microtime(true)*10000;
        $time = substr($time,5,7);
        return $time;
    }

}
