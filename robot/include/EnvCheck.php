<?php

echo PHP_EOL;
try {
	$zip = new ZipArchive;
	$res = $zip->open(dirname(__FILE__) . '/test.zip');
//解压缩到test文件夹 
	$zip->extractTo(dirname(__FILE__) . '/temp/');
	$zip->close();
	if (is_dir(dirname(__FILE__) . '/temp/')) {
		echo "zip-test:=========ok" . PHP_EOL;
	}else{		
		echo "zip-test:=========error" . PHP_EOL;
	}
} catch (Throwable $ex) {
	echo "zip-test:=========error" . PHP_EOL;
}
$shell = array(
	"/usr/local/php7/bin/php -r \"echo PHP_VERSION;\""=>"7.0.4",
	"/usr/local/php7/bin/php -r \"echo phpversion('mongodb');\"" => "1.1.5",
	"/usr/local/php7/bin/php -r  \"echo SWOOLE_VERSION;\" " => "1.8.4",
	"/usr/local/php7/bin/php  -r \"echo phpversion('redis');\"" => "2.2.8-devphp7",
	"/usr/local/php7/bin/php  -r \"echo phpversion('memcached');\"" => "2.2.0",
	"/usr/local/php7/bin/php -i|grep opcache" => "opcache.enable_cli => On",
);
foreach ($shell as $sh => $hash) {
	$ret = array();
	exec($sh, $ret);
	$ret = implode(',', $ret);
	if (!$ret || strpos($ret, $hash) === false) {
		echo "$sh=========:error" . PHP_EOL;
	} else {
		echo "$sh=========:ok" . PHP_EOL;
	}
}

