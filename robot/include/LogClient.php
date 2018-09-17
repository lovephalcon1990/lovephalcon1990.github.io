<?php

/**
 * 日志上传类
 */
class LogClient{

	private $ip, $port;

	public function __construct($udpIp, $udpPort) {
		$this->ip = $udpIp;
		$this->port = $udpPort;
	}

	public function debug($params, $fname = 'debug.txt', $fsize = 1){
		is_scalar($params) or ( $params = var_export($params, true)); //是简单数据
		if (!$params) {
			return false;
		}
		$udp = array($fname, max(1, $fsize) * 1024 * 1024, $params);
		$content = implode('+_+', $udp);
		//超出大小，直接丢掉
		if (strlen($content) > 65500) {
			return false;
		}
		
		$fp = stream_socket_client("udp://{$this->ip}:{$this->port}", $errno, $errstr, 2);
		if(!$fp){
			return false;
		}else{
			fwrite($fp, $content);
			fclose($fp);
		 	return true;
		}
	}
}
