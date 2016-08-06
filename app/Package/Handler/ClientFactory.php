<?php namespace App\Package\Handler;

class ClientFactory {

    public static function createApp($pid){
        switch ($pid){
            case 'pc':
                return new Pc();
                break;
            case 'wx':
                return new Wx();
                break;
            case 'server':
                return new Server();
                break;
            default;
                throw new \Exception($pid);
                break;
        }
    }
}