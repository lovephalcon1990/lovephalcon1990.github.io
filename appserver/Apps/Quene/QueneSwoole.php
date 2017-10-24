<?php

require dirname(__FILE__) . '/../../inc.php';
use Zengym\Server\Service;

if (SWOOLE_ENV === 1) {
	$SwooleConfig =include SWOOLE_ROOT . "Apps/Quene/Conf/pro.conf.php";
} else {
	$SwooleConfig =include SWOOLE_ROOT . "Apps/Quene/Conf/demo.conf.php";
}

$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = SWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("QueneBehivor", "Zengym\\Apps\\Quene\\Behivor\\QueneBehivor");
$QueneService = new Service($SwooleConfig);

register_shutdown_function("Zengym\\Apps\\Lib\\Helper\\Log::processEnd");

//用于记录 进程启动时间
global $crontab_work_table;
$crontab_work_table = new swoole_table(1024);
$crontab_work_table->column('workid', swoole_table::TYPE_INT, 1);
$crontab_work_table->column('beginTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('use_mem', swoole_table::TYPE_INT, 4);
$crontab_work_table->create();

$QueneService->Swoole->addListener($SwooleConfig['Host'], TSWOOLE_UDPPORT, SWOOLE_SOCK_UDP);
$QueneService->Start();
