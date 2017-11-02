<?php
namespace Zengym\Apps\Log\Behivor;

use Zengym\Lib\Core\Behavior;
use Zengym\Lib\Helper\Log;
use Zengym\Apps\Log\Model\DebugHandle;
use Zengym\Apps\Log\Model\MonitorHandle;
use Zengym\Lib\Protocols\UdpReadPackage;

class LogBehivor extends Behavior{
	/**
	 * 处理TCP协议
	 * @param type $server
	 * @param type $fd
	 * @param type $from_id
	 * @param type $packet_buff
	 * @throws Exception
	 */
	public function onReceive($server, $fd, $from_id, $packet_buff){
		
	}
	
	/**
	 * udp包解析
	 * @var UdpReadPackage
	 */
	private $udpPackage;
	private $working, $workCnt, $taskCnt = 0;
	
	/**
	 * 处理UDP协议
	 * @param type $server
	 * @param type $data
	 * @param type $client_info
	 */
	public function onPacket($server, $data, $client_info){
		$ret = $this->udpPackage->BeginReadBuff($data);
		if(!$ret){
			return;
		}
		//检测所有进程是否就绪
		if(!$this->working){
			global $atomic;
			$cnt = $atomic->get();
			if($cnt >= ($this->taskCnt + $this->workCnt)){
				$this->working = true;
			}else{
				return;
			}
		}
		$ret_data = false;
		switch($this->udpPackage->cmdType){
			case 0x0100://Mod\Base\Log::debug日志处理
				$ret_data = DebugHandle::Package($this->udpPackage, $client_info, $this->taskCnt);
				break;
			case 0x0200://用于server-mf数据上报
				$sid = $this->udpPackage->ReadUInt();
				$mid = $this->udpPackage->ReadUInt();
				$tableName = $this->udpPackage->ReadNewString();
				$data = $this->udpPackage->ReadNewString();
				$data = json_decode($data, true);
				if($sid && $tableName && $data){
					oo::mf()->send($sid, $mid, $tableName, $data);
				}
				Log::debug([$sid, $mid, $tableName, $data]);
				return;
		}
		if($ret_data){
			$server->task(['cmd' => $this->udpPackage->cmdType, 'data' => $ret_data['data']], $ret_data['task_id']);
		}
		$this->CheckMemoryLimitAndExit();
	}
	
	/**
	 * 处理文件调试日志
	 * @var DebugHandle
	 */
	private $logdebugHandle;
	/**
	 * 性能监控日志
	 * @var MonitorHandle
	 */
	private $monitoringHandle;
	
	/**
	 * Task进程处理
	 * @param type $serv
	 * @param type $task_id
	 * @param type $from_id
	 * @param type $data
	 */
	public function onTask($serv, $task_id, $from_id, $data){
		switch($data['cmd']){
			case 0x0100://Mod\Base\Log::debug日志处理
				$this->logdebugHandle->Log($data['data']);
				break;
			case 0x0200:
				$this->monitoringHandle->Fetch($data['data']);
				break;
		}
	}
	
	/**
	 * 达到内存峰值时(300M)则自动退出
	 * @param type $limit
	 */
	private function CheckMemoryLimitAndExit($limit = 300){
		$useMem = memory_get_usage(1) / 1024 / 1024;
		if($useMem > $limit){
			exit();
		}
	}
	
	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id){
		define('IN_WEB', true); //初始化配置
		define('IN_CRONTAB', true);
		ini_set('memory_limit', '512M');
		set_time_limit(0);
		$this->udpPackage = new UdpReadPackage();
		global $atomic;
		$atomic->add(1);
		$this->workCnt = $serv->setting['worker_num'];
		$this->taskCnt = $serv->setting['task_worker_num'];
		if($serv->taskworker){
			$this->logdebugHandle = new DebugHandle();
			$this->monitoringHandle = new MonitorHandle();
		}
	}
	
}