<?php
class LogSwooleWritePackage{
	private $buff;
	public $len = 0;
	public $cmdType = 0;

	public function WriteBegin($cmdType) {
		$this->cmdType = $cmdType;
		$this->buff = "";
		$this->len = 0;
	}

	public function WriteString($str) {
		$len = strlen($str);
		$this->WriteInt($len);
		$this->buff.=$str;
		$this->len+=$len;
	}

	public function WriteInt($int) {
		$this->buff.=pack('n', $int);
		$this->len+=2;
	}

	public function WriteByte($byte) {
		$this->buff.=pack('C', $byte);
		$this->len+=1;
	}

	public function WriteEnd() {
		if ($this->len > 8000) {
			//swoole-1.7.22以下版本,udp包大于8K时会出问题,此处禁止发送
			return false;
		}
		return pack('n', $this->cmdType) . pack('n', $this->len) . $this->buff;
	}
}
/**
 * 日志上传类
 */
class LogSwoole{

	private $ip, $port;

	public function __construct($udpIp, $udpPort) {
		$this->ip = $udpIp;
		$this->port = $udpPort;
	}

	public function debug($params, $fname = 'debug.txt', $fsize = 1){
		is_scalar($params) or ( $params = var_export($params, true)); //是简单数据
		if (!$params) {
			return false;
		}
		$udp=new LogSwooleWritePackage();
		$udp->WriteBegin(0x0100);
		$udp->WriteString($fname);
		$udp->WriteInt($fsize);
		$udp->WriteByte(0);//是否备份，当前无用，用于后续备用		
		$udp->WriteByte(0);//是否记录来源ip
		$udp->WriteString('-'.$params);
		$content = $udp->WriteEnd();
		if(!$content){
			return false;
		}
		$fp = stream_socket_client("udp://{$this->ip}:{$this->port}", $errno, $errstr, 2);
		if(!$fp){
			return false;
		}else{
			fwrite($fp, $content);
			fclose($fp);
		 	return true;
		}
	}
}
