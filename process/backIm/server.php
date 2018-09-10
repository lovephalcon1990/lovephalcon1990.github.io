<?php
$server = new swoole_server('0.0.0.0', 9501);
$server->on('connect', function($serv, $fd) {
	$serv->send($fd, "Hello, client {$fd}!\n");
	broadcast($serv, $fd, "client {$fd} comes in");
});
$server->on('receive', function($serv, $fd, $from_id, $data) {
	$data = trim($data);
	echo microtime(true), ", got data: \"{$data}\"\n";
	broadcast($serv, $fd, $data);
});
$server->on('close', function($serv, $fd) {
	echo "{$fd} gone\n";
	broadcast($serv, $fd, 'bye bye');
});
$server->start();

function broadcast($serv, $from_fd, $data) {
	$str = trim($data);
	if (empty($str)) {
		return;
	}
	$client_fds = $serv->connection_list();
	foreach ($client_fds as $client_fd) {
		if ($client_fd != $from_fd) {
			if ($serv->exist($client_fd)) {
				$serv->send($client_fd, "client {$from_fd} says: {$data}\n");
			} else {
				echo "bad client {$client_fd}!!";
			}
		}
	}
}