<?php
namespace classesPhp;
global $A_start;
if($A_start != 444){echo 'byby';exit();}

class ClassReturn
{
    function ARet(\structsPhp\StructReturn $data, $exit = 1){
        global $A_context,  $G;

        $A_context->ACreateContext();
        $data->context = $A_context->data;
        $data = json_encode($data);//
        echo $data;
        if($exit)exit();
    }
}

