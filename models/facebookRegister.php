<?php

class facebookRegisterController {

	private $registry;
	
	private $data = array('username'=>'','password_hash'=>'','email'=>'');
	
	private $user_profile;
	private $user_pic_large;
	private $user_pic_small;

	public function __construct($registry) {
		$this->registry = $registry;
	}
	/*
	 * Facebook login or signup
	 */
	public function facebookLogin() {
		require_once(ROOT_DIRECTORY.'/views/fbsdk/src/facebook.php');
		$config = array(
				'appId'=>'706627902716456',
				'secret'=>'77b718b3d288d97bba5482b2f2fbe1b0',
				'fileUpload'=>false,
				'allowSignedRequest'=>false
			);
		$fb = new facebook($config);
		$user = $fb->getUser();
		if($user) {
			try {
				$this->user_profile = $fb->api("/me", "GET", array('fields'=>'name,email,gender,birthday,currency,username',));
				$this->user_pic_large = $fb->api("/me/picture", "GET", array('redirect'=>false,'type'=>'large'));
				$this->user_pic_small = $fb->api("/me/picture", "GET", array('redirect'=>false,'type'=>'small'));
				if($this->checkUserExists($this->user_profile['username'], $this->user_profile['email'])) {
					//login him/her
					$this->populateData();
					$query = "SELECT * FROM users WHERE username='{$this->data['username']}' AND email ='{$this->data['email']}'";
					$this->registry->getObject('db')->executeQuery($query);
					if( $this->registry->getObject('db')->numRows() == 1 )
					{
						$data = $this->registry->getObject('db')->getRows();
						$salt = $data['password_salt'];
						$this->data['password_hash'] = sha1(md5($this->data['password_hash']));
						$this->data['password_hash'] = crypt($this->data['password_hash'],$salt);
						if($this->registry->getObject('hash')->validate_password($this->data['password_hash'],$data['password_hash'])) {
							$_SESSION['session_user_uid'] = $data['session_user_uid'];
						}
					}
					$this->registry->getObject('auth')->checkForAuthentication();
					if($this->registry->getObject('auth')->isloggedIn()) {
						header('location:/store/'.$this->registry->getObject('auth')->getUserObject()->getUser()['username']);
					}
					return false;
				}
				else {
					//register him/her
					$this->populateData();
					return true;
				}
			} catch(FacebookApiException $e) {
        			print_r($e->getType());
        			print_r($e->getMessage());
				header('location:/store/home');
            			echo 'caught fb exception';
            			return false;
  			}  
		}
		else {
			$params = array(
					'scope'=>'email,sms,friends_birthday,
						  user_about_me,user_birthday,user_location,',
					'redirect_uri'=>'http://localhost/store/login/signup/facebook'
				);
			$loginUrl = $fb->getLoginUrl($params);
			header("location:$loginUrl");
			return false;
		}
	}
	
	public function populateData() {
		$this->data['username'] = $this->user_profile['username'];
		$this->data['email'] = $this->user_profile['email'];
		$this->data['password_hash'] = $this->user_profile['email'].$this->user_profile['id'].$this->user_profile['username'];
		$this->data['name'] = $this->user_profile['name'];
		$this->data['dob'] = $this->user_profile['birthday'];
		$this->data['gender'] = $this->user_profile['gender'];
		$this->data['pic_large'] = $this->user_pic_large['data']['url'];
		$this->data['pic_small'] = $this->user_pic_small['data']['url'];
		$this->data['is_social'] = 1;
	}
	
	/*
	 *check if this fb user exists or not
	 *@param - $username that you got from facebook
	 *@oaram - $email that you got from facebook
	 *@return - bool true if user exists and false otherwise
	 */ 
	private function checkUserExists($username, $email) {
		//username check
		$query = "SELECT * FROM users WHERE username = '{$username}'";
		$this->registry->getObject('db')->executeQuery($query);
		if($this->registry->getObject('db')->numRows()>=1) {
			return true;
		}

		//email exist check
		$query = "SELECT * FROM users WHERE email = '{$email}'";
		$this->registry->getObject('db')->executeQuery($query);
		if($this->registry->getObject('db')->numRows()>=1) {
			echo 'here2';
			return true;
		}
		
		return false;
	}
	
	public function getData() {
		return $this->data;
	}


}

?>
