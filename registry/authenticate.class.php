<?php

class authenticate {

	private $registry;
	
	private $loggedIn = false;
	
	private $loggedInUser;
	
	private $loginFailureReason;
	
	private $justProcessed = false;
	
	private $user;
	private $siteurl;
	
	private $error='';
	
	public function __construct(Registry $registry) {
		$this->registry = $registry;
	}
	
	public function checkForAuthentication() {
		try {
			$this->siteurl = $this->registry->getSetting('siteurl');
			$this->registry->getObject('template')->getPage()->addTag('logout_link','login/signup');
			$this->registry->getObject('template')->getPage()->addTag('pic','');
		}catch(storeException $e) {
			throw new storeException($e->getMessage(),$e->getCode(),$e);
		}
		if(isset($_SESSION['session_backend_uid']) && $_SESSION['session_backend_uid']!='') {
			try {
				$this->sessionAuthenticate($_SESSION['session_backend_uid']);
				if($this->loggedIn) {
					$this->registry->getObject('template')->getPage()->addTag('name',$this->user->getUser()['name']);
					$this->registry->getObject('template')->getPage()->addTag('my-account-link',$this->user->getUser()['username']);
					$this->registry->getObject('template')->getPage()->addTag('logout_link',$this->user->getUser()['username'].'/logout');
					if($this->user->getUser()['pic_small']!='') {
						$pic = '<img src="{pic_link}" style="margin-top:-5px" width="40" height="40" alt="{my_account}" /> &nbsp;&nbsp;';
						$this->registry->getObject('template')->getPage()->addTag('pic',$pic);
						$this->registry->getObject('template')->getPage()->addTag('pic_link',$this->user->getUser()['pic_small']);
						$this->error='';
					}
				}
				else {
					$this->error = 'Username Or Password Wrong';
					unset($_SESSION['session_backend_uid']);
				}
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		elseif(isset($_POST['username']) && isset($_POST['password']) && $_POST['username']!='' && $_POST['password']!= '') {
			$u = $_POST['username'];
			$p = $_POST['password'];
			try {
				$this->postAuthenticate($u,$p);
				if($this->loggedIn) {
					$this->registry->getObject('template')->getPage()->addTag('my_account',$this->user->getUser()['name']);
					$this->registry->getObject('template')->getPage()->addTag('my_account_link','user');
					$this->registry->getObject('template')->getPage()->addTag('my_account_link',$this->user->getUser()['username']);
					$this->registry->getObject('template')->getPage()->addTag('logout_link',$this->user->getUser()['username'].'/logout');
					if($this->user->getUser()['pic_small']!='') {
						$pic = '<img src="{pic_link}" style="margin-top:-5px" width="40" height="40" alt="{my_account}" /> &nbsp;&nbsp;';
						$this->registry->getObject('template')->getPage()->addTag('pic',$pic);
						$this->registry->getObject('template')->getPage()->addTag('pic_link',$this->user->getUser()['pic_small']);
						$this->error='';
					}
				}
				else {
					unset($_SESSION['session_backend_uid']);
					$this->error = 'Username Or Password Wrong';
				}
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		elseif(isset($_POST['login']))
    		{
    			$this->error = 'You must enter a username and a password';	
    		}
	}
	
	private function sessionAuthenticate($uid) {
		if(file_exists(ROOT_DIRECTORY.'registry/user.class.php')) {
			try {
				require_once(ROOT_DIRECTORY.'registry/user.class.php');
	    			$this->user = new User( $this->registry, $_SESSION['session_backend_uid'], '', '' );
	    	
	    			if( $this->user->isValid() )
    				{	
	    				if(!$this->user->isActive())
	    				{
		    				$this->loggedIn = false;
			    			$this->loginFailureReason = 'inactive';
			    		}
	    				elseif($this->user->isBanned())
	    				{
	    					$this->loggedIn = false;
	    					$this->loginFailureReason = 'banned';
	    				}
	    				else
	    				{
			    			$this->loggedIn = true;
			    		}    		
			    	}
			    	else
			    	{
			    		$this->loggedIn = false;
			    		$this->loginFailureReason = 'no user';
			    	}
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		else {
			throw new storeException('user.class.php can\'t be found.',404);
		}    	    	
	}
	
	public function postAuthenticate($u, $p) {
		$this->justProcessed = true;
		if(file_exists(ROOT_DIRECTORY.'registry/user.class.php')) {
			try {
	    			require_once(ROOT_DIRECTORY.'registry/user.class.php');
		    		$this->user = new User( $this->registry, 0, $u, $p );
    			
    				if( $this->user->isValid() )
    				{
    					if(!$this->user->isActive())
    					{
    						$this->loggedIn = false;
    						$this->loginFailureReason = 'inactive';
    					}
    					elseif($this->user->isBanned())
			    		{
    						$this->loggedIn = false;
    						$this->loginFailureReason = 'banned';
    					}
    					else
    					{
			    			$this->loggedIn = true;
    						$_SESSION['session_backend_uid'] = $this->user->getUserID();
			    		}    		
    				}
    				else
			    	{
			    		$this->loggedIn = false;
			    		$this->loginFailureReason = 'invalidcredentials';
			    	}
			    	if( $this->loggedIn == false )
			    	{
			    		$this->logout();
			    	}
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		else {
			throw new storeException('user.class.php can\'t be found.',404);
		}
	}

	public function authenticatePassword($password='')
	{
		if($this->getUserObject()->authenticatePassword($password)) {
			return true;
		}
		return false;
	}
	
	public function getUserObject() {
		try {
			$this->checkForAuthentication();
			if($this->loggedIn) {
				return $this->user;
			}
			return false;
		}catch(storeException $e) {
			throw new storeException($e->getMessage(),$e->getCode(),$e);
		}
	}
	
	public function getError() {
		return $this->error;
	}
	
	public function isloggedIn() {
		return $this->loggedIn;
	}
	
	public function isJustProcessed() {
		return $this->justProcessed;
	}

	public function getUserWithID($id) {
		$query = "SELECT * FROM users WHERE id = '{$id}'";
		$this->registry->getObject('db')->executeQuery($query);
		$user = $this->registry->getObject('db')->getRows();
		unset($user['password_hash']);
		unset($user['password_salt']);
		return $user;
	}
	
	public  function logout() 
	{
		unset($_SESSION['session_backend_uid']);
		unset($_SESSION['priority']);
		$_SESSION['salt'] = '';
		setcookie('token','', time()-6300);
		$this->loggedIn = false;
		$this->user = NULL;
	}
}

?>
