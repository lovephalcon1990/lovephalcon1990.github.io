<?php
namespace Zengym\Apps\Log\Behivor;

use Exception;
use Zengym\Lib\Core\Behavior;
use Zengym\Lib\Core\MainHelper;
use Zengym\Lib\Protocols\ReadPackage;
use Zengym\Lib\Protocols\WritePackage;

class LogBehivor extends Behavior {

	/**
	 * 处理包信息
	 * @var ReadPackage
	 */
	private $readPackage;

	/**
	 * 处理TCP协议
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 * @param type $packet_buff
	 * @throws Exception
	 */
	public function onReceive($server, $fd, $from_id, $packet_buff) {
		try {
			$this->readPackage->ReadPackageBuffer($packet_buff);
			MainHelper::I()->Reset($server, $fd, $from_id);
			$action = $this->readPackage->GetCmdType();
			var_dump(dechex($this->readPackage->CmdType));
			echo "\n";
			switch ($action) {
				case "0x881"://Test
					$write = new WritePackage();
					$int = filter_var($action, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX); //php7 字符0x881不会转换16进制
					var_dump($int);
					$write->Begin($int);
					$write->Byte(1);
					$write->End();
					MainHelper::I()->Send($write->GetBuffer());
					$this->readPackage->ReadPackageBuffer($write->GetBuffer());
					var_dump($this->readPackage);
					
					//MainHelper::I()->SendPackage($write);
					break;
				case "0x882"://重新reload
					MainHelper::I()->Swoole->reload();
					$write = new WritePackage();
					$write->Begin($action);
					$write->Byte(1);
					MainHelper::I()->SendPackage($write);
					break;
				case "0x883"://获取监控信息及状态
					Cron::GetMonitorInfo($action);
					break;
			}
		} catch (Exception $ex) {
			$info = $server->connection_info($fd);
			$info['exption'] = $ex;
			Swoole_Log('CronBehivorRecev', var_export($info, 1));
		}
	}

	
	/**
	 * 处理Task异步任务
	 * @param type $serv
	 * @param type $task_id
	 * @param type $from_id
	 * @param type $data
	 */
	public function onTask($serv, $task_id, $from_id, $data) {
		
	}
	
	
	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id) {
		$this->readPackage = new ReadPackage();
	}

}
