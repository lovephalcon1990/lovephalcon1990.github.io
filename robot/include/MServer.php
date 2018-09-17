<?php
/**
 * 此处涉及四种Server的通讯,加密方式不尽相同:
 * 1: 打牌Server(需要指定IP和端口,用于控制房间里面的: 踢人,发广播,发重置命令,发暂停等)
 * 2: 存钱Server(利用配置文件指定IP和端口,用于创建用户重要信息如金币,台费值等)
 * 3: 存钱备份Server(利用配置文件指定IP和端口,用于备份用户的金币情况,每半小时切换一个文件)
 * 4: 淘金赛Server(告知Server开赛时间等)
 */
class MServer {

	const CMD_CREATE_RECORD = 0x1001; //创建记录
	const CMD_UPDATE_RECORD = 0x1002; //更新记录
	const CMD_DELETE_RECORD = 0x1003; //删除记录
	const CMD_GET_RECORD = 0x1004; //获取记录
	const CMD_REPORT_ID = 0x1005; //上报身份
	const CMD_GET_ALLRECORD = 0x1006; //取所有数据
	const CMD_GET_UPDATEALLRECORD = 0x1007; //更新数据
	const CMD_GET_LOGRECORD = 0x1009; //日志更新数据
	const CMD_SWITCH_LOG_FILE = 0x1008; //写日志开关
	const CLIENT_COMMAND_SYS_MSG = 0x3001; //广播命令
	const CLIENT_COMMAND_SYS_MYSQLRESET = 0x3002; //重置
	const CLIENT_COMMAND_SYS_STOPSERVER = 0x3004; //服务器停止开启
	const CLIENT_COMMAND_SYS_KICK_USER = 0x3005; //踢人
	const CLIENT_COMMAND_SYS_CLOSE_SEXYCROUPIERROOM = 0X3137;   //string key; //int tid;//桌子id //int uid;//荷官id
	const SERVER_COMMAND_SYS_CLOSE_SEXYCROUPIERROOM = 0X3138;
	const CLIENT_COMMAND_GETVAEYMONEY = 0x3023; // 获得金币加变化+
	const CLIENT_COMMAND_GETDECMONEY = 0x3024; // 获得金币加变化-
	const CLIENT_COMMAND_SYS_RETIME = 0x0102; //发给报名server，告知的开始时间
	const CLIENT_COMMAND_SYS_UPDATE_MONEY = 0x3015; //PHP端改变用户Money通知
	const CLIENT_COMMAND_SYS_GET_AVAILABLE_MONEY = 0x3016; // PHP端获取用户的可用钱数（PHP发包）
	const SERVER_COMMAND_RETURN_AVAILABLE_MONEY = 0x3017; // 返回PHP用户的可用钱数(server端回包)
	const SERVER_COMMAND_GET_RECORD = 0x2004; //服务器回应取用户
	const SERVER_KEY = "7b3b1b1fbc397a87e01a72265b5d3acd";

	/* 以下是比赛场重构常量 *///淘金赛、锦标赛配置控制接口
	const CLIENT_COMMAND_SYS_GETSTARTTIME = 0x3021; //获取开赛时间配置
	const CLIENT_COMMAND_SYS_SETSTARTTIME = 0x3023; //设置开赛时间

	/* 红利场BONUS */
	const CLIENT_COMMAND_GET_BONUS_MONEY = 0x3117;  //客户端获取server金钱信息
	const SERVER_COMMAND_GET_BONUS_MONEY = 0x3118;  //服务端返回金钱信息
	const CLIENT_COMMAND_SET_BONUS_MONEY = 0x3119;  //客户雄设置server金钱信息
	const SERVER_COMMAND_SET_BONUS_MONEY = 0x311A;  //服务端返回设置结果

	/* 获取按条件获取桌子信息 */
	const CLIENT_COMMAND_SYS_GET_TABLEINFO = 0x3122;
	const SERVER_COMMAND_SYS_GET_TABLEINFO = 0x3123;

