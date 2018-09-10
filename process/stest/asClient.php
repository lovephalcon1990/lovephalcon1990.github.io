<?php

/**
 * 处理与gameserver通信
 *
 * @author JsonChen
 */
class ASClient {

	private $requestList = array();
	public $client = false;
	private $working = false;
	public $ip;
	public $port;
	public static $Request_Cnt = 0;
	public static $Response_Cnt = 0;
	public static $Error_Cnt = 0;
	public static $Connect_cnt = 0;
	public static $workid = 0;
	public static $id = 0;
	public $_id = 0;

	public function __construct($ip, $port, $work_id) {
		self::$id++;
		$this->_id = self::$id;
		$this->ip = $ip;
		$this->port = $port;
		self::$workid = $work_id;
	}

	/**
	 * 记录日志
	 * @staticvar boolean $first
	 * @param type $type
	 * @param type $msg
	 */
	public function log($type, $msg = "") {
		$filename = dirname(__FILE__) . '/log/' . self::$workid . '_log_' . $type . '.log';
		$now = date('Ymd H:i:s');
		switch ($type) {
			case 1:
				self::$Request_Cnt++;
				file_put_contents($filename, $now . '-' . self::$Request_Cnt);
				break;
			case 2:
				self::$Response_Cnt++;
				file_put_contents($filename, $now . '-' . self::$Response_Cnt);
				break;
			case 3:
				self::$Error_Cnt++;
				file_put_contents($filename, $now . '-' . self::$Error_Cnt);
				break;
			case 4:
				file_put_contents($filename, $now . '-' . $msg, FILE_APPEND);
				break;
			case 5:
				self::$Connect_cnt++;
				file_put_contents($filename, $now . '-' . self::$Connect_cnt);
				break;
		}
	}

	/**
	 * 创建一条与gameserver通信的连接
	 */
	private function CreateClient() {
		ASClient::log(5);
		$this->working = false;
		$this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		$this->client->set(array(
			'open_length_check' => true,
			'package_length_type' => 's',
			'package_length_offset' => 6,
			'package_body_offset' => 13,
			'package_max_length' => 200000,
		));
		$this->client->on('connect', function (swoole_client $socket) {
			$this->working = true;
			$this->DoWrite();
		});
		$this->client->on('error', function (swoole_client $socket) {
			ASClient::log(3);
			$this->client = false;
			$err = swoole_strerror($socket->errCode);
			ASClient::log(4, "http-error---" . "swoole_errno:" . $err);
		});
		$this->client->on('close', function (swoole_client $socket) {
			$this->client = false;
		});
		$this->client->on('receive', function (swoole_client $socket, $data) {
			global $atomic2;
			$atomic2->add(1);
			ASClient::log(2);
			try {
				$r = new CCReadPackage();
				$rr = $r->ReadPackageBuffer($data);
				$fid = $r->ReadInt();
				$http_status = $r->ReadInt();
				if ($http_status == 200) {
					$body = $r->ReadString();
					$body = json_decode($body, true);
					//比较数据
					$ret_fid = $body['header']['HTTP_X_REQUESTED_TO'];
					if ($ret_fid != $fid) {
						global $atomic3;
						$atomic3->add(1);
					} else {
						global $atomic4;
						$atomic4->add(1);
					}
				} else {
					global $atomic5;
					$atomic5->add(1);
					ASClient::log(4, "http-error---" . "http-code:" . $http_status);
				}
			} catch (Exception $ex) {				
					ASClient::log(4, "http-error---" . "pack:" . $rr);
			}
			$this->DoWrite();
		});
		if (!$this->client->connect($this->ip, $this->port)) {
			$this->client = false;
			ASClient::log(4, "connect-error--");
		}
	}

	/**
	 * 写入gameserver_client的连接发送缓冲池
	 */
	public function DoWrite() {
		if (count($this->requestList) > 0) {
			$info = array_shift($this->requestList);
			$ret = $this->client->send($info);
			//发送失败直接断开连接并清理数据
			if (!$ret) {
				ASClient::log(4, "send-error--");
			}
		}
	}

	private $flowid = 1;
	private $flowfid = 0;

	/**
	 * 加入ccgate请求缓冲队列，并触发写入client缓冲池，
	 * @param type $data
	 */
	public function DoRequest() {
		$this->flowid++;
		$head = ['X-Requested-To' => $this->flowid,'TSLEEP'];
		$cw = new CCWritePackage();
		$cw->WriteBegin(0x0100);
		$cw->WriteByte(1);
		$cw->WriteInt($this->flowid);
		$cw->WriteString(json_encode($head));
		$cw->WriteString("data=1");
		$cw->WriteEnd();
		$data = $cw->GetPacketBuffer();
		
		global $atomic1;
		$atomic1->add(1);
		ASClient::log(1);
		array_push($this->requestList, $data);
		if (!$this->client) {
			$this->CreateClient();
		} else if ($this->working) {
			$this->DoWrite();
		}
	}

}
