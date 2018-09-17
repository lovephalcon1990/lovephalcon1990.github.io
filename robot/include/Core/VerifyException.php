<?php

class VerifyException extends Exception {

	public function __construct($message, $code = 99) {
		parent::__construct($message, $code);
	}

}
