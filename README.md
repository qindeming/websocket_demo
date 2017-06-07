## WebSocket运行实例说明文档

浏览器做webSocket的客户端，php做为webSocket的服务端，redis存储客户端发送的消息，经由服务端发送给客户端
php_thread实现webSocket的多进程

## PHP环境需求
* PHP版本 >= 5.3
* 开启php_socket扩展<br>
  一.直接在php.ini里开启即可。<br>
* 开启php_thread扩展<br>
  一、下载pthreads扩展<br>
      下载地址：http://windows.php.net/downloads/pecl/releases/pthreads<br>
  二、判断PHP是ts还是nts版<br>
      通过phpinfo(); 查看其中的 Thread Safety 项，这个项目就是查看是否是线程安全，如果是：enabled，
      一般来说应该是ts版，否则是nts版。<br>
  三、根据PHP ts\nts版选择对应pthreads的版本<br>
  本人php版本是5.5.38的所以下载php_pthreads-0.1.0-5.5-ts-vc11-x86.zip文件包，其中0.1.0表示为当前pthreads版本号，5.5为php版本号，
  ts就是之前判断php对应的ts、nts版，vs11代表是Visual Studio 2008 compiler编译器编译的，最后的x86代表的是32位的版本。<br>
  四、安装pthreads扩展
      复制php_pthreads.dll 到目录 bin\php\ext\ 下面。<br>
      复制pthreadVC2.dll 到目录 bin\php\ 下面。<br>
      复制pthreadVC2.dll 到目录 C:\windows\system32 下面。<br>
      打开php配置文件php.ini。在后面加上extension=php_pthreads.dll<br>
      提示！Windows系统需要将 pthreadVC2.dll 所在路径加入到 PATH 环境变量中。
      我的电脑--->鼠标右键--->属性--->高级--->环境变量--->系统变量--->找到名称为Path的--->编辑--->在变量值最后面加上pthreadVC2.dll的完整路径（本人的为C:\WINDOWS\system32\pthreadVC2.dll）
* 开启php_redis.dll扩展
  参考地址：http://jingyan.baidu.com/article/9989c74631873bf648ecfed4.html
* 安装redis服务
  参考地址：http://jingyan.baidu.com/article/0bc808fc26b58f1bd585b979.html


## 文件说明

*  BaseSocket.php文件是实现websocket通信的核心文件。
*  BaseThread.php文件确保可以开启PHP多线程
*  WebSocketCtl.php文件实例化核心文件BaseSocket.php文件
*  Controller.php开启php多线程的脚本文件
*  *.html是webSocket客户端文件demo


## 运行前准备

1.修改html文件里的webSocket的连接地址及端口号
2.修改config.php文件里的webSocket的IP地址及端口号
3.确认服务器已经运行redis服务
4.确认已经配置好web服务器，可以使用浏览器访问服务器网站资源

## 开始运行代码

1.cli模式运行Controller.php文件
2.使用浏览器运行qin.html文件，qin9902.html文件

## 功能缺陷

1.webSocket客户端js代码没有封装
2.webSocket服务端代码需要封装
3.代码结构需要优化
