<?php

class loginController {

	private $registry;
	
	public function __construct(Registry $registry) {
	
		$this->registry = $registry;
		//if user is in loged in take him to his profile
		$urlBits = $this->registry->getObject('url')->getURLBits();
		if(isset($_SESSION['session_user_uid'])) {
			$this->registry->getObject('auth')->checkForAuthentication();
			if($this->registry->getObject('auth')->isloggedIn()) {
				if(isset($urlBits[1])) {
					switch( $urlBits[1] )
					{
						case 'check':
							$this->check();
							break;
						case 'extend':
							$this->extendSession();
							break;
						default : 
							$url = $this->registry->getSetting('siteurl');
							header("location:$url".$this->registry->getObject('auth')->getUserObject()->getUser()['username']);
							break;
					}
				}
				else {
					$url = $this->registry->getSetting('siteurl');
					header("location:$url".$this->registry->getObject('auth')->getUserObject()->getUser()['username']);
				}
			}
		}
		
		//show him form to login
		if( isset( $urlBits[1] ) )
		{
			switch( $urlBits[1] )
			{
				case 'signin':
					// user has submited form now login him
					$this->login();
					break;
				case 'check':
					$this->check();
					break;
				case 'extend':
					$this->extendSession();
					break;
				default : 
					break;
			}	
		}
	}
	
	/*
	 * login function loges in the user if has submited form with proper credentials.
	 */
	private function login() {
		$this->registry->getObject('auth')->checkForAuthentication();
		if($this->registry->getObject('auth')->isloggedIn()) {
			$url = $this->registry->getSetting('siteurl');
			header("location:$url".$this->registry->getObject('auth')->getUserObject()->getUser()['username']);
		}
		else {
			$error = $this->registry->getObject('auth')->getError();
			if($error!='') {
				$this->registry->getObject('template')->addTemplateBit('error','error.html');
				$this->registry->getObject('template')->getPage()->addTag('errorlog',$error);
			}
		}
	}
	
	private function check() {
		$time = $_SESSION['time'];
		//echo (time()- $time);
		$this->registry->getObject('auth')->checkForAuthentication();
		if($this->registry->getObject('auth')->isloggedIn()) {
			if( (time()- $time) > 795 && (time()- $time) < 800) {
				echo 'false';
			}
			else {
				echo 'true';
			}	
			exit;
		}
		else {
				echo "logout";
				exit;
		}
		exit;
	}
	
	private function extendSession() {
		$this->registry->getObject('template')->buildFromTemplates(['extend.html']);
		$this->registry->getObject('template')->getPage()->addPPTag('siteurl',$this->registry->getSetting('siteurl'));
		$this->registry->getObject('template')->parseOutput();
		echo $this->registry->getObject('template')->getPage()->getContentToPrint();
		$urlBits = $this->registry->getObject('url')->getURLBits();
		if( isset( $urlBits[2] ) )
		{
			if($urlBits[2]=='done') {
				$token = sha1(md5($_SESSION['salt']));
				unset($_SESSION['time']);
				$_SESSION['time'] = time();
				setcookie('token', $token, time()+900, '/', ''); #try last two options as true
				echo '<script>window.close();</script>';
			}
		}
		exit;
	}
}

?>
