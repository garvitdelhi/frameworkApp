<?php

class contentController {

	private $registry;

	public function __construct($registry) {
		$this->registry = $registry;
		$this->setContent();
	}

	private function setContent() {
		$this->registry->getObject('template')->addTemplateBit('page-content','cakes.html');
	}
	
}

?>
