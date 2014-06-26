<?php

class errorlog {

	private $file;
	
	public function __construct() {
		$this->file = fopen('errorlog.txt', 'a');
	}
	
	public function logError($array) {
		$str = '';
		foreach($array as $exception) {
			foreach($exception as $key=>$value) {
				$data = date(DATE_RFC2822);
				$str .= $data." : '".$key."' => ".json_encode($value).", \n";
			}
		}
		$this->fwrite_stream($str."\n");
	}
	
	private function fwrite_stream($string) {
    		for ($written = 0; $written < strlen($string); $written += $fwrite) {
        		$fwrite = fwrite($this->file, substr($string, $written));
        		if ($fwrite === false) {
            			return $written;
        		}
	    	}
    		return $written;
	}
	
	public function __deconstruct() {
		fclose($this->file);
	}

}

?>
