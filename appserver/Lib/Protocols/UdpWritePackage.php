<?php

namespace Zengym\Lib\Protocols;

class UdpWritePackage {
	
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
		$this->buff .= $str;
		$this->len += $len;
	}
	
	/**
	 * 和gameserver兼容
	 * @param type $int
	 */
	public function WriteNewString($str) {
		$len = strlen($str) + 1;
		$this->WriteNewInt($len);
		$this->buff .= $str . pack("C", 0);
		$this->len += $len;
	}
	
	public function WriteInt($int) {
		$this->buff .= pack('n', $int);
		$this->len += 2;
	}
	
	/**
	 * 和gameserver兼容
	 * @param type $int
	 */
	public function WriteNewInt($int) {
		$this->buff .= pack('I', $int);
		$this->len += 4;
	}
	
	public function WriteUInt($int) {
		$this->buff .= pack('N', $int);
		$this->len += 4;
	}
	
	public function WriteByte($byte) {
		$this->buff .= pack('C', $byte);
		$this->len += 1;
	}
	
	public function WriteEnd() {
		if ($this->len > 8000) {
			//swoole-1.7.22以下版本,udp包大于8K时会出问题,此处禁止发送
			return false;
		}
		return pack('n', $this->cmdType) . pack('n', $this->len) . $this->buff;
	}
	
}