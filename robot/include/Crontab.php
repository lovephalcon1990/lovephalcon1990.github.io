<?php
define('SERVER_ROOT', dirname(__FILE__) . '/../'); //server根路径
define('SERVER_SID', intval($argv[1])); //站点sid
define('SERVER_PORT', intval($argv[3])); //端口
Crontab::CheckIsRestart(intval($argv[2]));
Crontab::clearRedundantCoreDumpFile();
Crontab::clearDataFile(SERVER_ROOT. 'data/');
//Crontab::kill();//临时kill掉
echo 'crontab-ok';

class Crontab{
	public static function kill(){
		if(date('Ymd') != 20160824){
			return;
		}
		$lockFile = SERVER_ROOT. 'data/kill0824.lock';
		if(is_file($lockFile)){
			return;
		}
		$sh= 'ps -eaf |grep "SicboRobot/6106/sicboRobotServer.php 13 1 6106" | grep -v "grep"| awk \'{print $2}\'|xargs kill -9';
		exec($sh, $ret);
		touch($lockFile);
	}
	/**
	 * 每天清理一次data文件
	 */
	public static function clearDataFile($path, $isRoot=1){
		try{
			$time = time();
			if($isRoot){
				$lockFile = $path.'clearDataFile.lock';
				if(!is_file($lockFile)){
					touch($lockFile);
					return;
				}
				$ltime = filemtime($lockFile);
				if($time - $ltime < 24*3600){
					return;
				}
				touch($lockFile);
			}
			if(!is_dir($path)){
				return;
			}
			
			$handle = dir($path);
			
			while($file = $handle->read()){
				if($file == '.' || $file== '..') {
					continue;
				}
				if($isRoot){
					$ltime = filemtime($path .'/'.$file);
					if($time - $ltime < 20*24*60*60){//20天内的不删除
						continue;
					}
				}
				if(is_dir($path .'/'.$file)){
					self::clearDataFile($path .'/'.$file, 0);
					rmdir($path .'/'.$file);
				} else{
					unlink($path .'/'.$file);
				}
			}
			$handle->close();
		}catch(Throwable $ex){
			self::warning(__FUNCTION__, $ex->getMessage());
		}
	}
	
	/**
	 * 清除多余的coredump文件,只保留一个
	 */
	public static function clearRedundantCoreDumpFile(){
		try {
			$first = true;
			$path = SERVER_ROOT;
			if (!is_dir($path))
				return;
			$handle = dir($path);
			while ($file = $handle->read()) {
				if ($file != '.' && $file != '..') {
					$path2 = $path . $file;
					if (is_file($path2) && preg_match("/^core\.\d+$/", $file)) {
						if ($first) {
							self::warning('发生coredump', $file);
							$first = false;
							
							//改名备份
							chmod($path2, 0755);
							rename($path2, str_replace('core.', 'core_' . time() . '_', $path2));
							
							continue;
						}
						unlink($path2);
					}
				}
			}
			$handle->close();
		} catch (Throwable $ex) {
			self::warning(__FUNCTION__, $ex->getMessage());
		}
	}

	/**
	 * 报警并发邮件、短信(cms控制)
	 * @param type $tip
	 */
	public static function warning($tip, $content = ''){
		try {
			$ip = self::Get_Local_Ip();
			$msg = "{系统:{$ip}发出}-SERVER(".SERVER_PORT.")服务报警：" . $tip . PHP_EOL . $content;
			$post_data = ['data' => $msg, 'sid' => SERVER_SID, 'typeid' => 26];
			$url = 'http://api.ifere.com:58080/cms/api/rest.php?cmd=warning';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			$file_contents = curl_exec($ch);
			curl_close($ch);
		} catch (Throwable $ex) {
			
		}
	}
	
	public static function Get_Local_Ip(){
		$ipList = swoole_get_local_ip();
		foreach ($ipList as $localIp) {
			//较验是否为内网ip
			$ip = ip2long($localIp);
			$jy = $ip == 2130706433 || $ip >> 24 === 10 || $ip >> 20 === 2753 || $ip >> 16 === 49320;
			if ($jy) {
				$server_ip = long2ip($ip);
			}
		}
		if(!$server_ip){
			$server_ip = "1.1.1.1";
		}
		return $server_ip;
	}

	/**
	 * 检测并强制重启
	 */
	public static function CheckIsRestart($ret){
		try {
			if ($ret === 2) {
				self::warning('Server被强制重启-' . date('Ymd H:i', time()));
			}
		} catch (Throwable $ex) {
			self::warning(__FUNCTION__, $ex->getMessage());
		}
	}
}
