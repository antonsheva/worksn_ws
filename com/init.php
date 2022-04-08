<?php
//$ROOT = $_SERVER['DOCUMENT_ROOT'];
//use workerman\mysql as Wmn;

$DB = new Connection(DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME);
$CNNCTS = array();
$C     = new  Ws\Classes\ClassConnect();
$A_ws_log = fopen('ws_log.txt', 'a');
$LOG   = new  Ws\classes\ClassLog();
$CNTRL = new  Ws\Classes\ClassController();
$MSG   = new  Ws\Classes\ClassMsg();
$USR   = new  Ws\Classes\ClassUser();
$TMR   = new  Ws\Classes\ClassTimer();
$WS    = new  Ws\Classes\Ws();



