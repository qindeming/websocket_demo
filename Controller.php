#!/usr/bin/php
<?php

require "WebsocketCtl.php";

class Controller
{
	private $w1;
	private $work = array();
	public function __construct($switch, $config = '')
    {
        if ($switch) {
            $this->run($config);
        } else {

        }
    }
	public function run($config)
	{
	    $threads = array();
	    foreach ($config as $key => $value) {
            $value['name'] = new WebsocketCtl($this, $value['name'], $value['address'], $value['port']);
            array_push($threads, $value['name']);
            array_push($this->work, $value['name']);
        }

        for ($i=0; $i < count($threads); $i++) {
	        $threads[$i]->startThread();
        }
	}

    /**
     * @param $obj //webSocket类
     * @param $resourceId //webSocket连接资源符
     * @param $port  //webSocket连接端口号
     * @param $msg  //发送的消息
     */
	public function onRev($obj, $resourceId, $port, $msg, $name='')
	{
	    if ($name == "Qin") {
	        print_r($this->w1);
        }
        $data = array('obj' => $obj,
            'resourceId' => $resourceId,
            'port' => $port,
            'msg' => $msg
        );
	    if ($msg['type'] == 'login') {
            $data['msg'] = '欢迎'.$data['msg']['username'].'来到'.$data['port'].'聊天室';
	        $this->sendMsg($data);
        } else if($msg['type'] == 'send') {
            $data['msg'] = '消息来自'.$data['msg']['username'].':'.$data['msg']['msg'];
            $this->sendMsg($data);
        } else if ($msg['type'] == 'close') {
            $data['msg'] = "您已断开连接";
            $this->sendMsg($data);
	        $this->closeWebSocket($obj, $resourceId);
        }
	}

    /**
     * 关闭webSocket连接
     * @param $obj //webSocket类
     */
	public function closeWebSocket($obj, $resourceId)
    {
        $obj->close($resourceId);
    }

    /**
     * 发送消息
     * @param $data //数组 obj resourceId port msg
     */
    public function sendMsg($data)
    {
        $data['obj']->write($data['resourceId'], $data['msg']);
    }
}

require "config.php";
$c = new Controller(true, $config['webSocket']);


?>