<?php

class service {

	private $registry;
	private $services = array();

	public function __construct(Registry $registry) {
		$this->registry = $registry;
	}
	
	public function createService($service, $key) {
		if(file_exists(ROOT_DIRECTORY.'services/'.$service.'class.php')) {
			require_once(ROOT_DIRECTORY.'services/'.$service.'class.php');
			$this->services[$key] = new $service($this->registry);
		}else {
			throw new storeException("{$service}.class.php doesn't exists.", 404);
		}
	}
	
	public function getService($key) {
		if(isset($this->services[$key])) {
			return $this->services;
		}
		else {
			throw new storeException("{$key} is invalid it doesn't exists.",0);
		}
	}

}


?>
