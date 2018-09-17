<?php
/**
*处理客户端请求数据
*/
class ModelDo{
	private static function _u($mid){
		return ModelHandler::$aClient[$mid];
	}
	
	/**
	* 登录回执
	*/
	public static function do_0x101($mid, $aData){
		if($aData['ret']){//登录失败了
			ModelHandler::onClose($mid);
			Main::logs($mid. ' 登录失败 '.$aData['ret']);
		}
	}
	
	/**
	* 收到退出房间命令
	*/
	public static function do_0x102($mid, $aData){
		ModelHandler::onClose($mid);
	}
	/*
	* 下发房间数据
	*/
	public static function do_0x104($mid, $aData){
		self::_u($mid)->aData['stime'] = time();//记录用户坐下时间
		//5%的几率会坐下
		self::randOk(Main::$cfg['aGl']['sit']) && self::sitDowm($mid);
		if($aData['status'] == 1){//下注状态
			self::randOk(Main::$cfg['aGl']['noBet']) || self::bet($mid);
		}
	}
	
	/**
	* 下注回执
	*/
	public static function do_0x201($mid, $aData){
		if($aData['ret'] == 2){//下注游戏币不够
			self::quit($mid);
		}
	}
	
	/**
	* 游戏下注开始
	*/
	public static function do_0x215($mid, $aData){
		//0.5%的几率会发表情
		self::_u($mid)->aData['aSeat'][$mid] && self::randOk(Main::$cfg['aGl']['betFace']) && self::sendFace($mid, 2);
		//10%的几率不下注
		self::randOk(Main::$cfg['aGl']['noBet']) || self::bet($mid);
	}
	
	
	/**
	* 游戏结果
	*/
	public static function do_0x214($mid, $aData){
		if(self::_u($mid)->aData['aBet']){//如果下注过 算一局
			unset(self::_u($mid)->aData['aBet']);
			Main::$class['report']->lc($mid, 3);
		}
		if($aData['winMoney'] > 0){
			//10%的概率发出表情
			self::_u($mid)->aData['aSeat'][$mid] && self::randOk(Main::$cfg['aGl']['winFace']) && self::sendFace($mid, 3);
			Main::$class['report']->lc($mid, 1);
		}elseif($aData['winMoney'] < 0){
			//2%的概率发出表情
			self::_u($mid)->aData['aSeat'][$mid] && self::randOk(Main::$cfg['aGl']['failFace']) && self::sendFace($mid, 4);
			Main::$class['report']->lc($mid, 2);
		}
		
		if(time()- self::_u($mid)->aData['stime'] > Main::$cfg['remainTime']*60){
			self::quit($mid);
			return;
		}
	}
	/**
	* 收到坐下命令 广播的
	*/
	public static function do_0x110($mid, $aData){
		self::_u($mid)->aData['aSeat'][$aData['mid']] = $aData['seatId'];
		if($aData['mid'] == $mid){//如果是自己坐下了
			//5%的几率会发表情
			self::randOk(Main::$cfg['aGl']['sitFace']) && self::sendFace($mid, 1);
			//5%的几率会发互动道具
			self::randOk(Main::$cfg['aGl']['sitProp']) && self::sendProps($mid, 1);
		}
	}
	
	/*
	* 收到站起广播
	*/
	public static function do_0x111($mid, $aData){
		$seatId = $aData['seatId'];
		if(is_array(self::_u($mid)->aData['aSeat'])){
			foreach(self::_u($mid)->aData['aSeat'] as $smid=>$s){
				if($s == $seatId){
					unset(self::_u($mid)->aData['aSeat'][$smid]);
					break;
				}
			}
		}
	}

	public static function do_109_1003($mid, $aData){
		if($aData['uid'] == 12794272 ){
			Main::$class['tick']->after(array('ModelDo', 't_chat'), array($mid, $aData), 2);
		}
	}


	public static function t_chat($params){
		list($mid, $aData) = $params;
		ModelHandler::sendPack($mid, 0x202, array('type'=>$aData['type'], 'chat'=>$aData['chat']));//复读机
	}
	
	/**
	* 互动道具广播
	* 在收到互动道具时，有80%的概率会对发送互动道具的玩家或机器人使用同类道具回击
	* 回击时间延迟2秒，两秒内如果再次收到道具，则100%只回击同类道具
	*/
	public static function do_0x6($mid, $aData){
		$fromId = $aData['fromId'];
		$toId = $aData['toId'];
		$propsId = $aData['propsId'];
		if($toId == self::_u($mid)->aData['aSeat'][$mid]){//是发给我的
			//延时回击
			if(self::_u($mid)->aData['p_tickId']){//2秒内收到互动道具 100%回击
				Main::$class['tick']->del(self::_u($mid)->aData['p_tickId']);
				unset(self::_u($mid)->aData['p_tickId']);
				Main::$class['tick']->after(array('ModelDo', 't_dosendProp'), array($mid, $fromId, $propsId), 2);	
			}else{
				self::_u($mid)->aData['p_tickId'] = Main::$class['tick']->after(array('ModelDo', 't_doProp'), array($mid, $fromId, $propsId), 2);	
			}
		}
	}
	
