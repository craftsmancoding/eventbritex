<?php
//ini_set('display_errors','Off');
if (!class_exists('modxException')):

class modxException extends Exception {
	public function errorMessage() {
		//error message
		$errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile()
		.': <b>'.$this->getMessage().'</b> is not a valid E-Mail address';
		return $errorMsg;
	}
}

endif;
/*EOF*/