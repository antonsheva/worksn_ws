<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 18.11.2020
 * Time: 17:51
 */
namespace Ws\Classes;

class ClassPrintLog
{
    public function __construct()
    {
        echo 'create ClassPrintLog ok...'.PHP_EOL;
    }

    function consoleLog($data){
        echo $data.PHP_EOL;
    }
}