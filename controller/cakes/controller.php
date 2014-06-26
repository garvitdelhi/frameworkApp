<?php

require_once ROOT_DIRECTORY.'models/upload.php';
require_once ROOT_DIRECTORY.'models/analyseCSV.php';

class cakesController {

	private $registry;
	private $service = 'cakes';

	public function __construct($registry) {
		$this->registry = $registry;
		$urlBits = $this->registry->getObject('url')->getURLBits();
		if(isset($urlBits[1])) {
			switch($urlBits[1]) {
				case 'bulk' :
					$this->handleBulkUpload();
					break;
				case 'products':
					$this->handleProducts();
					break;
				case 'home':
					break;
				case 'downloads':
					$this->handleDownloads();
					break;
				case 'images':
					$this->uploadImages();
					break;
				default:
					header("location:".$this->registry->getSetting('siteurl')."error404");
					break;
			}
		}
		else {
			header("location:".$this->registry->getSetting('siteurl')."cakes/home");
		}
	}

	private function handleBulkUpload() {
		try {
			$this->registry->getObject('template')->addTemplateBit('page-content','cakes/bulk.html');
			$urlBits = $this->registry->getObject('url')->getURLBits();
			if(isset($urlBits[2])) {
				if($urlBits[2]=='upload') {
					try {
						if(isset($_POST['option'])) {
							$option = ($_POST['option']==='add' || $_POST['option'] === 'overwrite')?$_POST['option']:'append';
						}
						$uploadCSV = new upload($this->registry, 'CSV', 'cakes', ROOT_DIRECTORY.'upload/');
						if($uploadCSV->uploadFile()) {
							$uploaded_file = $uploadCSV->getUploadedFile()[0];
							$analyseCSV = new analyseCSV($this->registry);
							if($analyseCSV->analyse($uploaded_file, $this->service, $option)) {
								$analyseCSV->updateDB();
								$this->registry->getObject('template')->addTemplateBit('success','success.html');
								$this->registry->getObject('template')->getPage()->addTag('success-content','Products updated.');
							}
						} else {
							$this->registry->getObject('template')->addTemplateBit('error','error.html');
							$this->registry->getObject('template')->getPage()->addTag('error-content','Got some error please try again later.');
						}
						
					} catch(storeException $e) {
						$this->registry->getObject('template')->addTemplateBit('error','error.html');
						$this->registry->getObject('template')->getPage()->addTag('error-content','Got some error please try again later.');
						$this->registry->log->logError($e->completeException());
					}
				}
			}
		} catch(storeException $e) {
			$this->registry->getObject('template')->addTemplateBit('error','error.html');
			$this->registry->getObject('template')->getPage()->addTag('error-content','Bulk upload not working. please try again later');
			$this->registry->log->logError($e->completeException());
		}
	}

	private function handleProducts() {
		$this->registry->getObject('template')->addTemplateBit('page-content','cakes/products.html');
		//require_once ROOT_DIRECTORY.'models/handleProducts.php';
		//$handleProducts = new handleProducts($this->registry);
	}

	private function handleDownloads() {
		try {
			require_once ROOT_DIRECTORY.'models/handleDownloads.php';
			$handleDownloads = new handleDownloads($this->registry, $this->service);
		} catch(storeException $e) {
			$this->registry->log->logError($e->completeException());
			$this->registry->getObject('template')->addTemplateBit('error','error.html');
			$this->registry->getObject('template')->getPage()->addTag('error-content','Download product not working. please try again later');
		}
	}

	private function uploadImages() {
		try {
			$this->registry->getObject('template')->addTemplateBit('page-content','cakes/bulk.html');		
			if(isset($_POST['upload_images'])) {
				if(isset($_FILES['images'])) {
					$uploadImage = new upload($this->registry, 'image', 'images', $this->registry->getSetting('uploadURL').'cakes/', false, true);
					if($uploadImage->uploadFile()){					
						$this->registry->getObject('template')->addTemplateBit('success','success.html');
						$this->registry->getObject('template')->getPage()->addTag('success-content','Files uploaded.');
					}
				} else {
					$this->registry->getObject('template')->addTemplateBit('error','error.html');
					$this->registry->getObject('template')->getPage()->addTag('error-content','NO images uploaded.');
				}
			}
		} catch(storeException $e) {
			$this->registry->log->logError($e->completeException());
			$this->registry->getObject('template')->addTemplateBit('error','error.html');
			$this->registry->getObject('template')->getPage()->addTag('error-content',$e->getMessage());
		}
	}
	
}

?>