	/**
	* 互动道具是否发送
	*/
	public static function t_doProp($aData){
		list($mid, $fromId, $propsId) = $aData;
		unset(self::_u($mid)->aData['p_tickId']);
		if(self::randOk(Main::$cfg['aGl']['getProp'])){
			 Main::$class['tick']->after(array('ModelDo', 't_dosendProp'), array($mid, $fromId, $propsId), 2);	
		}
	}
	
	/**
	* 延时后发出道具
	*/
	public static function t_dosendProp($aData){
		list($mid, $fromId, $propsId) = $aData;
		self::sendProps($mid, 2, $fromId, $propsId);
	}
	
	/*
	* 延时登录
	*/
	public static function t_login($aData){
		list($mid, $aData) = $aData;
		unset(ModelHandler::$addUser[$mid]);
		if(!ModelHandler::$aClient[$mid]){
			ModelHandler::$aClient[$mid] = new ModelClient($mid);
			ModelHandler::$aClient[$mid]->aData['login'] = $aData;
		}
		ModelHandler::$aClient[$mid]->sendLogin();
	}
	
	/**
	* 用户退出房间
	*/
	private static function quit($mid){
		ModelHandler::sendPack($mid, 0x102);
	}

	/**
	* 用户下注
	* 随机下注1-6个位置，
	* 每个位置随机下注一次前两个额度中的一个
	*/
	private static function bet($mid){
		$rand = mt_rand(1, 6);//随机下注次数
		$t = 0;
		$interval = floor(18/$rand);
		for($i=0;$i<$rand;$i++){
			$t = mt_rand($t + 1, ($i+ 1) * $interval);//随机下注间隔
			Main::$class['tick']->after(array('ModelDo', 't_bet'), $mid, $t);	
		}
	}
	
	public static function t_bet($mid){
		$aArea =  self::randOk(Main::$cfg['aGl']['betDx']) ? array(1, 2) : range(3, 29);//下注区域
		$id = $aArea[array_rand($aArea)];
		global $mid_table;
		$aMidTable = $mid_table->get($mid);
		if(!$ante = $aMidTable['ante']){
			return;
		}
		if(Main::$cfg['defaultBet']){
			$aBet = Main::$cfg['defaultBet'];
		}else{
			if(!is_array(Main::$cfg['aBet'][$ante])){
				return;
			}
			$aBet = array_slice(Main::$cfg['aBet'][$ante] , 0 , 2);//只取前两个下注额度
		}
		if(!is_array($aBet)){
			return;
		}
		$money = $aBet[array_rand($aBet)];
		self::_u($mid)->aData['aBet'][] = array($id, $money);
		ModelHandler::sendPack($mid, 0x201, array($id, $money));//用户下注了
	}
	
	/**
	* 发表情
	*/
	private static function sendFace($mid, $cause){
		if(!$aFace = Main::$cfg['aFace'][$cause]){
			return;
		}
		$faceId = $aFace[array_rand($aFace)];
		Main::$class['report']->lc($mid, 4);
		ModelHandler::sendPack($mid, 0x004, array($faceId));
	}
	
	/**
	* 发互动道具
	* @param $mid
	* @param $toId 发送给谁 这里是座位ID
	* @param $propsId 互动道具ID
	*/
	private static function sendProps($mid, $cause, $toId=0, $propsId=0){
		if(!$toId){
			$aSeat = array();
			foreach((array)self::_u($mid)->aData['aSeat'] as $smid=>$s){
				if($smids != $mid){
					$aSeat[] = $s;
				}
			}
			if(!$aSeat){
				return;
			}
			$toId = $aSeat[array_rand($aSeat)];
		}
		if(!$propsId){
			$aProps = Main::$cfg['props'];
			$propsId = $aProps[array_rand($aProps)];
		}
		Main::$class['report']->lc($mid, 5);
		ModelHandler::sendPack($mid, 0x005, array($toId, $propsId, 0));
	}
	
	/**
	* 发坐下命令包
	*/
	private static function sitDowm($mid){
		Main::$class['tick']->after(array('ModelDo', 't_sitDowm'), $mid, 1);	
	}
	
	/**
	* 延时坐下
	*/
	public static function t_sitDowm($mid){
		$aSeat = array();
		foreach((array)self::_u($mid)->aData['aSeat'] as $smid=>$s){
			$aSeat[] = $s;
		}
		$aHasSeatId = array_diff(range(1,6), $aSeat);
		$seatId = $aHasSeatId[array_rand($aHasSeatId)];
		ModelHandler::sendPack($mid, 0x110, array($seatId));
	}
	
	
	/**
	* 判断概率是否可以
	*/
	private static function randOk($num){
		if(!$num){
			return false;
		}
		if(self::random() <= $num){
			return true;
		}
		return false;
	}
	//产生随机数 0 到 100.00
	private static function random( $len = 2 ){
		return mt_rand( 0, 99 ) + mt_rand( 0, pow( 10, $len ) ) / pow( 10, $len );
	}

}