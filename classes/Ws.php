<?php

namespace Ws\Classes;

use Ws\Structs\structConnection;

class Ws{
    function send($conn, $data, $type, $result=1, $error=0){
        global $LOG;
        if ($conn == null){
            $LOG->write("conn is null");
            return;
        }
        if(isset($data['ws_token']))unset($data['ws_token']);
        if(is_array($data)){
            foreach ($data as $key=>$item){
                $msg[$key] = $item;
            }
        }else $msg['data']   = $data;
        $msg['result'] = $result;
        $msg['error']  = $error;
        $msg['type']   = $type;
        $json = json_encode($msg);
//        $LOG->write( '-> '.$json, $conn);
        if(method_exists($conn, 'send'))$conn->send($json);
        else {
            $str = 'error send data';
            $LOG->write($str, $conn);
        }
    }
}