<?php
namespace Zengym\Apps\Log;
use Zengym\Lib\Core\CrontabBase;

class LogMonitor extends CrontabBase {
	

	public function __construct() {
		$this->SwooleName = 'LogService';
		$this->SwooleTcpPort = 9852; //只需要监听udp端口,此端口无用的
		$this->SwooleUdpPort = 9852;
		$this->SwooleName = 'logSwoole';
		$this->SwooleDir = "Apps/Log/";
		
	}
	
	
	public function TestSwooleIsWorking() {
		$cntShell = "ps -eaf |grep \"" . $this->run_psGrep . "\" | grep -v \"grep\"|wc -l";
		echo $cntShell."\n";
		$cnt = system($cntShell);
		if ($cnt >= 1) {
			echo "not-exists\n";
			return false;
		}
		return true;
	}
	
	public function ReloadSwooleByTcp() {
		return true;
	}
	
	public function getKillVer() {
		
		return '201710271811';
	}

}
