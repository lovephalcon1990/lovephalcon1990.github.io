<?php
namespace Zengym\Apps\Quene\Behivor;

use Zengym\Apps\Quene\Model\Quene;
use Zengym\Lib\Protocols\ReadPackage;
use Zengym\Lib\Protocols\WritePackage;
use Zengym\Lib\Protocols\IpcPackage;
use Zengym\Lib\Core\Behavior;
use Zengym\Lib\Core\MainHelper;
use Zengym\Lib\Helper\DB;

class QueneBehivor extends Behavior {

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
					$write = new WritePackage();
					$write->Begin($action);
					$write->Byte(1);
					MainHelper::I()->SendPackage($write);
					break;
				case "0x882"://重新reload
					MainHelper::I()->Swoole->reload();
					$write = new WritePackage();
					$write->Begin($action);
					$write->Byte(1);
					MainHelper::I()->SendPackage($write);
					break;
				case "0x883"://获取监控信息及状态
					$data['status'] = MainHelper::I()->Swoole->stats();
					$write = new WritePackage();
					$write->Begin($action);
					$write->String(json_encode($data));
					MainHelper::I()->SendPackage($write);
					break;
				case "0x884"://锦标赛数据
					oo::swooleMtt()->lpush($fd, $this->readPackage);
					break;
			}
		} catch (Exception $ex) {
			$info = $server->connection_info($fd);
			$info['exption'] = $ex;
			Swoole_Log('QueneBehivorRecev', var_export($info, 1));
		}
	}

	/**
	 * 处理UDP协议
	 * @param type $server
	 * @param type $data
	 * @param type $client_info
	 */
	public function onPacket($server, $data, $client_info) {
		$ipcPackage = IpcPackage::String2IpcPack($data);
		$ipcPackage->Fd = $client_info['address'];
		$ipcPackage->From_id = $client_info['port'];
		Quene::Process($ipcPackage);
		$this->CheckMemoryLimitAndExit();
	}

	/**
	 * 达到内存峰值时(300M)则自动退出
	 * @param type $limit
	 */
	private function CheckMemoryLimitAndExit($limit = 200) {
		$useMem = memory_get_usage(1) / 1024 / 1024;
		if ($useMem > $limit) {
			exit();
		}
	}

	/**
	 * Work/Task进程启动
	 * @global type $config
	 * @param type $serv
	 * @param type $worker_id
	 */
	public function onWorkerStart($serv, $worker_id) {
		//加载Texas初始化配置
		define('IN_WEB', true);
		define('IN_CRONTAB', true);
		ini_set('memory_limit', '512M');
		set_time_limit(0);
		$this->readPackage = new ReadPackage();
		
		//以下代码为监控信息，每10s执行一次
		$beginTime = time();
		$serv->tick(10000, function() use ($serv, $beginTime, $worker_id) {
			global $crontab_work_table;
			$useMem = memory_get_usage(1) / 1024 / 1024;
			$crontab_work_table->set($worker_id, array('workid' => $worker_id, 'beginTime' => $beginTime, 'use_mem' => $useMem));
		});
		//以下代码为存日志至mongo,每1m执行一次
		if ($worker_id == 0) {
			$serv->tick(60001, function() use ($serv, $beginTime, $worker_id) {
				$serverInfo['status'] = $serv->stats();
				$serverInfo['main'] = 0;
				global $crontab_work_table;
				foreach ($crontab_work_table as $trow) {
					$serverInfo['workinfo'][$trow['workid']] = $trow;
				}
				$localIp = MainHelper::Get_Local_Ip();
				$CacheKey = "SWOOLE_MONITOR_SERVERLIST_QUENE";
				$data = DB::instance()->get($CacheKey);
				if (!$data) {
					$data = array();
				}
				$data[$localIp] = $serverInfo;
				DB::instance()->set($CacheKey, $data);
			});
		}
	}

}