	/* 用户信息及时更新 */
	const CLIENT_COMMAND_PHPUPDATE_USERINFO = 0x311B;
	const SERVER_COMMAND_UPDATE_USERINFO = 0x311C;
	const CLIENT_COMMAND_SYS_STARTGAME = 0x311D; //通知server开赛
	const CLIENT_COMMAND_CHANGE_CONFIG = 0x3035; //改变配置 config.ini
	const CLIENT_CONTROL_ROBOT_ACTION = 0x2001; //通知server加入机器人
	const SERVER_CONTROL_ROBOT_ACTION = 0x3001; //server返回加入机器人结果
	const CLIENT_CONTROL_CHANGE_DATA = 0x2002; //修改机器人数据
	const SERVER_CONTROL_CHANGE_DATA = 0x3002; //server返回加入机器人结果
	const CLIENT_COMMAND_GET_SERVERINFO = 0x3124; //请求server信息
	const SERVER_COMMAND_GET_SERVERINFO = 0x4083;
	const CLIENT_COMMAND_UPDATE_RECORD_TIL_ZERO = 0x100A;
	const SERVER_COMMAND_UPDATE_RECORD_TIL_ZERO = 0x2008;
	const CLIENT_COMMAND_UPDATE_RECORD_WITH_CHECK = 0x100B;
	const SERVER_COMMAND_UPDATE_RECORD_WITH_CHECK = 0x2009;
	const CLIENT_COMMAND_CAS_RECORD = 0x1009;
	const SERVER_COMMAND_CAS_RECORD = 0x2007;
	const CLIENT_COMMAND_FIELD_TRANSFER = 0x100C;
	const SERVER_COMMAND_FIELD_TRANSFER = 0x200A;
	const CLIENT_COMMAND_TRANSFER_SELF = 0x100D;
	const SERVER_COMMAND_TRANSFER_SELF = 0x200B;
	const CLIENT_COMMAND_MSERVER_RESET = 0x4001;

	/*	 * ************** VTC平台登陆验证 ********************* */
	const CLIENT_COMMAND_CHECK_ACCESSTOKEN = 0x100B;
	const SERVER_COMMAND_CHECK_ACCESSTOKEN = 0x2009;
	/*	 * **************************************************** */
	const CLIENT_COMMAND_SYS_PHP2CLIENT_INFO = 0x312B; // 新淘汰赛通知关赛
	const SERVER_COMMAND_SYS_PHP2CLIENT_INFO = 0x312C;
	const CLIENT_AND_MANAGER_COMMAND = 0x888; //系统管理命令-客户端
	const SERVER_AND_MANAGER_COMMAND = 0x888; //系统管理命令返回
	const ADMIN_CKEY = "nba83j^%amfadfna930&%)#4pj";

	private $ip, $port;
	public $aSeria = array(//PHP与Server的映射关系
		'mid' => 'mid',
		'cas' => 'cas',
		'sid' => 'sid',
		'mmoney' => 'money',
		'msavecount' => 'msavecount',
		'vmoney' => 'exp', //server台费，server字段是exp， php字段是vmoney
		'sngsub' => 'sub',
		'wmode' => 'wmode',
		'addmoney' => 'addmoney',
		'mbank' => 'mbank',
		'tid' => 'tid',
		'bid' => 'bid'
	);
	private $retCode = 0; //操作状态 0成功 1发送失败 2recv失败 3其他错误 4连接失败 5getRecord recv失败

	//设置操作状态

	public function setRetCode($code) {
		$this->retCode = $code;
	}

	//返回操作状态
	public function getRetCode() {
		return $this->retCode;
	}

	public function __construct($ip, $port) {
		$this->ip = $ip;
		$this->port = $port;
	}

	private $connect_time = 0;

	/**
	 * swoole-client
	 * @var swoole_client
	 */
	private $swooleclient = false;

