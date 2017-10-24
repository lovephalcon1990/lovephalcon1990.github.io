<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Zengym\Apps\Quene\Model;
use Zengym\Model\AsyncCall;
use Zengym\Lib\Protocols\IpcPackage;

class Quene {

	/**
	 * log
	 * @var SwooleModelUdp
	 */
	private static $I = false;

	public static function Process(IpcPackage $ipcPackage) {
		if (!self::$I) {
			self::$I = new self();
		}
		
		$contentData = $ipcPackage->Data;
		switch ($ipcPackage->Action) {
			case 0x101://异步调用写入队列
				AsyncCall::push($contentData);
				break;
			case 0x102://log
				//self::$I->Log($ipcPackage);
				break;
			case 0x103://udp
				 oo::udp()->push( $ipcPackage->From_id, $contentData);
				break;
			case 0x104:
				$aContentData = explode('##' ,$contentData);
				oo::swooleMtt($aContentData[1])->sendMsg(base64_decode($aContentData[0]));
				break;
		}
	}
}
