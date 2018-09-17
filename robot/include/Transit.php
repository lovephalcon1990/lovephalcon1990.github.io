<?php
//中转server调用
class Transit{
	private $sid;
	private $ip = '127.0.0.1';
	private $port = 6001;
	private $fp;
	public function __construct($sid, $aServer = array()){
		$this->sid = $sid;
		if($aServer){
			list($this->ip, $this->port) = $aServer;
		}
		$this->fp = stream_socket_client("udp://{$this->ip}:{$this->port}", $errno, $errstr, 2);
	}
	/**
	 * 上报mf
	 */
	public function mf($sid, $mid, $table, $aData, $time=0){
		$aData = array($sid, $mid, $table, $aData, $time);
		return $this->send(0x101, $aData);
	}
	
	/**
	 * 上报dc
	 */
	public function dc($sid, $act_name, $gdata){
		$aData = array($sid, $act_name, $gdata);
		return $this->send(0x102, $aData);
	}
	
	/**
	 * 上报lc
	 */
	public function lc($sid, $mid, $key, $aInfo, $isDemo = false, $aDemoInfo = array()){
		$aGameData = array('et_id'=>$key,'uid'=>$mid,'lts_at'=>time());
		
		if($isDemo){
			$monogo = $aDemoInfo['mongo'];//mongo类
			$log = new LogSwoole('192.168.202.93', $aDemoInfo['port']);//LogSwoole 类
			$msid = $this->sid;//主站Sid
			$monogo->update('texas_'.$msid.'_act.act'. date('ymd').'_set', array('_id'=>'Stats_eventSend_'.$key), array('k'=>'Stats_eventSend', 'v'=>$key ));
			$f = 'demoEventStat/'. gmdate('Ymd',time()+3600*8);
			$log->debug(date("Y-m-d H:i:s###").serialize(array_merge(array('__csid' => $sid),$aGameData, $aInfo)), $f, 10);
		}
		
		return $this->dc($sid, 'by_event', array_merge($aGameData, $aInfo));
	}
	
	/**
	 * 上报日志
	 */
	public function logs($fname, $string, $fsize = 1, $isBak = 0){
		$aData = array($fname, $fsize, $string, $isBak);
		return $this->send(0x103, $aData);
	}
	
	/**
	 * 伪存储
	 */
	public function proc($method, $args){
		$aData = array($this->sid, $method, $args);
		return $this->send(0x104, $aData, false);
	}
	
	/**
	 * 报警
	 */
	public function warning($content){
		$aData = array($this->sid, $content);
		return $this->send(0x105, $aData, false);
	}
	
	/**
	 * 实时牌局
	 */
	public function gameparty($sid, $gdata){
		$aData = array($sid, $gdata);
		return $this->send(0x106, $aData, false);
	}
	
	private function send($cmd, $aData, $gz=true){
		if($gz){
			$data = $cmd.'*$*'.gzcompress(json_encode($aData) ,9);
		}else{
			$data = $cmd.'*$*'.json_encode($aData);
		}
		fwrite($this->fp, $data);
		return true;
	}
}