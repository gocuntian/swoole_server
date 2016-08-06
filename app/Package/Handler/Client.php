<?php namespace App\Package\Handler;

abstract class Client
{

    protected $server;
    protected $logger;
    protected $redis_model;
    protected $ssdb_model;
    protected $actions = ['login','heartbeat','command','chat','error','notice','upload'];

    public function __construct(){}

    public function init($server,$redis,$ssdb,$logger){
        $this->server = $server;
        $this->redis_model = $redis;
        $this->ssdb_model  = $ssdb;
        $this->logger = $logger;
    }

    public function doCheck($frame,$data){
        //return true;
        if ($this->checkAction($data['action']) == false){
            $this->logger->error('bad action');
            return false;
        };

        if ($this->checkConnection($frame,$data) == false){
            $this->closeConnection($frame->fd);
            $this->logger->error('bad fd');
            return false;
        }

        return true;
    }

    protected function checkAction($action){
        return in_array($action,$this->actions);
    }

    protected function checkConnection($frame,$data){
        return true;
    }

    protected function checkBind($key,$val){
        return $this->redis_model->isBind($key,$val);
    }

    public function doLogin($frame,$data){}

    public function doHeartbeat($frame,$data){
        $fd = $frame->fd;
        $download = array(
            'sn'     => $data['sn'],
            'action' => $data['action'],
        );
        $this->push($fd,$download);
    }

    public function doReturn($fd,$data,$code){
        $download = array(
            "sn"  => $data['sn']?$data['sn']:0,
            "action" => 'return',
            "cmd" => $data['action']?$data['action']:'null',
            'ret' => $code,
        );

        if($data['action'] == 'sendCmd'){
            $download['data'] = $data['data'];
        }

        if($code == SERVER_ERROR){
            $this->logger->error('redis error');
        }

        $this->push($fd, $download);
    }

    public function doNotice($frame,$data){
        switch($data['to']){
            case 'pc':
                $this->doNoticePc($frame,$data);
                break;
            case 'wx':
                $this->doNoticeWx($frame,$data);
                break;
            default:
                break;
        }
    }

    protected function doNoticeWx($frame,$data){
        $fd = $frame->fd;
        $uid = $data['data']['uid'];
        $did = $data['data']['did'];
        if(empty($uid)){
            $this->forbidden($frame,'CommandArgumentError');
            return;
        }

        if(empty($did) || $this->checkBind($did,$uid) == false){
            $this->forbidden($frame,'Forbidden,noBindRelation');
            return;
        }

        $status = $this->redis_model->checkWxOnline($uid);
        if($status){
            $send_fd = $this->redis_model->getWxUserAttr($uid,'fd');
            $this->push($send_fd,$data);
            $this->doReturn($fd,$data,SUCCESS);
        }else{
            $this->doReturn($fd,$data,NOT_EXIST);
        }
    }

    protected function doNoticePc($frame,$data){
        $fd = $frame->fd;
        $uid = $data['data']['uid']?$data['data']['uid']:'';
        $did = $data['data']['did']?$data['data']['did']:'';

        if(empty($uid) || empty($did)){
            $this->forbidden($frame,'CommandArgumentError');
            return;
        }

        if($this->checkBind($did,$uid) == false){
            $this->forbidden($frame,'Forbidden,noBindRelation');
            return;
        }

        $status = $this->redis_model->checkPcOnline($did);
        if($status){
            $send_fd = $this->redis_model->getPcAttr($did,'fd');
            $this->push($send_fd,$data);
        }else{
            $this->doReturn($fd,$data,NOT_EXIST);
        }
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

    protected function sendNotice($clients,$event){
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

}
