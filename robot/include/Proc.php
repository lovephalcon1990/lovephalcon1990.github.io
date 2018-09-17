<?php
/**
* server的伪存储
*/
class Proc{
	private $sid;//站点ID
	private $redis;//redis class
	public function __construct($sid, $redis){
		$this->sid = $sid;
		$this->redis = $redis;
	}
	/**
	 * 添加到redis 业务proc中处理
	 */
	public function add($method, $args = array()){
		foreach($args as &$v){
			if(is_string($v)){
				$v = "'{$v}'";
			}elseif(is_array($v)){
				$v = "'". implode(',', $v) ."'";
			}elseif(is_bool($v)){
				$v = $v ? 1 : 0;
			}
		}
		$str =  "call ".$method."(".implode(',', $args).")";
		return $this->redis->lPush('PROC|'.$this->sid, $str);
	}
}