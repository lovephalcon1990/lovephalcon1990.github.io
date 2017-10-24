<?php
namespace Zengym\Lib\Protocols;
/**
 * 进程间通讯包格式
 *
 */

class IpcPackage {

	public $Fd;
	public $From_id;
	public $Data;
	public $Action;

	public function __construct($fd, $from_id, $action, $data) {
		if(is_array($data)){
			$data = json_encode($data);
		}
		$this->Fd = $fd;
		$this->From_id = $from_id;
		$this->Action = $action;
		$this->Data = $data;
	}

	/**
	 *  将ipc包转换封包成 String
	 * @param IpcPackage $ipcPackage
	 * @return type
	 */
	public static function IpcPack2String(IpcPackage $ipcPackage) {
		return $ipcPackage->Fd . '|' . $ipcPackage->From_id . '|' . $ipcPackage->Action . '|' . $ipcPackage->Data;
	}

	/**
	 * 将接收的字符串解包成 ipcPackage
	 * @param type $data
	 * @return IpcPackage
	 */
	public static function String2IpcPack($data) {
		$jg1 = strpos($data, '|');
		$fd = substr($data, 0, $jg1);

		$jg2 = strpos($data, '|', $jg1 + 1);
		$from_id = substr($data, $jg1 + 1, $jg2 - $jg1 - 1);

		$jg3 = strpos($data, '|', $jg2 + 1);
		$action = substr($data, $jg2 + 1, $jg3 - $jg2 - 1);
		$data = substr($data, $jg3 + 1);
		return new IpcPackage($fd, $from_id, $action, $data);
	}

}
