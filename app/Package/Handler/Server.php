<?php namespace App\Package\Handler;

use App\Client\WechatClient;

class Server extends Client
{
    protected $actions = ['notice','chat'];

    protected function checkBind($key,$val){
        return true;
    }

    public function doChat($frame,$data){
        $data['data']['time'] = time();
        $uid = $data['data']['uid'];
        $data['from'] = 'pc';

        $this->ssdb_model->addChat($data);
        $status   = $this->redis_model->checkWxOnline($uid);
        $this->logger->info('from server:status:'.$status);
        if( $status == 0){
            $wechat = WechatClient::getInstance();
            $message = array(
                'message'=> $data['data']['cont'],
                'uid'    => $data['data']['uid'],
            );
            $result = $wechat::send($message);
            $this->logger->info(json_encode($result));
            return;
        }

        $send_fd  = $this->redis_model->getWxUserAttr($uid,'fd');
        $this->logger->info('send fd:'.$send_fd);
        if(empty($send_fd)){
            return;
        }

        $this->push($send_fd,$data);
        $this->logger->info('send finish:'.json_encode($data));
    }
}
