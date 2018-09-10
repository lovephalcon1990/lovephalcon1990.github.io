<?php
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
$client->on('connect', function($cli) {
	$cli->send('hi, server!');
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
