<?php
/**
* 接收包 处理包
*/
class DoPack{
//	private $readPackage;
	private $package;
	private $cfg = array();//配置
	public function __construct(){
//		$this->readPackage = new GSReadPackage();
		$this->package = new sxdPacket();
		$this->init();
	}
	
	/**
	* 加载配置
	*/
	public function init(){
		$cfgFile = SERVER_ROOT.'cfg/pack.php';
		if(file_exists($cfgFile)){
			$this->cfg = include $cfgFile;
		}
	}
	/**
	* 获得需要发送包的字符串
	*/
	public function getSendPack($cmd, $aData){
		$aPack = $this->cfg['send'][$cmd];
		if(!is_array($aPack)){
			Main::logs($cmd.' 发送命令不存在', 'doPackErr');
			return false;
		}
		if(count($aPack) != count($aData)){
			Main::logs($cmd.' 发送数据长度错误', 'doPackErr');
			return false;
		}
		$this->writePackage->WriteBegin($cmd);
		foreach($aPack as $key=>$type){
			$this->writeType($type, $aData[$key]);
		}
		$this->writePackage->WriteEnd();
		$packet_buff = $this->writePackage->GetPacketBuffer();
		return $packet_buff;
	}

	public function getSendPackV2($cmd, $aData){
		$aPack = $this->cfg['send'][$cmd];
		if(!is_array($aPack)){
			Main::logs($cmd.' 发送命令不存在', 'doPackErr');
			return false;
		}
		$rule = $this->_rule($aPack);
		$str = http_build_query($aData);
		return $this->package->writeBegin($str, $rule);
	}

	/**
	 * @param $aPack
	 * @return array
	 */
	public function _rule($aPack){
		$rule =[
			"MainCmdID"=>$aPack[0],
			"SubCmdID"=>$aPack[1],
			"DataType"=>2,
			"TimeStamp"=>1,
			"ExtCmd"=>1
		];
		return $rule;
	}
	
	private function writeType($type, $val){
		if(is_array($type)){
			$len = count((array)$val);
			$this->writeType('byte', $len);
			for($i=0;$i<$len;$i++){
				foreach($type as $y=>$v){
					$this->writeType($v, $val[$i][$y]);
				}
			}
			return;
		}
		switch($type){
			case 'byte':
				$this->writePackage->WriteByte($val);
				break;
			case 'short':
				$this->writePackage->WriteShort($val);
				break;
			case 'int':
				$this->writePackage->WriteInt($val);
				break;
			case 'int64':
				$this->writePackage->WriteInt64($val);
				break;
			case 'string':
				$this->writePackage->WriteString($val);
				break;
		}
	}
	
	/**
	* 收到数据
	*/
	public function revPack($packet_buff){
		$val = $this->readPackage->ReadPackageBuffer($packet_buff);
		if($val != 1){
			Main::logs($val. ' buff err '.bin2hex($packet_buff) , 'doPackErr');
			return false;
		}
		$cmd = $this->readPackage->CmdType;
		if($cmd == 0x888){//管理员命令特殊处理
			return array('cmd'=>$this->readPackage->GetCmdType(), 'data'=>$this->readPackage);
		}
		if(!isset($this->cfg['rev'][$cmd])){
			Main::logs($cmd.' 接收命令不存在', 'doPackErr');
			return false;
		}
		$aPack =  $this->cfg['rev'][$cmd];
		$aData = array();
		try{
			foreach($aPack as $key=>$type){
				$this->readType($type, $key, $aData);
			}
		}catch(Exception $ex){
			$aErr = array('readTypeErr' ,$ex->getMessage(), $ex->getLine(), bin2hex($packet_buff));
			Main::logs($aErr , 'doPackErr');
			return false;
		}
		return array('cmd'=>$this->readPackage->GetCmdType(), 'data'=>$aData);
	}

	public function revPackV2($packet_buff){
		$ret = $this->package->readBeginV2($packet_buff);
		if(!$ret){
			Main::logs(json_encode($ret). ' buff err '.bin2hex($packet_buff) , 'doPackErr');
			return false;
		}
		return $ret;
	}
	
	private function readType($type, $key, &$aData){
		if(is_array($type)){
			$len = $this->readPackage->ReadByte();
			for($i=0;$i<$len;$i++){
				$aTemp = array();
				foreach($type as $y=>$v){
					$this->readType($v, $y , $aTemp);
				}
				$aData[$key][$i] = $aTemp;
			}
			return;
		}
		switch($type){
			case 'byte':
				$aData[$key] = $this->readPackage->ReadByte();
				break;
			case 'short':
				$aData[$key] = $this->readPackage->ReadShort();
				break;
			case 'int':
				$aData[$key] = $this->readPackage->ReadInt();
				break;
			case 'int64':
				$aData[$key] = $this->readPackage->ReadInt64();
				break;
			case 'string':
				$aData[$key] = $this->readPackage->ReadString();
				break;
		}
	}
}