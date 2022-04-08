<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 19.11.2020
 * Time: 17:49
 */

namespace Ws\Classes;


class ClassController
{
    var $act;
    var $user;
    var $err = 0;
    public function __construct(){
        echo 'create ClassController ok...'.PHP_EOL;
    }
    function getDataFromJson($json_str){
        global $LOG;
        $data = array();
        try{
            $inputJSON = json_decode($json_str, true);
            if(isset($inputJSON['data_group'])){
                if (is_array($inputJSON['data_group']))
                    foreach ($inputJSON['data_group'] as $k => $i)
                        $data[$k] = $i;
            }
            foreach ($inputJSON as $key => $item)
                if($key != 'data_group')$data[$key] = $item;
            return $data;
        }catch (\Exception $e){
            $str = 'ERROR ----- bad JSON: '.$json_str;
            $LOG->write($str);
            $this->err = ERR_JSON;
        }
        return 0;
    }
    function processingNewData($conn, $json_str){
        global $LOG, $MSG, $USR;
        $data = $this->getDataFromJson($json_str);
        if($data == 0)
            return;
        if(isset($data['act'])){
            if($this->checkWsToken($conn, $data)){
                switch ($data['act']){
                    case   ACT_CONFIRM_DELIVER_MSG : $MSG->confirmDeliver   ($conn, $data); break;
                    case   ACT_NEW_MSG             : $MSG->sendMsg          ($conn, $data); break;
                    case   ACT_CHECK_NEW_MSG       : $MSG->checkNewMsg      ($conn, $data); break;
                    case   ACT_AUTH_USER           : $USR->authUser         ($conn, $data); break;
                    case   ACT_PING_SERVER         : $this->pongServer      ($conn, $data); break;
                    case   ACT_GET_ONLINE_STATUS   : $USR->getOnlineStatus  ($conn, $data); break;
                    case   ACT_EXIT                : $USR->exitUser         ($conn, $data); break;
                    case   ACT_PRINT_MSG_PROCESS   : $MSG->printMsgProcess  ($conn, $data); break;
                    case   ACT_PONG                : $USR->userPong         ($conn, $data); break;
                    case   ACT_BIND_IMG_TO_MSG     : $MSG->bindImgToMsg     ($conn, $data); break;
                    case   ACT_ENABLE_SHOW_STATUS  : $USR->enableShowStatus ($conn, $data); break;
                    case   ACT_DISABLE_SHOW_STATUS : $USR->disableShowStatus($conn, $data); break;
                    default                        :{
                        $str = 'ERROR ---- bad act';
                        $LOG->write($str,$conn);
                        break;
                    }
                }
            }
        }
    }
    function testPing($conn, $data){
        global $WS;
        echo 'test ping -> ok';
        $WS->send($conn, '1', 'test_ping');
    }
    function pongServer($conn, $data){
        global $WS;
        $WS->send($conn, '1', ACT_AUTH_USER);
    }
    function checkWsToken($conn, $data){
        global $CNNCTS, $LOG, $USR, $worker, $C, $WS;
        $res = true;
        $err = 0;
        $array[] = ACT_AUTH_USER;
        if(!(in_array($data['act'], $array))){
            if((isset($data['user_id']))&&(isset($data['ws_token'])&&(isset($data['app_id'])))){
                $key = "u".$conn->user_id;
                if (isset($CNNCTS[$key])){
                    if (isset($worker->connections[$CNNCTS[$key]])){
                        if($worker->connections[$CNNCTS[$key]]->token  == $data['ws_token']){
                            return true;
                        }
                        else{
                            $err = 4;
                            $res = false;
                        }
                    }
                } else {
                    $res = $USR->checkRepeatedAuth($data, $conn);
                    if (!$res)$err = 3;
                }
            } else {
                $err = 2;
                $res = false;
            }
        }else $err = 0;
        if (!$res){
            $WS->send($conn, 0, ACT_EXIT);
            $note = 'ERROR  ----  token wtf; err -> '.$err;
            $LOG->write($note, $conn);
        }
        return $res;
    }
}



