<?php namespace App\Package\Handler;

use App\Helper\Common;

class Wx extends Client
{
    protected $actions = ['login','heartbeat','sendCmd','chat','notice'];

    protected function checkConnection($frame,$data){
        $id = $data['data']['uid'];
        $fd = $frame->fd;
        if($data['action'] != 'login' && $this->redis_model->getWxUserAttr($id,'fd') != $fd){
            return false;
        }

        return true;
    }

    public function doLogin($frame,$data){
        $fd     = $frame->fd;
        $pid    = isset($data['pid'])?$data['data']:'';
        $uid    = isset($data['data']['uid'])?$data['data']['uid']:'';
        $fd_info = $this->server->connection_info($fd);
        $ip     = $fd_info['remote_ip'];

        if(empty($uid)){
            $this->forbidden($frame,'LoginArgumentError');
            return;
        }

        if(Common::checkPackage($data) == false){
            $this->forbidden($frame,'LoginSignatureError');
            return;
        }

        $user = array(
            'fd' => $fd,
            'uid' => $uid,
            'online' => 1,
            'pid' => $pid,
            'ip'  => $ip,
            'time' => time(),
        );

        $this->redis_model->wxLogin($fd,$user);

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

    public function doSendCmd($frame,$data){
        $fd = $frame->fd;
        $did = $data['data']['did'];
        $uid = $data['data']['uid'];

        // pc return , do nothing
        if(isset($data['ret']) && $data['ret']){
            return;
        }

        if(empty($did)){
            $this->forbidden($frame,'CommandArgumentError');
            return;
        }

        if($this->checkBind($uid,$did) == false){
            $this->forbidden($frame,'Forbidden,noBindRelation');
            return;
        }

        $pc_status = $this->redis_model->checkPcOnline($did);
        $this->logger->info('pc status:'.$did.":".$pc_status);
        if($pc_status == 0){
            $this->doReturn($fd,$data,NOT_EXIST);
            return;
        }

        $pc_fd = $this->redis_model->getPcAttr($did,'fd');
        $this->logger->info('pc fd:'.$did.":".$pc_fd);
        if(empty($pc_fd)){
            $this->doReturn($fd,$data,SERVER_ERROR);
            return;
        }

        $result = $this->push($pc_fd,$data);

        if($result){
            $this->doReturn($fd,$data,SUCCESS);
        }else{
            $this->doReturn($fd,$data,SERVER_ERROR);
        }
    }

    public function doChat($frame,$data){

        $fd = $frame->fd;
        $from = $data['from'];
        $data['data']['time'] = time();
        $did = $data['data']['did'];
        $uid = $data['data']['uid'];
        $status   = $this->redis_model->checkPcOnline($did);

        if($this->checkBind($uid,$did) == false){
            $this->forbidden($frame,'Forbidden,noBindRelation');
            return;
        }

        $this->logger->info('from:'.$from.'status:'.$status);
        if( $status == 0 ){
            $this->doReturn($fd,$data,NOT_EXIST);
            return;
        }

        $send_fd  = $this->redis_model->getPcAttr($did,'fd');
        if(empty($send_fd)){
            $this->doReturn($fd,$data,SERVER_ERROR);
            return;
        }

        $result = $this->push($send_fd,$data);

        if($result == false){
            $this->doReturn($fd,$data,SERVER_ERROR);
            return;
        }

        $this->ssdb_model->addChat($data);
        $this->doReturn($fd,$data,SUCCESS);
    }

}
