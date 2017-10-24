<?php
define("SWOOLE_ROOT",dirname(__FILE__)."/../");


require SWOOLE_ROOT."vendor/autoload.php";

use Zengym\Lib\Helper\DB;

$key = "ts";
$load = sys_getloadavg();
DB::instance()->set($key,$load);
print_r(DB::instance()->get($key));
echo "\n";




