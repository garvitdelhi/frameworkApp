<?php

class homeController {

	private $registry;

	public function __construct($registry) {
		$this->registry = $registry;
		try {
			$this->setContent();
		} catch(storeException $e) {
			throw new storeException('Content can\'t be pushed to site',0,$e);
		}
		try {
			$urlBits = $this->registry->getObject('url')->getURLBits();
		} catch(storeException $e) {
			throw new storeException('Can\'t get Url data',0,$e);
		}
		if(isset($urlBits[0])) {
			if($urlBits[0]==$this->registry->getObject('auth')->getUserObject()->getUser()['username']) {
				$this->registry->getObject('template')->addTemplateBit('page-content','home/profile.html');
			}
			elseif($urlBits[0]!='') {
				$siteurl = $this->registry->getSetting('siteurl');
				$siteurl = substr($siteurl,0,-1);
				header("location:$siteurl/error404");
			}
		}
		if( isset( $urlBits[1] ) ) {
			if($urlBits[1]==='logout') {
				$this->registry->getObject('auth')->logout();
				header("location:{$this->registry->getSetting('siteurl')}");
			}
		}
	}
	
	private function setContent() {
		$this->registry->getObject('auth')->checkForAuthentication();
		if($this->registry->getObject('auth')->isloggedIn()) {
		}
		else {
			$siteurl = $this->registry->getSetting('siteurl');
			$siteurl = substr($siteurl,0,-1);
			header("location:$siteurl/login");
		}
		$this->registry->getObject('template')->addTemplateBit('page-content','home/dashboard.html');
	}

}

?>
