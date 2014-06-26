<?php

class mail {

	private $registry;
	
	private $mail = NULL;
	
	public function __construct($registry) {
		$this->registry = $registry;
		if(file_exists(ROOT_DIRECTORY.'lib/phpmail/PHPMailerAutoload.php')) {
			require_once(ROOT_DIRECTORY.'lib/phpmail/PHPMailerAutoload.php');
			$this->mail = new PHPMailer();
		}
		else {
			throw new storeException(ROOT_DIRECTORY.'lib/phpmail/PHPMailerAutoload.php was not found.', 404);
		}		
	}
	
	public function getMailObject() {
		if($this->mail != NULL) {
			return $this->mail;
		}else {
			throw new storeException('NullPointerException', 0);
		}
	}

	

}

?>