	private function _connect() {
		if ($this->swooleclient && $this->connect_time && (time() - $this->connect_time) > 120) {
			//超过2分钟，断线重连
			$this->swooleclient->close();
			$this->swooleclient = false;
		}
		if ($this->swooleclient && !$this->swooleclient->isConnected()) {
			$this->swooleclient = false;
		}
		if (!$this->swooleclient) {
			$this->swooleclient = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
			$this->swooleclient->set(array(
				'open_length_check' => true,
				'package_length_type' => 's',
				'package_length_offset' => 6,
				'package_body_offset' => 9,
				'package_max_length' => 2000,
			));
			$rc = $this->swooleclient->connect($this->ip, $this->port, 4);
			if (!$rc) {
				$this->swooleclient = false;
				return false;
			}
		}
		return true;
	}

	/**
	 * 发送消息
	 * @param type $wr
	 * @return MSReadPackage
	 */
	public function send2CServer($buff_data, $log_args, $funcname) {
		try {
			////操作状态 0成功 1发送失败 2recv失败 3其他错误 4连接失败 5getRecord recv失败
			$this->setRetCode(0);
			if ($this->_connect()) {
				$sret = $this->swooleclient->send($buff_data);
				if (!$sret) {
					$msg = '1-send:' . swoole_strerror($this->swooleclient->errCode);
					$this->logexception($funcname, $log_args, $msg);
					//发送失败
					$this->setRetCode(1);
					$this->swooleclient = false;
					return false;
				}
				$data = $this->swooleclient->recv();
				if (!$data) {
					$msg = '2-recv:' . swoole_strerror($this->swooleclient->errCode);
					$this->logexception($funcname, $log_args, $msg);
					$this->swooleclient = false;
					return false;
				}
				$rs = new MSReadPackage();
				$ret = $rs->ReadPackageBuffer($data);
				if ($ret != 1) {
					$msg = '2-package:' . $ret;
					$this->logexception($funcname, $log_args, $msg);
					$this->setRetCode(2);
					return false;
				}
				if ($rs->ReadInt() !== 0) {
					$this->setRetCode(3);
					return false;
				}
				$this->connect_time = time();
				return $rs;
			} else {
				$this->logexception($funcname, $log_args, '4:conecnt-error');
				$this->setRetCode(4);
				$this->swooleclient = false;
			}
			return false;
		} catch (Exception $ex) {
			$this->logexception($funcname, $log_args, $ex);
			$this->setRetCode(3);
			$this->swooleclient = false;
			return false;
		}
	}

	/**
	 * 获取用户最新资料
	 * @param int $mid
	 * @param Boolean $fromMServer 是否从mserver取数据
	 * @return Array
	 */
	public function GetRecord($mid, $fromMServer = false) {
		if (!$mid = fun::uint($mid)) {
			return array();
		}
		try {
			$wr = new MSWritePackage();
			$wr->WriteBegin(self::CMD_GET_RECORD);
			$wr->WriteInt($mid);
			$wr->WriteEnd();
			$logargs = array('mid:' . $mid);
			$rs = $this->send2CServer($wr->GetPacketBuffer(), $logargs, __FUNCTION__);
			if ($rs !== false) {
				if ($this->getRetCode() == 2) {
					//获取钱比较特殊，
					$this->setRetCode(5);
				}
				$aInfo = $this->unseria($rs->ReadString());
				if ($aInfo['mid'] != $mid) {
					return array();
				}
				unset($aInfo['sid'], $aInfo['unid']);
				return $aInfo;
			}
			return array();
		} catch (Exception $ex) {
			$this->logexception(__FUNCTION__, $logargs, $ex);
			return array();
		}
	}

	private function logexception($funcname, $logargs, $ex) {
		if ($ex instanceof Exception) {
			if ($logargs) {
				$logargs = implode('-', $logargs) . '--exception:' . $ex->getMessage();
			} else {
				$logargs = 'exception:' . $ex->getMessage() . ',line:' . $ex->getLine();
			}
		} else {
			$logargs = implode('-', $logargs) . '--exception:' . $ex;
		}
		$this->debug(date('Ymd H:i:s') . '---MServer---' . $funcname . '---' . $logargs, 'CServer.txt');
	}

