<?php namespace Nosun\Swoole\Manager;

interface Driver {

    function setProtocol($protocol);
    function run();
    function send ($client_id,$data);
    function close($client_id);
}