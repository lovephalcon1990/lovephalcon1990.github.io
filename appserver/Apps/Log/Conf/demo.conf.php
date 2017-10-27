<?php

return array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'reactor_num' => 2,
		'worker_num' => 2,
		'dispatch_mode' => 2,
		'max_request'=>20000,
		'open_length_check'=>true,
		'package_length_type'=>'n',
		'package_length_offset'=>0,
		'package_body_offset'=>2,
		'package_max_length'=>80000,
		'message_queue_key'=>99580,
		'task_worker_num' => 2,
		'task_max_request' => 100,
		'task_ipc_mode'=>3
	)
);




//return array(
//	"Host" => "0.0.0.0",
//	"Set" => array(
//		'worker_num' => 2,
//		'dispatch_mode' => 3,
//		'discard_timeout_request'=>true,
//		'enable_unsafe_event'=>true,
//		'task_max_request'=>0,
//		'task_worker_num' => 2,
//		'task_ipc_mode' => 1,
//		'task_max_request'=>0,
//		'heartbeat_check_interval'=>10,
//		'heartbeat_idle_time'=>60,
//		'open_length_check'=>true,
//		'package_length_type'=>'n',
//		'package_length_offset'=>0,
//		'package_body_offset'=>2,
//		'package_max_length'=>65537,
//		'message_queue_key'=>9980
//	)
//);
