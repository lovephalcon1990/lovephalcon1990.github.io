<?php
namespace Zengym\Apps\Cron\Model;

use Zengym\Lib\Core\MainHelper;
use Zengym\Lib\Protocols\WritePackage;
use Zengym\Lib\Helper\DB;
use Zengym\Lib\Helper\Log;

/**
 * Description of Cron
 */
class Cron {
	
	const ProcessListName = 'processList';
	const ProcessMaxCnt = 'processMaxCnt';
	
	/**
	 * 通过代码脚本 获取进程内部运行信息，用于调试
	 * @global type $monitor_table
	 * @global type $crontab_table
	 * @global type $crontab_task_table
	 * @param ReadPackage $readPack
	 */
	public static function GetMonitorInfo($action) {
		$write = new WritePackage();
		$write->Begin($action);
		global $monitor_table;
		$logList = array();
		foreach ($monitor_table as $arr) {
			$logList[] = $arr;
		}unset($arr);
		foreach ($logList as &$log) {
			$log['avg_runtime'] = $log['runtime'] / $log['runcnt'] * 1000;
			$log['avg_runtime'] = ceil($log['avg_runtime']);
			$log['begintime'] = date('Y-m-d H:i:s', $log['begintime']);
			$log['lasttime'] = date('Y-m-d H:i:s', $log['lasttime']);
		}
		$ret['logList'] = $logList;
		$ret['status'] = MainHelper::I()->Swoole->stats();
		$processList = self::GetCrontabConfig(self::ProcessListName);
		$cronList = array();
		$cronTimeList = array();
		//获取并发控制数据
		global $crontab_table;
		global $crontab_time_table;
		foreach ($processList as $pname => $pval) {
			$cronList[$pname] = $crontab_table->get($pname);
			$cronTimeList[$pname] = $crontab_time_table->get($pname);
		}
		$cronList[self::ProcessListName] = $crontab_table->get(self::ProcessListName);
		$ret['cronList'] = $cronList;
		$ret['cronTime'] = $cronTimeList;
		//$ret['processList'] = $processList;

		global $crontab_task_table;
		$taskInfos = array();
		foreach ($crontab_task_table as $task) {
			$taskInfos[$task['workid']] = $task;
		}
		$ret['taskInfo'] = $taskInfos;
		$write->String(json_encode($ret));
		MainHelper::I()->SendPackage($write);
	}	
	/**
	 * 记录Swoole运行情况信息，用于CMS显示监控分析使用
	 * @global type $monitor_table
	 * @global type $CrontabService
	 * @global type $crontab_work_table
	 */
	public static function SaveMonitorInfoToLocal() {
		global $monitor_table;
		$load = sys_getloadavg();
		$local_ip = MainHelper::Get_Local_Ip();
		DB::instance()->hSet("system_getloadavg", $local_ip, $load);
		$logList = array();
		$methods = array();
		foreach ($monitor_table as $row) {
			$logList[] = $row;
			$methods[] = $row['method'];
		}
		unset($row);
		//取完后清空数据
		foreach ($methods as $k) {
			$monitor_table->del($k);
		}
		
		//记录异步任务执行情况
		$cacheKey = "SWOOLE_MONITOR_LOGLIST|" . $local_ip;
		$data = DB::instance()->get($cacheKey);
		if (!is_array($data)) {
			$data = array();
		}
		foreach ($logList as $row) {
			//去掉特殊字符，并且只保留前40个字节
			$method =preg_replace("/[\. *<>\"]/", "",$row['method']);
			$method= substr($method, 0,40);
			if(!$method){
				continue;
			}
			if (isset($data[$method])) {
				$data[$method]['lasttime'] = $row['lasttime'];
				$data[$method]['runtime'] = $row['runtime'] + $data[$method]['runtime'];
				$data[$method]['runcnt'] = $row['runcnt'] + $data[$method]['runcnt'];
				$data[$method]['reveal_mem'] = $row['reveal_mem'];
				$data[$method]['reveal_cnt'] = $row['reveal_cnt'] + $data[$method]['reveal_cnt'];
				$data[$method]['maxtime'] = $row['maxtime'] > $data[$method]['maxtime'] ? $row['maxtime'] : $data[$method]['maxtime'];
				$data[$method]['maxtime_logtime'] = $row['maxtime'] > $data[$method]['maxtime'] ? $row['maxtime_logtime'] : $data[$method]['maxtime_logtime'];
			} else {
				$data[$method]['begintime'] = $row['begintime'];
				$data[$method]['lasttime'] = $row['lasttime'];
				$data[$method]['runtime'] = $row['runtime'];
				$data[$method]['runcnt'] = intval($row['runcnt']);
				$data[$method]['reveal_cnt'] = $row['reveal_cnt'];
				$data[$method]['reveal_mem'] = $row['reveal_mem'];
				$data[$method]['maxtime'] = $row['maxtime'];
				$data[$method]['maxtime_logtime'] = $row['maxtime_logtime'];
			}
		}
		foreach ($data as $method=>$val){
			if(!$method){
				unset($data[$method]);
			}
		}
		DB::instance()->set($cacheKey, $data);

		//记录服务器SWoole进程执行情况		
		global $CrontabService;
		$serverInfo['status'] = $CrontabService->Swoole->stats();
		$tasking_num = intval($serverInfo['status']['tasking_num']);
		
		if ($tasking_num > 100) {
			$pcip=  long2ip($local_ip);
			self::SendMonitorInfo('ip:'.$pcip.',swoole定时任务tasking队列过多，请检查swoole进程信息是否正常，tasking_num:' . $tasking_num);
		}
		$serverInfo['main'] =  1;
		global $crontab_work_table;
		foreach ($crontab_work_table as $trow) {
			$serverInfo['workinfo'][$trow['workid']] = $trow;
		}
		$CacheKey = "SWOOLE_MONITOR_SERVERLIST";
		$data = DB::instance()->get($CacheKey);
		if (!$data) {
			$data = array();
		}
		//$data[$local_ip] = $serverInfo;
		DB::instance()->set($CacheKey, $data);
	}

