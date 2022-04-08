<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 19.11.2020
 * Time: 20:18
 */

namespace Ws\Structs;


class structConnection
{
    var $id          = null;
    var $user_id     = null;
    var $user_login  = null;
    var $user_img    = null;
    var $token       = null;
    var $connect     = null;
    var $un_ping     = 0;
    var $msg         = null;
}