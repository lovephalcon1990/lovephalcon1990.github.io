<?php
namespace Zengym\Apps\Log\Model;

use Zengym\Lib\Helper\Log;
/**
 * 用于日志调试
 *
 */
class DebugHandle{
	
	/**
	 * 解包并转发
	 * @param type $udpPackage
	 */
	public static function Package($udpPackage, $client_info, $taskCnt){
		$data = [//以下$udpPackage解包顺序不可以乱
			'fname' => $udpPackage->ReadString(), //文件名
			'fsize' => $udpPackage->ReadInt(), //文件大小
			'is_bak' => $udpPackage->ReadByte(), //超出大小是否备份
			'is_ip' => $udpPackage->ReadByte(), //是否记录来源ip
			'content' => $udpPackage->ReadString(),
			'ip' => $client_info['address'],
		];
		$task_id = crc32($data['fname']) % $taskCnt;
		return array('data' => $data, 'task_id' => $task_id);
	}

	/**
	 * 记录日志
	 * @param type $data
	 */
	public function Log($data){
		$ext = [];
		if((int)$data['is_ip'] && $data['ip']){
			$ext['ip'] = $data['ip'];
		}
		$ext['bak'] = (int)$data['is_bak'];
		return Log::debugWrite($data['content'], $data['fname'], $data['fsize'], $ext);
	}

}