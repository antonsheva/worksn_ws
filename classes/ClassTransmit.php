<?php

namespace Ws\Classes;

use Ws\Structs\structConnection;

class ClassTransmit{
    function send(structConnection $conn, $data){
        $data = json_encode($data);
        $conn->connect->send($data);
    }
}