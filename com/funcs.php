<?php

function AClearField($str){    //Clear fields from except chars
    global $DB, $LOG;
    $str = str_replace("'","`",$str);
    $str = str_replace("\"","`",$str);
    try{
//        $str = $DB->db->real_escape_string($str);
        $str = htmlspecialchars(addslashes($str));

    }catch (Exception $e){
        $LOG->write($e->getMessage());
        mRESP_WTF();
    }

    $str = stripslashes($str);
    $str = htmlspecialchars($str);
    $str = trim($str);
    return $str;
}
function arrayToArray($src, $dest){
    $arr_src = array();
    foreach($src as $key=>$item)
        $arr_src[$key] = $item;


    foreach($dest as $key=> &$item)
        if (array_key_exists($key, $arr_src))
            if ($arr_src[$key]) $item = $arr_src[$key];
}
function getPostData(&$data){
    global $P;
    foreach ($data as $key=> &$item)
        $item = $P->AGet($key);
}
function shutdownFunc() {
    $error = error_get_last();
    if (is_array($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // очищаем буфер вывода (о нём мы ещё поговорим в последующих статьях)
        mRESP_WTF();
        while (ob_get_level()) {
            ob_end_clean();
        }
        // выводим описание проблемы

    }
}
function getToken(){
    global $S;
    mRESP_DATA($S->AGet('s_token'));
}
