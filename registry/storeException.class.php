<?php

class storeException extends Exception {

	public function __construct($message, $code, $previous=NULL) {
		parent::__construct($message, $code, $previous);
	}

	public function completeException() {
		$message = parent::getMessage();
		$code = parent::getCode();
		$previous = parent::getPrevious();
		$trace = parent::getTrace();
		$trace[count($trace)-1]['Exception'] = $message;
		$trace[count($trace)-1]['code'] = $code;
		$trace[count($trace)-1]['ip address'] = $_SERVER['REMOTE_ADDR'];;
		while($previous!=NULL) {
			$trace = $previous->getTrace();
			$message = $previous->getMessage();
			$code = $previous->getCode();
			$previous = $previous->getPrevious();
			$trace[count($trace)-1]['Exception'] = $message;
			$trace[count($trace)-1]['code'] = $code;
			$trace[count($trace)-1]['Exception'] = $message;
			$trace[count($trace)-1]['code'] = $code;
			$trace[count($trace)-1]['previous'] = $previous;
		}
		return $trace;
	}

}

?>
