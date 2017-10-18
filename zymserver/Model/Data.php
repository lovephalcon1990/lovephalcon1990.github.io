<?php
namespace Zengym\Model;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Data
 *
 */
class Data {

	public function updateConnectInfo($mid, $fd, $source, $friendcnt) {
		global $socket_table;
		$data = array(
			'fd' => $fd,
			'play' => 0,
			'tid' => 0,
			'mid' => $mid,
			'fcnt' => $friendcnt,
			'source' => $source
		);
		$socket_table->set($mid, $data);
		global $socket_mid_table;
		$socket_mid_table->set($fd, array('mid' => $mid));
	}

	/**
	 * 更新连接数据
	 * @global type $socket_table
	 * @param type $mid
	 * @param type $play
	 */
	public function updateConnectInfo_Table($mid, $tid, $play) {
		global $socket_table;
		$data = $socket_table->get($mid);
		if ($data) {
			//TODO:当前发现 $socket_table 与 $socket_mid_table 在执行一段时间后数量会对不上
			$fd_mid = $this->getMidByfd($data['fd']);
			if ($fd_mid != $mid) {
				return;
			}
			$data['tid'] = $tid;
			$data['play'] = $play;
			return $socket_table->set($mid, $data);
		}
		return false;
	}

	/**
	 * 获取在线人数
	 * @global type $socket_mid_table
	 * @return type
	 */
	public function getOnLineCnt() {
		global $socket_mid_table;
		return $socket_mid_table->count();
	}

	public function getSwooleTableInfo() {
		global $socket_mid_table;
		$smt_cnt = $socket_mid_table->count();
		global $socket_table;
		$st_cnt = $socket_table->count();
		return array('midt_cnt' => $smt_cnt, 'st_cnt' => $st_cnt);
	}

	/**
	 * 获取fd对应的mid
	 * @global type $socket_mid_table
	 * @param type $fd
	 * @return int
	 */
	public function getMidByfd($fd) {
		global $socket_mid_table;
		$fdinfo = $socket_mid_table->get($fd);
		if (isset($fdinfo['mid'])) {
			return intval($fdinfo['mid']);
		}
		return 0;
	}

	/**
	 * 获取mid对应的fd连接
	 * @global type $socket_table
	 * @param type $mid
	 * @return int
	 */
	public function getFdByMid($mid) {
		global $socket_table;
		$fdinfo = $socket_table->get($mid);
		if (isset($fdinfo['fd'])) {
			return intval($fdinfo['fd']);
		}
		return 0;
	}

	/**
	 * 通过mid获取用户信息
	 * @global type $socket_table
	 * @param type $mid
	 * @return type
	 */
	public function getUserInfoByMid($mid) {

		global $socket_table;
		$fdinfo = $socket_table->get($mid);
		return $fdinfo;
	}

	/**
	 * 通过fd获取用户信息
	 * @param type $fd
	 * @return type
	 */
	public function getUserInfoByFd($fd) {
		$mid = $this->getMidByfd($fd);
		if (!$mid) {
			return array();
		}
		return $this->getUserInfoByMid($mid);
	}

	/**
	 * 获取所有连接fd
	 * @global type $socket_table
	 * @global type $source 2:所有，0：移动,1:PC
	 * @return type
	 */
	public function getAllFds($source = 2) {
		$fds = array();
		global $socket_table;
		foreach ($socket_table as $fdInfo) {
			if ($source == 2 || $source == $fdInfo['source']) {
				$fds[] = $fdInfo['fd'];
			}
		}
		return $fds;
	}

	/**
	 * 获取所有socket数据
	 * @global type $socket_mid_table
	 * @global type $source 2:所有，0：移动,1:PC
	 * @return type
	 */
	public function getAllSocketInfo($source = 2) {
		$sockets = array();
		global $socket_table;
		foreach ($socket_table as $fdInfo) {
			if ($source == 2 || $source == $fdInfo['source']) {
				$sockets[] = $fdInfo;
			}
		}
		return $sockets;
	}

	/**
	 * 清空数据
	 * @param type $mid
	 */
	public function clearUserInfo($mid, $fd) {
		//清空表数据
		global $socket_table;
		global $socket_mid_table;
		$socket_table->del($mid);
		$socket_mid_table->del($fd);
	}

}
