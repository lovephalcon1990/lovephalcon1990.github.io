<?php

namespace Zengym\Lib\Protocols;

class UdpReadPackage {
	
	private $buff;
	private $offset = 0;
	public $cmdType = 0;
	
	public function BeginReadBuff($buff) {
		$this->offset = 0;
		$this->buff = $buff;
		$this->cmdType = $this->ReadInt();
		$len = $this->ReadInt();
		if (($len + 4) !== strlen($buff)) {
			$this->buff = "";
			return false;
		}
		return true;
	}
	
	public function ReadString() {
		$len = $this->ReadInt();
		$string = substr($this->buff, $this->offset, $len);
		$this->offset += $len;
		return $string;
	}
	
	/**
	 * 和gameserver兼容
	 * @param type $int
	 */
	public function ReadNewString() {
		$len = $this->ReadNewInt();
		$string = substr($this->buff, $this->offset, $len - 1);
		$this->offset += $len;
		return $string;
	}
	
	/**
	 * 和gameserver兼容
	 * @param type $int
	 */
	public function ReadNewInt() {
		$lenInfo = unpack('i', substr($this->buff, $this->offset, 4));
		$this->offset += 4;
		return $lenInfo[1];
	}
	
	public function ReadInt() {
		$lenInfo = unpack('n', substr($this->buff, $this->offset, 2));
		$this->offset += 2;
		return $lenInfo[1];
	}
	
	public function ReadUInt() {
		$lenInfo = unpack('N', substr($this->buff, $this->offset, 4));
		$this->offset += 4;
		return $lenInfo[1];
	}
	
	public function ReadByte() {
		$lenInfo = unpack('C', substr($this->buff, $this->offset, 1));
		$this->offset += 1;
		return $lenInfo[1];
	}
	
}