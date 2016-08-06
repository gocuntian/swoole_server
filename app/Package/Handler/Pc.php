<?php namespace App\Package\Handler;

use App\Helper\Common;
use Psr\Log\LogLevel;
use Noodlehaus\Config;
use App\Client\WechatClient;

class Pc extends Client
{
    protected $actions = ['login','heartbeat','chat','error','notice','upload','chatBench'];
    public function __construct(){
        parent::__construct();
    }

    protected function checkConnection($frame,$data){
        $id = $data['data']['did'];
        $fd = $frame->fd;
        if($data['action'] != 'login' && $this->redis_model->getPcAttr($id,'fd') != $fd){
            return false;
        }
        return true;
    }

    public function doLogin($frame,$data){
        $fd     = $frame->fd;
        $pid    = isset($data['pid'])?$data['pid']:'';
        $id     = isset($data['data']['did'])?$data['data']['did']:'';
        $sv     = isset($data['data']['sv'])?$data['data']['sv']:'';
        $pv     = isset($data['pv'])?$data['pv']:'';
        $fd_info = $this->server->connection_info($fd);
        $ip     = $fd_info['remote_ip'];

        if(empty($id)){
            $this->forbidden($frame,'LoginArgumentError_3');
            return;
        }

        if(Common::checkPackage($data) == false){
            $this->forbidden($frame,'LoginSignatureError');
            return;
        }

        $device = array(
            'did' => $id,
            'pv'  => $pv,
            'sv'  => $sv,
            'pid' => $pid,
            'fd'  => $fd,
            'ip'  => $ip,
            'online' =>1,
            'updated_at' => time(),
        );

        if($this->redis_model->isPcExist($id) == false){
            $device = array_merge($device, array(
                'name'=> 'pc',
                'pic' => 1,
                'created_at' => time(),
            ));
            $this->redis_model->pcReg($fd,$device);
        }else{
            $this->redis_model->pcLogin($fd,$device);
        }

        $download = array(
            'action' => $data['action'],
            'sn'     => $data['sn'],
            'ret'    => SUCCESS,
            'period' => $this->getHeartbeat()
        );

        $this->push($fd,$download);

        $notifiee = $this->redis_model->getNoticer($fd);
        if($notifiee){
            $this->sendNotice($notifiee,'online');
        }
    }

    public function doChat($frame,$data){

        $fd = $frame->fd;
        $from = $data['from'];
        $data['data']['time'] = time();
        $uid = $data['data']['uid'];
        $did = $data['data']['did'];

        if($this->checkBind($did,$uid) == false){
            $this->forbidden($frame,'Forbidden,noBindRelation');
            return;
        }

        $this->ssdb_model->addChat($data);

        $status   = $this->redis_model->checkWxOnline($uid);

        $this->logger->info('from:'.$from.'status:'.$status);
        if( $status == 0){
            $wechat = WechatClient::getInstance();
            $message = array(
                'message'=> $data['data']['cont'],
                'uid'    => $data['data']['uid'],
            );
            $result = $wechat::send($message);
            $this->logger->info(json_encode($result));
            $this->doReturn($fd,$data,NOT_EXIST);
            return;
        }

        $send_fd  = $this->redis_model->getWxUserAttr($uid,'fd');

        if(empty($send_fd)){
            $this->doReturn($fd,$data,SERVER_ERROR);
            return;
        }

        $result = $this->push($send_fd,$data);

        if($result == false){
            $this->doReturn($fd,$data,SERVER_ERROR);
            return;
        }

        $this->doReturn($fd,$data,SUCCESS);
    }

    public function doChatBench($frame,$data){
        $fd = $frame->fd;
        $data['data']['time'] = time();
        $this->ssdb_model->addChat($data);
        $this->doReturn($fd,$data,SUCCESS);
    }

    public function doError($frame,$data){
        $fd = $frame->fd;
        $did = $data['data']['did'];

        if(empty($did)){
            $this->forbidden($frame,'CommandArgumentError');
            return;
        }

        $result = $this->ssdb_model->addErrorLog($data['data']);

        if($result){
            $this->doReturn($fd,$data,SUCCESS);
        }else{
            $this->doReturn($fd,$data,SERVER_ERROR);
        }

    }

    public function doUpload($frame,$data){
        $fd = $frame->fd;
        $did = $data['data']['did'];

        if(empty($did)){
            $this->forbidden($frame,'CommandArgumentError');
            return;
        }

        $result = $this->redis_model->setPc($did,$data['data']);

        if($result){
            $this->doReturn($fd,$data,SUCCESS);
        }else{
            $this->doReturn($fd,$data,SERVER_ERROR);
        }
    }

}
