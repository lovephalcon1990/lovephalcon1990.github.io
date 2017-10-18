<?php
namespace Zengym\Server;
use swoole_server;

//注：此文件修改后需要重启Swoole服务
class Service {

	private $_Config;
	public $Swoole;

	public function __construct($Config) {
		$this->_Config = $Config;
		$this->Swoole = new swoole_server($this->_Config['Host'], $this->_Config['Port'], SWOOLE_PROCESS,
			$this->_Config['SocketType']);
	}

	private function LogErr(Exception $ex = null, $server = null, $fd = 0) {

		//var_dump($ex);
	}

	/**
	 * @var SwooleBehavior 
	 */
	private $_Behavior;

	public function onWorkerStart($serv, $worker_id) {
		if (function_exists('apc_clear_cache')) {
			apc_clear_cache();
		}
		if (function_exists('opcache_reset')) {
			opcache_reset();
		}
		try {
			$arr = $this->_Config;
			$this->_Behavior = new $arr['Behavior'][1]();
			$pid = $serv->master_pid;
			if (isset($arr['MainProcessName'])) {
				if ($serv->taskworker) {
					swoole_set_process_name($arr['MainProcessName'] . ' task_pid_' . $pid);
				} else {
					swoole_set_process_name($arr['MainProcessName'] . ' worker_pid_' . $pid);
				}
			}
			$this->_Behavior->onWorkerStart($serv, $worker_id);
		} catch (Exception $ex) {
			$this->LogErr($ex, $serv);
		}
	}

	public function onReceive($server, $fd, $from_id, $data) {
		try {
			$this->_Behavior->onReceive($server, $fd, $from_id, $data);
		} catch (Exception $ex) {
			$this->LogErr($ex, $server);
		}
	}

	public function onTask($server, $task_id, $from_id, $data) {
		try {
			$this->_Behavior->onTask($server, $task_id, $from_id, $data);
		} catch (Exception $ex) {
			$this->LogErr($ex, $server);
		}
	}

	public function onFinish($serv, $task_id, $data) {
		try {
			$this->_Behavior->onFinish($serv, $task_id, $data);
		} catch (Exception $ex) {
			$this->LogErr($ex, $serv);
		}
	}

	public function onWorkerError($serv, $worker_id, $worker_pid, $exit_code) {
		
	}

	public function onWorkerStop($server, $worker_id) {
		try {
			$this->_Behavior->onWorkerStop($server, $worker_id);
		} catch (Exception $ex) {
			$this->LogErr($ex, $server);
		}
	}

	public function onPacket($server, $data, $client_info) {
		try {
			$this->_Behavior->onPacket($server, $data, $client_info);
		} catch (Exception $ex) {
			$this->LogErr($ex, $server);
		}
	}

	public function onPipeMessage($server, $from_worker_id, $message) {
		try {
			$this->_Behavior->onPipeMessage($server, $from_worker_id, $message);
		} catch (Exception $ex) {
			$this->LogErr($ex, $server);
		}
	}

	public function onClose($server, $fd, $from_id) {
		try {
			$this->_Behavior->onClose($server, $fd, $from_id);
		} catch (Exception $ex) {
			$this->LogErr($ex, $server);
		}
	}

	public function onStart($server) {
		
	}

	public function Start($clearMsg = true) {
		if ($clearMsg) {
			if (isset($this->_Config['Set']['message_queue_key'])) {
				$messagekey = sprintf("0x%08x", intval($this->_Config['Set']['message_queue_key']));
				system('ipcrm -Q ' . $messagekey);
			}
		}
		$this->Swoole->set($this->_Config['Set']);
		$events = array(
			"Start",
			"Shutdown",
			"WorkerStart",
			"WorkerStop",
			"Timer",
			"Connect",
			"Receive",
			"Close",
			"Task",
			"Finish",
			"PipeMessage",
			"WorkerError",
			"ManagerStart",
			"ManagerStop", "Packet");
		foreach ($events as $event_name) {
			$event_fun = 'on'.$event_name;
			if (method_exists($this, $event_fun)){
				$this->Swoole->on($event_name, array($this, $event_fun));
			}
		}
		$this->Swoole->start();
	}

}
