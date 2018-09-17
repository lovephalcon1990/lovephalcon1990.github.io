<?php
include_once __DIR__ . '/GSSocketPackage.php';
/**
 * Description of ReadPackage
 *
 */
class GSReadPackage extends GSSocketPackage {

	public $m_Offset = 0;

	public function ReadPackageBuffer($packet_buff){
		if($packet_buff == ''){
			return -1;
		}
		$this->realpacket_buff = $packet_buff;
		if (!isset($this->m_packetBuffer)){
			$this->m_packetBuffer = new swoole_buffer(9000);
		} else {
			$this->m_packetBuffer->clear();
		}
		$this->package_realsize = $this->m_packetBuffer->append($packet_buff);
		if ($this->package_realsize < self::PACKET_HEADER_SIZE) {
			//包头为9个字节
			return -1;
		}
		if ($this->package_realsize > self::PACKET_BUFFER_SIZE) {
			//包长度为2个字节，包内容最多65535个字节
			return -2;
		}
		$headerInfo = unpack("c2Iden/sCmdType/cVer/sLen", $this->m_packetBuffer->read(0, self::PACKET_HEADER_SIZE));
		if ($headerInfo['Len'] >= 0 && $headerInfo['Len'] != $this->package_realsize - self::PACKET_HEADER_SIZE) {
			//throw new VerifyException("非法包头",-3);
			return -3;
		}
		if ($headerInfo['Iden1'] != ord('S') || $headerInfo['Iden2'] != ord('W')) {
			//throw new VerifyException("非法包头",-4);
			return -4;
		}
		if ($headerInfo['Ver'] != self::SERVER_PACEKTVER) {
			//throw new VerifyException("非法包头",-5);
			return -5;
		}
		if ($headerInfo['CmdType'] <= 0 || $headerInfo['CmdType'] >= 32000) {
			//throw new VerifyException("非法包头",-6);
			return -6;
		}
		$this->CmdType = $headerInfo['CmdType'];
		$this->m_packetSize = $headerInfo['Len'];
		if ($this->m_packetSize) {
			$packetBuffer = $this->m_packetBuffer->read(self::PACKET_HEADER_SIZE, $this->m_packetSize);
			$DecryptObj = new GSEncryptDecrypt();
			$DecryptObj->DecryptBuffer($packetBuffer, $this->m_packetSize);
			$this->m_packetBuffer->write(self::PACKET_HEADER_SIZE, $packetBuffer);
		}
		$this->m_Offset = self::PACKET_HEADER_SIZE;
		return 1;
	}

	public function GetPacketBuffer() {
		return $this->realpacket_buff;
	}

	public function GetLen() {
		return $this->package_realsize - $this->m_Offset;
	}

	public function ReadByte() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new VerifyException("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 1);
		if ($temp === false) {
			throw new VerifyException("读取溢出");
		}
		$value = unpack("C", $temp);
		$this->m_Offset+=1;
		return $value[1];
	}

	public function ReadShort() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new VerifyException("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 2);
		if ($temp === false) {
			throw new VerifyException("读取溢出");
		}
		$value = unpack("s", $temp);
		$this->m_Offset+=2;
		return $value[1];
	}

	public function ReadInt() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new VerifyException("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 4);
		if ($temp === false) {
			throw new VerifyException("读取溢出");
		}
		$value = unpack("i", $temp);
		$this->m_Offset+=4;
		return $value[1];
	}
	
	public function ReadInt64(){
		$low = $this->ReadUInt();
		$high = $this->ReadInt();
		return $low | ($high<<32);
	}

	public function ReadUInt() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new VerifyException("读取溢出");
		}
		$temp = $this->m_packetBuffer->read($this->m_Offset, 4);
		if ($temp === false) {
			throw new VerifyException("读取溢出");
		}
		list(, $var_unsigned) = unpack("L", $temp);
		$this->m_Offset+=4;
		return floatval(sprintf("%u", $var_unsigned));
	}

	public function ReadString() {
		if ($this->package_realsize <= $this->m_Offset) {
			throw new VerifyException("读取溢出");
		}
		$len = $this->ReadInt();
		if ($len === false) {
			throw new VerifyException("读取溢出");
		}
		$realLen = $this->m_packetBuffer->length - $this->m_Offset;
		if ($realLen < $len - 1) {
			throw new VerifyException("读取溢出");
		}
		$value = $this->m_packetBuffer->read($this->m_Offset, $len - 1);
		$this->m_Offset+=$len;
		return $value;
	}	

}
