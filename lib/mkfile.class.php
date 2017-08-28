<?php

/**
 * Created by PhpStorm.
 * User: YuemingZeng
 * Date: 2017/8/22
 * Time: 16:56
 */
class mkfile{
	static $br = "\r\n";
	static $tab = "\t";
	
	static function ecphp($data){
		$file = date("Ym")."/".time().".php";
		$dest = "./name.php";
		$dir = dirname($file);
		$s = self::wstart();
		$s.=self::$br.'return'.self::$tab;
		$s.= self::arr2str($data);
		is_dir($dir) || mkdir($dir,755,true);
		
		$s = trim($s,',');
		$s = trim($s,self::$br);
		$s .=';';
		file_put_contents($file,$s);
		if(self::checkErr($file)){
			copy($file,$dest);
			true;
		}else{
			return false;
		}
	}
	
	static function wstart(){
		$desc = (array)debug_backtrace();
		foreach($desc as $rs){
			$desc = $rs['file'] .':'. $rs['line'];
			break;
		}
		return "<?php".self::$br."//test,". 'demo' .",". gmdate('Y-m-d H:i:s',time()) .",". "zengyueming" . $desc .
			self::$br;
	}
	
	static function arr2str($data,$l=true,$t="\r\n"){
		$s = 'array(';
		if(!$l){
			$t.=self::$tab;
		}
		foreach($data as $k=>$val){
			if(is_array($val)){
				$v = self::arr2str($val,false,$t);
			}else if(is_numeric($val)){
				$v = $val;
			}else{
				$v = "'".$val."'";
			}
			if(!is_numeric($k)) $k="'".$k."'";
			$s.= $t.$k ."=>".$v.",";
		}
		$s = trim($s,',');
		$s.= $t.')';
		return $s;
	}
	
	static function checkErr($file){
		$content = file_get_contents($file);
		$content = str_replace('<?php','',$content);
		$content = str_replace('?>','',$content);
		return eval('return true;'.$content) ? true : false;
	}
}