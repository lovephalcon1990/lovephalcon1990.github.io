<?php
namespace Zengym\Apps\Cron\Model;

/**
 * Description of SwooleCrontabConfig
 */
class CronConfig{
	
	/**
	 * 主服务器任务列表
	 * @return type
	 */
	public static function getMainRunList( $online){
		//method:要运行的方法名，
		//cnt:最大并发执行数  
		//interval:每隔n秒触发一次，
		//workerid:只在指定 workid进程上执行，默认为第1个
		//excludeSid array 排除的站点
		//includeSid array 包含的站点
		//switch 表示必须开启某个开关，oo::$config['']
		
		$timei = intval(date('i', time())); //分钟,00 - 59
		
		$processList['AsyncCall'] = array(
			'method' => 'Zengym\Model\AsyncCall::exec();',
			'cnt' => 1,
			'interval' => 2,
			'exclude' =>  array(),
			'cnt'=>2
		);
		
		
		$processList['doproc0'] = array(
			'method' => 'Zengym\Model\Proc::doProc(0);',
			'cnt' => 1,
			'interval' => 5,
			'exclude' =>  array(),
		);
		$processList['doproc1'] = array(
			'method' => 'Zengym\Model\Proc::doProc(1);',
			'cnt' => 1,
			'interval' => 2,
			'exclude' =>  array(),
			'switch' => 'newhall'
		);
		
		return self::filter($processList);
	}

	private static function filter($processList){
		$processListData = array();
		foreach($processList as $key => $process){
			if(isset($process['include']) && (!empty($process['include'])) ){
				continue;
			}
			if(isset($process['exclude']) && (!empty($process['exclude'])) ){
				continue;
			}
			$process['interval'] = isset($process['interval']) ? $process['interval'] : 5;
			$process['interval'] = $process['interval'] * 1000;
			
			if(!($process['cnt'] && $process['method'])){
				continue;
			}
			//关键字开关控制
			//if(isset($process['switch']) && !oo::$config[$process['switch']]){
			//	continue;
			//}
			$process['method'] = trim($process['method']);
			if(substr($process['method'], -1) != ';'){
				$process['method'].= ';';
			}
			if(!isset($process['workid'])){
				$process['workid'] = 0;
			}
			$processListData[$key] = $process;
		}
		return $processListData;
	}

}