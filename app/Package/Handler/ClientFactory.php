<?php namespace App\Package\Handler;

class ClientFactory {

    public static function createApp($pid){
        switch ($pid){
            case 'apple':
                return new Apple();
                break;
            default;
                throw new \Exception($pid);
                break;
        }
    }
}