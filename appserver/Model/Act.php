<?php
/**
 * Created by PhpStorm.
 * User: YuemingZeng
 * Date: 2017/10/24
 * Time: 17:52
 */
namespace Zengym\Model;


class Act{
	
	public static function test(){
		$argv = func_get_args();
		print_r($argv);
		echo "\n";
	}
}