<?php namespace Nosun\Swoole\Server\Http;

interface Protocol {

    function onRequest($request, $response);

}

