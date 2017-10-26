<?php

/**
 * Description of ReadPackage
 */
namespace Zengym\Lib\Protocols;
use swoole_buffer;
//use Zengym\Lib\Protocols\SocketPackage;
//use Zengym\Lib\Protocols\EncryptDecrypt;

class ReadPackage extends SocketPackage {

	private $m_Offset = 0;
	private $package_realsize = 0;
	private $realpacket_buff='';
	
	public function ReadPackageBuffer($packet_buff) {
		$this->realpacket_buff=$packet_buff;	
		if (!$this->m_packetBuffer) {
			$this->m_packetBuffer = new swoole_buffer(65537);
		} else {
			$this->m_packetBuffer->clear();
		}
		$this->package_realsize = $this->m_packetBuffer->append($packet_buff);
		if ($this->package_realsize < 7) {
			//包头为7个字节
			//非法包,包过小;
			return -1;
		}
		if ($this->package_realsize > 65537) {
			//包长度为2个字节，包内容最多65535个字节
			//非法包,包过大;
			return -2;
		}
		$header = $this->m_packetBuffer->read(0,7);
		
		$headerInfo = unpack("nPackLen/c2Iden/cVer/nCmdType", $header);
		if ($headerInfo['PackLen'] != ($this->package_realsize - 2)) {
			//非法包,包内容和包长度不符合;
			return -3;
		}
		
		if ($headerInfo['Iden1'] != ord(self::PACKET_NAME_1) || $headerInfo['Iden2'] != ord(self::PACKET_NAME_2)) {
			//非法包头;
			return -4;
		}
		if ($headerInfo['Ver'] != self::SERVER_PACEKTVER) {
			//非法包头;
			return -5;
		}
		if ($headerInfo['CmdType'] <= 0 || $headerInfo['CmdType'] >= 32000) {
			//throw new Exception("非法包头,-3");
			return -6;
		}
		$this->CmdType = $headerInfo['CmdType'];
		$this->m_packetSize = $headerInfo['PackLen'] - 5;
		if ($this->m_packetSize) {
			$packetBuffer = $this->m_packetBuffer->read(7,$this->m_packetSize);
			if ($this->m_Encrypt) {
				$DecryptObj = new EncryptDecrypt();
				$DecryptObj->Decrypt($packetBuffer, $this->m_packetSize, 0);
				$this->m_packetBuffer->write(7, $packetBuffer);
			}
		}
		$this->m_Offset = 7;
		return 1;
	}
	public function GetBuffer() {
		return $this->realpacket_buff;
	}

	public function GetLen() {
		return $this->package_realsize - $this->m_Offset;
	}

	public function Byte() {
		if($this->package_realsize <= $this->m_Offset){
			return false;
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 1);
		$this->m_Offset+=1;
		if($temp===false){
			return false;
		}
		$value = unpack("C", $temp);
		return $value[1];
	}

	public function Short() {
		if($this->package_realsize<=$this->m_Offset){
			return false;
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 2);
		$this->m_Offset+=2;
		if($temp===false){
			return false;
		}
		$value = unpack("n", $temp);
		return $value[1];
	}

	public function Int() {
		if($this->package_realsize<=$this->m_Offset){
			return false;
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 4);		
		$this->m_Offset+=4;
		if($temp===false){
			return false;
		}
		$value = unpack("N", $temp);
		return $value[1];
	}

	public function String() {
		if($this->package_realsize<=$this->m_Offset){
			return false;
		}
		$len = $this->Int();
		if($len===false){
			return false;
		}
		$realLen=$this->m_packetBuffer->length-$this->m_Offset;
		if($realLen<$len-1){
			return false;
		}
		$value = $this->m_packetBuffer->read($this->m_Offset, $len - 1);
		$this->m_Offset+=$len;
		return $value;
	}

}
