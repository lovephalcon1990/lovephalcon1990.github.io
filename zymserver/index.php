<?php
/**
 * Created by PhpStorm.
 * User: YuemingZeng
 * Date: 2017/10/13
 * Time: 11:08
 */

define("ROOT",__DIR__);
require ROOT . '/vendor/autoload.php';

use Zengym\Lib\Protocols\EncryptDecrypt;

$msg = "哈";
//$newmsg = $msg;
$newmsg = rawurlencode($msg);
echo $newmsg."<br>";

$len = strlen($newmsg);
echo $len."<br>";
$EnDe = new EncryptDecrypt();
$EnMsg = $EnDe->Encrypt($newmsg,0,$len);
echo $newmsg."<br>";
echo "EN:".$EnMsg."<br>";
$len2 = strlen($newmsg);
echo $len2."<br>";
$DeMsg = $EnDe->Decrypt($newmsg,$len2,$EnMsg);

echo $newmsg."<br>";
echo "DE:".$DeMsg."<br>";

echo rawurldecode($newmsg)."<br>";
exec($cmd,$output);