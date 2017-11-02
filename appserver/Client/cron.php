<?php
define("SWOOLE_ROOT",dirname(__FILE__)."/../");


require SWOOLE_ROOT."vendor/autoload.php";

use Zengym\Lib\Helper\DB;

use Zengym\Model\Proc;
$temp = [
	'call AddSlotsV3Play(\'13\',64171086,9,900,\'{"1":{"1":3,"2":2,"3":6},"2":{"1":2,"2":4,"3":3},"3":{"1":10,"2":1,"3":3},"4":{"1":2,"2":12,"3":2},"5":{"1":1,"2":1,"3":4}}\',\'[]\',0,0,0,0,0,900,1,0,\'1508819490|1|\',\'78819\',1,0)',
	'call AddLotteryBuyLog(55525030,37352,1141258,1000,300,700)',
];

foreach($temp as $val){
	Proc::push($val);
}
echo "done";

use Zengym\Model\AsyncCall;

AsyncCall::add("Zengym\\Model\\Act::test",array(1,23,44),3);
echo "done \n";


//use Zengym\Model\SClient;
//echo "<pre>";
//$wr = new Zengym\Lib\Protocols\WritePackage();
//$msg = "123444aaa哈哈";
//$wr->Begin(0x881);
//$wr->Int(20202);
//$wr->String('cade073b2c1b6612db735a41c11853f4');
//$wr->String(rawurlencode($msg));
//$wr->End();
//$tcpData = $wr->GetBuffer();
//$swoole_client = SClient::CreateClientAndConnect("127.0.0.1", 9850);
//var_dump($tcpData);
//echo "\n";
//$swoole_client->send($tcpData);
//$responseData = $swoole_client->recv();
//var_dump($responseData);
//$rd = new Zengym\Lib\Protocols\ReadPackage();
//$rd->ReadPackageBuffer($responseData);
//var_dump($rd);
//var_dump($rd->GetCmdType());
//var_dump($rd->GetBuffer());
//var_dump($rd->Int());
//var_dump(rawurldecode($rd->String()));
//exit;

//use Zengym\Lib\Protocols\WritePackage;
//$write = new WritePackage();
//$action = "0x881";
//$write->Begin($action);
//$write->Byte(1);
//$write->End();
//print_r($write->GetBuffer());