	/**
	 * 更新钱数,正数值为加钱,负数值为减钱
	 * @param int $mid
	 * @param Array $aInfo array([mmoney] => -1)
	 * @param int $limit 限制扣币操作必须间隔多少秒
	 * @return Boolean
	 */
	public function UpdateRecord($mid, $aInfo, $limit = 1, $isForce = 0) {
		try {
			if (!$mid = fun::uint($mid)) {
				return false;
			}
			if (!isset($aInfo['wmode'])) {
				$aInfo['wmode'] = -1;
			}
			$aInfo['addmoney'] = 0;
			isset($aInfo['mmoney']) && ($aInfo['addmoney'] = $aInfo['mmoney']);

			if (isset($aInfo['sngsub'])) {
				$aInfo['sngsub'] = (int) $aInfo['sngsub'];
			}
			if (!$sInfo = $this->seria($aInfo)) {
				$this->debug(array('mid' => $mid, 'aInfo' => $aInfo, 'msg' => 'updaterecord seria error', 'date' => date('Y-m-d H:i:s')), 'setmoney-err.txt');
				return false;
			}
			$mmoney = $aInfo['mmoney'];
			if (($mmoney < 0) && ($isForce !== 1)){
				$aRes = $this->GetRecord( $mid, true);
				$usedMoney = (int)$aRes['mmoney'];;
				//游戏币不够
				if ($usedMoney < abs($mmoney)) {
					$this->setRetCode(7);
					$this->debug(array('mid' => $mid, 'aInfo' => $aInfo, 'msg' => 'updaterecord no money', 'date' => date('Y-m-d H:i:s')), 'setmoney-err.txt');
					return false;
				}
			}
			$packet = new MSWritePackage();
			$packet->WriteBegin(self::CMD_UPDATE_RECORD);
			$packet->WriteInt(1);
			$packet->WriteInt($mid);
			$packet->WriteString($sInfo);
			$packet->WriteEnd();
			$logargs = array('mid:' . $mid, $sInfo);
			$rs = $this->send2CServer($packet->GetPacketBuffer(), $logargs, __FUNCTION__);
			if ($rs !== false) {
				return true; //返回的结果  0 成功 -1 失败
			}
			return false;
		} catch (Exception $ex) {
			$this->logexception(__FUNCTION__, $logargs, $ex);
			return false;
		}
	}
	
	/**
	 * 更新钱数,不判断钱是否够
	 * @param int $mid
	 * @param Array $aInfo array([mmoney] => -1)
	 * @return Boolean
	 */
	public function UpdateMoney($mid, $aInfo){
		try {
			if (!$mid = fun::uint($mid)) {
				return false;
			}
			if (!isset($aInfo['wmode'])) {
				$aInfo['wmode'] = -1;
			}
			$aInfo['addmoney'] = 0;
			isset($aInfo['mmoney']) && ($aInfo['addmoney'] = $aInfo['mmoney']);
			if (!$sInfo = $this->seria($aInfo)){
				$this->debug(array('mid' => $mid, 'aInfo' => $aInfo, 'msg' => 'UpdateMoney seria error', 'date' => date('Y-m-d H:i:s')), 'setmoney-err.txt');
				return false;
			}
			$packet = new MSWritePackage();
			$packet->WriteBegin(self::CMD_UPDATE_RECORD);
			$packet->WriteInt(1);
			$packet->WriteInt($mid);
			$packet->WriteString($sInfo);
			$packet->WriteEnd();
			$logargs = array('mid:' . $mid, $sInfo);
			$rs = $this->send2CServer($packet->GetPacketBuffer(), $logargs, __FUNCTION__);
			if ($rs !== false) {
				return true; //返回的结果  0 成功 -1 失败
			}
			return false;
		} catch (Exception $ex) {
			$this->logexception(__FUNCTION__, $logargs, $ex);
			return false;
		}
	}

