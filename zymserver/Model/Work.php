<?php
namespace Zengym\Model;

use Zengym\Lib\Protocols\ReadPackage;
use Zengym\Lib\Protocols\WritePackage;

use Zengym\Lib\Core\MainHelper;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Work
 */

class Work {

	/**
	 * socket包
	 * @var ReadPackage
	 */
	public $readPackage;

	/**
	 * 0x203，通知客户端不要再发起重连,
	 * 命令字0x302，通知所有好友我已下线,
	 * 0x201，登录成功
	 * 0x881 监控测试
	 * 0x882 重新reload
	 * 0x883 获取监控信息及状态
	 * @var type 
	 */
	private $login_0x203, $login_0x201, $login_0x302, $sys_0x881, $sys_882;

	/**
	 * SwooleModelData
	 * @var type 
	 */
	private $dataAccess;

	/**
	 * Task进程api
	 * @var Task
	 */
	private $taskApi;

	/**
	 * swoole服务
	 * @var swoole_server
	 */
	private $swoole;

	/**
	 * ip地址
	 * @var type 
	 */
	public $localIp;

	public function __construct($serv) {
		$this->readPackage = new ReadPackage(true);
		$wp = new WritePackage(true);
		$wp->Begin(0x203);
		$wp->End();
		$this->login_0x203 = $wp->GetBuffer();
		$wp->Begin(0x201);
		$wp->End();
		$this->login_0x201 = $wp->GetBuffer();
		$wp->Begin(0x302);
		$wp->End();
		$this->login_0x302 = $wp->GetBuffer();
		$wp->Begin(0x881);
		$wp->Byte(1);
		$wp->End();
		$this->sys_0x881 = $wp->GetBuffer();
		$wp->Begin(0x882);
		$wp->Byte(1);
		$wp->End();
		$this->sys_0x882 = $wp->GetBuffer();
		$this->swoole = $serv;
		$this->dataAccess = new Data();
		$this->taskApi = new Task($serv);
	}

	/**
	 * 监控测试
	 */
	public function tcp_0x881() {
		if (!$this->assert(0, 1)) {
			return;
		}
		MainHelper::I()->Send($this->sys_0x881);
	}

	/**
	 * 重新reload
	 */
	public function tcp_0x882() {
		if (!$this->assert(0, 1)) {
			return;
		}
		MainHelper::I()->Reload();
		MainHelper::I()->Send($this->sys_0x882);
	}

	/**
	 * 获取系统信息
	 */
	public function tcp_0x888() {
		if (!$this->assert(4 + 33, 1)) {
			return;
		}
		$key = $this->readPackage->String();
		if ($key != 'f35537b335a767c5b60d76863daff7af') {
			MainHelper::I()->Close();
			return;
		}
		$data = array();
		$data['status'] = $this->swoole->stats();
		$data['tableinfo'] = $this->dataAccess->getSwooleTableInfo();
		$write = new WritePackage(true);
		$write->Begin(0x888);
		$write->String(json_encode($data));
		MainHelper::I()->SendPackage($write);
	}

	/**
	 * 登录
	 */
	public function tcp_0x101() {
		if (!$this->assert(6)) {
			return;
		}
		$mid = $this->readPackage->Int();
		$midTmp = $this->dataAccess->getMidByfd(MainHelper::I()->fd);
		if ($midTmp && $midTmp != $mid) {
			MainHelper::I()->Close();
			return;
		}
		$source = $this->readPackage->Short(); // 0:移动 1:PC
		if ($mid > 0) {
			$fd = $this->dataAccess->getFdByMid($mid);
			if ($fd) {
				$smid = $this->dataAccess->getMidByfd($fd);
				if ($smid) {
					//通知客户端不要重连
					//MainHelper::I()->Send($this->login_0x203);
					return;
				}else{
					$this->dataAccess->clearUserInfo($mid, $fd);
				}
			}
			$friends = array();
			$cnt = 1000;
			while ($cnt > 0) {
				$fmid = $this->readPackage->Int();
				if (!$fmid) {
					break;
				}
				$friends[] = $fmid;
				$cnt--;
			}
			$this->dataAccess->updateConnectInfo($mid, MainHelper::I()->fd, $source, count($friends));
			$this->taskApi->ipc_1_api($mid, $friends, MainHelper::I()->fd);
			MainHelper::I()->Send($this->login_0x201);
		}
	}

