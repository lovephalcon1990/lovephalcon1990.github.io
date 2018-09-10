<?php
/**
 * svr soket二进制收发包
 */
class Packet {
    public $buffer;
    public $DataLen;
    public $DataSize;
    public $size;
	public static $packet=[];//包header
	public static $body = [];//包 body


    public function readBegin($buffer){
        $this->buffer = $buffer;
        $this->readSvrHead();
        $this->readUserNetInfo();
        $this->readClnNetHead();
    }

	/**
	 * @uses 获取客户端 包数据
	 * @param $buffer
	 * @return array
	 */
    public function readBeginV2($buffer){
		self::$packet=[];
		self::$body=[];
		$this->buffer = $buffer;
		return $this->readClnNetHead();
	}

	public function readFrSvr($buffer){
		self::$packet=[];
		$this->buffer = $buffer;
		$this->DataSize = $this->readInt2();
		switch ($this->DataSize){
			case 46: $this->readBeginV4();break;
			default: $this->readBeginV3();
		}

	}

	/**
	 * @uses 获取svr 包数据
	 */
	public function readBeginV3(){
		self::$packet=[];
//		$this->buffer = $buffer;
//		$this->DataSize = $this->readInt2();
		self::$packet['DataType'] = $this->readInt2();
		if(self::$packet['DataType'] == 1){
			return true;
		}
		self::$packet['CmdID'] = $this->readInt2();
		self::$packet['SvrID'] = $this->readInt4();
		return true;
	}

	/**
	 * @uses 获取svr 扣币数据包
	 */
	public function readBeginV4(){
		self::$packet=[];
		self::$packet['DataType'] = $this->readInt2();
		self::$packet['CmdID'] = $this->readInt2();//6

		self::$packet['uResult'] = $this->readInt4();
		self::$packet['uSrcSvrType'] = $this->readInt4();
		self::$packet['uSrcSvrID'] = $this->readInt4();
		self::$packet['uOpType'] = $this->readInt4();
		self::$packet['uUserID'] = $this->readInt4();
		self::$packet['nType'] = $this->readInt4();
		self::$packet['nMoney'] = $this->readInt8();
		self::$packet['uCurMoney'] = $this->readInt8();
		return true;
	}
    /**
     * svr包头 6个字节
     * DataLen 总长度
     * CmdType 消息类型
     * CmdID 主命令
     */    
    public function readSvrHead(){
        $this->DataLen = $this->readInt2();
        self::$packet['CmdType'] = $this->readInt2();
        self::$packet['CmdID'] = $this->readInt2();
    }
    /**
     * 用户信息 128个字节
     */
    public function readUserNetInfo(){
        self::$packet['UserNetInfoBuff'] = substr($this->buffer, 0, 128);
        self::$packet['Sid'] = $this->readInt4();
        $ip = self::$packet['ClientIp'] = $this->readInt4();
        self::$packet['AccessID'] = $this->readInt4();
        $_GET['uid'] = self::$packet['UserID'] = $this->readInt4();
        $_GET['pid'] = self::$packet['PlatID'] = $this->readInt4();
        $_GET['appid'] = self::$packet['AppTypeID'] = $this->readInt4();
        $_GET['mcid'] = self::$packet['MChanID'] = $this->readInt4();
        $_GET['scid'] = self::$packet['SChanID'] = $this->readInt4();
        self::$packet['State'] = $this->readInt4();
        self::$packet['RoomID'] = $this->readInt4();
        $_GET['mcmd'] = self::$packet['Mcmd'] = $this->readInt4();
        $_GET['scmd'] = self::$packet['Scmd'] = $this->readInt4();
        $aVer = explode("\0", substr($this->buffer, 0, 16));//string 以 \0结束

		$_GET['ver'] = $aVer[0];
        $this->buffer = substr($this->buffer, 80);//Version ExData 占了80位
        $_GET['ip'] = fun::ntoip($ip);

    }
    /**
     * 客户端信息 16个字节
     */
    public function readClnNetHead(){
        $this->DataSize = $this->readInt2();
        self::$packet['MainCmdID'] = $this->readInt2();
        self::$packet['SubCmdID'] = $this->readInt2();
        self::$packet['DataType'] = $this->readInt2();
        self::$packet['TimeStamp'] = $this->readInt4();
        self::$packet['ExtCmd'] = $this->readInt4();

        $size = $this->DataSize - 16;
        $SzData = $this->readString($size);

        if($SzData){
            parse_str($SzData, $array);
            foreach ($array as $key => $value) {
                self::$body[$key] = $value;
            }
        }
//        if(self::$packet['MainCmdID'] != 1 && self::$packet['SubCmdID'] !=11 ){
//			Log::debug(['body'=>self::$body,'data'=>self::$packet,'length'=>$this->DataSize,'Szdata'=>$SzData],"rbuff.log");
//		}
        return ['body'=>self::$body,'data'=>self::$packet,'length'=>$this->DataSize,'Szdata'=>$SzData];
    }

