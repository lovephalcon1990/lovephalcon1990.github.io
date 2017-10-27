<?php
namespace Zengym\Apps\Cron\Behivor;

use Exception;
use Zengym\Lib\Core\Behavior;
use Zengym\Lib\Core\MainHelper;
use Zengym\Lib\Protocols\ReadPackage;
use Zengym\Lib\Protocols\WritePackage;
use Zengym\Apps\Cron\Model\Cron;
use Zengym\Apps\Cron\Model\CronConfig;

class CronBehivor extends Behavior {

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
			switch ($action) {
				case "0x881"://Test					
					Cron::TestAndClearCrontabRunCnt();
					$write = new WritePackage();
					//if(IS_PHP7){
						$action = $this->readPackage->CmdType;
					//}
					$write->Begin($action);
					$write->Byte(1);
					MainHelper::I()->SendPackage($write);
					break;
				case "0x882"://重新reload
					//if(IS_PHP7){
						$action = $this->readPackage->CmdType;
					//}
					MainHelper::I()->Swoole->reload();
					$write = new WritePackage();
					$write->Begin($action);
					$write->Byte(1);
					MainHelper::I()->SendPackage($write);
					break;
				case "0x883"://获取监控信息及状态
					//if(IS_PHP7){
						$action = $this->readPackage->CmdType;
					//}
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
	 * 达到内存峰值时(300M)则自动退出
	 * @param type $limit
	 */
	private function CheckMemoryLimitAndExit($server,$limit = 200) {
		$useMem = memory_get_usage(1) / 1024 / 1024;
		if ($useMem > $limit) {
			system("kill -15 " . $server->worker_pid);
			usleep(50);
			if($useMem>300){				
				exit();
			}
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
		$cmdArr = explode('|', $data);
		if (count($cmdArr) > 1) {
			$beginTime = microtime(true);
			$beginUseMem = memory_get_usage(1);
			$processName = $cmdArr[0];
			$method = $cmdArr[1];
			$GLOBALS['crontab_method'] = $method;
			$workid = $serv->worker_id;
			$result = Cron::SaveCrontabRunCnt($processName, true, $workid);
			if (!$result) {
				return;
			}
			eval($method);
			Cron::SaveCrontabRunCnt($processName, false, $workid);
			//$this->destoryCache();
			Cron::SaveMonitorInfo($beginTime, $method,$beginUseMem);
		}
		Cron::SaveWorkerRequestCount($serv->worker_id);
		$this->CheckMemoryLimitAndExit($serv);
	}
	
	

	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id) {
		//加载swoole初始化配置
		ini_set('memory_limit', '512M');
		define('IN_WEB', true);
		define('IN_CRONTAB', true);
		set_time_limit(0);
		Cron::SaveWorkerRunInfo($worker_id);
		Cron::SaveMonitorInfoToLocal();
		
		if (!$serv->taskworker) {
			$this->readPackage = new ReadPackage();
			//前4个工作进程
			if ($worker_id > 4) {
				return;
			}
			//Work进程启动，执行定时任务
			$processList = Cron::GetCrontabConfig(Cron::ProcessListName);
			if (empty($processList)) {
				return;
			}
			$ticks = array();
			foreach ($processList as $process) {
				if (!in_array($process['interval'], $ticks)) {
					$ticks[] = $process['interval'];
				}
			}
			
			foreach ($ticks as $interval) {
				$serv->tick($interval, function() use ($serv, $interval) {
					$workid = $serv->worker_id;
					$processList = Cron::GetCrontabConfig(Cron::ProcessListName);
					foreach ($processList as $name => $process) {
						if (($process['interval'] == $interval) && (!isset($process['workid']) || ($workid == $process['workid']))) {
							Cron::CrontabProcess($serv, $name);
						}
					}
				});
			}
		}
	}

}
