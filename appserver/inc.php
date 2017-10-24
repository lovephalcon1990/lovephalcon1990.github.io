<?php
system("umask 002");
define("SWOOLE_ROOT",dirname(__FILE__)."/");
define("PATH_DAT",SWOOLE_ROOT."data/");
//此进程为Swoole常驻启动进程入口，

define('SWOOLE',true);
define('SWOOLE_ENV', intval($argv[1])); //1：线上，0：内网
define('SWOOLE_PORT', intval($argv[2])); //1:监听端口
define('SWOOLE_UDPPORT', intval($argv[3])); //1:监听Udp端口

require SWOOLE_ROOT."vendor/autoload.php";

