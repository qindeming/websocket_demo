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
     * @param $obj //webSocket��
     * @param $resourceId //webSocket������Դ��
     * @param $port  //webSocket���Ӷ˿ں�
     * @param $msg  //���͵���Ϣ
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
            $data['msg'] = '��ӭ'.$data['msg']['username'].'����'.$data['port'].'������';
	        $this->sendMsg($data);
        } else if($msg['type'] == 'send') {
            $data['msg'] = '��Ϣ����'.$data['msg']['username'].':'.$data['msg']['msg'];
            $this->sendMsg($data);
        } else if ($msg['type'] == 'close') {
            $data['msg'] = "���ѶϿ�����";
            $this->sendMsg($data);
	        $this->closeWebSocket($obj, $resourceId);
        }
	}

    /**
     * �ر�webSocket����
     * @param $obj //webSocket��
     */
	public function closeWebSocket($obj, $resourceId)
    {
        $obj->close($resourceId);
    }

    /**
     * ������Ϣ
     * @param $data //���� obj resourceId port msg
     */
    public function sendMsg($data)
    {
        $data['obj']->write($data['resourceId'], $data['msg']);
    }
}

require "config.php";
$c = new Controller(true, $config['webSocket']);


?>