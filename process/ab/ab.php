<?php
/**
 * Created by SxdStorm.
 * User: yuemingzeng
 * Date: 2018/8/3
 * Time: 14:53
 * usr/local/php7/bin/php ab.php -c 10 -n 10 -f imserverTest.php
 */
define("ROOT",dirname(__FILE__)."/");

$opt = getopt("c:n:f:w:s:");// c:每秒并发;n:请求次数;f:加载文件;w:每次请求是否等待子进程退出;s:每隔多少秒请求一次
$c = intval($opt['c']); //每n秒并发
$n = intval($opt['n']); //共请求多少次,= $c*$n
$f = $opt['f'];
$w = isset($opt['w']) ? $opt['w'] : true; //每次请求是否等待子进程退出
$sleep = intval($opt['s']) ? intval($opt['s']) : 1; //每隔n秒执行一次

if(!$c || !$n || !$f){
	echo "参数错误 \n";
	exit;
}
$f = ROOT.$f;
if(!file_exists($f)){
	echo "文件不存在 \n";
	exit;
}
include_once $f;

$request = new swoole_atomic(0);
$success = new swoole_atomic(0);
$failure = new swoole_atomic(0);

$s1time = microtime(true);
$utime = 0;
while ($n > 0){
	$n--;
	$pids = [];
	$stime = microtime(true);
	for($i = 0; $i < $c; $i++){
		$pid = pcntl_fork();
		if($pid == -1){
			echo "can`t create sub process pid ";
			exit;
		}elseif ($pid == 0) {// sub process
			$request->add(1);
			echo $i."\n";
			$ret = test();
			if ($ret) {
				$success->add(1);
			}
			else {
				$failure->add(1);
			}
			exit(0);
		}
		else { //parent process
			$pids[] = $pid;
		}
	}
	if ($w) {
		foreach ($pids as $id) {
			pcntl_wait($id);
		}
	}
	$etime = microtime(true);
	$utime += $etime - $stime;
	sleep($sleep);
}

$e1time = microtime(true);
$cnt = $request->get();
echo "request:" . $cnt . PHP_EOL;
echo "success:" . $success->get() . PHP_EOL;
echo "failure:" . $failure->get() . PHP_EOL;
echo "utime:" . $utime*1000 . ' ms' . PHP_EOL;
$avgtime = ($utime / $cnt);
echo "avgtime:" . $avgtime*1000 . ' ms' . PHP_EOL;
echo "ab_utime:" . ($e1time - $s1time)*1000 . ' ms' . PHP_EOL;
