<?php

require('BaseSocket.php');
require('BaseThread.php');

class WebsocketCtl extends BaseThread {
	public $m_port    = '';
    public $m_url    = '';
    public $m_name   = '';
    public $m_ctl;

    public function __construct($ctl, $name, $address, $port )
    {
        parent::__construct($name);
        $this->m_ctl  = $ctl;
        $this->m_name = $name;
        $this->m_url  = $address;
        $this->m_port = $port;
    }

    public function run() {
	    $config = array(
	        'address' => $this->m_url,
	        'port' => $this->m_port,
	        'ctl' => $this->m_name,
	    );

		$m_websocket = new BaseSocket($this->m_ctl, $this->m_name, $config['address'], $config['port']);
		$m_websocket->core();
    }
}

//$address = '192.168.1.177';
//$port = '9900';
//$name = 'Qin';
//$baseSocket = new WebsocketCtl($name, $address,$port);
//$baseSocket->run();
