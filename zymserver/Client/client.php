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
$wr->Begin(0x109);
$wr->End();
$swoole_client = Tclient::CreateClientAndConnect("127.0.0.1", 9752);
$tcpData = $wr->GetBuffer();
var_dump($tcpData);
echo "\n";
$swoole_client->send($tcpData);

$responseData = $swoole_client->recv();
var_dump($responseData);die();