<?php

class assign_sub_adminController {

	private $registry;

	public function __construct($registry) {
		$this->registry = $registry;
		$urlBits = $this->registry->getObject('url')->getURLBits();
		if(isset($urlBits[1])) {
			switch($urlBits[1]) {
				case 'show' :
					$this->showUser();
					break;
				case 'confirmAndAssign':
					$this->confirm();
					break;
			}
		}
	}

	private function showUser() {
		if(isset($_POST['user'])) {
			$this->registry->getObject('template')->buildFromTemplates(['']);
			$user = $this->registry->getObject('db')->sanitizeData($_POST['user']);
			if($user!='') {
				$sql = "SELECT * FROM users WHERE (username LIKE '%$user%' OR email LIKE '%$user%') AND super_admin = '0' AND is_social <> '1' ";
				$this->registry->getObject('db')->executeQuery($sql);
				$i = 0;
				while($row = $this->registry->getObject('db')->getRows()) {
					$i = 1;
					$this->registry->getObject('template')->buildFromTemplates( ['assign_sub_admin/show-user.html'], $i);
					$this->registry->getObject('template')->getPage()->addTag('user-username', $row['username'], $i);
					$this->registry->getObject('template')->getPage()->addTag('user-name', $row['name'],$i);
					$this->registry->getObject('template')->getPage()->addTag('user-email', $row['email'],$i);
					if(!$row['admin']) {
						$this->registry->getObject('template')->getPage()->addTag('assign-tag', 'Assign <strong>'.$row["name"].'</strong> as Admin',$i);						
					}
					else {
						$this->registry->getObject('template')->getPage()->addTag('assign-tag', 'Remove <strong>'.$row['name'].'</strong> from admin panel',$i);
					}
					$this->registry->getObject('template')->getPage()->addTag('siteurl',$this->registry->getSetting('siteurl'),$i);
					$this->registry->getObject('template')->parseOutput($i);
					echo $this->registry->getObject('template')->getPage()->getContentToPrint($i);
				}
				if(!$i) {
					echo 'No User found';
				}
			}
		}
		else {
			$url = $this->registry->getSetting('siteurl');
			header("location:$url");
		}
		exit;
	}

	private function confirm() {
		if(isset($_POST['confirm-password']) && isset($_POST['user-username']) && isset($_POST['user-email'])) {
			$password = $this->registry->getObject('db')->sanitizeData($_POST['confirm-password']);
			$user_username = $this->registry->getObject('db')->sanitizeData($_POST['user-username']);
			$user_email = $this->registry->getObject('db')->sanitizeData($_POST['user-email']);
			$result = $result = ['valid' => false, 'content' => '<div id="error-passwrd-'.$user_username.'" class="text-danger"><strong>wrong password</strong></div>'];
			if($this->registry->getObject('auth')->authenticatePassword($password)) {
				if($this->assign($user_email,$user_username)) {
					$result = ['valid' => true, 'content' => '<div id="confirm-passwrd-'.$user_username.'" class="text-success"><strong>Success</strong></div>'];
				}
				else {
					$result = ['valid' => false, 'content' => '<div id="error-passwrd-'.$user_username.'" class="text-danger"><strong>Got some error while assigning admin please try again later.</strong></div>'];
				}
			}
			echo json_encode($result);
			exit;
		}
		$url = $this->registry->getSetting('siteurl');;
		header("location:$url");
		exit;
	}
	
	private function assign($email, $username) {

		$urlBits = $this->registry->getObject('url')->getURLBits();
		if(isset($urlBits[2])) {
			switch($urlBits[2]) {
				case 'cakes' :
					if($this->assignValue($email, $username, 'cakes')) {
						return true;
					}
					return false;
					break;
				case 'apperals':
					if($this->assignValue($email, $username, 'apperals')) {
						return true;
					}
					return false;
					break;
				case 'content':
					if($this->assignValue($email, $username, 'content')) {
						return true;
					}
					return false;
					break;
			}
		}
	}

	private function assignValue($email, $username, $option) {
		try {
			$this->registry->getObject('db')->executeQuery("SELECT * FROM users WHERE username = '{$username}' AND email = '{$email}'");
			$data = $this->registry->getObject('db')->getRows();
			$admin = $data['admin'];
			$super_admin = $data['super_admin'];
			if(!$admin && !$super_admin) {
				$query = "SELECT * FROM  `backend_priority` WHERE  `key` =  '{$option}'";
				$this->registry->getObject('db')->executeQuery($query);
				if($this->registry->getObject('db')->numRows() == 1){
					$data = $this->registry->getObject('db')->getRows();			
					$data = $data['value'];
					$this->registry->getObject('db')->updateRecords('users',['admin'=>1],"username = '{$username}' AND email = '{$email}'");
					$this->registry->getObject('db')->executeQuery("SELECT id FROM users WHERE username = '{$username}' AND email = '{$email}'");
					$uid = $this->registry->getObject('db')->getRows()['id'];
					$this->registry->getObject('db')->insertRecords('access',['uid'=>$uid, 'access_code'=>$data]);
					return true;
				}
			} elseif(!$super_admin) {
				$this->registry->getObject('db')->updateRecords('users',['admin'=>0],"username = '{$username}' AND email = '{$email}'");
				$this->registry->getObject('db')->executeQuery("SELECT id FROM users WHERE username = '{$username}' AND email = '{$email}'");
				$uid = $this->registry->getObject('db')->getRows()['id'];
				$this->registry->getObject('db')->deleteRecords('access',"uid = '{$uid}'",1);
				return true;
			} else {
				return false;
			}
		} catch(storeException $e) {
			$this->registry->log->logError($e->completeException());
		}
		return false;
	}

}

?>



