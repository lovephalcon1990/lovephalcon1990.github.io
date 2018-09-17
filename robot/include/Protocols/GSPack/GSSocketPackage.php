<?php
include_once __DIR__ . '/GSEncryptDecrypt.php';
/**
 * 适用于Tcp/UDP之间传递包读写
 *
 */
abstract class GSSocketPackage {
	const PACKET_BUFFER_SIZE = 8192;
	const SERVER_PACEKTVER = 1;
	const PACKET_HEADER_SIZE = 7;
	abstract function GetPacketBuffer();
	protected $m_packetBuffer;
	public function __construct(){

	}

	public function GetPacketSize() {
		return $this->m_packetSize;
	}

	public function GetCmdType() {
		return '0x' . dechex($this->CmdType);
	}

}
