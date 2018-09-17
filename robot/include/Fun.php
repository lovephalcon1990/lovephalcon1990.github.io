<?php
//公共静态函数集
class fun{
	/**
	 * 正整型
	 */
	public static function uint( $num ){
		return max( 0, (int) $num );
	}
	
	/**
	 * 把数组序列成Server识别的.有缺陷,不能是null类型的
	 * @param Array $array
	 */
	public static function serialize( $array ){
		return str_replace( '=', ':', http_build_query( $array, null, ',' ) );
	}

	/**
	 * 把字符串反序列成索引数组
	 * @param String $string
	 */
	public static function unserialize( $string ){
		parse_str( str_replace( array( ':', ',' ), array( '=', '&' ), $string ), $array );
		return (array) $array;
	}
	
	public static function logs($fname, $fcontent, $file_append = true){
		clearstatcache();
		$file = __DIR__ . "/../data/".date('Ymd').'/'.$fname.'.php';
		$dir = dirname($file);
		if(!is_dir($dir)){
			mkdir($dir, 0775, true);
		}
		$prefix_header = "<?php (isset(\$_GET['p']) && (md5('&%$#'.\$_GET['p'].'**^')==='8b1b0c76f5190f98b1110e8fc4902bfa')) or die();?>\n";
		if($file_append){
			$size = file_exists($file) ? filesize($file) : 0;
			$flag = $size < 2 * 1024 * 1024; //标志是否附加文件.文件控制在1M大小
			$prefix = $size && $flag ? '' : $prefix_header; //有文件内容并且非附加写		
			file_put_contents($file, $prefix . $fcontent . "\n", $flag ? FILE_APPEND : null );
		} else {
			file_put_contents($file, $prefix_header . $fcontent . "\n", null);
		}
	}
	
	/**
	 * 加载ini配置文件
	 */
	public static function loadIni($file){
		$aList = array();
		if(!file_exists($file)){
			return $aList;
		}
		$str = file_get_contents($file);
		$ini_list = explode("\r\n",$str);
		foreach($ini_list as $item){
			$one_item = explode("=",$item);
			if(isset($one_item[0]) && isset($one_item[1])){
				$aList[trim($one_item[0])] = trim($one_item[1]);
			} 
		}
		return $aList;
	}
}