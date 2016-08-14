<?php ;

use League\Container\Container as LContainer;

class Container
{
    protected static $instance;

    public static function getInstance(){

        if(!self::$instance instanceof LContainer){
            self::$instance = new LContainer();
        }

        return self::$instance;
    }

}