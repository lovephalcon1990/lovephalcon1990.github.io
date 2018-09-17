<?php
class DataPool implements IProcessData{
	public $aPhpCfg = array();
	public function __construct(){}
	public function getStorageData(){
		return array('userData'=>ModelHandler::getClientData(), 'cfg' =>$this->aPhpCfg);
	}
	
	/**
	 * {@inheritDoc}
	 * @see IProcessData::setStorageData()
	 */
	public function setStorageData($data){
		if (isset($data['userData'])){
			ModelHandler::setClientData($data['userData']);
		}
		if (isset($data['cfg'])){
			Main::setCfg($data['cfg']);
		}
	}
}