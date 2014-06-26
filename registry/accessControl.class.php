<?php

class accessControl {

	private $registry;
	private $user;
	private $controllers = array();
	private $allowedAccess;
	private $access;

	public function __construct($registry) {
		$this->registry = $registry;
		$this->registry->getObject('auth')->checkForAuthentication();
		if($this->registry->getObject('auth')->isloggedIn()) {
			$this->giveAccess();
		} else {
			$this->registry->getObject('auth')->logout();
			$registry->getObject('template')->buildFromTemplates(['login.html']);
		}
	}
	
	private function giveAccess() {
		$this->user = $this->registry->getObject('auth')->getUserObject()->getUser();
		$this->registry->getObject('db')->executeQuery("SELECT access_code FROM access WHERE uid = {$this->user['id']}");
		$this->allowedAccess = $this->registry->getObject('db')->getRows();
		$this->allowedAccess = $this->allowedAccess['access_code'];
		$this->access = $this->allowedAccess;
		$this->allowedAccess = str_split($this->allowedAccess);
		$this->registry->getObject('db')->executeQuery("SELECT * FROM backend_controllers WHERE active = '1'");
		while( $cttrlr = $this->registry->getObject('db')->getRows() )
		{
			$this->controllers[$cttrlr['controller']] = $cttrlr['priority'] ;
		}
		foreach($this->controllers as $controller=>$priority) {
			if($priority) {
				if(!in_array($priority,$this->allowedAccess)) {
					unset($this->controllers[$controller]);
				}
			}
		}
		ksort($this->controllers, SORT_NATURAL);
		$this->setContent();
		$this->setPriority();
		$this->activateController();
	}
	
	private function activateController() {
			$this->registry->getObject('url')->getURLData();
			$controller = $this->registry->getObject('url')->getURLBit(0);
			if( in_array( $controller, array_keys($this->controllers) ) )
			{
				if($this->controllers[$controller]) {
					$this->registry->getObject('template')->getPage()->addTag($controller.'-active','class="active"');
					$this->registry->getObject('template')->addTemplateBit('page-content',$controller.'/page-content.html');
				}
				require_once( ROOT_DIRECTORY . 'controller/' . $controller . '/controller.php');
				$controllerInc = $controller.'controller';
				$controller = new $controllerInc( $this->registry, true );
			}
			else {
				$controller = 'home';
				$this->registry->getObject('template')->getPage()->addTag($controller.'-active','class="active"');
				require_once( ROOT_DIRECTORY . 'controller/' . $controller . '/controller.php');
				$controllerInc = $controller.'controller';
				$controller = new $controllerInc( $this->registry, true );
			}
	}

	private function setContent() {
		$this->registry->getObject('template')->buildFromTemplates(['index.html']);
		$files = array();
		foreach(array_keys($this->controllers) as $controller) {
			$files[] = $controller.'/side-bar.html';
		}
		$this->registry->getObject('template')->buildFromTemplates($files, 'side-bar');
		$this->registry->getObject('template')->parseOutput('side-bar');
		$data = $this->registry->getObject('template')->getPage()->getContentToPrint('side-bar');
		$this->registry->getObject('template')->getPage()->addTag('side-bar', $data);		
	}

	private function setPriority() {
		if(!isset($_SESSION['priority'])) {
			$query = "SELECT *  FROM backend_priority WHERE value = '{$this->access}'";
			$this->registry->getObject('db')->executeQuery($query);
			$result = $this->registry->getObject('db')->getRows();
			$_SESSION['priority'] = $result['key'];
		}
	}
}

?>
