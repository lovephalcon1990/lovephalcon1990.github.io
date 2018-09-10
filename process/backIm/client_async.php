<?php
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

swoole_process::signal(SIGTERM, function($sig) {
	global $child;
	swoole_process::kill($child->pid);
	swoole_process::wait();
	exit();
});

$client->on('connect', function($cli) {
	$child = new swoole_process(function($child) {
		while (true) {
			$recv = trim(read());
			if ($recv) {
				$child->write($recv);
			}
		}
	});
	$child->start();

	$GLOBALS['cli'] = $cli;
	$GLOBALS['child'] = $child;
	swoole_event_add($child->pipe, function($pipe) {
		$cli = $GLOBALS['cli'];
		$child = $GLOBALS['child'];
		$recv = trim($child->read());
		if ($recv) {
			$cli->send($recv);
		}
	});
});

$client->on('error', function($cli) {
	echo "error!\n";
});

$client->on('receive', function($cli, $data) {
	echo microtime(true), ", {$data}\n";
});

$client->on('close', function($cli) {
});

$client->connect('127.0.0.1', 9501, 1);

function read() {
	$fp = fopen('php://stdin', 'r');
	$input = fgets($fp, 255);
	fclose($fp);
	return $input;
}