<?php
namespace Zengym\Apps\Cron;
use Zengym\Model\SClient;
use Zengym\Lib\Core\CrontabBase;

class CronMonitor extends CrontabBase {
	/**
	 * @var SClient
	 */
	private $SwooleModel;

	public function __construct() {
		$this->SwooleModel = new SClient();
		$this->SwooleName = 'cronSwoole';
		$this->SwooleDir = "Apps/Cron/";
		$this->SwooleTcpPort = $this->SwooleModel->SwooleTcpPort;
		$this->SwooleUdpPort = $this->SwooleModel->SwooleUdpPort;
	}

	public function TestSwooleIsWorking() {
		return $this->SwooleModel->TestSwooleIsWorking();
	}

	public function ReloadSwooleByTcp() {
		return $this->SwooleModel->ReloadSwooleByTcp();
	}

	public function getKillVer(){
		return 20171026;
		//return $this->getHotReloadVer();
	}

}
