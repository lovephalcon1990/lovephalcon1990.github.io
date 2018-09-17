<?php

/**
 * swoole 相关配置
 */
return array(
	"Host" => "0.0.0.0",
	"Set" => array(
		'worker_num' => 3,
		'dispatch_mode' => 3,
		'max_request'=>0,
		'message_queue_key'=>65535 + SWOOLE_PORT*10,
		'package_max_length' => 65535,
		'open_length_check'=> true,
		'package_length_offset' => 0,
		'package_body_offset' => 4,
		'package_length_type' => 'N',
		'heartbeat_check_interval'=>100,
		'heartbeat_idle_time'=>300,
		'discard_timeout_request'=>true,
	)
);
