<?php
/**
 * Created by PhpStorm.
 * User: YuemingZeng
 * Date: 2017/10/17
 * Time: 12:20
 */
define("SWOOLE_ROOT",dirname(__FILE__).'/');
require SWOOLE_ROOT . '../vendor/autoload.php';
use Zengym\Client\Tclient;

$wr = new Zengym\Lib\Protocols\WritePackage(true);
$msg = "123444aaa哈哈";
$wr->Begin(0x888);
//$wr->Int(20202);
$wr->String('f35537b335a767c5b60d76863daff7af');
//$wr->String(rawurlencode($msg));
$wr->End();
$tcpData = $wr->GetBuffer();
$swoole_client = Tclient::CreateClientAndConnect("127.0.0.1", 9752);

$swoole_client->send($tcpData);
$responseData = $swoole_client->recv();
$rd = new Zengym\Lib\Protocols\ReadPackage(true);
$rd->ReadPackageBuffer($responseData);
var_dump($rd->GetCmdType());

echo "\n";
var_dump($rd->String());
echo "\n";

var_dump($rd->Short());
echo "\n";
var_dump($rd->Byte());
echo "\n";

//var_dump($rd->GetBuffer());
//var_dump($rd->Int());
//var_dump(rawurldecode($rd->String()));
exit;