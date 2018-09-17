<?php
/**
*
*/
class Main{
	static $swoole;
	static $worker_id;
	static $readPackage;
	static $cfg = array();//配置数据
	static $class = array();//类
	
	public static function workerStart($serv, $worker_id){
		date_default_timezone_set('Asia/Shanghai');
		set_time_limit(0);
		ini_set('memory_limit', '512M');
		
		self::$swoole = $serv;
		self::$worker_id = $worker_id;
		
		self::$class['admin'] 		= new Admin();
		self::$class['report'] 		= new Report();
		self::$class['tick'] 		= new Tick();
		self::$class['doPack'] 		= new DoPack();
		self::$class['data'] 		= new Data();
		self::$class['dataPool']    = new DataPool();
		
		self::loadCfg(1);//加载cfg数据
		
//		self::$class['transit'] 	= new Transit(SWOOLE_SID, self::$cfg['transit']);
//		SWOOLE_ENV or self::$class['mongo'] 		= new MongoHelper(self::$cfg['mongo']);//测试环境才用到mongo
		
		//self::loadFromCache();
		self::$class['data']->start();//数据加载
		self::$class['tick']->init();//定时任务启动
		self::logs('workerStart', 'sys');
	}
	
	
	/**
	 * 加载cfg目录下配置
	 */
	public static function loadCfg($isStart=0){
		if(!$isStart && function_exists('opcache_reset')) {
			opcache_reset();
		}
		$cfgFile = CFG_ROOT.'service.php';
		if(file_exists($cfgFile)){
			$cfg = include $cfgFile;
			self::$cfg = array_merge(self::$cfg, (array)$cfg);
		}
	}
	
	/**
	 * 从缓存中加载配置
	 */
	public static function setCfg($acfg){
		self::$class['dataPool']->aPhpCfg = $acfg;
		self::$cfg = array_merge(self::$cfg, (array)$acfg);
	}
	
	/**
	 * 接收数据
	 */
	public static function onReceive($fd, $packet_buff){
		ModelHandler::init($fd, $packet_buff);
	}
	
	/**
	 * task任务处理
	 * Main::$swoole->task(array($method, $args));
	 */
	public static function doTask($data){
		
	}
	
	/**
	 * 连接断开了
	 */
	public static function onClose($fd){
		
	}
	
	/**
	 * 收到由sendMessage发送的管道消息时会触发
	 */
	public static function onPipeMessage($fromId, $message){
		$jg1 = strpos($message, '|');
		$fd = substr($message, 0, $jg1);
		$packet_buff = substr($message, $jg1 + 1);
		if($fd == 'setCfg'){
			Main::logs($packet_buff, 'setCfg');
			return self::setCfg(json_decode($packet_buff, true));
		}
		ModelHandler::init($fd, $packet_buff);
	}
	
	/**
	 * 
	 */
	public static function onWorkerStop(){
		self::logs('onWorkerStop', 'sys');
		self::$class['data']->stop();//数据加载
	}
	
	/**
	 * register_shutdown_function 注册函数调用
	 */
	public static function processEnd(){
		self::logs('processEnd', 'sys');
		self::$class['data']->stop();//数据加载
	}
	
	/**
	 * 分配work进程
	 * @param type $dispatch 当前以tid/mid来求模分配
	 */
	public static function dispatch($dispatch=0){
		$dispatch = intval($dispatch);
		$workerNum = self::$swoole->setting['worker_num'];
		$workerId = $dispatch % $workerNum;
		return $workerId;
	}
	
	/*
	 * 日志记录
	 */
	public static function logs($msg, $file='syslog', $size=1){
		if(is_array($msg)){
			$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
		}
		$msg = date('Y-m-d H:i:s').' '.self::$worker_id. ' '.$msg;
		if(self::$class['transit']){
			self::$class['transit']->logs('SicboRobot/'.$file , $msg, $size);
		}else{
			fun::logs($file, $msg);
		}
	}
}