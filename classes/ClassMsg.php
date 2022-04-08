<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 19.11.2020
 * Time: 22:22
 */

namespace Ws\Classes;


use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Finder\Adapter\PhpAdapter;
use Ws\Structs\structConnection;
use Ws\Structs\structMsg;

class ClassMsg
{
    public function __construct(){
        echo 'create ClassMsg ok...'.PHP_EOL;
    }
    function printMsgProcess($conn, $data){
        global $LOG, $CNNCTS, $worker, $WS;
        $err = 0;
        if(!isset($data['discus_id']))$err = ERR_CONTENT;
        if(!isset($data['consumer_id']))$err = ERR_CONTENT;

        if($err){
            $str = 'ERROR ---- msg content';
            $LOG->write($str);
            return;
        }else{
            $senderId   = $data['user_id'];
            $consumerId = $data['consumer_id'];
            if ($this->checkBwList($senderId, $consumerId))return;
            $key = 'u'.$data['consumer_id'];
            if(isset($CNNCTS[$key]))
                if (isset($worker->connections[$CNNCTS[$key]])){
                    unset($data['txt']);
                    $WS->send($worker->connections[$CNNCTS[$key]], $data, ACT_PRINT_MSG_PROCESS);
                }
        }
    }
    function confirmDeliver($conn, $data){
        global $LOG, $CNNCTS, $worker, $WS;
        $err = 0;
        if(!isset($data['discus_id']))$err   = ERR_CONTENT;
        if(!isset($data['consumer_id']))$err = ERR_CONTENT;
        if(!isset($data['status_msg']))$err  = ERR_CONTENT;

        if($err){
            $str = 'ERROR ---- msg content';
            $LOG->write($str);
            return;
        }else{
            $this->setMsgAsViewed($conn,$data);
            $key = 'u'.$data['consumer_id'];
            if(isset($CNNCTS[$key]))
                if (isset($worker->connections[$CNNCTS[$key]]))
                    $WS->send($worker->connections[$CNNCTS[$key]], $data, ACT_CONFIRM_DELIVER_MSG);

        }
    }
    function setMsgAsViewed($conn, $data){
        global $DB;
        $discus_id   = $data['discus_id'];
        $consumer_id = $data['user_id'  ];
        $status_msg  = $data['status_msg'];
        $query = "UPDATE msg SET view='$status_msg' WHERE discus_id   = '$discus_id' AND consumer_id = '$consumer_id' AND view < '2'";
        $DB->query($query);
    }
    function checkNewMsg($conn, $data){
        global $LOG,$WS;
        $respData = array();
        $msg = $this->findNewMsg($conn->user_id, $data);
//        if ($msg)
//            $str = 'there are a new messages';
//        else
//            $str = 'no new messages ';
//
//        $LOG->write($str, $conn);
        if ($msg)$respData['msg'] = $msg;
        if (count($respData)>0){
            $WS->send($conn, $respData, ACT_NEW_MSG);
        }
    }
    function findNewMsg($id, $data){
        global $DB, $LOG;
        try{
            if ($id){
                $query = "SELECT * FROM msg WHERE 
                      (consumer_id = '$id' AND view ='0' AND ((rmv_1 != '$id')AND(rmv_2 != '$id'))) ORDER BY id DESC LIMIT 1";
                $res = $DB->row($query);
                if ($res){
                    $senderId = $res['sender_id'];
                    $query = "SELECT login FROM users WHERE id='$senderId'";
                    $res1 = $DB->row($query);
                    if ($res1)
                        $res['sender_login'] = $res1['login'];
                }
                return $res;
            }
        }catch (\Exception $e){
            $str = 'ERROR ---- user id: '.$id;
            $LOG->write($str);
        };
        return 0;
    }

    //---------- SEND MSG -----------------------------------------------------
    function sendMsg($conn, $data){
        global $LOG, $CNNCTS, $worker, $WS;
        $err = 0;
        if(!isset($data['discus_id']))$err = ERR_CONTENT;
        if(!isset($data['ads_id']))$err = ERR_CONTENT;
        if(!isset($data['sender_id']))$err = ERR_CONTENT;
        if(!isset($data['consumer_id']))$err = ERR_CONTENT;
        if((!isset($data['content']))&&(!isset($data['img'])))$err = ERR_CONTENT;
        if($err){
            $str = 'ERROR ---- msg content';
            if (isset($conn->user_id))
                $str.='; user_id->'.$conn->user_id;
            $LOG->write($str);
            return;
        }else{
            $senderId   = $data['sender_id'];
            $consumerId = $data['consumer_id'];
            if ($this->checkBwList($senderId, $consumerId))return;
            $key = 'u'.$data['consumer_id'];
            if(isset($CNNCTS[$key]))
                if (isset($worker->connections[$CNNCTS[$key]]))
                    $WS->send($worker->connections[$CNNCTS[$key]], $data, ACT_NEW_MSG);

        }
    }
    function checkBwList($senderId, $consumerId){
        global $DB;
        $query = "SELECT ban_list FROM users WHERE id=$consumerId";
        $res = $DB->row($query);
        if ($res){
            $banList = $res['ban_list'];
            if($banList != null){
                if (strlen($banList) > 0){
                    $banList = explode("_", $banList);
                    if (in_array($senderId, $banList))return true;
                }
            }
        }
        return false;
    }
    function bindImgToMsg($conn, $data){
        global $LOG, $CNNCTS, $worker, $WS;
        $err = 0;
        if(!isset($data['img']))$err = ERR_CONTENT;
        if(!isset($data['img_icon']))$err = ERR_CONTENT;
        if(!isset($data['msg_id']))$err = ERR_CONTENT;
        if(!isset($data['consumer_id']))$err = ERR_CONTENT;

        if($err){
            $str = 'ERROR ---- msg content';
            if (isset($conn->user_id))
                $str.='; user_id->'.$conn->user_id;
            $LOG->write($str);
            return;
        }else{
            $key = 'u'.$data['consumer_id'];
            if(isset($CNNCTS[$key]))
                if (isset($worker->connections[$CNNCTS[$key]]))
                    $WS->send($worker->connections[$CNNCTS[$key]], $data, ACT_BIND_IMG_TO_MSG);

        }
    }

    //--------- SYSTEM NOTIFY ------------------------------------------------
    function checkConfirmedEmail($id){
        global $DB, $G;
        $query = "SELECT email, confirm_email FROM users WHERE id='$id'";
        $res = $DB->row($query);
        if($res){
            echo ' ----------sys_notify-----------'.PHP_EOL;
            foreach ($res as $key=>$item)
                echo $key.' => '.$item.PHP_EOL;
            if (($res['confirm_email'] == 0)&&(strlen($res['email'])>4)){
                echo 'do not confirm_email is set'.PHP_EOL;
                return 1;
            }
        }
        return 0;
    }

}










