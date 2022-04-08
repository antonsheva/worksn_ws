<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 18.11.2020
 * Time: 17:19
 */

namespace Ws\Classes;

use function Sodium\crypto_auth;
use Ws\Structs\structConnection;

class  ClassConnect{

    public $tmp_conn = array();
    public $connects = array();
    private $connCnt = 0;
    public function __construct(){
        echo 'create ClassConnect ok...'.PHP_EOL;
    }
    public function addConnect($conn){
        $conn->user_id = null;
        $conn->user_login = null;
        $conn->connId = $this->connCnt++;

    }
    public function closeConnect($conn, $cause){
        global $LOG, $CNNCTS, $worker;
        if ($conn == null){
            $LOG->write("conn is null 1: ".$cause);
            return;
        }

        $key = "u".$conn->user_id;

        if (isset($CNNCTS[$key])){
            if (isset($worker->connections[$CNNCTS[$key]]))
                if(method_exists($worker->connections[$CNNCTS[$key]], 'close'))$conn->close();

            if (isset($worker->connections[$CNNCTS[$key]]))
                     unset($worker->connections[$CNNCTS[$key]]);

            unset($CNNCTS[$key]);
        }

        if ($conn != null)
            if(method_exists($conn, 'close'))$conn->close();

        $str = 'close connection. '.$cause;
        $str.='; all connects -> '.count($worker->connections).'; auth connects -> '.count($CNNCTS);
        $LOG->write($str);

    }
    public function externalConnectConnect($conn){
        global $LOG, $CNNCTS, $worker;
        $str = 'close connection';

        $str.='; all connects -> '.count($worker->connections).'; auth connects -> '.count($CNNCTS);
        $LOG->write($str);

        $key = "u".$conn->user_id;
        if (isset($CNNCTS[$key])){
            if (isset($worker->connections[$CNNCTS[$key]]))
                     unset($worker->connections[$CNNCTS[$key]]);
            unset($CNNCTS[$key]);
        }

    }
    public function onMsg($conn, $msg){
        global $CNTRL;
        $CNTRL->processingNewData($conn, $msg);
    }
}







