<?php
namespace Zengym\Model;
use Zengym\Lib\Protocols\IpcPackage;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Task
 *
 */
class Task {

	/**
	 * 数据访问
	 * @var SwooleModelTaskData 
	 */
	private $dataAccess;

	/**
	 * swoole服务
	 * @var swoole_server
	 */
	private $swoole;

	/**
	 * 进程间通信数据格式
	 * @var IpcPackage 
	 */
	public $ipcPackage;

	public function __construct($swoole) {
		$this->swoole = $swoole;
		$this->dataAccess = new TaskData(true);
	}

	/**
	 * 分配task进程
	 * @param type $dispatch 当前以tid/mid来求模分配
	 */
	public function dispatch($dispatch) {
		$dispatch = intval($dispatch);
		$taskNum = $this->swoole->setting['task_worker_num'];
		if (!$dispatch) {
			//没有的话随机取一个方便求模
			$dispatch = rand(1, $taskNum);
		}
		$taskId = $dispatch % $taskNum;
		return $taskId;
	}

	private function work2task($ipcdata, $dispatch = 0) {
		if (!$dispatch) {
			$dispatch = $ipcdata->Fd;
		}
		$this->swoole->task(IpcPackage::IpcPack2String($ipcdata), $this->dispatch($dispatch));
	}

	/**
	 * 保存好友，work调用
	 */
	public function ipc_1_api($mid, $friends) {
		if (!$friends || !$mid) {
			return;
		}
		$data = json_encode($friends);
		$ipcdata = new IpcPackage($mid, 0, 1, $data);
		$this->work2task($ipcdata);
	}

	/**
	 * 保存好友数据
	 * @return type
	 */
	public function ipc_1() {
		$mid = $this->ipcPackage->Fd;
		$data = $this->ipcPackage->Data;
		$this->dataAccess->updateFriend($mid, json_encode($data, true));
	}

	/**
	 * 广播好友,work调用
	 */
	public function ipc_2_api($mid, $data) {
		$ipcdata = new IpcPackage($mid, 0, 2, $data);
		$this->work2task($ipcdata);
	}

	/**
	 * 广播好友
	 */
	public function ipc_2() {
		$mid = $this->ipcPackage->Fd;
		$data = $this->ipcPackage->Data;
		$friends = $this->dataAccess->getFriend($mid);
		if (!is_array($friends)) {
			return;
		}
		foreach ($friends as $fmid) {
			$fd = $this->dataAccess->getFdByMid($fmid);
			if ($fd) {
				$this->swoole->send($fd, $data);
			}
		}
	}

	/**
	 * 广播全部人,work调用
	 */
	public function ipc_3_api($data, $target) {
		$ipcdata = new IpcPackage($target, 0, 3, $data);
		$this->work2task($ipcdata);
	}

	/**
	 * 广播全部人
	 */
	public function ipc_3() {
		$target = $this->ipcPackage->Fd;
		$data = $this->ipcPackage->Data;
		$fds = $this->dataAccess->getAllFds($target);
		foreach ($fds as $fd) {
			$this->swoole->send($fd, $data);
		}
	}

	/**
	 * 保存桌子数据-api
	 */
	public function ipc_4_api($tid, $mid, $fd, $play, $in = 1) {
		$ipcdata = new IpcPackage($mid, $fd, 4, array('tid' => $tid, 'play' => $play, 'in' => $in));
		$this->work2task($ipcdata, $tid);
	}

	/**
	 * 保存桌子数据
	 */
	public function ipc_4() {
		$mid = $this->ipcPackage->Fd;
		$fd = $this->ipcPackage->From_id;
		$data = $this->ipcPackage->Data;
		$data = json_decode($data, true);
		$this->dataAccess->updateTableInfo($mid, $fd, $data['tid'], $data['play'], $data['in']);
	}

	/**
	 * 从PHP发出全桌广播，推JS [ok]
	 * @param type $tid
	 * @param type $data
	 */
	public function ipc_5_api($tid, $play, $data) {
		$ipcdata = new IpcPackage($tid, $play, 5, $data);
		$this->work2task($ipcdata, $tid);
	}