	public static function SendMonitorInfo($msg) {
		Log::debug($msg);
	}

	/**
	 * 保存每个work进程的请求次数及请求时间
	 * @global type $crontab_work_table
	 * @param type $workid
	 */
	public static function SaveWorkerRequestCount($workid) {
		global $crontab_work_table;
		$useMem = memory_get_usage(1) / 1024 / 1024;
		$ret = $crontab_work_table->get($workid);
		if (is_array($ret)) {
			$ret['use_mem'] = $useMem;
			$ret['requestcount']+=1;
			$ret['lastTime'] = time();
			$crontab_work_table->set($workid, $ret);
		}
	}

	/**
	 * 保存每个work进程的启动时间及内存使用情况
	 * @global type $crontab_work_table
	 * @param type $workid
	 */
	public static function SaveWorkerRunInfo($workid) {
		global $crontab_work_table;
		$useMem = memory_get_usage(1) / 1024 / 1024;
		$crontab_work_table->set($workid, array('workid' => $workid, 'beginTime' => time(), 'use_mem' => $useMem, 'requestcount' => 0, 'lastTime' => time()));
	}

	/**
	 * 用于控制task进程并发执行任务的计数器
	 * @global type $crontab_table
	 * @global type $crontab_task_table
	 * @param type $crontab_name
	 * @param type $add
	 * @param type $workid
	 */
	public static function SaveCrontabRunCnt($crontab_name, $add, $workid) {
		//用于监控某个方法是否执行是否超出预期
		global $crontab_time_table;
		static $firstRun = 1;
		global $crontab_table;
		global $crontab_task_table;	
		if ($add && !$firstRun) {
			//并发执行检测，解决同一类型同一时刻队列积压情况下导致 并发同步问题.			
			$processList = self::GetCrontabConfig(self::ProcessListName);
			$process = $processList[$crontab_name];
			$run_process_max_cnt = self::getCnt($crontab_name);
			if ($run_process_max_cnt >= $process['cnt']) {
				return false;
			}
		}
		$crontab_time_table->set($crontab_name, array('time' => time()));
		$task_key = 'swooletask_' . $workid;
		if ($add) {
			$crontab_table->incr($crontab_name, 'cnt', 1);
			$crontab_table->incr(self::ProcessListName, 'cnt', 1);
			//用于检测上次执行的信息执行完后 计数器没有减1
			$task_cnt = $crontab_table->incr($task_key, 'cnt', 1);
			if ($firstRun && $task_cnt > 1) {
				$arr = $crontab_task_table->get($workid);
				if (isset($arr['crontab_name'])) {
					self::err_decrCrontabCnt($arr['crontab_name'], $arr, $workid, $arr['begintime']);
				}
			}			
			$work_taskInfo = array(
				'workid' => $workid,
				'crontab_name' => $crontab_name,
				'begintime' => time(),
			);
			$crontab_task_table->set($workid, $work_taskInfo);
			$firstRun = 0;
		} else {
			$crontab_table->decr($crontab_name, 'cnt', 1);
			$crontab_table->decr(self::ProcessListName, 'cnt', 1);
			$crontab_table->set($task_key, array('cnt' => 0));
		}
		return true;
	}

