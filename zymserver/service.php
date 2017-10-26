<?php

define("SWOOLE_ROOT",dirname(__FILE__).'/');
define("PATH_DAT",SWOOLE_ROOT."data/");

define("SWOOLE_ENV",$argv[1]);

define("SWOOLE_PORT",$argv[2]);

define("SWOOLE_UDPPORT",$argv[3]);

require SWOOLE_ROOT . '/vendor/autoload.php';
use Zengym\Server\Service;

if (SWOOLE_ENV === 1) {
	error_reporting(0);
	$SwooleConfig = include SWOOLE_ROOT . "Config/pro.conf.php";
} else {
	error_reporting(E_WARNING|E_ERROR);
	$SwooleConfig = include SWOOLE_ROOT . "Config/demo.conf.php";
}

$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = SWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = ["ZYBehivor","Zengym\\Model\\ZYBehivor"];
$Service = new Service($SwooleConfig);

register_shutdown_function("Zengym\\Lib\\Helper\\Log::processEnd");

$tablesize = 32768;
//以mid为key存放fd
global $socket_table;
$socket_table = new swoole_table($tablesize);
$socket_table->column('fd', swoole_table::TYPE_INT, 4);//文件描述符
$socket_table->column('id', swoole_table::TYPE_INT, 4);//id
$socket_table->column('source', swoole_table::TYPE_INT, 1);//0:移动 1:PC
$socket_table->column('play', swoole_table::TYPE_INT, 1);//玩家状态，0=旁观，1=在玩
$socket_table->column('tid', swoole_table::TYPE_INT, 4);//桌子id
$socket_table->column('fcnt', swoole_table::TYPE_INT, 4);//好友数量
$socket_table->create();

//以fd为key存放mid
global $socket_mid_table;
$socket_mid_table = new swoole_table($tablesize);
$socket_mid_table->column('mid', swoole_table::TYPE_INT, 4);//文件描述符
$socket_mid_table->create();


$Service->Swoole->addListener($SwooleConfig['Host'], SWOOLE_UDPPORT, SWOOLE_SOCK_UDP);
$Service->Start();



