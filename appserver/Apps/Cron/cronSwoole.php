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
$SwooleConfig["Behavior"] = array("CronBehivor",   'Zengym\Apps\Cron\Behivor\CronBehivor');

//每次重启清空消息队列
if (isset($SwooleConfig['Set']['message_queue_key'])) {
	$messagekey = sprintf("0x%08x", intval($SwooleConfig['Set']['message_queue_key']));
	system('ipcrm -Q ' . $messagekey);
}
global $crontab_work_table;
global $monitor_table;
global $crontab_task_table;
global $crontab_table;
global $crontab_time_table;
global $CrontabService;
$CrontabService = new Service($SwooleConfig);

//用于记录 进程启动时间
$crontab_work_table = new swoole_table(1024);
$crontab_work_table->column('workid', swoole_table::TYPE_INT, 1);
$crontab_work_table->column('beginTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('use_mem', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('requestcount', swoole_table::TYPE_INT, 4);
$crontab_work_table->column('lastTime', swoole_table::TYPE_INT, 4);
$crontab_work_table->create();

//用于记录 定时任务 当前并发次数
$crontab_table = new swoole_table(1024);
$crontab_table->column('cnt', swoole_table::TYPE_INT, 1);
$crontab_table->create();

//用于记录 定时任务 执行时间，方便检测程序监控
$crontab_time_table = new swoole_table(1024);
$crontab_time_table->column('time', swoole_table::TYPE_INT, 4);
$crontab_time_table->create();

//用于处理 table只加不减的原子性问题
$crontab_task_table = new swoole_table(1024);
$crontab_task_table->column('workid', swoole_table::TYPE_INT, 1); //进程编号
$crontab_task_table->column('crontab_name', swoole_table::TYPE_STRING, 64); //方法名
$crontab_task_table->column('begintime', swoole_table::TYPE_INT, 4); //开始记录时间
$crontab_task_table->create();


//用于监控方法运行时间
$monitor_table = new swoole_table(1024);
$monitor_table->column('method', swoole_table::TYPE_STRING, 64); //方法名
$monitor_table->column('begintime', swoole_table::TYPE_INT, 4); //开始记录时间
$monitor_table->column('lasttime', swoole_table::TYPE_INT, 4); //最后更新时间
$monitor_table->column('runtime', swoole_table::TYPE_FLOAT, 8); //执行总时间 n 毫秒
$monitor_table->column('runcnt', swoole_table::TYPE_INT, 4); //执行次数
$monitor_table->column('reveal_mem', swoole_table::TYPE_INT, 4); //上一次执行前与执行后的内存相差,未释放内存
$monitor_table->column('reveal_cnt', swoole_table::TYPE_INT, 4); //未释放次数 >1mb
$monitor_table->column('maxtime', swoole_table::TYPE_INT, 4); //最多的一次执行时间
$monitor_table->column('maxtime_logtime', swoole_table::TYPE_INT, 4); //最多的一次执行时间-日志
$monitor_table->create();

register_shutdown_function("Zengym\\Lib\\Helper\\Log::processEnd");
$CrontabService->Start();
