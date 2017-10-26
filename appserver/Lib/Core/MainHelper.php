<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SwooleInfo
 */
namespace Zengym\Lib\Core;

use Zengym\Lib\Protocols\WritePackage;

class MainHelper {
	
	public static function I() {
		static $I = false;
		if (!$I) {
			$I = new self();
		}
		return $I;
	}
	
	public $Swoole;
	public $fd;
	public $from_id;
	
	public function Reset($swoole, $fd, $from_id) {
		$this->Swoole = $swoole;
		$this->fd = $fd;
		$this->from_id = $from_id;
	}
	
	private function __construct() {
		
	}
	
	/**
	 * socket 发信息回用户
	 * @param WritePackage $writePack
	 * @param type $fd
	 */
	public function SendPackage(WritePackage $writePack) {
		$writePack->End();
		$this->Send($writePack->GetBuffer());
	}
	
	/**
	 * 调用Swoole的send方法
	 * @param type $data
	 */
	public function Send($data) {
		$this->Swoole->send($this->fd, $data, $this->from_id);
	}
	
	/**
	 * 关闭连接
	 */
	public function Close() {
		$this->Swoole->close($this->fd);
	}
	
	/**
	 * 重启worker进程
	 */
	public function Reload() {
		$this->Swoole->reload();
	}
	
	/**
	 * 将任务转交至Task进程异步处理
	 * @param type $data
	 * @param type $dst_worker_id
	 * @return type
	 */
	public function Task($data, $dst_worker_id = -1) {
		return $this->Swoole->task($data, $dst_worker_id);
	}
	
	/**
	 * 与Worker进程通信
	 * @param type $message
	 * @param type $dst_worker_id
	 */
	public function SendMessage($message, $dst_worker_id) {
		$this->Swoole->sendMessage($message, $dst_worker_id);
	}
	/**
	 * 检测否为本地虚拟机，方便去掉调试
	 */
	public static function IsLocalVm(){
		$local_ip = self::Get_Local_Ip();
		$ipaddress = long2ip($local_ip);
		if(strpos($ipaddress, '192.168.56.')!==FALSE){
			return true;
		}
		return false;
	}
	/**
	 * 获取本机IP，优化取局域网地址
	 * @staticvar boolean $server_ip
	 * @return type
	 */
	public static function Get_Local_Ip() {
		static $server_ip = false;
		if (!$server_ip) {
			$ipList = swoole_get_local_ip();
			arsort($ipList);
			$gwip = '';
			foreach ($ipList as $ip) {
				if (strpos($ip, '192.168.') === 0) {
					$server_ip = $ip;
					break;
				}
				if (strpos($ip, '172.16.') === 0) {
					$server_ip = $ip;
					break;
				}
				if (strpos($ip, '10.') === 0) {
					$server_ip = $ip;
					break;
				}
				$gwip = $ip;
			}
			if (!$server_ip) {
				$server_ip = $gwip ? $gwip : '1.1.1.1';
			}
		}
		return ip2long($server_ip);
	}
	
}
