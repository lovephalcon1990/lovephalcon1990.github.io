<?php
/**
 * Created by SxdStorm.
 * User: yuemingzeng
 * Date: 2018/8/6
 * Time: 17:19
 */

$atomic1 = new swoole_atomic(0);
$atomic2 = new swoole_atomic(0);
$atomic3 = new swoole_atomic(0);
$atomic4 = new swoole_atomic(0);
$atomic5 = new swoole_atomic(0);
$maxrequest_atomic = new swoole_atomic(0);

define('MAX_CLIENT', 100);
define('MAX_REQUEST',0);
$port =  intval($argv[1]);
$port && $port=844;

$swoole = new swoole_server('127.0.0.1', $port, SWOOLE_BASE, SWOOLE_SOCK_TCP);

$swoole->set(
	['worker_num'=>1]
);

$serv->on('receive', function ($serv, $fd, $from_id, $data) {

});
$serv->on('close', function ($serv, $fd) {

});
$serv->on('connect', function ($serv, $fd) {

});
$serv->on('WorkerStart', function ($serv, $worker_id) {
	include_once dirname(__FILE__) . '/asClient.php';
	$cnt = 0;
	while ($cnt < MAX_CLIENT) {
		$GLOBALS[$cnt] = new ASClient("127.0.0.1", 9501, $worker_id);
		$cnt++;
	}
	$serv->tick(1000, function() {
		$cnt = 0;
		while ($cnt < MAX_CLIENT) {
			if(MAX_REQUEST>0){
				global $maxrequest_atomic;
				$max_cnt=$maxrequest_atomic->add(1);
				if($max_cnt>MAX_REQUEST){
					return;
				}
			}
			$asClient = $GLOBALS[$cnt];
			$asClient->DoRequest();
			$cnt++;
		}
	});
	if ($worker_id == 0) {
		$serv->tick(10000, function() {
			global $atomic1;
			$r1 = $atomic1->get();
			global $atomic2;
			$r2 = $atomic2->get();
			global $atomic3;
			$r3 = $atomic3->get();
			global $atomic4;
			$r4 = $atomic4->get();
			global $atomic5;
			$r5 = $atomic5->get();
			$arr=['request'=>$r1,'res'=>$r2,'error'=>$r3,'200ok'=>$r4,'no200'=>$r5];
			//Trace(['request'=>$r1,'res'=>$r2,'error'=>$r3,'200ok'=>$r4],'tick');
			//return;
			$filename = dirname(__FILE__) . '/log/atomic.log';
			file_put_contents($filename, date('Ymd H:i:s') . '-' . json_encode($arr));
		});
	}
});

$dir = dirname(__FILE__) . '/log/';
if (!is_dir($dir)) {
	@mkdir($dir, 0775, true);
} else {
	system("rm -rf " . $dir . '*');
}
$serv->start();