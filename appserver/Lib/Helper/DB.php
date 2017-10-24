<?php
/**
 * Created by PhpStorm.
 * User: YuemingZeng
 * Date: 2017/10/20
 * Time: 11:07
 */

namespace Zengym\Lib\Helper;
use Zengym\Model\Redis;

class DB{
	/**
	 * @var self
	 */
	private static $instance;
	
	/**
	 * @return \Redis
	 */
	public static function instance(){
		$redisconfig = include SWOOLE_ROOT."/Config/redis.php";
		if(!self::$instance){
			new self($redisconfig);
		}
		return self::$instance;
	}
	
	/**
	 * DB constructor.
	 * @param $redisconfig
	 */
	private function __construct($redisconfig){
		self::$instance = new Redis($redisconfig);
	}
}