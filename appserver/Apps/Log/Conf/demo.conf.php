<?php

return array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'worker_num' => 1,
		'dispatch_mode' => 3,
		'task_worker_num' => 2,
		'task_ipc_mode' => 2,
		'message_queue_key' => SWOOLE_UDPPORT * 10 + 65535,
	)
);