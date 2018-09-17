<?php
/**
 * 系统服务器列表操作
 */
class Servers{
	private $tb;//server 表
	private $mongo;//class mongo
	public $aServer = array();
	public function __construct($tb, $mongo){
		$this->tb = $tb;
		$this->mongo = $mongo;
	}
	public function getOneServer($svid){
		if($ret = $this->aServer[$svid]){
			return $ret;
		}
		$array = $this->updateServerInfoCache();
		return (array) $array[$svid];
	}

	/**
	 * 更新缓存中的数据
	 * @return Array
	 */
	public function updateServerInfoCache(){
		$retData = $this->mongo->find($this->tb, array('svstatus'=>0), array('svid', 'svlip', 'svip', 'svport'));
		foreach($retData as $array){
			unset($array['_id']);
			$aList[$array['svid']] = $array;
		}
		return $this->aServer = $aList;
	}
	
}
