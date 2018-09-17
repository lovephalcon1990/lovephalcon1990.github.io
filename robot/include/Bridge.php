<?php

/*
 * 数据处理
 */

class ModelBridgePack{
	/**
	* 以|分割数据
	*/
	public static function string2Data($data){
		$jg1 = strpos($data, '|');
		$cmd = substr($data, 0, $jg1);
		
		$jg2 = strpos($data, '|', $jg1 + 1);
		$id = substr($data, $jg1 + 1, $jg2 - $jg1 - 1);
		$data = substr($data, $jg2 + 1, -2);
		return array($cmd, $id, $data);
	}
	
	/*
	* 封装数据包
	*/
	public static function data2String($cmd, $id, $data){
		if(is_array($data)){
			$data = json_encode($data);
		}
		return $cmd . '|' . $id . '|' .$data."\r\n";//以\r\n结尾
	}
}

//德州代码与游戏server沟通的桥梁
class Bridge{
	private $ip;
	private $port;
	private $client;
	private $aData;
	private $working = false;
	private $isConnect = false;//是否在连接中
	private $id = 0;
	private $aTask = array();
	public function __construct($aServer){
		list($this->ip, $this->port) = $aServer;
		$this->aData = new SplQueue();
	}
	
	/**
	* 
	*/
	public function send($cmd, $data, $fun =''){
		$this->id++;
		if($this->id > 99999999){
			$this->id = 1;
		}
		$this->aTask[$this->id] = array($cmd, $data, $fun, time());
		$data = ModelBridgePack::data2String($cmd, $this->id, $data);
		$this->DoRequest($data);
	}
	
	/**
	 * 写入gameserver_client的连接发送缓冲池
	 */
	public function DoWrite(){
		if(!$this->aData->isEmpty()){
			$info = $this->aData->shift();
			$this->client->send($info);
		}
	}
	/**
	 * 加入ccgate请求缓冲队列，并触发写入client缓冲池，
	 * @param type $data
	 */
	public function DoRequest($data){
		$this->aData->push($data);
		//array_push($this->aData, $data);
		if($this->client && $this->isConnect){
			return;
		}
		if($this->connect() && $this->working){
			$this->DoWrite();
		}
	}
	
	/**
	* 连接
	*/
	private function connect(){
		if($this->client && $this->client->isConnected()){
			return true;
		}
		$this->isConnect = true;
		$this->working = false;
		$this->client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		$this->client->set(array(
			'open_eof_check' => true, //打开EOF检测
			'package_eof' => "\r\n", //设置EOF
			'open_eof_split' => true,
		));
		$this->client->on('connect', function (swoole_client $socket){
			$this->working = true;
			$this->DoWrite();
			$this->isConnect = false;
			//$this->connect_time = time();
		});
		$this->client->on('error', function (swoole_client $socket){
			$this->client = false;
			$this->connect();
		});
		$this->client->on('close', function (swoole_client $socket){
			$this->client = false;
			$this->connect();
		});
		$this->client->on('receive', function(swoole_client $socket, $data){
			list($cmd, $id, $data) = ModelBridgePack::string2Data($data);
			if($arr = $this->aTask[$id]){
				unset($this->aTask[$id]);
				if($arr[2] && is_callable($arr[2])){
					call_user_func($arr[2], $data, $arr[0], $arr[1]);
				}
			}
			$this->DoWrite();
		});
		if(!$this->client->connect($this->ip, $this->port)){
			$this->client = false;
		}
		return false;
	}
}