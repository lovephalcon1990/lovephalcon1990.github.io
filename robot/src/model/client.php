<?php
class ModelClient{
	private $mid;
	private $aQueue;
	private $client = false;
	public $ip;
	public $port;
	private $sysClose = 0; //是否系统端口连接
	public $aData = array();//保存交互过程中的数据
	
	public function __construct($mid){
		$this->mid = $mid;
		$this->aQueue = new SplQueue();
		$this->aData['mtime'] = time();//记录最后收到包时间
		list($this->ip, $this->port) = Main::$cfg['server'];
	}
	
	/**
	* 发送登录包
	*/
	public function sendLogin(){
		if(!$this->aData['login']){
			return;
		}
		ModelHandler::sendPack($this->mid, 0x101, $this->aData['login']);
	}
	/**
	* 代理转发包
	*/
	public function send($cmd, $data){
		if($this->client && $this->client->isConnected() && $this->client->send($data)){
			return true;
		}
		if($cmd != 0x101){//先发登录包
			$this->sendLogin();
		}
		$this->aQueue->push($data);
		$this->connect();//重新连接
	}
	
	/**
	* 关闭链接
	*/
	public function close(){
		if($this->client && $this->client->isConnected()){
			$this->sysClose = 1;
			$this->client->close();
			$this->client = false;
		}
	}
	
	/**
	* 连接成功后
	*/
	private function doSend(){
		while(count($this->aQueue)){
			$data = $this->aQueue->shift();
			$this->client->send($data);
		}
	}
	/**
	 * 创建一条与gameserver通信的连接
	 */
	private function connect(){
		$this->close();
		$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
//		$client->set(array(
//			'open_length_check' => true,
//			'package_length_type' => 's',
//			'package_length_offset' => 5,
//			'package_body_offset' => 7,
//			'package_max_length' => 2000,
//		));
		$client->on('connect', function (swoole_client $socket){
			$this->doSend();
		});
		$client->on('error', function (swoole_client $socket){
			$this->client = false;
			ModelHandler::onClose($this->mid);
		});
		$client->on('close', function (swoole_client $socket){
			if($this->sysClose){
				$this->sysClose = 0;
			}else{
				$this->client = false;
				ModelHandler::onClose($this->mid);
			}
		});
		$client->on('receive', function(swoole_client $socket, $data){
			try{
				$this->aData['mtime'] = time();//记录最后收到包时间
				ModelHandler::revPack($this->mid, $data);
			}catch(Throwable $ex){
				$aErr = array('connect' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
				Main::logs($aErr , 'exErr');
			}
		});
		if($client->connect($this->ip, $this->port)){
			$this->client = $client;
		}else{
			$this->client = false;
			ModelHandler::onClose($this->mid);
		}
	}
}