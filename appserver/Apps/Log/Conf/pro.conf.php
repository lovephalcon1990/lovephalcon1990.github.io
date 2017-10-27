<?php

//如需判断站点，使用TSWOOLE_SID,当前作用域还不存在 oo::$config['sid'];
$onSlave = array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'reactor_num' => 2,
		'worker_num' => 3,
		'max_request' => 20000,
		'dispatch_mode' => 2,
		'task_worker_num' => 10,
		'task_max_request' => 600,
		'task_ipc_mode' => 3,
		'open_length_check' => true,
		'package_length_type' => 'n',
		'package_length_offset' => 0,
		'package_body_offset' => 2,
		'package_max_length' => 80000,
		'message_queue_key' => 9980
	)
);