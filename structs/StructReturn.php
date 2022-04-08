<?php
namespace structsPhp;
global $A_start;
if($A_start != 444){echo 'byby';exit();}

class StructReturn
{
    var $error     = 0;
    var $response  = 0;
    var $result    = 0;
    var $data      = null;
    var $context   = null;
    var $test_data = null;
}