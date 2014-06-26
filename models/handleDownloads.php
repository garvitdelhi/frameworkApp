<?php

	class handleDownloads {
		private $registry;
		private $service;
		private $data;
		private $acceptedColumn = array();
		private $validData = true;
		private $dataError = '';
		private $file;
		private $columns;
		private $url;

		public function __construct($registry, $service) {
			try {
				$this->registry = $registry;
				$this->service = $service;
				$this->registry->getObject('template')->addTemplateBit('page-content','cakes/downloads.html');
				$this->giveAccess();
				$urlBits = $this->registry->getObject('url')->getURLBits();
				if (isset($urlBits[2])) {			
					if($urlBits[2] == 'getFile') {
						$this->getFile();
					} elseif ($urlBits[2] == 'backup' && isset($urlBits[3])) {
						$this->downloadFile();
					} elseif ($urlBits[2] == 'backupOverwrite' && isset($urlBits[3])) {
						$this->downloadOverwriteFile();
					} else {
						header("location:{$this->registry->getsetting('siteurl')}error404");
						exit();
					}
				}
			} catch(storeException $e) {
				throw new storeException($e->getMessage(), $e->getCode());
			}
		}

		private function giveAccess() {
			try {
				if(isset($_SESSION['priority'])) {
					if($_SESSION['priority'] == 'super_admin' || $_SESSION['priority'] == 'admin') {
						$this->registry->getObject('template')->addTemplateBit('download-options', 'cakes/overwrite-download.html');
					} else {
						$this->registry->getObject('template')->getPage()->addTag('download-options', '');
					}
				} else {
					header("location:{$this->registry->getSetting('siteurl')}{$this->registry->getObject('auth')->getUserObject()->getUser()['username']}/logout");
				}
			} catch(storeException $e) {
				throw new storeException($e->getMessage(), $e->getCode());
			}
		}

		private function getFile() {
			try {
				if(isset($_POST['option']) || isset($_POST['overwrite_status']) || isset($_POST['password'])) {
					$this->option = $this->registry->getObject('db')->sanitizeData($_POST['option']);
					$this->overwrite_status = $this->registry->getObject('db')->sanitizeData($_POST['overwrite_status']);
					$this->password = $this->registry->getObject('db')->sanitizeData($_POST['password']);
					$result = $result = ['valid' => false, 'content' => '<div id="error-passwrd" class="text-danger"><strong>wrong password</strong></div>'];
					if($this->registry->getObject('auth')->authenticatePassword($this->password)) {
						if($this->option == 1) {
							$this->generateCompleteFile();
						} elseif ($this->option == 2) {
							$this->generateOverwriteFile();
						}
						$result = ['valid' => true, 'content' => '<div id="success-passwrd" class="text-success"><a target="_blank" href="'.$this->url.'">Click Me to download file</a></div>'];
					}
				} else {
					$result = ['valid' => false, 'content' => '<div id="error-passwrd" class="text-danger"><strong>Got some error while loading file please try again later.</strong></div>'];
				}
			} catch(storeException $e) {
				$this->registry->log->logError($e->completeException());
				$result = ['valid' => false, 'content' => '<div id="error-passwrd" class="text-danger"><strong>Got some error while loading file please try again later.</strong></div>'];
			}
			$result = json_encode($result);
			print_r($result);
			exit();
		}

		private function generateCompleteFile() {
			try {
				$query = "SELECT * FROM `backup` WHERE service = '{$this->service}'";
				$this->registry->getObject('db')->executeQuery($query);
				$result = $this->registry->getObject('db')->getRows();
				if($result['backup_date'] < $result['db_update']) {
					if($result['file']!=NULL) {
						//unlink($result['file']);
					}
					$this->file = ROOT_DIRECTORY.'backup/'.$this->option.'_'.$this->service.'_'.date('c').'.csv';
					try {
						$handle = new SplFileObject($this->file, 'w');
						$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'store' AND TABLE_NAME = '{$this->service}' ";
						$this->registry->getObject('db')->executeQuery($query);
						while($acceptedColumn = $this->registry->getObject('db')->getRows()) {
							$this->columns[] = $acceptedColumn['COLUMN_NAME'];
						}
						array_shift($this->columns);
						array_shift($this->columns);
						array_shift($this->columns);
						$handle->fputcsv($this->columns);
						$query = "SELECT * FROM `{$this->service}` WHERE overwrite_status = '0'";
						$this->registry->getObject('db')->executeQuery($query);
						while ($rows = $this->registry->getObject('db')->getRows()) {
							array_shift($rows);
							array_shift($rows);
							array_shift($rows);
							$rows['start_discount_date'] = date('i-G-d-m-Y', $rows['start_discount_date']);
							$rows['end_discount_date'] = date('i-G-d-m-Y', $rows['end_discount_date']);
							$handle->fputcsv($rows);
						}
						$size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
						$saltString = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
						$salt = $this->registry->getObject('hash')->create_hash($saltString);
						$_SESSION['download_salt'] = $salt;
						$this->url = $this->registry->getSetting('siteurl').'cakes/downloads/backup/'.sha1($salt);
						$data = array("file"=>$this->file, "backup_date"=>time());
						$this->registry->getObject('db')->updateRecords('backup', $data,"service = '{$this->service}'");
					} catch(Exception $e) {
						throw new storeException($e->getMessage(), $e->getCode());
					}
				} else {
					$size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
					$saltString = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
					$salt = $this->registry->getObject('hash')->create_hash($saltString);
					$_SESSION['download_salt'] = $salt;
					$this->url = $this->registry->getSetting('siteurl').'cakes/downloads/backup/'.sha1($salt);
				}
			} catch(storeException $e) {
				throw new storeException($e->getMessage(), $e->getCode());
			}
		}

		private function generateOverwriteFile() {
			$this->file = ROOT_DIRECTORY.'backup/'.$this->option.'_'.$this->service.'_'.date('c').'.csv';
			try {
				$handle = new SplFileObject($this->file, 'w');
				$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'store' AND TABLE_NAME = '{$this->service}' ";
				$this->registry->getObject('db')->executeQuery($query);
				while($acceptedColumn = $this->registry->getObject('db')->getRows()) {
					$this->columns[] = $acceptedColumn['COLUMN_NAME'];
				}
				array_shift($this->columns);
				array_shift($this->columns);
				array_shift($this->columns);
				$handle->fputcsv($this->columns);
				$query = "SELECT * FROM `{$this->service}` WHERE overwrite_status = '{$this->overwrite_status}'";
				$this->registry->getObject('db')->executeQuery($query);
				while ($rows = $this->registry->getObject('db')->getRows()) {
					array_shift($rows);
					array_shift($rows);
					array_shift($rows);
					$rows['start_discount_date'] = date('i-G-d-m-Y', $rows['start_discount_date']);
					$rows['end_discount_date'] = date('i-G-d-m-Y', $rows['end_discount_date']);
					$handle->fputcsv($rows);
				}
				$size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
				$saltString = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
				$salt = $this->registry->getObject('hash')->create_hash($saltString);
				$_SESSION['download_salt'] = $salt;
				$this->url = $this->registry->getSetting('siteurl').'cakes/downloads/backupOverwrite/'.sha1($salt);
				$_SESSION['file'] = $this->file;
			} catch(Exception $e) {
				throw new storeException($e->getMessage(), $e->getCode());
			}
		}

		private function downloadFile() {
			$urlBits = $this->registry->getObject('url')->getURLBits();
			$salt = $urlBits[3];
			if(isset($_SESSION['download_salt'])){
				if($salt == sha1($_SESSION['download_salt'])) {
					unset($_SESSION['download_salt']);
					$query = "SELECT * FROM `backup` WHERE service = '{$this->service}'";
					$this->registry->getObject('db')->executeQuery($query);
					$result = $this->registry->getObject('db')->getRows();
					$file = $result['file'];
					if(file_exists($file)) {
						header('Content-Description: File Transfer');
    					header('Content-Type: application/octet-stream');
    					header('Content-Disposition: attachment; filename='.basename($file));
    					header('Expires: 0');
    					header('Cache-Control: must-revalidate');
    					header('Pragma: public');
    					header('Content-Length: ' . filesize($file));
    					ob_clean();
    					flush();
    					readfile($file);
    					exit;
					}
				}
			} else {
				header("location:{$this->registry->getsetting('siteurl')}error404");
				exit();
			}
		}

		private function downloadOverwriteFile() {
			$urlBits = $this->registry->getObject('url')->getURLBits();
			$salt = $urlBits[3];
			if(isset($_SESSION['download_salt'])){
				if($salt == sha1($_SESSION['download_salt'])) {
					unset($_SESSION['download_salt']);
					$file = $_SESSION['file'];
					if(file_exists($file)) {
						header('Content-Description: File Transfer');
    					header('Content-Type: application/octet-stream');
    					header('Content-Disposition: attachment; filename='.basename($file));
    					header('Expires: 0');
    					header('Cache-Control: must-revalidate');
    					header('Pragma: public');
    					header('Content-Length: ' . filesize($file));
    					ob_clean();
    					flush();
    					readfile($file);
    					exit;
					}
				}
			} else {
				header("location:{$this->registry->getsetting('siteurl')}error404");
				exit();
			}
		}

	}

?>