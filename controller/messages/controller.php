<?php

class messagesController {

	private $registry;
	private $service = 'cakes';
	private $controller = 'messages';

	public function __construct($registry) {
		try {
			$this->registry = $registry;
			$urlBits = $this->registry->getObject('url')->getURLBits();
			if(isset($urlBits[1])) {
				switch($urlBits[1]) {
					case 'new':
						$this->newMessages();
						break;
					case 'home':
						$this->handleHome();
						break;
					case 'downloads':
						$this->handleDownloads();
						break;
					default:
						$this->loadAllMessages();
						break;
				}
			}
			else {
				header("location:".$this->registry->getSetting('siteurl'));
			}
		} catch(storeException $e) {
			$this->registry->log->logError($e->completeException());
			$this->registry->getObject('template')->addTemplateBit('error','error.html');
			$this->registry->getObject('template')->getPage()->addTag('error-content','Messages Not working Please try Again later.<br>'.$e->getMessage());
			$this->registry->getObject('template')->getPage()->addTag('messages','');
		}
	}

	private function loadAllMessages() {
		try {
			$this->registry->getObject('template')->addTemplateBit('page-content', "{$this->controller}/page-content.html");
			$userid = $this->registry->getObject('auth')->getUserObject()->getUser()['id'];
			$query = "SELECT * FROM `backend_messages` WHERE reciever_id = '{$userid}'";
			$this->registry->getObject('db')->executeQuery($query);
			$this->getMessages('recieved');
			
			$userid = $this->registry->getObject('auth')->getUserObject()->getUser()['id'];
			$query = "SELECT * FROM `backend_messages` WHERE sender_id = '{$userid}'";
			$this->registry->getObject('db')->executeQuery($query);
			$this->getMessages('sent');
			
		} catch(storeException $e) {
			throw new storeException($e->getMessage(), $e->getCode());
			
		}
	}

	private function getMessages($type) {
		if($this->registry->getObject('db')->numRows() >= 1) {
				while ($messages = $this->registry->getObject('db')->getRows()) {
					if($type == 'recieved') {
						$sender = $this->registry->getObject('auth')->getUserWithID($messages['sender_id']);
					} elseif($type == 'sent') {
						$sender = $this->registry->getObject('auth')->getUserWithID($messages['reciever_id']);
					}
					$this->registry->getObject('template')->buildFromTemplates(["{$this->controller}/single-message.html"],'single');
					$this->registry->getObject('template')->getPage()->addTag('sender-user-name',$sender['name'],'single');
					$this->registry->getObject('template')->getPage()->addTag('subject', $messages['subject'],'single');
					$this->registry->getObject('template')->getPage()->addTag('message', $messages['message'],'single');
					$time = date('j M h:j:s a ', $messages['time']);
					$this->registry->getObject('template')->getPage()->addTag('time', $time,'single');
					$content = $this->registry->getObject('template')->printContent('single');
					$this->registry->getObject('template')->getPage()->addTag($type.'-messages', $content);
				}
			} else {
				$this->registry->getObject('template')->getPage()->addTag($type.'-messages', 'You have no messages');
			}
	}

	private function newMessages() {
		$this->registry->getObject('template')->addTemplateBit('page-content', "{$this->controller}/new-message.html");
	}
	
}

?>
