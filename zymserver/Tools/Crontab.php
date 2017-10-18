<?php
namespace Zengym\Tools;

define('SWOOLE_ROOT', dirname(__FILE__) . '/../'); //server根路径
define('SWOOLE_ENV', intval($argv[1])); //站点sid,内网环境

define("PATH_DAT",SWOOLE_ROOT."data/");
require SWOOLE_ROOT . '/vendor/autoload.php';

use Zengym\Lib\Helper\Log;
use Throwable;

//if (SWOOLE_ENV !== 1) {
//	return;
//}
Crontab::checkCodeFileChange();
//Crontab::CheckIsRestart(intval($argv[2]));
Crontab::clearRedundantCoreDumpFile();
echo 'crontab-ok';

class Crontab {

	/**
	 * 清除多余的coredump文件,只保留一个
	 */
	public static function clearRedundantCoreDumpFile() {
		try {
			$first = true;
			$newcore = false;
			$path = SWOOLE_ROOT;
			if (!is_dir($path))
				return;
			$handle = dir($path);
			while ($file = $handle->read()) {
				if ($file != '.' && $file != '..') {
					$path2 = $path . $file;
					if (is_file($path2) && preg_match("/^core\.\d+$/", $file)) {
						self::warning('发生coredump', $file);
						//改名备份
						rename($path2, str_replace('core.', 'core_' . time() . '_', $path2));
						$newcore = true;
					} else if (is_file($path2) && preg_match("/^core_\d+_\d+$/", $file)) {
						if ($newcore) {
							unlink($path2);
						} else if ($first) {
							$first = false;
						} else {
							unlink($path2);
						}
					}
				}
			}
			$handle->close();
		} catch (Throwable $ex) {
			//self::warning(__FUNCTION__, $ex->getMessage());
		}
	}

	/**
	 * 报警并发邮件、短信(cms控制)
	 * @param type $tip
	 */
	public static function warning($tip, $content = '') {
		//try {
		//	$msg = "小喇叭服务报警：" . $tip . PHP_EOL . $content;
		//	$post_data = ['data' => $msg, 'sid' => IMSERVER_SID, 'typeid' => 27];
		//	$url = 'http://api.ifere.com:58080/cms/api/rest.php?cmd=warning';
		//	$ch = curl_init();
		//	curl_setopt($ch, CURLOPT_URL, $url);
		//	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		//	$file_contents = curl_exec($ch);
		//	curl_close($ch);
		//} catch (Throwable $ex) {
		//
		//}
	}

	/**
	 * 检测并强制重启
	 */
	public static function CheckIsRestart($ret) {
		try {
			if ($ret === 2) {
				self::warning('小喇叭服务被强制重启-' . date('Ymd H:i', time()));
			}
		} catch (Throwable $ex) {
			self::warning(__FUNCTION__, $ex->getMessage());
		}
	}
	
	/**
	 * @uses check dir file is changed
	 */
	public static function checkCodeFileChange() {
		try {
			$path = SWOOLE_ROOT;
			$new_file_map = array();
			self::getDirAllFile($path, $new_file_map); //MD5_file，文件修改时间
			$old_file_map_path = dirname(__FILE__) . '/file_map_'  . '.txt';
			$old_file_map = array();
			if (file_exists($old_file_map_path)) {
			    $content = file_get_contents($old_file_map_path);
			    $old_file_map= json_decode($content,true);
			}
			$checkList = array();
			foreach ($new_file_map as $path => $info) {
			    if (strpos($path, 'file_map_'  . '.txt') !== false) {
					continue;
			    }
			    if (!isset($old_file_map[$path]) || $old_file_map[$path][0] != $info[0]) {
					$checkList[] = $path . ',change:' . date('Ymd H:i:s', $info[1]);
			    }
			}
			if (!empty($checkList)) {
				Log::debug( implode(',', $checkList));
			    $ret= json_encode($new_file_map);
			    file_put_contents($old_file_map_path, $ret);
			}
		} catch (Throwable $ex) {
			Log::debug($ex->getMessage());
		}
	}
	
	
	/**
	 * 获取目录下所有文件
	 * @param type $dirPath
	 */
	private static function getDirAllFile($path, &$file_map) {
		$handle = dir($path);
		$ignoreDir = array('data', 'errlog', 'Tests', 'Docs','vendor');
		foreach ($ignoreDir as $idir) {
			if (strpos($path, SWOOLE_ROOT . $idir) !== false) {
				return;
			}
		}
		if (!is_dir($path)) {
			return;
		}
		while ($file = $handle->read()) {
			if ($file != '.' && $file != '..'&& (strpos($file, 'core_') !== 0)) {
				$path2 = $path . $file;
				if (is_file($path2)) {
					$file_map[str_replace(SWOOLE_ROOT, '', $path2)] = array(md5_file($path2), filemtime($path2));
				} elseif (is_dir($path2)) {
					self::getDirAllFile($path2 . '/', $file_map);
				}
			}
		}
		$handle->close();
	}

}
