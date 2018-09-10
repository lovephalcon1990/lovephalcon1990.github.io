<?php
$client = new swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
$client->connect('127.0.0.1', 9501, 1);
if ($client->isConnected()) {
	$child = new swoole_process(function($child) {
		global $client;
		while (true) {
			$recv = trim(read());
			if ($recv) {
				$client->send($recv);
			}
		}
	});
	$child->start();
	while (true) {
		$recv = trim(@$client->recv());
		if ($recv) {
			echo microtime(true), ": {$recv}\n";
		}
	}
} else {
	echo "error!\n";
}
function read() {
	$fp = fopen('php://stdin', 'r');
	$input = fgets($fp, 255);
	fclose($fp);
	return $input;
}