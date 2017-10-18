<?php
/**
 * @uses:writePackage
 */
namespace Zengym\Lib\Protocols;

use swoole_buffer;


class WritePackage extends SocketPackage {
	
	/**
	 * @param $CmdType
	 */
	public function Begin($CmdType) {
		$this->CmdType = $CmdType;
		if(!$this->m_packetBuffer){
			$this->m_packetBuffer = new swoole_buffer(1024);
		}else{
			$this->m_packetBuffer->clear();
		}
		$this->m_packetSize =0;
	}
	
	/**
	 * @param $value
	 */
	public function Int($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("N", $value));
	}
	
	/**
	 * @param $value
	 */
	public function Byte($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("C", $value));
	}
	
	/**
	 * @param $value
	 */
	public function Short($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("n", $value));
	}
	
	/**
	 * @param $value
	 */
	public function String($value) {
		$len = strlen($value) + 1;
		$this->m_packetBuffer->append(pack("N", $len));
		$this->m_packetBuffer->append($value);
		$this->m_packetSize = $this->m_packetBuffer->append(pack("C", 0));
	}
	
	/**
	 * @uses: perfect swoole_buffer
	 */
	public function End() {
		$tmp = "";
		if ($this->m_packetSize) {
			$tmp = $this->m_packetBuffer->read(0, $this->m_packetSize);
			$this->m_packetBuffer->clear();
			if ($this->m_Encrypt) {
				$EncryptObj = new EncryptDecrypt();
				$EncryptObj->Encrypt($tmp, 0, $this->m_packetSize);
			}
		}
		$head = pack("n", $this->m_packetSize + 5);
		$this->m_packetBuffer->append($head);
		$this->m_packetBuffer->append(self::PACKET_NAME);
		$this->m_packetBuffer->append(pack("c", self::SERVER_PACEKTVER));  //ver
		$this->m_packetSize = $this->m_packetBuffer->append(pack("n", $this->CmdType));   //cmd
		if ($tmp) {
			$this->m_packetSize = $this->m_packetBuffer->append($tmp);
		}
	}
	
	/**
	 * @uses swoole_buffer length
	 * @return mixed
	 */
	public function GetBuffer() {
		return $this->m_packetBuffer->read(0, $this->m_packetSize);
	}

	

}
