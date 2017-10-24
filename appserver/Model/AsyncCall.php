<?php
/**
 * 异步调用类
 * AsyncCall::add添加一个异步调用
 * 通过UDP执行AsyncCall::push方法加入redis队列
 * 再由swoole定时执行AsyncCall::exec方法进行处理
 */
namespace Zengym\Model;

use Zengym\Lib\Helper\DB;
use Zengym\Lib\Helper\Log;
use Zengym\Apps\Cron\Model\Cron;

class AsyncCall{
	private static $key = 'UDP|CB';//缓存key

	/**
	 * 添加异步调用
	 * @param string $obj 调用对象和方法，如'oo::minfo->set'
	 * @param array $args 调用参数列表，数据组型，如参数为：1,array('a'=>2) 则传参 array(1,array('a'=>2))
	 * @param int $delay 单位秒 延时多少秒后执行
	 * @return boolean
	 */
	public static function add($obj, $args, $delay = 0){
		if(!$obj || !is_array($args)){
			return false;
		}
		$aData = array(oo::$config['sid'], $obj, $args);
		if($delay > 0){
			$aData[] = (int)$delay;
		}
		oo::swoolequene()->SendToCallBack(json_encode($aData));//通过swoole udp，然后会调用AsyncCall::push方法
		return true;
	}

	/**
	 * 添加到队列，参见：crontab/Swoole/Apps/Quene/Model/SwooleModelUdp.php
	 * @param type $sData
	 * @return type
	 */
	public static function push($sData){
		$aData = json_decode($sData, true);
		if(($delay = (int)$aData[3]) && defined('SWOOLE')){//延时执行
			swoole_timer_after($delay*1000, function($sData){
				self::lPush($sData);
			}, $sData);
			return true;
		}else{
			return self::lPush($sData);
		}
	}

	/**
	 * 执行异步调用 crontab/Swoole/Apps/Crontab/Model/SwooleCrontabConfig.php
	 */
	public static function exec(){
		if(!defined('SWOOLE')) return;
		$count = 100;
		while($count > 0){
			$count--;
			$sdata = self::rPop();
			$sdata = trim($sdata);
			if(!$sdata){
				return;
			}
			$begin_microTime = microtime(true);
			$begin_usemem = memory_get_usage(1);
			$func = self::evals($sdata);
			Cron::SaveMonitorInfo($begin_microTime, 'callback|'.$func, $begin_usemem);
		}
	}

	/**
	 * 获取队列长度，为后台展示提供接口
	 * @return int
	 */
	public static function length(){
		return self::cache()->lSize(self::$key);
	}

	//****************************以下为私有方法********************************************************

	/**
	 * 本类用的cache
	 * @return \Redis
	 */
	private static function cache(){
		return DB::instance();
	}

	/**
	 * 执行插入队列操作
	 * @param string $sData
	 * @return int
	 */
	private static function lPush($sData){
		$length = self::cache()->lPush(self::$key, $sData);
		if($length === false){
			$msg = '[lPush error] line:' . __LINE__;
			Log::debug($msg);
		}
		return $length;
	}

	/**
	 * 执行的时候从队列中取出一个
	 * 原 rpop
	 */
	private static function rPop(){
		for($try = 0; $try < 3; $try++){
			if($res = (string)self::cache()->rPop(self::$key)){
				break;
			}
			self::cache()->close();
		}
		return $res;
	}

	/**
	 * 实际执行
	 * 原 dorun
	 * @param string $sdata
	 * @return boolean
	 */
	private static function evals($sdata){
		$aData = json_decode($sdata, true);//[sid, func, args, delay]

		if(empty($aData)){
			return false;
		}
		if(count($aData) <= 2){//新增$aData是否为空及长度是否<=2，防止代码往下执行告警
			return false;
		}

		$args = '';
		foreach((array)$aData[2] as $k => $v){//组装参数
			if(!is_scalar($v) || is_bool($v)){
				$param = var_export($v, true);
				$args .= $param . ',';
			}else{
				$param = substr($v, 0, 5) == 'array' ? $v .',' : "'". $v ."',";
				$args .= $param;
			}
		}
		$args = substr($args, 0, -1);
		$scall = $aData[1] . '(' . $args . ');';
		$GLOBALS['crontab_method'] = $scall .'(callback)';//用于发生错误的时候获取错误信息
		$evalRet = eval($scall);
		if($evalRet === false){
			$error = error_get_last();
			Log::debug(array($scall, $error));
		}
		return $aData[1];
	}
}