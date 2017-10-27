<?php
require dirname(__FILE__) . '/../../inc.php';

use Zengym\Server\Service;
if (SWOOLE_ENV === 1) {
	$SwooleConfig =include SWOOLE_ROOT . "Apps/Cron/Conf/pro.conf.php";
} else {
	$SwooleConfig =include SWOOLE_ROOT . "Apps/Cron/Conf/demo.conf.php";
}
$SwooleConfig['MainProcessName'] = implode(' ', $argv);
$SwooleConfig['Port'] = SWOOLE_PORT;
$SwooleConfig['SocketType'] = SWOOLE_SOCK_TCP;
$SwooleConfig["Behavior"] = array("LogBehivor",   'Zengym\Apps\Log\Behivor\LogBehivor');

//每次重启清空消息队列
if (isset($SwooleConfig['Set']['message_queue_key'])) {
	$messagekey = sprintf("0x%08x", intval($SwooleConfig['Set']['message_queue_key']));
	system('ipcrm -Q ' . $messagekey);
}

$CrontabService = new Service($SwooleConfig);

register_shutdown_function("Zengym\\Lib\\Helper\\Log::processEnd");
$CrontabService->Start();
