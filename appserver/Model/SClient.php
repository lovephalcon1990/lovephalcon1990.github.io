<?php
namespace Zengym\Model;
use Zengym\Lib\Helper\Log;
use Zengym\Lib\Protocols\IpcPackage;
use swoole_client;

class SClient{
	
	/**
	 * 发送至Udp
	 * @param type $ip
	 * @param type $port
	 * @param type $udpData
	 * @return boolean
	 */
	public static function SendByUdp($ip, $port, $action, $contentData) {
		$ipcPackage = new IpcPackage(0, 0, $action, $contentData);
		$udpData = IpcPackage::IpcPack2String($ipcPackage);
		if (strlen($udpData) > 8000) {
			//udp包大于8K时会出问题，做检测，并记录
			if (defined('IN_WEB')) {
				$udpInfo = date('Ymd H:i:s') . PHP_EOL;
				$udpInfo.= Log::debug_backtrace();
				$udpInfo = $udpInfo . 'udp包超出8K,size:' . strlen($udpData);
				Log::debug($udpInfo, 'swoole_monitor.txt');
				if (strlen($udpData) > 65500) {
					return false;
				}
			}
		}
		$client = new swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
		$client->connect($ip, $port);
		$client->send($udpData);
	}
}