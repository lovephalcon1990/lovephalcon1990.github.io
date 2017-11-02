<?php
system('umask 002');
system('source /etc/profile');
//此进程会每隔1分钟启动一次,用于启动Swoole及监控，确保swoole出故障后会自动重启
define("SWOOLE_ROOT",dirname(__FILE__)."/");
define("PATH_DAT",SWOOLE_ROOT."data/");
define("SWOOLE_ENV",$argv[1]);
define('IN_WEB', true);
define('SWOOLE_VERROOT', SWOOLE_ROOT . 'Ver/');
define('SWOOLE_WEBMAIN', 1); //1：为主web服务 0：为从web
defined('IS_PHP7') or define('IS_PHP7', strpos(PHP_VERSION, '7.') === 0);

require SWOOLE_ROOT."vendor/autoload.php";
if(IS_PHP7){
	define('PHP_BIN', 'php -f ');
}else{
	define('PHP_BIN', '/usr/local/php-5.6.29/bin/php -f ');
}

define('SWOOLE_VERTMPROOT', PATH_DAT . 'swooletmp/');
if(!is_dir(SWOOLE_VERTMPROOT)){
	mkdir(SWOOLE_VERTMPROOT, 0775, true);
}
//启动监控程序
//$monitorPath = PHP_BIN . SWOOLE_ROOT . 'Monitor.php >/dev/null 2>&1 &';
//system($monitorPath);


//启动CrontabService.php 定时任务,内网所有定时只有主Web执行
$CronMonitor = new Zengym\Apps\Cron\CronMonitor();
$CronMonitor->Start();


//iplocation
//if((SERVER_TYPE === 'demo' && oo::$config['sid'] == 13) || PRODUCTION_SERVER){
//	include_once dirname(__FILE__) . '/IpLocationMonitor.php';
//	$IpLocationMonitor = new IpLocationMonitor();
//	$IpLocationMonitor->Start();
//}

////启动Udp/Tcp服务
//include_once dirname(__FILE__) . '/QueneMonitor.php';
//$QueneMonitor = new SwooleQueneMonitor();
//$QueneMonitor->Start();
//if(PRODUCTION_SERVER && TSWOOLE_WEBMAIN && oo::$config['OpenUdpLogStart_Swoole']){
//	//启动Udp/日志服务
//	include_once dirname(__FILE__) . '/LogMonitor.php';
//	$LogMonitor = new SwooleLogMonitor();
//	$LogMonitor->Start();
//}elseif(!PRODUCTION_SERVER && !TSWOOLE_WEBMAIN){
	//内网日志全部放在192.168.202.93机器上
	//启动Udp/日志服务
	//$LogMonitor = new Zengym\Apps\Log\LogMonitor();
	//$LogMonitor->Start();
//}
//
//if(PRODUCTION_SERVER){
//	//杀进程,用于部署需要
//	include_once dirname(__FILE__) . '/SwooleKillOtherProcess.php';
//}
//
//if(PRODUCTION_SERVER && TSWOOLE_WEBMAIN){//主服务器预警系统监控
//	oo::warning()->check();
//}
//
//if(oo::$config['swivtserver'] && TSWOOLE_WEBMAIN){
//	//启动邀请打牌服务,只有主Web执行
//	include_once dirname(__FILE__) . '/InviteMonitor.php';
//	$InviteMonitor = new SwooleInviteMonitor();
//	$InviteMonitor->Start();
//}