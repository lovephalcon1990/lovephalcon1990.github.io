<?php
define("SWOOLE_ROOT",dirname(__FILE__)."/../");


require SWOOLE_ROOT."vendor/autoload.php";

//use Zengym\Lib\Helper\DB;

//use Zengym\Model\Proc;
//$temp = [
//	'call AddSlotsV3Play(\'13\',64171086,9,900,\'{"1":{"1":3,"2":2,"3":6},"2":{"1":2,"2":4,"3":3},"3":{"1":10,"2":1,"3":3},"4":{"1":2,"2":12,"3":2},"5":{"1":1,"2":1,"3":4}}\',\'[]\',0,0,0,0,0,900,1,0,\'1508819490|1|\',\'78819\',1,0)',
//	'call AddLotteryBuyLog(55525030,37352,1141258,1000,300,700)',
//];
//
//foreach($temp as $val){
//	Proc::push($val);
//}
//echo "done";

use Zengym\Model\AsyncCall;

AsyncCall::add("Zengym\\Model\\Act::test",array(1,23,44),3);
echo "done \n";