	/**
	 * 从PHP发出全桌广播，推JS [ok]
	 * @param type $tid
	 * @param type $play 2=全桌用户，1坐下状态的用户，0站起用户
	 * @param type $data
	 */
	public function ipc_5() {
		$data = $this->ipcPackage->Data;
		$tid = $this->ipcPackage->Fd;
		$play = $this->ipcPackage->From_id;
		$write = new WritePackage(true);
		$write->WriteBegin(0x10E);
		$write->WriteString($data);
		$write->WriteEnd();
		$package = $write->GetPacketBuffer();
		$tableInfo = $this->dataAccess->getTableInfo($tid);
		if (!is_array($tableInfo)) {
			return;
		}
		$i = 0;
		foreach ($tableInfo as $mid => $val) {
			if (($play == 2) || ($play == 1 && $val[1] == 1) || ($play === 0 && $val[1] === 0)) {
				$this->swoole->send($val[0], $package);
			}
			$i++;
			if ($i > 200) {
				break;
			}
		}
	}

	/**
	 * 清除桌子数据-api
	 * @param type $tid
	 */
	public function ipc_6_api($tid, $mid) {
		$ipcdata = new IpcPackage($tid, $mid, 6, '');
		$this->work2task($ipcdata, $tid);
	}

	/**
	 * 清除桌子数据
	 */
	public function ipc_6() {
		$tid = $this->ipcPackage->Fd;
		$mid = $this->ipcPackage->From_id;
		$this->dataAccess->clearTableInfo($tid, $mid);
	}

	/**
	 * 清除好友数据
	 */
	public function ipc_7_api($mid) {
		$ipcdata = new IpcPackage($mid, 0, 7, '');
		$this->work2task($ipcdata, $mid);
	}

	/**
	 * 清除好友数据
	 */
	public function ipc_7() {
		$mid = $this->ipcPackage->Fd;
		$this->dataAccess->clearFriendInfo($mid);
	}

	/**
	 * 获取单tid里的玩家状态-api
	 */
	public function ipc_8_api($tid, $from_fd, $play) {
		$ipcdata = new IpcPackage($tid, $from_fd, 8, $play);
		$this->work2task($ipcdata, $tid);
	}

	/**
	 * 获取单tid里的玩家状态
	 */
	public function ipc_8() {
		$tid = $this->ipcPackage->Fd;
		$from_fd = $this->ipcPackage->From_id;
		//1获取坐下在玩的玩家 2旁观 3所有	
		$play = $this->ipcPackage->Data;
		$tableInfo = $this->dataAccess->getTableInfo($tid);
		$result = array();
		foreach ($tableInfo as $mid => $val) {
			if ($play == 3 || ($play == 1 && $val[1] == 1) || ($play == 2 && $val[1] == 0)) {
				$result[$val[1]][] = $mid;
			}
		}
		$wr = new WritePackage(true);
		$wr->WriteBegin(0x887);
		$wr->WriteString(json_encode($result));
		$wr->WriteEnd();
		$this->swoole->send($from_fd, $wr->GetPacketBuffer());
	}

	/**
	 * 获取多tid里的玩家状态-wait 堵塞进程
	 * @param type $tids
	 */
	public function ipc_9_wait($tids, $workid, $play) {
		$taskids = array();
		foreach ($tids as $tid) {
			$dist_workid = $this->dispatch($tid);
			if (!isset($taskids[$dist_workid])) {
				$taskids[$dist_workid] = array();
			}
			$taskids[$dist_workid][] = $tid;
		}
		$ret = array();
		foreach ($taskids as $taskid => $tidsTmp) {
			$ipcdata = new IpcPackage($workid, $play, 9, $tidsTmp);
			$result = $this->swoole->taskwait(IpcPackage::IpcPack2String($ipcdata), 0.5, $taskid);
			$result = json_decode($result, true);
			if ($result) {
				$ret+=$result;
			}
		}
		return $ret;
	}

	/**
	 * 获取多tid里的玩家状态-直接通过finish返回数据
	 * @param type $tids
	 */
	public function ipc_9() {
		$workid = $this->ipcPackage->Fd;
		$data = $this->ipcPackage->Data;
		//1获取坐下在玩的玩家 2旁观 3所有
		$play = $this->ipcPackage->From_id;
		$tids = json_decode($data, true);
		$result = array();
		foreach ($tids as $tid) {
			$tableInfo = $this->dataAccess->getTableInfo($tid);
			if ($tableInfo) {
				foreach ($tableInfo as $mid => $val) {
					if (($play == 3) || ($play == 1 && $val[1] == 1) || ($play == 2 && $val[1] == 0)) {
						if (!isset($result[$tid])) {
							$result[$tid] = array();
						}
						if (!isset($result[$tid][$val[1]])) {
							$result[$tid][$val[1]] = array();
						}
						$result[$tid][$val[1]][] = $mid;
					}
				}
			}
		}
		$data = json_encode($result);
		$this->swoole->finish($data);
	}

}
