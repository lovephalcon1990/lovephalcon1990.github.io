<?php
/**
 * Created by SxdStorm.
 * User: yuemingzeng
 * Date: 2018/8/8
 * Time: 17:42
 */
//$now = date("Y-m-d H:i:s");

$now = "2018-08-13 02:00:00";

$times=(strtotime($now)-2*60*60);  //当前日期
$date = date("Y-m-d", $times);


$first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期

$w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6

$now_start=date('Y-m-d',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天

echo $now_start;
echo "\n";

#$w=date('W',strtotime("2018-08-12 23:59:59"));
#echo $w."\n";
exit;