	/**
	 * 处理因未知异常导致的计数器错误，修复计数器
	 * @global type $crontab_time_table
	 * @global type $crontab_table
	 * @param type $crontab_name
	 */
	public static function err_decrCrontabCnt($crontab_name, $data = array(), $workid = 0, $time = 0) {
		global $crontab_time_table;
		global $crontab_table;
		$crontab_table->decr($crontab_name, 'cnt', 1);
		$crontab_table->decr(self::ProcessListName, 'cnt', 1);
		$crontab_time_table->set($crontab_name, array('time' => time()));
		$log = array(
			'crontabName' => $crontab_name,
			'table_time' => date('Ymd H:i:s', $time),
			'workid' => $workid,
		);
		$log['data'] = $data;
		Log::debug($log);
	}

	/**
	 * 定时检测swoole是否正常，并且修复因table坑产生的计数器问题
	 */
	public static function TestAndClearCrontabRunCnt() {
		global $crontab_time_table;
		global $crontab_table;
		//检测进程10分钟内未完成，则减计数器
		$processList = self::GetCrontabConfig(self::ProcessListName);
		foreach ($processList as $pname => $pval) {
			$arrTime = $crontab_time_table->get($pname);
			$curTime = time();
			if (isset($arrTime['time']) && ($curTime - $arrTime['time']) > 600) {
				$arrCnt = $crontab_table->get($pname);
				if (isset($arrCnt['cnt']) && $arrCnt['cnt'] > 0) {
					self::err_decrCrontabCnt($pname, $arrTime, 0, $arrTime['time']);
				}
			}
		}
	}

	/**
	 * 保存每个任务的执行时间【不清楚-慎用，有可能超出swoole_table行数会报错】
	 * @global type $monitor_table
	 * @param type $begin_microTime
	 * @param type $processName
	 */
	public static function SaveMonitorInfo($begin_microTime, $processName, $begin_usemem = 0) {	
		if(strpos($processName,'|')!==false){
			$processName = preg_replace("/[\d]+/", 'int', $processName);
		}
		$processName = str_replace('$', '_', $processName);
		$processName = str_replace('/', '_', $processName);
		$processName = str_replace('\\', '_', $processName);
		$processName = str_replace('\0', '_', $processName);
		$processName = str_replace('.', '_', $processName);
		if(!$processName){
			return;
		}
		global $monitor_table;
		if ($monitor_table->count() > 1024) {
			return;
		}
		if($begin_usemem){
			$usemem = memory_get_usage(1) - $begin_usemem;
			$usemem = $usemem / 1024 / 1024;
		}else{
			$usemem = 0;
		}		
		$arr = $monitor_table->get($processName);
		$useTime = microtime(true) - $begin_microTime;
		$maxTime = intval($useTime*1000);
		$tmparr = array();
		$tmparr['method'] = $processName;
		$now = time();
		$reveal_cnt = $usemem >= 1 ? 1 : 0;
		if (is_array($arr) && $arr['runcnt'] < 100000 && $arr['runtime'] < 86400000) {
			$arr['maxtime'] = intval($arr['maxtime']);
			$tmparr['begintime'] = $arr['begintime'];
			$tmparr['lasttime'] = $now;
			$tmparr['runtime'] = $arr['runtime'] + $useTime;
			$tmparr['runcnt'] = $arr['runcnt'] + 1;
			$tmparr['reveal_mem'] = $usemem;
			$tmparr['reveal_cnt'] = intval($arr['reveal_cnt']) + $reveal_cnt;
			$tmparr['maxtime'] = $arr['maxtime'] > $maxTime ? $arr['maxtime'] : $maxTime;
			$tmparr['maxtime_logtime'] = $arr['maxtime'] > $maxTime ? $arr['maxtime_logtime'] : $now;
		} else {
			$tmparr['begintime'] = intval($begin_microTime);
			$tmparr['lasttime'] = $now;
			$tmparr['runtime'] = $useTime;
			$tmparr['runcnt'] = 1;
			$tmparr['reveal_mem'] = $usemem;
			$tmparr['reveal_cnt'] = $reveal_cnt;
			$tmparr['maxtime'] = $maxTime;
			$tmparr['maxtime_logtime'] = $now;
		}
		$monitor_table->set($processName, $tmparr);
	}

	