	/**
	 * 创建用户记录,字段为id,钱数,经验值,积分
	 * @param int $mid
	 * @param Array $aInfo array([mmoney] => 2943681, [msavecount] => 16, [vmoney] => 65149, [sngsub] => 32)
	 * CreateRecord(1, "money:1,exp:100");
	 */
	public function CreateRecord($mid, $aInfo) {
		try {
			if (!$mid = fun::uint($mid)) { //非法用户
				return false;
			}
			if (!$sInfo = $this->seria($aInfo)) { //序列化.没有相应的值
				return false;
			}
			$packet = new MSWritePackage();
			$packet->WriteBegin(self::CMD_CREATE_RECORD);
			$packet->WriteInt($mid);
			$packet->WriteString($sInfo);
			$packet->WriteEnd();
			$logargs = array('mid:' . $mid, $sInfo);
			$rs = $this->send2CServer($packet->GetPacketBuffer(), $logargs, __FUNCTION__);
			return $rs !== false;
		} catch (Exception $ex) {
			$this->logexception(__FUNCTION__, $logargs, $ex);
			return false;
		}
	}

	/**
	 * 字段值转移，主要用于银行存取
	 * @param type $mid
	 * @param type $val
	 * @param type $from
	 * @param type $to
	 * @param type $type
	 * @param type $isForce
	 * @param type $wmode
	 * @return boolean
	 */
	public function move($mid, $val, $from, $to, $type = 1, $isForce = 0, $wmode = 0) {
//		try {
//			if (!$mid = fun::uint($mid)) {
//				return false;
//			}
//			$arrUser = oo::minfo()->get($mid, true, true, array('sid'));
//			$aInfo = array();
//			$aInfo['val'] = $val;
//			$aInfo['from'] = $from;
//			$aInfo['to'] = $to;
//			$aInfo['acttype'] = (int) $type; //0存1取
//			$aInfo['sid'] = (int) $arrUser['sid'];
//			$aInfo['wmode'] = (int) $wmode; //451取款 452存款
//
//
//			if (!$sInfo = fun::serialize($aInfo)) {
//				$this->debug(array('mid' => $mid, 'aInfo' => $aInfo, 'msg' => 'updaterecord seria error', 'date' => date('Y-m-d H:i:s')), 'setmoney-err.txt');
//				return false;
//			}
//
//			$packet = new MSWritePackage();
//			$packet->WriteBegin(self::CLIENT_COMMAND_FIELD_TRANSFER);
//			$packet->WriteInt($mid);
//			$packet->WriteString($sInfo);
//			$packet->WriteInt($mid);
//			$packet->WriteInt($isForce === 1 ? 0 : -1);
//			$packet->WriteEnd();
//			$logargs = array('move', 'mid:' . $mid, $sInfo);
//			$rs = $this->send2CServer($packet->GetPacketBuffer(), $logargs, __FUNCTION__);
//			if ($rs !== false) {
//				$sInfo = $rs->ReadString();
//				return array_merge($this->unseria($sInfo), array('flag' => $flag));
//			}
//			return false;
//		} catch (Exception $ex) {
//			$this->logexception(__FUNCTION__, $logargs, $ex);
//			return false;
//		}
	}

