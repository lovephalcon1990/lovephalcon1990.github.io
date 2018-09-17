<?php
/**
* 加载文件
*/
include_once INC_ROOT . 'Core/SwooleBehavior.php';
include_once INC_ROOT . 'Core/VerifyException.php';
include_once INC_ROOT . 'Fun.php';//公共函数
include_once INC_ROOT . 'MongoHelper.php';//mongo
include_once INC_ROOT . 'Transit.php';//中转server接口
//include_once INC_ROOT . 'Protocols/GSPack/GSReadPackage.php';
//include_once INC_ROOT . 'Protocols/GSPack/GSWritePackage.php';
include_once INC_ROOT . 'Protocols/MSPack/MSReadPackage.php';
include_once INC_ROOT . 'Protocols/MSPack/MSWritePackage.php';
include_once INC_ROOT . 'LogSwoole.php';//mongo

include_once LIB_ROOT . 'dataLoader.php';
include_once LIB_ROOT . 'iProcessData.php';
include_once LIB_ROOT . 'timer.php';
include_once LIB_ROOT . 'doPack.php';
include_once LIB_ROOT . 'sxdPacket.php';//处理发包 收包的类

include_once MOD_ROOT . 'main.php';//游戏入口文件 初始化类
include_once MOD_ROOT . 'admin.php';//管理员命令
include_once MOD_ROOT . 'data.php';//数据层
include_once MOD_ROOT . 'dataPool.php';//数据层
include_once MOD_ROOT . 'report.php';//数据上报
include_once MOD_ROOT . 'tick.php';//定时处理
include_once MOD_ROOT . 'handler.php';
include_once MOD_ROOT . 'client.php';
include_once MOD_ROOT . 'do.php';