	/**
	 * TCP对单个用户消息
	 */
	public function tcp_0x103() {
		if (!$this->assert(8)) {
			return;
		}
		$mid = $this->dataAccess->getMidByfd(MainHelper::I()->fd);
		if (!$mid) {
			return;
		}
		$this->_0x103();
	}

	/**
	 * 对单个用户消息
	 * @return type
	 */
	private function _0x103() {
		$mid = $this->readPackage->Int();
		$tomid = $this->readPackage->Int();
		$fd = $this->dataAccess->getFdByMid($tomid);
		if ($fd) {
			$this->swoole->send($fd, $this->readPackage->GetBuffer());
		}
	}

	/**
	 * udp对单个用户消息
	 */
	public function udp_0x103() {
		if (!$this->assert(8, 1)) {
			return;
		}
		$this->_0x103();
	}

	/**
	 * tcp广播所有用户
	 */
	public function tcp_0x104() {
		if (!$this->assert(1, false)) {
			return;
		}
		$mid = $this->dataAccess->getMidByfd(MainHelper::I()->fd);
		if (!$mid) {
			return;
		}
		$this->_0x104(0);
	}

	/**
	 * 广播所有用户
	 */
	public function _0x104($isUdp) {
		if ($isUdp) {
			$target = $this->readPackage->Short(); //2:所有用户 0:移动 1：pc
			$string = $this->readPackage->String();
			if ($target === false || $string === false) {
				return;
			}
			$wr = new WritePackage(true);
			$wr->Begin(0x104);
			$wr->String($string);
			$wr->End();
			$this->taskApi->ipc_3_api($wr->GetBuffer(), $target);
		} else {
			$this->taskApi->ipc_3_api($this->readPackage->GetBuffer(), 2);
		}
	}

	/**
	 * udp广播所有用户
	 */
	public function udp_0x104() {
		if (!$this->assert(8, true)) {
			return;
		}
		$this->_0x104(1);
	}

	/**
	 * 广播所有好友
	 */
	public function tcp_0x105() {
		if (!$this->assert(4)) {
			return;
		}
		$this->readPackage->Int(); //客户端mid不可信
		$mid = $this->dataAccess->getMidByfd(MainHelper::I()->fd);
		$fdInfo = $this->dataAccess->getUserInfoByMid($mid);
		if ($fdInfo && $fdInfo['fcnt']) {
			$this->taskApi->ipc_2_api($mid, $this->readPackage->GetBuffer());
		}
	}

	/**
	 * 进入房间[ok]
	 */
	public function tcp_0x106() {
		if (!$this->assert(8)) {
			return;
		}
		$this->readPackage->Int(); //客户端mid不可信
		$fmid = $this->dataAccess->getMidByfd(MainHelper::I()->fd);
		$tid = $this->readPackage->Int();
		if (!$tid || !$fmid) {
			return;
		}
		$this->taskApi->ipc_4_api($tid, $fmid, MainHelper::I()->fd, 0, true);
	}

	/**
	 * 退出房间[ok]
	 */
	public function tcp_0x107() {
		if (!$this->assert(4)) {
			return;
		}
		$this->readPackage->Int(); //客户端mid不可信
		$mid = $this->dataAccess->getMidByfd(MainHelper::I()->fd);
		if (!$mid) {
			return;
		}
		$userInfo = $this->dataAccess->getUserInfoByMid($mid);
		if ($userInfo['tid']) {
			$this->taskApi->ipc_4_api($userInfo['tid'], $mid, MainHelper::I()->fd, 0, false);
		}
	}

	/**
	 * 设置坐下状态，0站起，1坐下[ok]
	 */
	public function tcp_0x108() {
		if (!$this->assert(2)) {
			return;
		}
		$userInfo = $this->dataAccess->getUserInfoByFd(MainHelper::I()->fd);
		if (!$userInfo) {
			return;
		}
		$play = $this->readPackage->Short();
		$play = $play == 1 ? 1 : 0;
		$this->taskApi->ipc_4_api(intval($userInfo['tid']), intval($userInfo['mid']), MainHelper::I()->fd, $play, true);
	}

