<?php
define('SERVER_ROOT', dirname(__FILE__).'/');

$argv = $_SERVER['argv'];
define('SWOOLE_SID', intval($argv[1])); //站点sid
define('SWOOLE_ENV', intval($argv[2])); //1：线上，0：内网
define('SWOOLE_PORT', intval($argv[3])); //1:监听端口

define('INC_ROOT', SERVER_ROOT.'include/');
define('CFG_ROOT', SERVER_ROOT.'cfg/'.SWOOLE_SID . '/');
define('LIB_ROOT', SERVER_ROOT.'src/lib/');
define('MOD_ROOT', SERVER_ROOT.'src/model/');

include INC_ROOT . 'SwooleService.php';

error_reporting(SWOOLE_ENV?0:(E_ALL^E_NOTICE));
$SwooleConfig = include CFG_ROOT.'swoole.php';//加载配置
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = SWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("robotBehivor", SERVER_ROOT . 'robotBehivor.php');
$sicboRobotService = new SwooleService($SwooleConfig);

function processEnd(){
	$error = error_get_last();
	$error['date'] = date('Y-m-d H:i:s');
	$error['info'] = debug_backtrace();
	Main::logs($error, 'runEndErr');
	Main::processEnd();
}

function error_handler($errno, $errstr, $errfile, $errline){
	$error = '';
	$error .= date( 'Y-m-d H:i:s') . '--';
	$error .= 'Type:' . $errno . '--';
	$error .= 'Msg:' . $errstr . '--';
	$error .= 'File:' . $errfile . '--';
	$error .= 'Line:' . $errline . '--';
	Main::logs($error, 'handlerErr');
}

set_error_handler( 'error_handler', E_ALL ^ E_NOTICE); //注册错误函数 E_WARNING|E_ERROR
register_shutdown_function("processEnd");

//用于记录 进程启动时间
global $crontab_work_table;
$crontab_work_table = new swoole_table(32);
$crontab_work_table->column('beginTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('use_mem', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('lastTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('ver', swoole_table::TYPE_INT, 4);//目前版本号
$crontab_work_table->create();

//以mid为key记录用户信息
global $mid_table;
$mid_table = new swoole_table(2048);
$mid_table->column('ante', swoole_table::TYPE_INT, 4);
$mid_table->create();

$sicboRobotService->Start();
