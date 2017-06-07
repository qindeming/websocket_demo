<?php

/**
 * Created by PhpStorm.
 * User: Ruige004
 * Date: 2017/5/27
 * Time: 9:24
 */
class BaseThread extends Thread
{
    /*Thread 源码*/
    /*var $hooks = array();
    var $args = array();

    function thread()
    {

    }

    function addthread($func)
    {
        $args = array_slice(func_get_args(), 1);
        $this->hooks[] = $func;
        $this->args[] = $args;
        return true;
    }

    function runthread()
    {
        if (isset($_GET['flag'])) {
            $flag = intval($_GET['flag']);
        }
        if ($flag || $flag === 0) {
            call_user_func_array($this->hooks[$flag], $this->args[$flag]);
        } else {
            for ($i = 0, $size = count($this->hooks); $i < $size; $i++) {
                $fp = fsockopen($_SERVER['HTTP_HOST'], $_SERVER['SERVER_PORT']);
                if ($fp) {
                    $out = "GET {$_SERVER['PHP_SELF']}?flag=$i HTTP/1.1rn";
                    $out .= "Host: {$_SERVER['HTTP_HOST']}rn";
                    $out .= "Connection: Closernrn";
                    fputs($fp, $out);
                    fclose($fp);
                }
            }
        }
    }*/

    public function __construct($arg)
    {
        $this->arg = $arg;
    }

    public function run()
    {
        if ($this->arg) {
            printf("Hello %s\n", $this->arg);
        }
    }

    //开启线程
    public function startThread()
    {
        $this->start();
    }
}

//demo
//$thread = new BaseThread("World");
//
//if ($thread->start()) {
//    $thread->join();
//}
//
//$thread = new BaseThread("Qindeming");
//if ($thread->start()) {
//    $thread->join();
//}

