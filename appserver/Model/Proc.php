<?php
namespace Zengym\Model;
use Zengym\Lib\Helper\Log;
use Zengym\Lib\Helper\DB;
use Zengym\Apps\Cron\Model\Cron;
/**
 * 由swoole调用定时执行proc
 * @staticvar boolean $first
 * @param type $procid
 * @return mixed
 */
class Proc{
	
	private static $keyfix = "PROC";
	
	public static function doProc($procid){
		if(!defined('SWOOLE')){
			return false;
		}
		static $first = false;
		$count = 100;
		while($count > 0){
			$count--;
			if(!$content = self::pop($procid)){ //队列中没有值
				if(!$first){
					$first = true;
					continue;
				}
				return;
			}
			$content = trim($content);
			Log::debug(date('Y-m-d H:i:s') . $content, 'procargs.txt', 10);
			$content = str_replace("\n", "", $content);
			$matches = $aArgs = [];
			preg_match('/^call\s+(\S+)\s*\((.*)\)/', $content, $matches);
			if(!$matches){
				Log::debug(date('Y-m-d H:i:s') . $content . ' parse error!', 'procerr.txt');
				continue;
			}
			$func = trim($matches[1]);
			Log::debug(date('Y-m-d H:i:s') . json_encode($matches), 'procmatches.txt');
			Log::debug(date('Y-m-d H:i:s') . $content, 'proccontent.txt');
			if(!method_exists("Zengym\\Model\\Proc", $func)){
				Log::debug(date('Y-m-d H:i:s') . $content . ' is not exists!(method:' . $func . ')', 'procerr.txt');
				continue;
			}
			$begin_microTime = microtime(true);
			$begin_usemem = memory_get_usage(1);
			$sArgs = $matches[2];
			if($sArgs[0] === '{' && strpos($sArgs, '"') !== false){//json格式
				$aArgs = base64_encode($sArgs);
				$scall = $func . '(\'' . $aArgs . '\')';
			}else{
				eval("\$aArgs = array($sArgs);");
				if(!$aArgs){
					Log::debug(date('Y-m-d H:i:s') . $content . ' is error!(method:' . $func . ')', 'procerr.txt');
					continue;
				}
				$scall = $func . '(\'' . implode('\',\'', $aArgs) . '\')';
			}
			$GLOBALS['crontab_method'] = "Zengym\\Model\\Proc::$scall;";
			eval("self::$scall;");
			Cron::SaveMonitorInfo($begin_microTime, "Zengym\\Model\\Proc::" . $func, $begin_usemem);
		}
	}
	
	/**
	 * $wmode 值 1 swoole游戏占用
	 */
	public static function pop( $wmode = 0 ){
		$wmode = intval( $wmode );
		
		$redisObj = DB::instance();
		
		$key = !empty( $wmode ) ? self::$keyfix."|".$wmode : self::$keyfix;
			
		for( $try = 0; $try < 2; $try++ ){
			$res = (string) $redisObj->rPop( $key );
			if( $res ){
				break;
			}
			$redisObj->close();
		}
		
		return $res;
	}
	
	public static function push($value, $wmode = 0){
		$wmode = intval( $wmode );
		$redisObj = DB::instance();
			
		$key = !empty( $wmode ) ? self::$keyfix."|".$wmode :self::$keyfix;
		
			
		for( $try = 0; $try < 2; $try++ ){
			$res = (string) $redisObj->lPush( $key, $value );
			if( $res ){
				break;
			}
			$redisObj->close();
		}
		
		return $res;
	}
	
	public static function AddLotteryBuyLog(){
		$aArgs = func_get_args();
		print_r($aArgs);
		echo "\n";
	}
	
	public static function AddSlotsV3Play(){
		$aArgs = func_get_args();
		print_r($aArgs);
		echo "\n";
	}
}