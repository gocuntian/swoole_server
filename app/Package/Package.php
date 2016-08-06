<?php namespace App\Package;

class Package {

    protected $handler;
    protected $cmd;

    Static function divide($data){
        return explode("\n", $data);
    }

    static function decode($package) {

        $data =json_decode($package,true);

        if (json_last_error() != JSON_ERROR_NONE){
            return false;
        }

        if(is_array($data) == false){
            return false;
        }

        if (self::getType($data) == ''){
            return false;
        }

        return $data;
    }

    static function getType(array $data){
        return $data['action'];
    }



}