<?php
class BaseSocket
{
    public $users; //连接socket的客户端列表
    public $master;
    public $address; //地址
    public $port; //端口号
    public $name;
    public $sockets;
    public $obj;
    public $redis;

    const MSG_TYPE_CLOSE = 'close';
    const MSG_TYPE_SEND  = 'send';
    const MSG_TYPE_LOGIN = 'login';
    const MSG_TYPE_HEART_TEST = 'heartTest';

    public function __construct($obj, $name, $address, $port)
    {
        error_reporting(E_ALL);
        set_time_limit(0);

        $socket  = $this->WebSocket($address, $port);
        $this->address = $address;
        $this->port    = $port;
        $this->master = $socket;
        $this->obj = $obj;
        $this->name = $name;
        $this->sockets = array('super' => $this->master);

        $redisManage = new RedisManage();
        $this->redis = $redisManage->getRedisObj();
    }

    /**
     * 建立webSocket通道
     * @param $address //连接地址（IP或者域名）
     * @param $port //连接端口
     * @return resource 返回资源
     */
    private function WebSocket($address, $port)
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, $address, $port);
        socket_listen($socket);
        $this->serverLog('start to listen: ' . $address . ' : ' . $port);
        return $socket;
    }

    /**
     * 握手的过程
     * 解析客户端header信息，获取Sec-WebSocket-Key
     * @param $port  //端口号
     * @param $userId  //webSocket连接通道唯一标识
     * @param $request_header  //请求头信息
     * @return bool
     */
    private function handshake($port, $userId, $request_header)
    {
        $buffer     = substr($request_header, strpos($request_header, 'Sec-WebSocket-Key:') + 18);
        $key        = trim(substr($buffer, 0, strpos($buffer, "\r\n")));//$key = YYlUFiEvDD6yGM5QLy3YAA==;
        $accept_key = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $response_header = "HTTP/1.1 101 Switching Protocols\r\n";
        $response_header .= "Upgrade: webSocket\r\n";
        $response_header .= "Sec-WebSocket-Version: 13\r\n";
        $response_header .= "Connection: Upgrade\r\n";
        $response_header .= "Sec-WebSocket-Accept: " . $accept_key . "\r\n\r\n";
        $writeResult = socket_write($this->users[$port][$userId]['socket'], $response_header, strlen($response_header));
        if ($writeResult !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 核心代码
     * 建立通道，握手，互相通信
     */
    function core()
    {
        //实现握手操作
        while (true) {
            $changes = $this->sockets;
            @socket_select($changes, $write = NULL, $except = NULL, NULL);
            foreach ($changes as $sign_k => $resourceId) {
                if ($resourceId == $this->master) {
                    //$this->serverLog(1);
                    //第一步：客户端第一次连接webSocket时，运行这里
                    $client = socket_accept($resourceId);//存入socket连接列表
                    $this->sockets[] = $client;
                    $this->users[$this->port][]   = array(
                        'socket' => $client,
                        'hand' => false,
                        'username' => ''
                    );
                } else {
                    //$this->serverLog(2);
                    //第二步：客户端发送消息时，运行这里
                    $buffer_bytes = socket_recv($resourceId, $buffer, 2048, 0);//接收来自已连接socket的客户端的消息 int(410)|false
                    if ($buffer_bytes == false) {
                        //接收客户端数据出现错误，关闭连接
                        $this->close($resourceId);
                        continue;
                    }

                    $userId = $this->search($this->port, $resourceId);
                    if (!$this->users[$this->port][$userId]['hand']) {

                        //第三步：通道打开后，开始握手
                        $handshakeResult = $this->handshake($this->port, $userId, $buffer);
                        if ($handshakeResult) {
                            //握手成功
                            $this->users[$this->port][$userId]['hand'] = true;
                            continue;
                        }
                    } else {
                        //第四步：握手成功，互相通信
                        $buffer = $this->unCode($buffer);
                        $clientInfo = json_decode($buffer, true);
                        $this->users[$this->port][$userId]['username'] = $clientInfo['username'];
                        if (is_array($clientInfo)) {
                            $msg = $this->getRedisMsg($clientInfo['username']);
                            if (is_array($msg)) {
                                foreach ($msg as $key => $value) {
                                    $clientInfo['msg'] = $value['msg'];
                                    $clientInfo['username'] = $value['from'];
                                    $clientInfo['to'] = $value['to'];
                                    $this->sendMessageFromAToB($clientInfo, $resourceId, $userId);
                                }
                                continue;
                            } else {
                                $this->sendMessageFromAToB($clientInfo, $resourceId, $userId);
                            }
                        } elseif ($buffer) {
                            $this->write($resourceId, $buffer);
                        } else {
                            $this->close($resourceId);
                        }
                    }
                }
            }
        }
    }

    public function setRedisMsg($info)
    {
        $msg = $this->getRedisMsg($info['to']);
        if ($msg) {
        } else {
            $msg = array();
        }

        $new = ['from'=>$info['username'], 'to'=>$info['to'], 'msg'=> $info['msg']];
        array_push($msg, $new);

        $this->redis->set($info['to'].'msg', json_encode($msg));
        return;
    }

    public function getRedisMsg($username)
    {
        $msg = false;
        $getMyMsg = $this->redis->get($username.'msg');
        if ($getMyMsg) {
            $clientInfo = json_decode($getMyMsg, true);
            $msg = $clientInfo;
            $this->redis->set($username.'msg', null);
        }
        return $msg;
    }

    /**
     * 点对点发送消息
     * @param array $info
     * @param       $resourceId
     * @param $userId //连接ID
     */
    private function sendMessageFromAToB(array $info, $resourceId, $userId)
    {
        if (!is_array($info)) {
            $this->write($this->master, '我不懂');
        }


        if (isset($info['order']) && $info['order'] == self::MSG_TYPE_SEND && isset($info['to'])) {

            $resource = $this->getResourceByName($info['to']);
            if (!$resource) {
                //当前端口里没有这个连接ID
                $this->setRedisMsg($info);
                return '';
            }

            //发送的消息close, 让对方下线
            if ($info['msg'] == self::MSG_TYPE_CLOSE) {
                $type = self::MSG_TYPE_CLOSE;
            } elseif ($info['msg'] == self::MSG_TYPE_SEND) {
                $type = self::MSG_TYPE_SEND;
            } elseif ($info['msg'] == self::MSG_TYPE_HEART_TEST) {
                $type = self::MSG_TYPE_HEART_TEST;
            } else {
                $type = self::MSG_TYPE_SEND;
            }
        } elseif (isset($info['order']) && $info['order'] == self::MSG_TYPE_LOGIN) {
            $resource = $resourceId;
            $type = self::MSG_TYPE_LOGIN;
        } elseif (isset($info['order']) && $info['order'] == self::MSG_TYPE_HEART_TEST) {
            $resource = $resourceId;
            $type = self::MSG_TYPE_HEART_TEST;
            return '';
        }
        $msg = isset($info['msg']) ? $info['msg'] : '';
        $this->obj->onRev($this, $resource, $this->port, ["type" =>$type, 'username'=>$info['username'],'msg'=>$msg], $this->name);
    }

    /**
     * 根据用户名，查询该用户的webSocket资源地址
     * @param $username
     *
     * @return bool
     */
    private function getResourceByName($username)
    {
        $resource = false;
        foreach ($this->users as $port => $value_array) {
            foreach ($value_array as $key => $value) {
                if ($username == $value['username']) {
                    //找到收件人
                    $resource = $value['socket'];
                    break;
                }
            }
        }

        if ($resource == false) {
            //当前服务器没有该用户信息
//            require "list.php";
        }

        return $resource;
    }

    /**
     * 查询用户的webSocket的连接ID
     * @param $port //端口号
     * @param $resourceId //资源符
     *
     * @return bool|int|string
     */
    private function search($port, $resourceId)
    {
        $userInfo = $this->users[$port];
        //通过标示遍历获取id
        foreach ($userInfo as $id => $v) {
            if ($resourceId == $v['socket'])
                return $id;
        }

        return false;
    }

    /**
     * 关闭某客户端的webSocket通道
     * @param $resourceId //连接通道的唯一标识
     */
    public function close($resourceId)
    {
        $userFlag = array_search($resourceId, $this->sockets);
        if ($userFlag !== false) {
            unset($this->users[$this->port][$userFlag]);
            unset($this->sockets[$userFlag]);
            socket_close($resourceId);
        }
    }

    /**
     * 解码请求头信息
     * @param $str  //客户端发送的消息
     * @return bool|string
     */
    private function unCode($str)
    {
        $mask = array();
        $data = '';
        $msg = unpack('H*', $str);
        /*
         * $msg = array
            (
                [1] => 8197abd36c41d0f11932cea10220c6b64e7b8ba2052fcfb60128c5b411
            )
         */
        $head = substr($msg[1], 0, 2);
        if (hexdec($head{1}) === 8) {
            $data = false;
        } else if (hexdec($head{1}) === 1) {
            $mask[] = hexdec(substr($msg[1], 4, 2));
            $mask[] = hexdec(substr($msg[1], 6, 2));
            $mask[] = hexdec(substr($msg[1], 8, 2));
            $mask[] = hexdec(substr($msg[1], 10, 2));

            $length = strlen($msg[1]) - 2;
            $n = 0;
            for ($i = 12; $i <= $length; $i += 2) {
                $data .= chr($mask[$n % 4] ^ hexdec(substr($msg[1], $i, 2)));
                $n++;
            }
        }

        return $data;
    }

    /**
     * 编码响应头信息，返回到客户端
     * @param $msg
     * @return string
     */
    private function code($msg)
    {
        $msg = preg_replace(array('/\r$/', '/\n$/', '/\r\n$/',), '', $msg);
        $frame = array();
        $frame[0] = '81';
        $len = strlen($msg);
        $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        $frame[2] = $this->ord_hex($msg);
        $data = implode('', $frame);

        return pack("H*", $data);
    }

    /**
     * 响应头信息从10进制转换到16进制
     * @param $data
     * @return string
     */
    private function ord_hex($data)
    {
        $msg = '';
        $str_length = strlen($data);
        for ($i = 0; $i < $str_length; $i++) {
            $msg .= dechex(ord($data{$i}));
        }

        return $msg;
    }

    /**
     * 发送客户端消息
     * @param $resourceId //连接webSocket的客户端信息
     * @param $data //发送的数据信息
     *
     * @return int
     */
    public function write($resourceId, $data)
    {
        //通过标示推送信息
        $data = $this->code($data);
        return socket_write($resourceId, $data, strlen($data));
    }

    /**
     * 发送服务器输出消息
     * @param $data  //发送的数据
     */
    public function serverLog($data)
    {
        $data = $data . "\r\n";
        $msg = iconv('utf-8', 'gbk//IGNORE', $data);
//        fwrite("php://STDOUT", $msg);
        echo $msg;
    }

}

class RedisManage
{
    public $redis;
    public function __construct()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1','6379');
        $redis->auth('123456');
        $this->redis = $redis;

    }

    public function getRedisObj()
    {
        return $this->redis;
    }
}
