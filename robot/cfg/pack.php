<?php
/**
 * pack 配置
 */
return array(
	'send' => array(//发送包
		0x2 => [1,11],//array(),
		0x101 =>[109,1],// array('int','int','short','short','string','string'),
		0x102 => array(),
		0x201 => array('byte', 'int64'),
		0x004 => array('int'),
		0x005 => array('byte', 'int', 'byte'),
		0x110 => array('byte'),
		0x202 =>[109,3],
	),
	'rev' => array(//接收包
		0x2 => array(),
		0x101 => array('ret'=>'byte','int'),//登录回执
		0x102 => array(),
		0x104 => array('status'=>'byte'),
		0x201 => array('ret'=>'byte'),//下注回执
		0x215 => array('byte'),
		0x214 => array(array('byte'),array('byte'),'winMoney'=>'int64', 'mmoney'=>'int64'),
		0x110 => array('mid'=>'int', 'seatId'=>'byte'),
		0x111 => array('seatId'=>'byte'),
		0x06 => array('fromId'=>'byte', 'toId'=>'byte','propsId'=>'int', 'int'),
	),
);