<?php
global $A_start;
$A_start = 444;
require_once '../workerman/vendor/autoload.php';

include 'com/includes.php';
include 'com/init.php';


use Workerman\Lib\Timer;
use Workerman\Worker;

$context = array(
    'ssl' => array(
        'local_cert'=> CRT_PATH,
        'verify_peer'  => false,
    )
);
$worker = new Worker(WS_NAME, $context);
//$worker->count = 4;
$worker->transport = 'ssl';


$worker->onWorkerStart = function (){

   echo 'Start server'.PHP_EOL;
   date_default_timezone_set("Europe/Moscow");
 
   Timer::add(TIME_PING, function() {
        global $TMR;
        $TMR->ping();
   });



   //------- отключение просроченных объявлений ------------------
   Timer::add(TIME_ADS_LIFETIME, function (){
       global $TMR;
       $TMR->adsLifetime();
   });
    Timer::add(TIME_REMOVE_OLD_FILES, function (){
        global $TMR;
        $TMR->removeOldTmpImgs();
    });
};

$worker->onConnect = function ($conn){
    global $C, $worker;

    echo 'new connect; connect_qt -> '.count($worker->connections).PHP_EOL;
    $C->addConnect($conn);
};
$worker->onClose = function ($connect){
    global $C, $worker;
    echo 'close connect; connect_qt -> '.count($worker->connections).PHP_EOL;
    $C->externalConnectConnect($connect);
};
$worker->onMessage = function ($conn, $data){
    global $C;
    $C->onMsg($conn, $data);
};

Worker::runAll();