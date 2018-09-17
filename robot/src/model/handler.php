<?php
/*
 * 游戏数据处理逻辑
 */
class ModelHandler{
	public static $aClient = array();//所有的连接
	public static $addUser = array();//添加中的用户
	/**
	* 主动调用的数据
	*/
	public static function init($fd, $buff){
		list($mid, $cmd, $aData) = self::string2Data($buff);

		if($cmd == 0x888){
			return Main::$class['admin']->init($fd, $mid, $aData);
		}
		if(!$mid){//没有mid
			return;
		}
		$toWorkId = Main::dispatch($mid);
		if(Main::$worker_id !== $toWorkId){
			Main::$swoole->sendMessage($fd.'|'.$buff, $toWorkId);
			return;
		}
		if($cmd == 0x101){//登录
			$t = (int)$aData['t'];
			unset($aData['t']);
			if($t){
				self::$addUser[$mid] = Main::$class['tick']->after(array('ModelDo', 't_login'), array($mid, $aData), $t);	
				return Main::$swoole->send($fd, self::data2String($mid, $cmd, array('err'=>0)));
			}
			if(!self::$aClient[$mid]){
				self::$aClient[$mid] = new ModelClient($mid);
				self::$aClient[$mid]->aData['login'] = $aData;
			}
		}
		if($cmd == 0x102){//退出
			if(self::$addUser[$mid]){
				Main::$class['tick']->del(self::$addUser[$mid]);
				unset(self::$addUser[$mid]);
			}
			if(!self::$aClient[$mid]){
				return Main::$swoole->send($fd, self::data2String($mid, $cmd, array('err'=>0)));
			}
		}
		if(self::sendPack($mid, $cmd, $aData)){
			return Main::$swoole->send($fd, self::data2String($mid, $cmd, array('err'=>0)));
		}
		Main::$swoole->send($fd, self::data2String($mid, $cmd, array('err'=>1)));
		Main::logs($mid. ' 发包错误');
	}
	
	/**
	* 获取数据
	*/
	public static function getClientData(){
		$aData = array();
		foreach(self::$aClient as $mid=>$client){
			$aData[$mid] = $client->aData;
		}
		return $aData;
	}
	/**
	* 设置数据
	*/
	public static function setClientData($aData){
		foreach((array)$aData as $mid=>$aD){
			if(!self::$aClient[$mid]){
				self::$aClient[$mid] = new ModelClient($mid);
			}
			self::$aClient[$mid]->aData = $aD;
			self::$aClient[$mid]->sendLogin();//重新登录
			Main::logs($mid. ' 重新登录');
		}
	}
	

	public static function string2Data($buff){
		$data = json_decode($buff, true);
		return array($data['mid'], $data['cmd'], $data['info']);
	}
	
	/*
	* 封装数据包
	*/
	public static function data2String($mid, $cmd, $data){
		return json_encode(
			[	'mid'=>$mid,
				'cmd'=>$cmd,
				'info'=>$data,
			]
		);
	}

	
	/**
	* 发送心跳
	*/
	public static function heartbeat(){
		$t = time();
		foreach(self::$aClient as $mid=>$client){
			if($t - $client->aData['mtime'] > 20){//20秒没有收到数据了
				self::onClose($mid);
				Main::logs($mid. ' 大于20秒关闭');
				continue;
			}
			ModelHandler::sendPack($mid, 0x2);
		}
	}
	
	/**
	* 清除数据
	*/
	public static function onClose($mid){
		Main::$class['tick']->after(array('ModelHandler', 't_close'), $mid, 1);
	}
	
	/**
	* 关闭连接
	* $df 原因 1用户主动关闭 0 系统连接断开了
	*/
	public static function t_close($mid){
		global $mid_table;
		$mid_table->del($mid);
		if(self::$aClient[$mid]){
			self::$aClient[$mid]->close();
			unset(self::$aClient[$mid]);
			unset(self::$addUser[$mid]);
		}
	}
	
	/**
	* 收到server数据处理
	*/
	public static function revPack($mid, $packet_buff){
		if(!$aData = Main::$class['doPack']->revPackV2($packet_buff)){
			return;
		}
		$ret = $aData['data'];
		if($ret['MainCmdID'] != 1){
			Main::logs($aData , 'revPack');
		}
		$cmd = $ret['MainCmdID']."_".$ret['SubCmdID'];
		$method = 'do_' . $cmd;
		if(method_exists('ModelDo', $method)){
			call_user_func(array('ModelDo', $method), $mid, $ret['Info']);
		}
	}
	
	
	/**
	* 发送数据给服务器
	*/
	public static function sendPack($mid, $cmd, $aData=array()){
		if(!self::$aClient[$mid]){
			return false;
		}
		if($cmd == 0x101){//登录
			global $mid_table;
			$mid_table->set($mid, array('ante' => $aData[1]));
		}
		
		if($buff = Main::$class['doPack']->getSendPackV2($cmd, $aData)){
			self::$aClient[$mid]->send($cmd, $buff);
			return true;
		}
		return false;
	}
}
