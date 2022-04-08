<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 23.11.2020
 * Time: 6:30
 */

namespace Ws\Classes;


class ClassLog
{
    public function __construct()
    {
        echo 'create ClassLog ok...'.PHP_EOL;
    }

    function write($data, $conn = null){
        global $A_ws_log;
        $str = '';
        $strData = '';
        $date = date('m-d H:i:s');
        $connId = '';
        if ($conn){
            if (isset($conn->user_id))
                $str .= 'u '. $conn->user_id;
            if (isset($conn->user_login))
                $str .= ' '.$conn->user_login.' ';
        }
        if (isset($conn->connId))$connId = 'cId '.$conn->connId;
        if (is_array($data)){
            foreach ($data as $key=>$item)
                $strData .= $key.' -> '.$data.PHP_EOL;
        }else
            $strData = $data;
        $data = $date.': '.$str.$strData.$connId.PHP_EOL;
        echo $data;
        fwrite($A_ws_log,$data);
    }

    function connectsQt(){
        global $CNNCTS, $worker;

        echo PHP_EOL;
        echo PHP_EOL;
        echo 'all  connects -> '.count($worker->connections).PHP_EOL;
        echo 'auth connects -> '.count($CNNCTS).PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
    }
}