	public function cas($mid, $cas, $aInfo) {
		try {
			if (!$mid = fun::uint($mid)) {
				return false;
			}

			$aInfo['cas'] = (int) $cas;

			$aInfo['wmode'] = -1;
			isset($aInfo['wmode']) && $aInfo['wmode'] = (int) $aInfo['wmode'];

			$aInfo['addmoney'] = 0;
			isset($aInfo['mmoney']) && $aInfo['addmoney'] = (int) $aInfo['mmoney'];

			isset($aInfo['sngsub']) && $aInfo['sngsub'] = (int) $aInfo['sngsub'];

			if (!$sInfo = $this->seria($aInfo)) {
				$this->debug(array('mid' => $mid, 'aInfo' => $aInfo, 'msg' => 'updaterecord seria error', 'date' => date('Y-m-d H:i:s')), 'setmoney-err.txt');
				return false;
			}

			$packet = new MSWritePackage();
			$packet->WriteBegin(self::CLIENT_COMMAND_CAS_RECORD);
			$packet->WriteInt($mid);
			$packet->WriteString($sInfo);
			$packet->WriteEnd();
			$logargs = array('mid:' . $mid, $sInfo);
			$rs = $this->send2CServer($packet->GetPacketBuffer(), $logargs, __FUNCTION__);
			if ($rs !== false) {
				$sInfo = $rs->ReadString();
				return array_merge($this->unseria($sInfo), array('flag' => $flag));
			}
			return false;
		} catch (Exception $ex) {
			$this->logexception(__FUNCTION__, $logargs, $ex);
			return false;
		}
	}

	public function update($mid, $aInfo, $isForce = 0) {
		try {
			if (!$mid = fun::uint($mid)) {
				return false;
			}
			if(!isset($aInfo['wmode'])){
				$aInfo['wmode'] = -1;
			}
			$aInfo['wmode'] = (int) $aInfo['wmode'];
			$aInfo['addmoney'] = 0;
			isset($aInfo['mmoney']) && $aInfo['addmoney'] = (int) $aInfo['mmoney'];
			isset($aInfo['sngsub']) && $aInfo['sngsub'] = (int) $aInfo['sngsub'];

			if (!$sInfo = $this->seria($aInfo)) {
				$this->debug(array('mid' => $mid, 'aInfo' => $aInfo, 'msg' => 'updaterecord seria error', 'date' => date('Y-m-d H:i:s')), 'setmoney-err.txt');
				return false;
			}

			$cmd = $isForce === 1 ? self::CLIENT_COMMAND_UPDATE_RECORD_TIL_ZERO : self::CLIENT_COMMAND_UPDATE_RECORD_WITH_CHECK;
			$packet = new MSWritePackage();
			$packet->WriteBegin($cmd);
			$packet->WriteInt($mid);
			$packet->WriteString($sInfo);
			$packet->WriteEnd();
			$logargs = array('mid:' . $mid, $sInfo);
			$rs = $this->send2CServer($packet->GetPacketBuffer(), $logargs, __FUNCTION__);
			if ($rs !== false) {
				if ($isForce === 1) {
					$zNum = $rs->ReadInt(); //如果是置零更新，置零了多少个值
				}
				$sInfo = $rs->ReadString();
				if (empty($sInfo)){
					return false;
				}
				return array_merge($this->unseria($sInfo), array('flag' => $flag));
			}
			return false;
		} catch (Exception $ex) {
			$this->logexception(__FUNCTION__, $logargs, $ex);
			return false;
		}
	}

	/**
	 * 反序列化成数组并且映射成php的key
	 * @param String $sInfo
	 * @return Array
	 */
	private function unseria($sInfo) {
		$aInfo = fun::unserialize($sInfo);

		$aSeria = array_flip($this->aSeria);

		foreach ((array) $aInfo as $k => $v) {

			unset($aInfo[$k]);

			if (key_exists($k, $aSeria)) {
				$aInfo[$aSeria[$k]] = (string) $v;
			}
		}
		return (array) $aInfo;
	}

	/**
	 * 映射成Server的key并序列化成字符串
	 * @param Array $aInfo
	 * @return String
	 */
	private function seria($aInfo) {

		$aSeria = $this->aSeria; //

		unset($aInfo['mid']); //该key不允许传入

		foreach ((array) $aInfo as $k => $v) {

			unset($aInfo[$k]);

			if (key_exists($k, $aSeria)) {
				$aInfo[$aSeria[$k]] = (string) $v;
			}
		}
		return fun::serialize($aInfo);
	}
	
	private function debug($msg, $file='MServer.txt'){
		if(is_array($msg)){
			$msg = json_encode($msg);
		}
		fun::logs($file, $msg);
	}

}
