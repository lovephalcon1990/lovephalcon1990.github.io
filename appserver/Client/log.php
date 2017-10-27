<?php
define("SWOOLE_ROOT",dirname(__FILE__)."/../");


require SWOOLE_ROOT."vendor/autoload.php";


use Zengym\Lib\Helper\Log;


Log::debug(['李大爷','二大爷'],'bb.txt','',false);


echo "done\n";