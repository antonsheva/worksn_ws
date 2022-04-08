<?php

namespace Ws\Classes;

use Ws\Structs\structConnection;

class WsSend{
    function send($conn, $data, $type, $result=1, $error=0){
        $msg['data']   = $data;
        $msg['result'] = $result;
        $msg['error']  = $error;
        $msg['type']   = $type;
        $json = json_encode($msg);
        $conn->send($json);
    }
}