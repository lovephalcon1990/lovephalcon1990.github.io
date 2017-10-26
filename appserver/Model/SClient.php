<?php
namespace Zengym\Model;
use Zengym\Lib\Helper\Log;
use Zengym\Lib\Protocols\IpcPackage;
use Zengym\Lib\Protocols\WritePackage;
use Zengym\Lib\Protocols\ReadPackage;
use swoole_client;
use Exception;

class SClient{
	
	public  $SwooleTcpPort=9850;
	public  $SwooleUdpPort=9850;
	
	public function __construct(){
		
	}
	
	/**
	 * 测试Swoole是否在工作
	 * @return int
	 */
	public function TestSwooleIsWorking(){
		return self::TestSwoole('127.0.0.1', $this->SwooleTcpPort);
	}
	
	/**
	 * 重启指定的swoole进程
	 */
	public function ReloadSwooleByTcp() {
		return self::ReloadSwoole('127.0.0.1', $this->SwooleTcpPort);
	}
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
	
	/**
	 * 测试Swoole是否正常
	 * @param type $ip
	 * @param type $port
	 * @param type $action
	 * @return boolean
	 */
	public static function TestSwoole($ip, $port, $action = 0x881) {
		$sockpack = new WritePackage();
		$sockpack->Begin($action);
		$sockpack->Short(1);
		$sockpack->End();
		$readPackage = self::SendAndRecive($ip, $port, $sockpack->GetBuffer());
		if ($readPackage) {
			return $readPackage->Byte();
		}
		return false;
	}
	
	/**
	 * 创建连接并发送数据
	 * @param type $ip
	 * @param type $port
	 * @param type $tcpData
	 * @param type $timeOut
	 * @return ReadPackage  当===false时，表示出现协议问题
	 */
	public static function SendAndRecive($ip, $port, $tcpData, $timeOut = 1) {
		$client = self::CreateClientAndConnect($ip, $port, $timeOut);
		if ($client) {
			return self::SendAndReciveByClient($client, $tcpData);
		}
		return false;
	}
	
	
	/**
	 * 创建SwooleClient并建立连接
	 * @param type $ip
	 * @param type $port
	 * @param type $timeOut
	 */
	public static function CreateClientAndConnect($ip, $port, $timeOut = 1,$keep = false) {
		if($keep){
			$client = new swoole_client(SWOOLE_SOCK_TCP|SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
		}else{
			$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
		}
		$client->set(array(
			'open_length_check' => 1,
			'package_length_type' => 'n',
			'package_length_offset' => 0, //第N个字节是包长度的值
			'package_body_offset' => 2, //第几个字节开始计算长度
			'package_max_length' => 80000, //协议最大长度
		));
		$result = $client->connect($ip, $port, $timeOut);
		if ($result) {
			return $client;
		}
		return false;
	}
	
	/**
	 * 发送并接收数据
	 * @param type $swoole_client
	 * @param type $tcpData
	 * @return \ReadPackage 当===false时，表示出现协议问题
	 */
	public static function SendAndReciveByClient($swoole_client, $tcpData,$isEncrypt=false) {
		try {
			$swoole_client->send($tcpData);
			$responseData = $swoole_client->recv();
			$readPackage = new ReadPackage($isEncrypt);
			$readPackage->ReadPackageBuffer($responseData);
			return $readPackage;
		} catch (Exception $ex) {
			Log::debug(var_export($ex, 1), "SwooleClient.log");
		}
		return false;
	}
	
	/**
	 * 重启Swoole
	 * @param type $ip
	 * @param type $port
	 * @param type $action
	 * @return boolean
	 */
	public static function ReloadSwoole($ip, $port, $action = 0x882) {
		$sockpack = new WritePackage();
		$sockpack->Begin($action);
		$sockpack->Byte(2);
		$sockpack->End();
		$readPackage = self::SendAndRecive($ip, $port, $sockpack->GetBuffer());
		if ($readPackage) {
			return $readPackage->Byte();
		}
		return false;
	}
}