<?php
namespace Ws\Classes;
use Ws\Structs\structConnection;
class ClassUser
{
    public function __construct()
    {
        echo 'create ClassUser ok...'.PHP_EOL;
    }
    function getUserData($user_id){
        global $DB, $LOG;
        $query = "SELECT ws_token, app_id FROM users WHERE id = '$user_id'";
        $res = $DB->row($query);
        if($res)return $res;
        else {
            $LOG->write('ERROR ----  get user token');
            return 0;
        }
    }
    function authUser($conn, $rcvData){
        global $LOG, $CNNCTS, $worker, $WS;
        $err = 0;
        if(!isset($rcvData['user_id']    ))$err = ERR_USER_DATA;
        if(!isset($rcvData['user_login'] ))$err = ERR_USER_DATA;
        if(!isset($rcvData['ws_token']   ))$err = ERR_USER_DATA;
        if(!isset($rcvData['app_id']     ))$err = ERR_USER_DATA;
        if(!isset($rcvData['show_status']))$err = ERR_USER_DATA;
        if($err){$this->errorUserData($conn);return;}

        $dbData = $this->getUserData($rcvData['user_id']);
        if (!$dbData){$this->errorUserData($conn);return;}

        if ($rcvData['ws_token'] != $dbData['ws_token']){
            $WS->send($conn, 0, ACT_NEW_AUTH_DATA);
        }else{
            $this->equalToken($conn,$rcvData,$dbData);
        }
    }
    function equalToken($conn,$rcvData,$dbData){
        global $CNNCTS, $worker;
        $key = "u".$rcvData['user_id'];
        if (isset($CNNCTS[$key]) && isset($worker->connections[$CNNCTS[$key]]))
            $this->connIsExistEqualToken($conn,$rcvData,$dbData,$key);
        else
            $this->connIsNotExistEqualToken($conn,$rcvData,$dbData,$key);
    }
    function connIsExistEqualToken($conn,$rcvData,$dbData,$key){
        global $WS, $worker, $CNNCTS, $LOG, $C;
        if ($worker->connections[$CNNCTS[$key]]->app_id == $rcvData['app_id']){
            $C->closeConnect($worker->connections[$CNNCTS[$key]], "repeat auth");
            $this->regNewConnection($conn,$rcvData,0,true);
        }else{
            $WS->send($worker->connections[$CNNCTS[$key]], 0, ACT_NEW_AUTH_DATA);
            $this->regNewConnection($conn,$rcvData,0);
            $LOG->write("new device:", $conn);
        }
    }
    function connIsNotExistEqualToken($conn,$rcvData,$dbData,$key){
        global $LOG, $worker, $CNNCTS;
        $this->regNewConnection($conn,$rcvData);
        $this->updateUserLastTime($rcvData['user_id']);

        $str = 'new auth:';
        $LOG->write($str, $conn);
    }
    function connIsNotExistUnequalToken($conn, $receiveData, $dbUserData,$key){
        global $WS, $worker, $CNNCTS;

    }
    function connIsExistUnequalToken($conn, $receiveData, $dbUserData,$key){

    }
    function errorUserData($conn){
        global $LOG, $WS;
        $str = 'ERROR ---- user_data';
        $LOG->write($str);
        $WS->send($conn,0,ACT_NEW_AUTH_DATA);
    }
    function checkRepeatedAuth($data, $conn){
        global $LOG, $CNNCTS, $worker, $WS;
        $tokenData = $this->getUserData($data['user_id']);
        if (!$tokenData){
            $str = 'ERROR ---- user_data';
            $LOG->write($str);
            $WS->send($conn, 0, ACT_EXIT);
            return false;
        }
        $key = "u".$data['user_id'];
        $token = $tokenData['ws_token'];
        $appId = $tokenData['app_id'];
        if ($appId == $data['app_id'] && ($token == $data['ws_token'])) {
            $str = 'repeated  auth_data: id -> ' . $data['user_id'] . ' login -> ' . $data['user_login'];
            $LOG->write($str);
            if (isset($CNNCTS[$key]))
                if (isset($worker->connections[$CNNCTS[$key]]))
                    if(method_exists($worker->connections[$CNNCTS[$key]], 'close'))$conn->close();

            $this->regNewConnection($conn, $data, 0,0);
//            new \SocketMsg($CNNCTS[$key], 0, ACT_AUTH_USER);
            return true;
        }
        return false;
    }
    function updateUserLastTime($userId){
        global $DB;
        $date = date('Y-m-d H:i:s');
        $query = "UPDATE users SET last_time='$date' WHERE id=$userId";
        $DB->query($query);
    }
    function regNewConnection($conn, $data, $token = 0, $confirmConnection = true){
        global $LOG, $CNNCTS, $WS;
        $key = "u".$data['user_id'];
        $appId = $data['app_id'];

        $conn->user_id    = $data['user_id'];
        $conn->user_login = $data['user_login'];
        $conn->token      = $data['ws_token'];
        $conn->app_id     = $appId;
        $conn->un_ping    = 0;
        $conn->show_status = $data['show_status'];

        if(isset($data['user_img']))$conn->user_img = $data['user_img'];
        $CNNCTS[$key] = $conn->id;

        $msg = array();
        if ($token != 0)$msg['new_token'] = $token;

        if ($confirmConnection)$WS->send($conn, $msg, ACT_AUTH_USER);
    }
    function userPong($conn, $data){
        global $CNNCTS, $LOG, $worker;
        try{
            $key = "u".$conn->user_id;
            $worker->connections[$CNNCTS[$key]]->un_ping = 0;
        }catch(\Exception $e){
            $LOG->write($e->getMessage());
        }
    }
    function getOnlineStatus($conn, $data){
        global $LOG, $WS;
        if(!isset($conn->user_id))$conn->user_id = null;
        if (isset($data['id_list'])){
            $idList = $data['id_list'];
            $res = $this->searchOnlineUsers($idList);
            $respData['id_list'] =  $res['id_list'];
            $WS->send($conn, $respData, ACT_ONLINE_LIST, $res['qt']);
        }
        else{
            $str = 'ERROR ---- idList';
            $LOG->write($str);
        }
    }
    function searchOnlineUsers($idList){
        global $CNNCTS, $worker;
        echo 'idList -> '.$idList.PHP_EOL;
        $idArray = explode('_', $idList);
        $onlineList = '';
        $idQt = 0;
        foreach ($idArray as $key=>$id){
            if (!is_numeric($id))continue;
            $key = 'u'.$id;
            if(isset($CNNCTS[$key]))
                if (isset($worker->connections[$CNNCTS[$key]]))
                    if (isset($worker->connections[$CNNCTS[$key]]->show_status))
                        if($worker->connections[$CNNCTS[$key]]->show_status == 1){
                            $idQt++;
                            $onlineList.=$id.'_';
                        }

        }

        $onlineList = substr($onlineList, 0, strlen($onlineList)-1);

        $res['qt'] = $idQt;
        $res['id_list'] = $onlineList;
        return $res;
    }
    function exitUser($conn, $data){
        global $LOG;
        try {
                if (isset($conn->user_id)){
                    $str = 'exit user: id -> '.$conn->user_id.'; login -> '.$conn->user_login;
                    $LOG->write($str);
                }
                if (isset($data['user_id']))
                    $this->updateUserLastTime($data['user_id']);

        }catch(\Exception $e){
            $str = 'ERROR ---- exit user';
            $LOG->write($str);
        }
        if(method_exists($conn, 'close'))$conn->close();
    }
    function enableShowStatus($conn, $data){
        global $CNNCTS, $LOG, $worker;
        try{
            $key = "u".$conn->user_id;
            $worker->connections[$CNNCTS[$key]]->show_status = true;
        }catch(\Exception $e){
            $LOG->write($e->getMessage());
        }
    }
    function disableShowStatus($conn, $data){
        global $CNNCTS, $LOG, $worker;
        try{
            $key = "u".$conn->user_id;
            $worker->connections[$CNNCTS[$key]]->show_status = false;
        }catch(\Exception $e){
            $LOG->write($e->getMessage());
        }
    }
}