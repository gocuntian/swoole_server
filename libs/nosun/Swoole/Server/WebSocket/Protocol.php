<?php namespace Nosun\Swoole\Server\WebSocket;

interface Protocol {

    function onOpen($server, $req);
    function onMessage($server, $frame);
    function onClose($server,$fd);

}

