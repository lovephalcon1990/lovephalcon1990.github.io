<?php
//德州扑克用户信息有关
include_once __DIR__ . '/Fun.php';
class Member{
	private $giRedis;
	private $giMem;
	public function __construct($giRedis, $giMem){
		$this->giRedis = $giRedis;
		$this->giMem = $giMem;
	}
	
	private $aFmt = array(
			0 => 'mnick',
			1 => 'micon',
			7 => 'showVip',//是否显示VIP
			8 => 'mvip',//VIP 等级
			10 => 'vipLv'//VIP 子等级
	);
	
	//目前就昵称 头像
	public function getMinfo($mid, $aRrr = array(0, 'mbig')){
		if( !$mid = fun::uint( $mid ) ){
			return array();
		}
		$aRet = $aKey1 = $aKey2 = array();
		foreach($aRrr as $v){
			if(is_string($v)){
				$aKey2[] = $v;
			}else{
				$aKey1[] = $v;
			}
		}
		if($aKey1){
			$aInfo = (array)$this->giRedis->hMGet($this->giKey($mid, 0), $aKey1);
			foreach ($aInfo as $k=>$v){
				if (isset($this->aFmt[$k])){
					$aRet[$this->aFmt[$k]] = $v;
				}
			}
		}
		if($aKey2){
			$aRet += (array)$this->giRedis->hMGet($this->giKey($mid, 1), $aKey2);
		}
		
		
		if (isset($aRet['mbig'])){
			$aRet['micon'] = $aRet['mbig'];
			unset($aRet['mbig']);
		}
		
		if (false == $aRet['micon']){
			$aRet['micon'] = '';
		}
		
		if (false == $aRet['mnick']){
			$aRet['mnick'] = '';
		}
		
		return $aRet;
	}
	
	private function giKey($mid, $type=0){
		return "EXT{$type}_{$mid}";
	}
	
	//目前就昵称 头像
	public function getFieldsMulti($mids){
		if( !is_array( $mids ) || !$mids || (!$mids = array_unique( $mids ))){ //不是数组或者空数组或者...
			return array( );
		}
		$aRet = array();
		foreach($mids as $mid){
			$aRet[$mid] = $this->getMinfo($mid);
		}
		return $aRet;
	}
	/**
	 * 验证德州扑克的mtkey是否合法
	 */
	public function checkMtkey($mid, $mtkey){
		if( !$mid = fun::uint( $mid ) ){
			return false;
		}
		$aOnline = $this->onlineinfo($mid);
		if(!$aOnline['mtkey']){
			return false;
		}
		if( strlen( $mtkey ) == 30 ){//苹果用户直接传过来32个字符的mtkey，因此此处不可做转换
			$mtkey = $this->calcMtkey( $mid, $mtkey );
		}
		if( strcmp( $aOnline['mtkey'], $mtkey ) !== 0 ){//非法的用户
			return false;
		}
		return $aOnline;
	}
	
	/**
	 * 获得用户的在线表信息(当前仅从cache中查)
	 * @param int $mid
	 * @return Array
	 */
	public function onlineinfo( $mid ){
		if( !$mid = fun::uint( $mid ) ){
			return array( );
		}
		$string = $this->giMem->get( $mid, false );
		$aInfo = fun::unserialize( $string );
		return $aInfo['mid'] == $mid ? $aInfo : array( );
	}
	//mtstatus  1 房间中 2 座位上  0房间外面
	public function updateOnlineInfo($mid, $aInfo= array(), $isclear=0){
		$result = $this->giMem->get( $mid, false, true);
		if(!$result[0]){
			return false;
		}
		$aOnline = fun::unserialize( $result[0] );
		if($aInfo['tid'] && $aOnline['tid'] && ($aOnline['tid'] != $aInfo['tid'])){
			fun::logs('upgiErr', date('Y-m-d H:i:s').' tid err,'.$aInfo['tid'].' '.$aOnline['tid']. ' '. $isclear);
			return false;
		}
		if($aInfo['svid'] && $aOnline['svid'] && ($aOnline['svid'] != $aInfo['svid'])){
			fun::logs('upgiErr', date('Y-m-d H:i:s').' svid err,'.$aInfo['svid'].' '.$aOnline['svid']. ' '. $isclear);
			return false;
		}
		if($isclear){//清理tid svid
			$aInfo['tid'] = $aInfo['svid'] = 0;
		}
		$aOnline = array_merge($aOnline, $aInfo );
		$cas = $result[1];
		$aOnline['mttime'] = time();
		if($this->giMem->cas( $cas, $mid, fun::serialize($aOnline), 7 * 86400)){
			return $aOnline;
		}
		return  false;
	}
	
	/**
	 * 从mtkey2计算出mtkey密文
	 *
	 * @param Int $mid
	 * @param String $mtkey2
	 * @return String $mtkey
	 */
	public function calcMtkey( $mid, $mtkey2 ){
		if( (!$mid = fun::uint( $mid )) || strlen( $mtkey2 ) != 30 ){
			return 0;
		}

		$sum = 0;
		$offset = 15;
		for( $i = 0; $i < 30; $i++ ){
			$offset = ord( substr( $mtkey2, $offset, 1 ) ) % 30;
			$sum += $offset;
		}
		return md5( $sum + $mid );
	}
}