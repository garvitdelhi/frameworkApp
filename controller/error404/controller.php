<?php

class error404Controller {

	private $registry;

	public function __construct($registry) {
		$this->registry = $registry;
		$this->registry->getObject('template')->buildFromTemplates(['404.html']);
	}

}

?>
