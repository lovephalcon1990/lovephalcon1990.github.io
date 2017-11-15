<?php
$root = dirname(__FILE__);
$files = $_FILES['img'];
$dir = $root."/up/";
if(!is_dir($dir)){
	mkdir($dir,755);
}
$filedes =$dir.$files['name'];
move_uploaded_file($files['tmp_name'],$filedes);
$src = $_SERVER["HTTP_REFERER"]."/up/{$files['name']}";




echo "<script>parent.callback('$src')</script>";
