<?php
/**
* 管理员命令
*/
class Admin{
	public function init($fd, $cmd, $aData){
		$cinfo = Main::$swoole->connection_info($fd);
		$localIp = $cinfo['remote_ip'];
		$ip = ip2long($localIp);
		if (!($ip == 2130706433 || $ip >> 24 === 10 || $ip >> 20 === 2753 || $ip >> 16 === 49320)){
			Main::$swoole->close($fd);
			return;
		}
		Main::logs($cmd. ' '.$localIp, 'adminCmd');
		$data = 'ok';
		switch($cmd){
			case 1://只重启task进程
				break;
			case 2://重启所有进程
				Main::$swoole->reload();
				break;
			case 3: //查看系统信息
				$aServerInfo['stats'] = Main::$swoole->stats();//系统信息
				$aServerInfo['cfg'] = Main::$cfg;//系统信息
				$aServerInfo['mem'] = memory_get_usage(1) / 1024 / 1024;
				global $mid_table;
				$aSum = array();
				$aMids = array();
				foreach($mid_table as $mid=>$aInfo){
					$aSum[$aInfo['ante']]++;
					$aMids[] = $mid;
				}
				$aServerInfo['mids'] = $aMids;
				$aServerInfo['userNum'] = $aSum;
				$data = $aServerInfo;
				break;
			case 4://清除数据
				global $mid_table;
				foreach($mid_table as $mid=>$aInfo){
					$mid_table->del($mid);
				}
				break;
			case 5: //重新加载配置 仅仅task进程数据
				$this->sendCfg($aData);
				break;
			case 6://停服命令 仅仅task进程数据
				break;
			case 7://关闭服务器
				Main::$swoole->shutdown();
				break;
		}
		$this->sendPack($fd, $data);
	}
	
	public function sendCfg($aData){
		for($i=0;$i<Main::$swoole->setting['worker_num'];$i++){
			if($i == Main::$worker_id){
				Main::setCfg($aData);
			}else{
				$buff = json_encode($aData);
				Main::$swoole->sendMessage('setCfg|'.$buff, $i);
			}
		}
	}
	
	private function sendPack($fd, $data){
		Main::$swoole->send($fd, ModelHandler::data2String($fd, 0x888, is_array($data) ? $data : array('ret'=>$data)));
	}
}