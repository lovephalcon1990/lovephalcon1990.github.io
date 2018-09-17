<?php
class Tick{
	public $mainTimer;
	
	public function __construct(){
		$this->mainTimer = new Timer();
	}
	
	public function init(){
		//主业务定时器
		self::_swooleTick(500, array($this->mainTimer, 'triger'));
		self::_swooleTick(9000, array('ModelHandler', 'heartbeat'));//心跳
		self::_swooleTick(100000, array($this, 'sysInfo'));//记录内存
	}
	
	/**
	 * 添加一个延迟执行定时任务
	 * @param callbck $callback
	 * @param array $data
	 * @param int $time
	 * 
	 * @return string/null
	 */
	public function after($callback, $data, $time){
		if (!is_array($callback)){
			return null;
		}
		$tickId = $this->mainTimer->tick($callback, $data, $time);
		return $tickId;
	}
	
	/**
	 * 超内存退出
	 */
	public static function sysInfo(){
		$useMem = memory_get_usage(1) / 1024 / 1024;
		$num = count(ModelHandler::$aClient);
		fun::logs('SicboRobot/mem.txt', date('Y-m-d H:i:s').' '.Main::$worker_id.' >> mem:'. $useMem .  ' num:'. $num);
		if($useMem > 60){//超过内存 退出
			Main::$swoole->stop();
		}
	}
	
	
	/**
	 * 
	 * @param string $tickId
	 * @return boolean
	 */
	public function del($tickId){
		$ret = $this->mainTimer->del($tickId);
		return $ret;
	}
	
	
	
	
	private function _swooleTick($time, $callback){
		Main::$swoole->tick($time, function() use ($callback){
			try {
				call_user_func($callback);
			} catch (Throwable $ex) {
				$aErr = array('swooleTick' ,$ex->getMessage(), $ex->getFile() . ' on line:' . $ex->getLine());
				Main::logs($aErr , 'exErr');
			}
		});
	}
	
	
	
}
