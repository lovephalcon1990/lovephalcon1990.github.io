<?php
namespace Zengym\Lib\Core;
use Zengym\Lib\Core\MainHelper;
use Zengym\Lib\Helper\Log;

abstract class CrontabBase{
	/**
	 * 测试服务器是否在运行
	 */
	abstract function TestSwooleIsWorking();
	/**
	 * 使用TCP热重启服务器
	 */
	abstract function ReloadSwooleByTcp();
	/**
	 * 进程名
	 * @var type
	 */
	protected $SwooleName, $SwooleTcpPort, $SwooleUdpPort;
	/**
	 * 主进程名
	 * @var type
	 */
	public $run_processName;
	
	/**
	 * 强制重启需设置的版本号
	 */
	abstract function getKillVer();
	/**
	 * 启动Swoole
	 */
	public function Start(){
		$port = $this->SwooleTcpPort;
		$udpport = $this->SwooleUdpPort;
		//防止端口忘记配置
		if($port < 5000 || $udpport < 5000){
			die('not set port!');
		}
		
		$env = SWOOLE_ENV;
		
		$runFile = SWOOLE_ROOT .$this->SwooleDir. $this->SwooleName . '.php' .  ' ' . $env  . ' ' . $port . ' ' . $udpport;
		$psGrep = $this->SwooleName . '.php' . ' '. $env  . ' ' . $port . ' ' . $udpport;
		
		$this->run_processName = PHP_BIN . $runFile;
		//强制重启Swoole进程
		$killVer = $this->getKillVer();
		echo $killVer."\n";
		$killFile = SWOOLE_VERTMPROOT . $this->SwooleName . '.kill.ver';
		$reload = false;
		if((!is_file($killFile)) || file_get_contents($killFile) != $killVer){
			echo $killFile."===".file_get_contents($killFile)."\n";
			$this->ReloadSwooleService($runFile, $psGrep, 1, $this->SwooleName);
			file_put_contents($killFile, $killVer, null);
			$reload = true;
		}else{
			#检查进程是否存在，不存在则重启
			$check = 3;
			$working = 0;
			while($check){
				$working = $this->TestSwooleIsWorking();
				//print_r($working);echo "\n";
				if($working){
					break;
				}
				sleep(1);
				$check--;
			}
			if(!$working){
				$this->ReloadSwooleService($runFile, $psGrep, 2, $this->SwooleName);
				$reload = true;
			}
		}
		//重启work进程
		if(!$reload){
			$swooleVer = $this->getHotReloadVer();
			echo $swooleVer."\n";
			$CrontabVerFile = SWOOLE_VERTMPROOT . $this->SwooleName . '.ver';
			if((!is_file($CrontabVerFile)) || file_get_contents($CrontabVerFile) != $swooleVer){
				$check = 3;
				$send = false;
				while($check){
					$send = $this->ReloadSwooleByTcp();
					if($send){
						break;
					}
					sleep(1);
					$check--;
				}
				if(!$send){
					$this->ReloadSwooleService($runFile, $psGrep, 3, $this->SwooleName);
				}
				file_put_contents($CrontabVerFile, $swooleVer, null);
			}
		}
	}
	
	/**
	 * 获取重启的版本号
	 * @return type
	 */
	protected function getHotReloadVer(){
		$swooleverData = include_once SWOOLE_VERROOT . $this->SwooleName . '.ver.php';
		return $swooleverData['ver'];
	}
	
	/**
	 * 记录版本号
	 * @param type $swooleVer
	 */
	private function writeHotReloadVer($swooleVer){
		$SwooleVerFile = SWOOLE_VERTMPROOT . $this->SwooleName . '.ver';
		echo $SwooleVerFile."====".$swooleVer."\n";
		file_put_contents($SwooleVerFile, $swooleVer, null);
	}
	
	/**
	 * 重启Swoole进程，先kill再重启
	 * @param type $runFile
	 * @param type $psGrep
	 * @param type $runType
	 * @param type $swooleName
	 */
	public function ReloadSwooleService($runFile, $psGrep, $runType, $swooleName){
		$kill_runFile_sh = 'ps -eaf |grep "' . $psGrep . '" | grep -v "grep"| awk \'{print $2}\'|xargs kill -9';
		//主进程退出后，杀掉所有未退出的子进程，防止task进程刚启动情况
		system($kill_runFile_sh);
		sleep(1);
		system($kill_runFile_sh);
		sleep(1);
		system($kill_runFile_sh);
		$this->run_processName = PHP_BIN . $runFile;
		$run = $this->run_processName . ' > /tmp/crontab.txt 2>&1 &';
		system($run);
		//更新重启版本号
		$swooleVer = $this->getHotReloadVer();
		$this->writeHotReloadVer($swooleVer);
		//记录重启日志
		$types = array(
			1 => '被人为强制杀进程重启',
			2 => '检测到服务异常，系统强制杀进程重启',
			3 => '无法热重启，系统强制杀进程重启'
		);
		if($runType != 1){
			$ip = long2ip(MainHelper::Get_Local_Ip());
			$info = "[server error]-Swoole服务进程【" . $ip . "-" . $swooleName . "】异常:" . $types[$runType];
			
			Log::debug($info);
		}
	}
	
}