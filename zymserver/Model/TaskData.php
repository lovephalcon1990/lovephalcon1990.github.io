<?php
namespace Zengym\Model;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SwooleModelTaskData
 */
class TaskData extends Data {

	private $frinedData = array();

	/**
	 * 更新数据[只允许task进程访问]
	 * @param type $mid
	 * @param type $data
	 * @return int
	 */
	public function updateFriend($mid, $frineds) {
		$this->frinedData[$mid] = $frineds;
	}

	/**
	 * 清空好友数据
	 * @param type $mid
	 */
	public function clearFriendInfo($mid) {
		unset($this->frinedData[$mid]);
	}

	/**
	 * 获取好友信息
	 * @param type $mid
	 * @return type
	 */
	public function getFriend($mid) {
		if(isset($this->frinedData[$mid])){
			return $this->frinedData[$mid];
		}
		return array();
	}

	/**
	 * [0=>fd,play=0站起，1：坐下]
	 * @var type 
	 */
	private $tableInfos = array();

	/**
	 * 更新桌子信息
	 * @param type $mid
	 * @param type $fd
	 * @param type $tid
	 * @param type $play
	 * @param type $in 1:进入，0：退出
	 */
	public function updateTableInfo($mid, $fd, $tid, $play, $in) {
		$connectionInfo = $this->getUserInfoByMid($mid);
		if ($connectionInfo['tid'] && $connectionInfo['tid'] != $tid) {
			$this->clearTableInfo($tid, $mid);
		}
		if ($in) {
			if (isset($this->tableInfos[$tid])) {
				$this->tableInfos[$tid][$mid] = array($fd, $play);
			} else {
				$this->tableInfos[$tid] = array(
					$mid => array($fd, $play)
				);
			}
			$this->updateConnectInfo_Table($mid, $tid, $play);
		} else {
			$this->clearTableInfo($tid, $mid);
			$this->updateConnectInfo_Table($mid, 0, 0);
		}
	}

	/**
	 * 获取桌子信息
	 * @param type $tid
	 * @return type
	 */
	public function getTableInfo($tid) {
		if(isset($this->tableInfos[$tid])){
			return $this->tableInfos[$tid];
		}
		return array();
	}

	/**
	 * 清空桌子数据
	 * @param type $mid
	 */
	public function clearTableInfo($tid, $mid) {
		if (isset($this->tableInfos[$tid])) {
			unset($this->tableInfos[$tid][$mid]);
			if (count($this->tableInfos[$tid]) == 0) {
				unset($this->tableInfos[$tid]);
			}
		}
	}

}