	/**
	 * @uses lua客户端 写报数据
	 * @param $string
	 * @param array $rule
	 * @return string
	 */
    public function writeBegin($string,$rule=[]){
    	if(empty($rule)){
    		$rule = self::$packet;
		}
        $this->buffer = '';
        $this->size = 0;
//        $this->writeSvrHead();
//        $this->writeUserNetInfo();
        $this->writeClnNetHead($string,$rule);
        $this->size += 2;//DataSize
        $this->buffer = pack("s", $this->size).$this->buffer;
        return $this->buffer;
    }

	/**
	 * @uses websocket 客户端写包数据
	 * @param $data
	 * @param array $rule
	 * @return string
	 */
    public function writeBeginV2($data, $rule=[]){
		if(empty($rule)){
			$rule = self::$packet;
		}
		$temp = ['body'=>$data];
		$this->buffer = json_encode(array_merge($temp, $rule));

		return $this->buffer;
	}

	/**
	 * @uses  跟svr 通信登录数据包
	 */
	public function writeBeginV3($srvid=10000){
		$this->buffer='';
		$this->size = 0;
//		self::$packe =['CmdType'=>13,'CmdID'=>123];
		//包头
		$this->writeInt2(13); //服务之间通信 13
		$this->writeInt2(1); //服务注册 1
		//包体
		$this->writeByte(7); //直播type 7
		$this->writeInt4($srvid); //直播服务器 10000
		$this->size += 2;//DataSize
		$this->buffer = pack("s", $this->size).$this->buffer;
		return $this->buffer;
		// 13 5 7 房间id
	}

	/**
	 * @uses 跟svr 打牌房间内直播 数据包
	 * @return string
	 */
	public function writeBeginV4($sid, $uid, $money, $srvid=10000, $reason=77, $type=1){
		$this->buffer='';
		$this->size = 0;
		$this->writeInt2(13);//包头
		$this->writeInt2(5);//服务器之间消息透传转发

		$this->writeInt4(7);//直播类型
		$this->writeInt4($srvid);//直播服务器id
		$this->writeInt4(3);//固定值
		$this->writeInt4((int)$sid); //房间id

		$this->writeInt2(34);//包体长度
		$this->writeInt2(14);//固定值
		$this->writeInt2(1401);//固定值

		$this->writeInt4(7);//直播类型
		$this->writeInt4(10000);//直播服务器id
		$this->writeInt4($reason);//直播礼物消耗reason

		$this->writeInt4((int)$uid);//uid
		$this->writeInt4($type);//金币类型 0-钻石 1-金币 3-积分
		$this->writeInt8((int)$money);//扣费money

		$this->size += 2;//DataSize
		$this->buffer = pack("s", $this->size).$this->buffer;
		return $this->buffer;

	}

