<?php
include_once __DIR__ . '/MSSocketPackage.php';
class MSWritePackage extends MSSocketPackage {

	public function WriteEnd() {
		$EncryptObj = new MSEncryptDecrypt();
		$content = $this->GetPacketBuffer();
		$code = $EncryptObj->EncryptBuffer($content, 0, $this->m_packetSize);
		$this->m_packetBuffer->clear();
		$this->m_packetBuffer->append("IC");
		$this->m_packetBuffer->append(pack("s", $this->CmdType));
		$this->m_packetBuffer->append(pack("c", self::SERVER_PACEKTVER));
		$this->m_packetBuffer->append(pack("c", self::SERVER_SUBPACKETVER));
		$this->m_packetBuffer->append(pack("s", $this->m_packetSize));
		$this->m_packetBuffer->append(pack("c", $code));
		$this->m_packetSize = $this->m_packetBuffer->append($content);
	}
	
	public function WriteBegin($CmdType) {
		$this->CmdType = $CmdType;
		$this->m_packetSize = 0;
		if (!$this->m_packetBuffer) {
			$this->m_packetBuffer = new swoole_buffer(1024);
		} else {
			$this->m_packetBuffer->clear();
		}
	}

	public function GetPacketBuffer() {
		return $this->m_packetBuffer->read(0, $this->m_packetSize);
	}

	public function WriteInt($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("i", $value));
	}
	
	public function WriteInt64($value){
		$low = $value & 0xFFFFFFFF;
		$high = ($value >> 32) & 0xFFFFFFFF;
		$this->WriteInt($low);
		$this->WriteInt($high);
	}

	public function WriteUInt($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("I", $value));
	}

	public function WriteByte($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("C", $value));
	}

	public function WriteShort($value) {
		$this->m_packetSize = $this->m_packetBuffer->append(pack("s", $value));
	}

	public function WriteString($value) {
		$len = strlen($value) + 1;
		$this->m_packetBuffer->append(pack("i", $len));
		if ($len > 1) {
			$this->m_packetBuffer->append($value);
		}
		$this->m_packetSize = $this->m_packetBuffer->append(pack("C", 0));
	}

}
