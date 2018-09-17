<?php
/**
* 数据上报类
*/
class Report{
	const NAME_WIN = "胜局";
	const NAME_LOST = "负局";
	const NAME_PLAY = "游戏局数";
	const NAME_FACE = "表情";
	const NAME_PROP = "互动道具";
	
	/**
	* 上报lc
	*/
	public function lc($mid, $cause){
		$aKey = array(
			1 => self::NAME_WIN,
			2 => self::NAME_LOST,
			3 => self::NAME_PLAY,
			4 => self::NAME_FACE,
			5 => self::NAME_PROP
		);
		if(!isset($aKey[$cause])){
			return;
		}
		$this->sendLc($mid, array($aKey[$cause] => 1));
	}
	/**
	* 发送数据统计
	*/
	private function sendLc($mid, $aFields){
		if(SWOOLE_ENV){
			$aDemoInfo = array();
		}else{
			$aDemoInfo = array(
				'port' => Main::$cfg['logservers'][1],//swooleUDP端口
				'mongo' => Main::$class['mongo']
			);
		}
		Main::$class['transit']->lc(SWOOLE_SID, $mid, 'sicboRobot', $aFields, SWOOLE_ENV?false:true, $aDemoInfo);
	}
}