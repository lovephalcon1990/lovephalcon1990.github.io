<?php
$root = dirname(__FILE__);
//$files = $_FILES['img'];
$files = $_FILES;
$data= $_REQUEST;
print_r($data);
$dir = $root."/up/";
if(!is_dir($dir)){
	mkdir($dir,755);
}
$filedes =$dir.$files['name'];
move_uploaded_file($files['tmp_name'],$filedes);
$src = $_SERVER["HTTP_REFERER"]."/up/{$files['name']}";



json_encode(array("data"=>$src));
//echo "<script>parent.callback('$src')</script>";