	public function readTest($buff){
		$this->buffer = $buff;
		self::$packet=[];
		self::$packet['len'] = $this->readInt2();
		self::$packet['DataType'] = $this->readInt2();
		self::$packet['CmdID'] = $this->readInt2();//6

		self::$packet['zhibo'] = $this->readInt4();
		self::$packet['srvid'] = $this->readInt4();
		self::$packet['guding'] = $this->readInt4();
		self::$packet['gameroomid'] = $this->readInt4();

		self::$packet['bodylen'] = $this->readInt2();
		self::$packet['g1'] = $this->readInt2();
		self::$packet['g2'] = $this->readInt2();

		self::$packet['zhibo1'] = $this->readInt4();
		self::$packet['mysrvid'] = $this->readInt4();
		self::$packet['reason'] = $this->readInt4();

		self::$packet['uid'] = $this->readInt4();
		self::$packet['type'] = $this->readInt4();
		self::$packet['money'] = $this->readInt8();
		return self::$packet;
	}

    /**
     * svr包头 6个字节
     */
    public function writeSvrHead(){
        $this->writeInt2(self::$packet['CmdType']);//消息类型
        $this->writeInt2(self::$packet['CmdID']);//主命令码
    }
    public function writeUserNetInfo(){
        $this->buffer .= self::$packet['UserNetInfoBuff'];
        $this->size += strlen(self::$packet['UserNetInfoBuff']);
    }

    public function writeClnNetHead($string, $rule){
        $datatype = is_int($string) ? 1 : $rule['DataType'];
        $this->writeInt2($rule['MainCmdID']);
        $this->writeInt2($rule['SubCmdID']);
        $this->writeInt2($datatype);
        $this->writeInt4($rule['TimeStamp']);
        $this->writeInt4($rule['ExtCmd']);

        $this->writeString($string);
//		if($rule['MainCmdID'] != 1 && $rule['SubCmdID'] != 11 ){
//			Log::debug(['data'=>$rule,'length'=>$this->size,],"wbuff.log");
//		}
    }
    public function writeInt4($value){
        $this->buffer .= pack("L", $value);
        $this->size += 4;
    }
    public function writeByte($value){
        $this->buffer .= pack("C", $value);
        $this->size += 1;
    }
    public function writeInt2($value){
        $this->buffer .= pack("s", $value);
        $this->size += 2;
    }

	public function writeInt8($value){
		$this->buffer .= pack("q", $value);
		$this->size += 8;
	}

    public function writeString($value){
        $len = strlen($value);
        $this->buffer .= $value;
        $this->size += $len;
    }
    public function writeEnd(){
        $this->size += 2;//DataSize
        $this->buffer = pack("s", $this->size).$this->buffer;
        return $this->buffer;
    }
    public function readInt4(){
        $temp = substr($this->buffer, 0, 4);
        $value = unpack("L", $temp);
        $this->buffer = substr($this->buffer, 4);
        return $value[1];
    }

	public function readInt8(){
		$temp = substr($this->buffer, 0, 8);
		$value = unpack("q", $temp);
		$this->buffer = substr($this->buffer, 8);
		return $value[1];
	}

    public function readByte($n = 1){
        $temp = substr($this->buffer, 0, $n);
        $value = unpack("C", $temp);
        $this->buffer = substr($this->buffer, $n);
        return $value[1];
    }
    public function readByte2($n = 1){
        $temp = substr($this->buffer, 0, $n);
        $value = unpack("c", $temp);
        $this->buffer = substr($this->buffer, $n);
        return $value[1];
    }
    public function readInt2(){
        $temp = substr($this->buffer, 0, 2);
        $value = unpack("s", $temp);
        $this->buffer = substr($this->buffer, 2);
        return $value[1];
    }
    public function readString($len = 0){
        $len or $len = $this->DataSize;
        $value = substr($this->buffer, 0, $len);
        $this->buffer = substr($this->buffer, $len);
        return $value;
    }
}
