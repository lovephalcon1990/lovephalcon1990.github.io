<?php
namespace Zengym\Apps\Log\Model;

/**
 * 性能监控日志
 *
 */
class MonitorHandle {
	
	/**
	 * 文件最大 < 1m * 1024 * 1024
	 * @var integer
	 */
	const FILE_WRITE_MAX_SIZE = 1048576;
	
	/**
	 * 每次发送多少个数据文件到魔方
	 * @var integer
	 */
	const SEND_DATA_TO_MF_ONCE_LIMIT = 10;
	
	/**
	 * API日志收集数据文件数超过多少个进行上报魔方
	 * @var integer
	 */
	const SEND_DATA_TODO_MIN_VAL = 10000000000;
	
	/**
	 * 解包并转发
	 * @param type $udpPackage
	 */
	public static function Package($udpPackage, $client_info, $taskCnt) {
		//json string
		$udpPackageData = $udpPackage->ReadString();
		$task_id = crc32($udpPackageData) % $taskCnt;
		return array("data" => $udpPackageData, "task_id" => $task_id);
	}
	
	public function check_and_clear() {
		if(empty($this->fileHandles)) return;
		$today = strtotime(date('Ymd'));
		if ($this->prev_check_time == $today) {
			return;
		}
		$this->prev_check_time = $today;
		$now = time();
		foreach ($this->fileHandles as $fname => $v) {
			if (($now - $v[2]) > 172800) {
				unset($this->fileHandles[$fname]);
			}
		}
	}
	
	/**
	 * 上报魔方操作
	 * @param array $arr
	 * @return boolean
	 */
	protected function todoSendMf(array $arr){
		$filename = $arr['filename'];
		if(!file_exists($filename)) return false;
		$filedata = file_get_contents($filename);
		if($filedata){
			//todo send socket to mf
		}
		unlink($filename);
		return true;
	}
	
	
	/**
	 * 采集数据
	 * @param type $data
	 */
	public function Fetch($data) {
		$this->check_and_clear();
		
		$arr = json_decode($data, true);
		if(isset($arr['filename']) && !empty($arr['filename'])){
			$file = PATH_DAT.$arr['filename'];
		}else{
			$file = PATH_DAT.'api_fetch_log.txt';
		}
		
		clearstatcache();
		if(!file_exists($file)) touch($file);
		
		if (isset($this->fileHandles[$file]) && isset($this->fileHandles[$file]['items'])) {
			array_push($this->fileHandles[$file]['items'], $data);
		} else {
			$this->fileHandles[$file]['items'][] = $data;
		}
		
		if(count($this->fileHandles[$file]['items']) > 20){
			$temp = array_slice($this->fileHandles[$file]['items'], 0, 20);
			if($temp) file_put_contents($file, implode("\n", $temp), FILE_APPEND);
			$this->fileHandles[$file]['items'] = array_slice($this->fileHandles[$file]['items'], 20);
		}
		
		
		$file_bak_all = $file.'.bak.all';
		if(!file_exists($file_bak_all)) touch($file_bak_all);
		$file_size = filesize($file);
		if($file_size > self::FILE_WRITE_MAX_SIZE){
			$file_bak_all_data = array();
			//读取
			$fp_r = fopen($file_bak_all, "r");
			if(!$fp_r) return false;
			if(flock($fp_r , LOCK_SH | LOCK_NB)){
				$file_bak_all_res = fread($fp_r, filesize($file_bak_all));
				if($file_bak_all_res) eval($file_bak_all_res);
				flock($fp_r , LOCK_UN);
			}
			fclose($fp_r);
			//写入
			$fp = fopen($file_bak_all, "w+");
			if(!$fp) return false;
			if(flock($fp, LOCK_EX | LOCK_NB)){
				$time = time();
				$file_bak_rename = $file.".bak.".$time;
				if(rename($file, $file_bak_rename) === true){
					$file_bak_all_data[$time] = $file_bak_rename;
				}
				$file_bak_all_data_str = '';
				if($file_bak_all_data){
					$file_bak_all_data_count = count($file_bak_all_data);
					$i = 0;
					foreach ($file_bak_all_data as $k => $v){
						//暂不定时上报魔方
						/*
						if(($i < self::SEND_DATA_TO_MF_ONCE_LIMIT) && ($file_bak_all_data_count > self::SEND_DATA_TODO_MIN_VAL)){
							$temp = array(
									'filename' => $v,
									'ip' => '127.0.0.1',
									'port' => '11111'
							);
							if($this->todoSendMf($temp) ==  true){
								$i++;
								continue;
							}
						}
						*/
						$file_bak_all_data_str .= '$file_bak_all_data["'.$k.'"]="'.$v.'";';
					}
				}
				fwrite($fp, $file_bak_all_data_str);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
	}
	
}
