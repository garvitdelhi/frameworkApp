<?php

class user {

	private $username;
	private $password;
	private $session_user_uid;
	private $registy;
	private $isValid = false;
	private $isActive = false;
	private $isBanned = false;
	private $isAdmin = false;
	private $pwd_reset_key;
	private $pswd_reset_time;
	private $email;
	private $data = '';

	public function __construct($registry, $session_user_uid=0, $u=0, $p=0) {
		$this->username = $u;
		$this->password = $p;
		$this->session_user_uid = $session_user_uid;
		$this->registry = $registry;
		try {
			$this->checkUser();
		}catch(storeException $e) {
			throw new storeException($e->getMessage(),$e->getCode(),$e);
		}
	}
	
	private function checkUser() {
		if($this->username && $this->password) {
			try {
				if($this->postUser()) {
					$query = "SELECT * FROM users WHERE username='{$this->username}' OR email ='{$this->username}' AND admin = '1'";
					$this->registry->getObject('db')->executeQuery($query);
					if( $this->registry->getObject('db')->numRows() == 1 )
					{
						$data = $this->registry->getObject('db')->getRows();
						unset($data['password_hash']);
						unset($data['password_salt']);
						if($data['deleted']!=0) {
							$this->deletedUser();
						}
						else {
							$this->data = $data;
							$this->username = $data['username'];
							$this->isActive = $data['active'];
							$this->isBanned = $data['banned'];
							$this->isAdmin = $data['admin'];
							$this->email = $data['email'];
							$this->pwd_reset_key = $data['reset_key'];
							$this->isValid = true;
							$table = 'users';
						}
					}
				}
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		elseif($this->session_user_uid) {
			try {
				$query = "SELECT * FROM users WHERE session_user_uid='{$this->session_user_uid}' AND admin = '1'";
				$this->registry->getObject('db')->executeQuery( $query );
				if( $this->registry->getObject('db')->numRows() == 1 )
				{
					$data = $this->registry->getObject('db')->getRows();
					unset($data['password_hash']);
					unset($data['password_salt']);
					if($data['deleted']) {
						$this->deletedUser();
					}
					else {
						$this->data = $data;
						$this->username = $data['username'];
						$this->password = 0;
						$this->isActive = $data['active'];
						$this->isBanned = $data['banned'];
						$this->isAdmin = $data['admin'];
						$this->email = $data['email'];
						$this->pwd_reset_key = $data['reset_key'];
						$this->isValid = true;
					}
				}
				else {
					$this->password = 0;
					$this->password = 0;
					$this->session_user_uid = 0;
					$this->idValid = false;
					$this->isActive = false;
					$this->isBanned = false;
					$this->isAdmin = false;
					$this->data = '';
				}
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
	}
	
	private function postUser() {
		try {
			$this->username = $this->registry->getObject('db')->sanitizeData($this->username);
			$this->password = $this->registry->getObject('db')->sanitizeData($this->password);
			$query = "SELECT * FROM users WHERE (username='{$this->username}' OR email ='{$this->username}') AND admin = '1'";
			$this->registry->getObject('db')->executeQuery($query);
			if( $this->registry->getObject('db')->numRows() == 1 )
			{
				$data = $this->registry->getObject('db')->getRows();
				$salt = $data['password_salt'];
				$this->password = sha1(md5($this->password));
				$this->password = crypt($this->password,$salt);
				if($this->registry->getObject('hash')->validate_password($this->password,$data['password_hash'])) {
					$this->session_user_uid = $data['session_user_uid'];
					$this->password = 0;
					return true;
				}
				else {
					$this->password = 0;
					$this->password = 0;
					$this->session_user_uid = 0;
					$this->idValid = false;
					$this->isActive = false;
					$this->isBanned = false;
					$this->isAdmin = false;
					$this->data = '';
					return false;
				}
			}
		}catch(storeException $e) {
			throw new storeException($e->getMessage(),$e->getCode(),$e);
		}
	}

	public function authenticatePassword($password = ''){
		$query = "SELECT * FROM users WHERE session_user_uid='{$this->session_user_uid}' AND admin = '1'";
		$this->registry->getObject('db')->executeQuery( $query );
		if( $this->registry->getObject('db')->numRows() == 1 ) {
			$data = $this->registry->getObject('db')->getRows();
			$salt = $data['password_salt'];
			$password = sha1(md5($password));
			$password = crypt($password,$salt);
			if($this->registry->getObject('hash')->validate_password($password,$data['password_hash'])) {
				return true;
			}
		}
		
		return false;
	}

	private function deletedUser() {
	}
	
	private function resetPassword() {
		$query = "SELECT * FROM users WHERE ID='{$this->session_user_uid}'";
		$this->registry->getObject('db')->executeQuery( $query );
		if( $this->registry->getObject('db')->numRows() == 1 )
		{
			$data = $this->registry->getObject('db')->getRows();
			//generate new salt and password think later :(
		}
	}
	
	public function isValid() {
		return $this->isValid;
	}
	
	public function isActive() {
		return $this->isActive;
	}
	
	public function getUserID() {
		return $this->session_user_uid;
	}
	
	public function isBanned() {
		return $this->isBanned;
	}
	
	public function getUser() {
		return $this->data;
	}
	
}

?>
