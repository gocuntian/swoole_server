<?php namespace App\Model;

use Nosun\Swoole\Client\Redis;

class DeviceRedis {

    private $redis;
    static  $prefix = "yun_";
    static  $fd ='f_';
    static  $mac ='m_';
    static  $dv_attr ='a_'; //online status
    static  $dv_data ='d_';  //device data
    static  $mac_test ='mac_test';  //device data

    static  $wx_topic  ='wx_t_';
    static  $wx_fd  ='wx_f_';

    public function __construct(){

        $redis = new Redis();
        $this->redis = $redis;
    }

    public function setMac($key,$value){
        $this->redis->set(self::$prefix.self::$mac.$key,$value);
    }

    public function getMac($key){
        return $this->redis->get(self::$prefix.self::$mac.$key);
    }

    public function delMac($key){
        return $this->redis->del(self::$prefix.self::$mac.$key);
    }

    public function setFd($key,$value){
        $this->redis->set(self::$prefix.self::$fd.$key,$value);
    }

    public function getFd($key){
        return $this->redis->get(self::$prefix.self::$fd.$key);
    }

    public function delFd($key){
        return $this->redis->del(self::$prefix.self::$fd.$key);
    }

    public function setDevice($key,array $data){
        $this->redis->hMset(self::$prefix.self::$dv_attr.$key,$data);
    }

    public function getDevice($key){
        return $this->redis->hGetAll(self::$prefix.self::$dv_attr.$key);
    }

    public function checkDevice($key){
        return $this->redis->exists(self::$prefix.self::$dv_attr.$key);
    }

    public function getDeviceAttr($key,$field){
        return $this->redis->hget(self::$prefix.self::$dv_attr.$key,$field);
    }

    public function getDeviceData($key){
        return $this->redis->hGetAll(self::$prefix.self::$dv_data.$key);
    }

    public function setDeviceData($key,array $value){
        $this->redis->hMset(self::$prefix.self::$dv_data.$key,$value);
    }

    public function getWxToken(){
        return $this->redis->get('access_token');
    }

    public function setSub($tag,$value){
        $this->redis->sAdd(self::$wx_topic.$tag,$value);
    }

    public function getSub($tag){
        return $this->redis->sMembers(self::$wx_topic.$tag);
    }

    public function remSub($tag,$value){
        $this->redis->sRemove(self::$wx_topic.$tag,$value);
    }

    public function setWxFd($key,$value){
        $this->redis->set(self::$prefix.self::$wx_fd.$key,$value);
    }

    public function getWxFd($key){
        return $this->redis->get(self::$prefix.self::$wx_fd.$key);
    }

    public function delWxFd($key){
        return $this->redis->del(self::$prefix.self::$wx_fd.$key);
    }

    public function isMacTest($mac){
        return $this->redis->sIsMember(self::$prefix.self::$mac_test,$mac);
    }


}