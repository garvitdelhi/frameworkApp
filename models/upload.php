<?php
/**
* this class is to handle bulk uploading files
*/
class upload {

	private $registry;
	private $uploaded_file_name = array();
	private $uploaded_file_path = array();
	private $uploaded_file = array();
	private $fileType;
	private $submited_file_name;
	private $max_size;
	private $multiple;
	private $change_file_name;

	
	function __construct($registry, $fileType, $submited_file_name, $path, $change_file_name = true, $multiple = false, $max_size = 71680) {
		$this->registry = $registry;
		$this->fileType = $fileType;
		$this->submited_file_name = $submited_file_name;
		$this->max_size = $max_size;
		$this->path = $path;
		$this->change_file_name = $change_file_name;
		$this->multiple = $multiple;
	}

	public function uploadFile() {
		if(isset($_FILES[$this->submited_file_name])) {
			try {
				if($this->multiple) {
					$this->handleMultiple($_FILES[$this->submited_file_name]);
				} else {
					$this->handleSingle($_FILES[$this->submited_file_name]);
				}
			} catch(storeException $e) {
				throw new storeException($e->getMessage(), $e->getCode());
			}
			if($this->checkForError()) {
				return true;
			}
		}
		else {
			try {
				$this->registry->getObject('template')->addTemplateBit('error','error.html');
				$this->registry->getObject('template')->getPage()->addTag('error-content','File not uploaded.');
			}catch(storeException $e) {
				throw new storeException($e->completeException, $e->getCode());
			}
		}

		return false;
	}

	private function handleSingle($files) {
		$mimes = $this->getMimes();
		if(in_array($files['type'],$mimes)) {
			if($files['error'] === UPLOAD_ERR_OK && $files['size'] <= $this->max_size) {
				$ext = pathinfo($files["name"], PATHINFO_EXTENSION);
				$fileName = $files['name'];
				if($this->change_file_name) {
					$fileName = $this->registry->getObject('auth')->getUserObject()->getUser()['username'].'_'.pathinfo($files['name'],PATHINFO_FILENAME).date('_d_D_M_o--H:i:s').".$ext";
				}
				while(file_exists($this->path.$fileName)) {
					if($this->change_file_name) {
						$fileName = pathinfo($files['name'],PATHINFO_FILENAME).uniqid().date('_d_D_M_o--H:i:s').".$ext";
					} else {
						throw new storeException("File name already exists in path provided.", 1);
						
					}
				}
				if(move_uploaded_file($files['tmp_name'], $this->path.$fileName)) {
					$this->uploaded_file_name[] =  $fileName;
					$this->uploaded_file_path[] = $this->path;
					$this->uploaded_file[] = $this->path.$fileName;
				}
				return true;
			}
			else {
				$this->registry->getObject('template')->addTemplateBit('error','error.html');
				$this->registry->getObject('template')->getPage()->addTag('error-content','Max size limit reached or got some other error. ');
				return false;
			}
		}
		else {
			$this->registry->getObject('template')->addTemplateBit('error','error.html');
			$this->registry->getObject('template')->getPage()->addTag('error-content','Uploaded file is not of '.$this->fileType.' format.');
			return false;
		}
	}

	private function handleMultiple($files) {
		$a = true;
		$file = $this->diverse_array($files);
		foreach ($file as $key => $value) {
			if(!$this->handleSingle($value)) {
				$a = false;
				break;
			}
		}
		return $a;
	}

	private function diverse_array($vector) { 
    	$result = array(); 
    	foreach($vector as $key1 => $value1) 
	        foreach($value1 as $key2 => $value2) 
    	        $result[$key2][$key1] = $value2; 
    	return $result; 
	} 

	public function getUploadedFileName() {
		return $this->uploaded_file_name;
	}

	public function getUploadedFilePath() {
		return $this->uploaded_file_path;
	}
	private function checkForError() {
		if(isset($_FILES[$this->submited_file_name]['error'])) {
				try {
					switch($_FILES[$this->submited_file_name]['error']) {
						case UPLOAD_ERR_INI_SIZE :
							throw new storeException('The uploaded file exceeds the upload_max_filesize directive in php.ini.',UPLOAD_ERR_INI_SIZE);
							break;
						case UPLOAD_ERR_FORM_SIZE :
							throw new storeException('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',UPLOAD_ERR_FORM_SIZE);
							break;
						case UPLOAD_ERR_PARTIAL :
							throw new storeException('The uploaded file was only partially uploaded.', UPLOAD_ERR_PARTIAL);
							break;
						case UPLOAD_ERR_NO_FILE :
							throw new storeException('No file was uploaded.',UPLOAD_ERR_NO_FILE);
							break;
						case UPLOAD_ERR_NO_TMP_DIR :
							throw new storeException('Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.',UPLOAD_ERR_NO_TMP_DIR);
							break;
						case UPLOAD_ERR_CANT_WRITE :
							throw new storeException('Failed to write file to disk. Introduced in PHP 5.1.0.',UPLOAD_ERR_CANT_WRITE);
							break;
						case UPLOAD_ERR_EXTENSION :
							throw new storeException(' A PHP extension stopped the file upload.',UPLOAD_ERR_EXTENSION);
							break;
						default : return true;
					}
				} catch(storeException $e) {
					throw new storeException($e->completeException(), $e->getCode());
				}
			}
	}

	private function getMimes() {
		switch ($this->fileType) {
			case 'image':
				return array('image/jpeg', 'image/png');;
				break;
			case 'CSV':
				return array('application/vnd.ms-excel','text/plain','text/csv','text/tsv');
				break;
			default:
				return array();
				break;
		}
	}

	public function getUploadedFile() {
		return $this->uploaded_file;
	}
}

?>