	/**
	 * PHP用这个命令字来取在线人数
	 */
	public function tcp_0x109() {
		if (!$this->assert(0, true)) {
			return;
		}
		$write = new WritePackage(true);
		$write->Begin(0x109);
		$write->Int($this->dataAccess->getOnLineCnt());
		MainHelper::I()->SendPackage($write);
	}

	/**
	 * 从php发出加密单播，推js [OK]
	 */
	public function udp_0x10E() {
		if (!$this->assert(8 + 33 + 7, true)) {
			return;
		}
		$mid = $this->readPackage->Int();
		$key = $this->readPackage->String();
		if ($key != 'cade073b2c1b6612db735a41c11853f4') {
			return;
		}
		$fd = $this->dataAccess->getFdByMid($mid);
		if ($fd) {
			$write = new WritePackage(true);
			$write->Begin(0x10E);
			$write->String($this->readPackage->String());
			$write->End();
			$this->swoole->send($fd, $write->GetBuffer());
		}
	}

	/**
	 * 较验合法性
	 * @param type $len
	 * @param type $isClose
	 * @return boolean
	 */
	private function assert($len, $islocal = false) {
		if ($this->readPackage->GetLen() < $len) {
			return false;
		}
		if ($islocal) {
			//较验是否为内网ip
			$ip = ip2long($this->localIp);
			//127.0.0.1
			//$net_a = ip2long('10.255.255.255') >> 24; //A类网预留ip的网络地址
			//$net_b = ip2long('172.31.255.255') >> 20; //B类网预留ip的网络地址
			//$net_c = ip2long('192.168.255.255') >> 16; //C类网预留ip的网络地址
			return $ip == 2130706433 || $ip >> 24 === 10 || $ip >> 20 === 2753 || $ip >> 16 === 49320;
		}
		return true;
	}

	/**
	 * 从PHP发出全桌广播，推JS [ok]
	 */
	public function udp_0x10F() {
		if (!$this->assert(8 + 33 + 2 + 7, true)) {
			return;
		}
		$tid = $this->readPackage->Int();
		$key = $this->readPackage->String();
		if (!$tid || $key != 'c801792bc8959b4842f526e8dc11b322') {
			return;
		}
		$play = $this->readPackage->Short();
		$this->taskApi->ipc_5_api($tid, $play, $this->readPackage->String());
	}

	/**
	 * 批量获取ID在线状态，支持一次最多获取1000个ID
	 */
	public function tcp_0x110() {
		if (!$this->assert(4, 1)) {
			return;
		}
		$write = new WritePackage(true);
		$write->Begin(0x110);
		$i = 1000;
		while ($i > 0) {
			$mid = $this->readPackage->Int();
			if (!$mid) {
				break;
			}
			$write->WriteInt($mid);
			$fdInfo = $this->dataAccess->getUserInfoByMid($mid);
			$stat = 0;
			//0=离线，1=大厅，2=旁观，3=在玩
			if ($fdInfo) {
				if ($fdInfo['tid']) {
					$stat = $fdInfo['play'] ? 3 : 2;
				} else {
					$stat = 1;
				}
			}
			$write->WriteByte($stat);
			$i--;
		}
		MainHelper::I()->SendPackage($write);
	}

	/**
	 * 获取单tid里的玩家状态
	 */
	public function tcp_0x887() {
		if (!$this->assert(6, 1)) {
			return;
		}
		$play = $this->readPackage->Short();
		$tid = $this->readPackage->Int();
		$this->taskApi->ipc_8_api($tid, MainHelper::I()->fd, $play);
	}

	/**
	 * 获取多tid里的玩家状态
	 */
	public function tcp_0x886() {
		if (!$this->assert(6, 1)) {
			return;
		}
		$play = $this->readPackage->Short();
		$tids = array();
		$i = 0;
		while ($i < 1000) {
			$tid = $this->readPackage->Int();
			if (!$tid) {
				break;
			}
			$tids[] = $tid;
			$i++;
		}
		if ($tids) {
			$ret = $this->taskApi->ipc_9_wait($tids, $this->swoole->worker_id, $play);
			$wr = new WritePackage(true);
			$wr->Begin(0x886);
			$wr->String(json_encode($ret));
			MainHelper::I()->SendPackage($wr);
		}
	}

}
