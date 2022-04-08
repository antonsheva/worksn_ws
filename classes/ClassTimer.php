<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 29.11.2020
 * Time: 20:54
 */

namespace Ws\Classes;


use Workerman\Events\Libevent;

class ClassTimer
{
    function __construct()
    {
        echo 'create ClassTimer ok...'.PHP_EOL;
    }

    function ping(){
        global $CNNCTS, $LOG, $worker, $WS;
        $worker->connections = $worker->connections;
        foreach ($CNNCTS as $key=>$index) {
            if (isset($worker->connections[$index])){
                if ($worker->connections[$index]->un_ping > 3) {
                    $worker->connections[$index]->close();
                    unset($worker->connections[$index]);
                    $LOG->write('User '.$key.' is unconnected');
                } else {
                    $WS->send($worker->connections[$index],'ping', ACT_PING);
                    $worker->connections[$index]->un_ping++;
                }
            }

        }
//        $memoryPick  = memory_get_peak_usage(true);
//        $memoryUsage = memory_get_usage(true);
//        $data = 'memory pick - > '.$memoryPick.' bytes'.PHP_EOL;
//        $data .= 'memory usage - > '.$memoryUsage.' bytes'.PHP_EOL;
//        echo $data.PHP_EOL;
    }
    function adsLifetime(){
        global $DB;
        $time = time();
        echo 'remove ads';
        $query = "UPDATE ads SET remove='1' WHERE (ads.create_time + ads.lifetime) < '$time'";
        $DB->query($query);

    }
    function removeOldTmpImgs(){
        $imgs     ='../wksn_users_img/tmp_img/';
        $imgsIcon ='../wksn_users_img/tmp_img/icon/';
        $this->removeOldFileFromDir($imgs);
        $this->removeOldFileFromDir($imgsIcon);

    }

    function removeOldFileFromDir($path){
        global $LOG;
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if(is_file($path.$file)){
                        $LOG->write('read file -> '.$path.$file);
                        $fPath = explode('.', $file)[0];
                        $arr = explode('_', $fPath);
                        $createTime = (int)$arr[count($arr)-1];
                        $dTime = time()-$createTime;
                        $LOG->write('dTime -> '.$dTime);
                        if (((time() - $createTime) > 3600) || ($createTime = 0)) {
                            $LOG->write('remove file -> '.$path.$file);
                            unlink($path.$file);

                        }
                    }
                }
                closedir($dh);
            }
        }
    }
}