	/**
	 * 获取定时任务的配置
	 * @global type $CrontabService
	 * @staticvar boolean $cfg
	 * @param type $type
	 * @return type
	 */
	public static function GetCrontabConfig($type) {
		static $cfg = false;
		if (!$cfg) {
			$cfg[self::ProcessListName] = CronConfig::getMainRunList(SWOOLE_ENV === 1);
			$cnt = 0;
			foreach ($cfg[self::ProcessListName] as $cfg1) {
				$cnt+=$cfg1['cnt'];
			}
			global $CrontabService;
			$cfg[self::ProcessMaxCnt] = $cnt;
		}
		return $cfg[$type];
	}

	/**
	 * 从 swoole_table中获取 计数器值
	 * @global type $crontab_table
	 * @param type $key
	 * @return type
	 */
	public static function getCnt($key) {
		global $crontab_table;
		$cnt = 0;
		$cntArr = $crontab_table->get($key);
		if (isset($cntArr['cnt'])) {
			$cnt = intval($cntArr['cnt']);
		}
		return $cnt;
	}

	/**
	 * 发送任务执行指令至 task
	 * @param type $serv
	 * @param type $name
	 * @return type
	 */
	public static function CrontabProcess($serv, $name) {
		$cnt = 1;
		$processList = self::GetCrontabConfig(self::ProcessListName);
		//获取总的进程数限制配置
		$cfg_all_max_cnt = self::GetCrontabConfig(self::ProcessMaxCnt);
		$process = $processList[$name];
		if(isset($process['runTime']) && is_array($process['runTime']) && !self::checkRunTime($process['runTime']))
		{//检查时间是否满足
			return;
		}
		//获取当前进程的进程数限制配置
		$cfg_process_max_cnt = $process['cnt'] > $cfg_all_max_cnt ? $cfg_all_max_cnt : $process['cnt'];
		while (1) {
			//获取当前进程的进程数
			$run_process_max_cnt = self::getCnt($name);
			//获取总的进程数
			$run_all_max_cnt = self::getCnt(self::ProcessListName);			
			//不能超出总进程数及当前限制进程数
			if ($run_all_max_cnt < $cfg_all_max_cnt && $run_process_max_cnt < $cfg_process_max_cnt && $cnt <= $cfg_process_max_cnt) {
				if(strpos($name,'genTableList')!==false){
					Log::debug($process['method']);
				}
				$serv->task($name . '|' . $process['method']);
				$cnt++;
			} else {
				return;
			}
		}
	}
	
	/**
	 * 检查时间
	 */
	public static function checkRunTime($aTime){
		if(!is_array($aTime)){
			return true;
		}
		$Hi = date('H:i');
		foreach($aTime as $stime){
			$aT = explode('-' , $stime);
			if(count($aT) != 2){
				return false;
			}
			if($aT[0] <= $Hi && $Hi <= $aT[1]){//满足条件
				return true;
			}
		}
		return false;
	}
	

}
