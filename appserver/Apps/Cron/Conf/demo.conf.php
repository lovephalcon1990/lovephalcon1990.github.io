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
		'message_queue_key'=>9980,
		'task_worker_num' => 4,
		'task_max_request' => 100,
		'task_ipc_mode'=>3
	)
);
