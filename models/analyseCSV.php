<?php

class analyseCSV {

	private $registry;
	private $columns;
	private $data;
	private $acceptedColumn = array();
	private $service;
	private $validData = true;
	private $dataError = '';
	private $file;
	private $option;

	public function __construct($registry) {
		$this->registry = $registry;
	}

	public function analyse($file, $service, $option) {
		if(isset($file) && isset($service) && ($option == 'add' || $option == 'overwrite' ) ) {
			try {
				$this->file = $file;
				try {
					$handle = new SplFileObject($file, 'r');
					$handle->setFlags(SplFileObject::READ_CSV);
				} catch(Exception $e) {
					throw new storeException($e->getMessage(), $e->getCode());
				}
				$i = 0;
				$data = array();
				foreach ($handle as $row) {
					if(!$i) {
						$columns = $row;
						$i++;
					}
					else {
						$data[] = $row;
					}
				}
				$this->columns = $columns;
				$this->data = $data;
				unset($this->data[count($this->data)-1]);
				$handle = NULL;
				$this->service = $service;
				$this->option = $option;
				$this->acceptedColumn();
				if($this->checkData()) {
					return true;
				}
			} catch(storeExceptionon $e) {
				throw new storeException($e->getMessage(), $e->getCode());
				
			}
		}else {
			try {
				throw new storeException("either file or service or option is wrong", 1);
			}catch(storeException $e) {
				$this->registry->log->logError($e->completeException());
				$this->registry->getObject('template')->addTemplateBit('error','error.html');
				$this->registry->getObject('template')->getPage()->addTag('error-content',$e->getMessage());
				return false;
			}
		}
	}
	
	private function acceptedColumn() {
		try {
			$service = $this->service;
			$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'store' AND TABLE_NAME = '$service' ";
			$this->registry->getObject('db')->executeQuery($query);
			while($acceptedColumn = $this->registry->getObject('db')->getRows()) {
				$this->acceptedColumn[] = $acceptedColumn['COLUMN_NAME'];
			}
			array_shift($this->acceptedColumn);
			array_shift($this->acceptedColumn);
			array_shift($this->acceptedColumn);
			array_shift($this->acceptedColumn);
		} catch(storeException $e) {
			throw new storeException($e->getMessage(), $e->getCode());
		}
	}
	
	private function checkData() {
		try {
			if($this->columns == $this->acceptedColumn) {
				$this->validateData();
				if($this->validData) {
					return true;
				}
				else {
					unlink($this->file);
					$this->registry->getObject('template')->addTemplateBit('error','error.html');
					$this->registry->getObject('template')->getPage()->addTag('error-content',$this->dataError);
				}
			}	
			else {
				$this->registry->getObject('template')->addTemplateBit('error','error.html');
				$col = array_diff($this->columns, $this->acceptedColumn);
				$acol = array_diff($this->acceptedColumn, $this->columns);
				$str1 = '';
				$str2 = '';
				$columns = '';
				$acolumns = '';
				if(count($col)) {
					foreach ($col as $value) {
						$columns .= $value.', ';
					}
					$columns = substr($columns, 0, -2);
					$str1 = "Wrong columns : ".$columns.'.';
				}
				if(count($acol)) {
					foreach ($acol as $value) {
						$acolumns .= $value.', ';
					}
					$acolumns = substr($acolumns, 0, -2);
					$str2 = "<br> Missing columns : ".$acolumns.'.';
				}
				$str = $str1.$str2;
				$this->registry->getObject('template')->getPage()->addTag('error-content',$str);
				return false;
			}
		} catch(storeException $e) {
			throw new storeException($e->getMessage(), $e->getCode());
		}
	}

	private function validateData() {
		$product = 1;
		$tempData = array();
		foreach ($this->data as $key=> $rows) {
			//check name

			$tempData[$key]['product_id'] = $this->service.'_'.$this->registry->getObject('auth')->getUserObject()->getUser()['username'].date('_d_D_M_o--H:i:s').'_pid:'.$product;

			if($this->option == 'add') {
				$service = $this->service;
				$pid = $rows[0];
				$query = "SELECT * FROM `$service` WHERE sku = '$pid' AND overwrite_status = '0' ";
				$this->registry->getObject('db')->executeQuery($query);
				if( $this->registry->getObject('db')->numRows() >= 1 || $rows[0] == '') {
					$this->validData = false;
					$this->dataError = "sku on product ".$product." is either left empty or is present in database";
					break;
				}
			}
			else {
				if($rows[0]=='') {
					$this->validData = false;
					$this->dataError = "sku on product ".$product." is either left empty or is present in database";
					break;
				}
			}
			$tempData[$key][$this->columns[0]] = $rows[0];

			if( $rows[1] != '0' && $rows[1] != '1' ) {
				$this->validData = false;
				$this->dataError = "status on product ".$product." has to be 0 or 1.";
				break;
			}
			$tempData[$key][$this->columns[1]] = $rows[1];

			if(preg_match_all("/[^a-zA-z0-9\s]/", $rows[2])!=0) {
				$this->validData = false;
				$this->dataError = "Name on product ".$product." contain special corrector like *,-,/,; which are not allowed";
				break;
			}
			$tempData[$key][$this->columns[2]] = $rows[2];

			if(strlen($rows[3])<=100) {
				$this->validData = false;
				$this->dataError = "Description on product ".$product." is less then 100 characters.";
				break;
			}
			$tempData[$key][$this->columns[3]] = $rows[3];

			if(strlen($rows[4]) == '') {
				$this->validData = false;
				$this->dataError = "Information on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[4]] = $rows[4];

			if($rows[5] == '') {
				$this->validData = false;
				$this->dataError = "Manufacturer on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[5]] = $rows[5];

			if($rows[6] != '') {
				$images = json_decode($rows[6], true);
				if (json_last_error() != JSON_ERROR_NONE) {
					$this->validData = false;
					$this->dataError = "images on product ".$product." is not a JSON object.";
					break;
				} else {
					foreach ($images as $value) {
						if(preg_match("/\.jpg|\.png|\.gif/", $value) == 0) {
							$this->validData = false;
							$this->dataError = "Image on product ".$product." has to be a .jpg or .png or .gif file.";
							break;
						}
					}
					if(!$this->validData) {
						break;
					}
				}
			} else {
				$row[6] = json_encode(["noimage.jpg"]);
			}
			$tempData[$key][$this->columns[6]] = $rows[6];

			json_decode($rows[7], true);
			if (json_last_error() != JSON_ERROR_NONE) {
				$this->validData = false;
				$this->dataError = "Options on product ".$product." is not a JSON object.";
				break;
			}
			$tempData[$key][$this->columns[7]] = $rows[7];

			json_decode($rows[8], true);
			if (json_last_error() != JSON_ERROR_NONE) {
				$this->validData = false;
				$this->dataError = "cp on product ".$product." is not a JSON object.";
				break;
			}
			$tempData[$key][$this->columns[8]] = $rows[8];

			if (!is_numeric($rows[9]) || $rows[9] <= 0) {
				$this->validData = false;
				$this->dataError = "quantity on product ".$product." is not numeric or is a negative number.";
				break;
			}
			$tempData[$key][$this->columns[9]] = $rows[9];

			if($rows[10] == '') {
				$this->validData = false;
				$this->dataError = "variant on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[10]] = $rows[10];

			if($rows[11] == '') {
				$this->validData = false;
				$this->dataError = "occasion on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[11]] = $rows[11];

			if($rows[12] == '') {
				$this->validData = false;
				$this->dataError = "seo_keywords on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[12]] = $rows[12];

			if($rows[13] == '') {
				$this->validData = false;
				$this->dataError = "met_tag_keywords on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[13]] = $rows[13];

			if($rows[14] == '') {
				$this->validData = false;
				$this->dataError = "met_tag_description on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[14]] = $rows[14];

			if($rows[15] == '') {
				$this->validData = false;
				$this->dataError = "Product_tags on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[15]] = $rows[15];

			if($rows[16] == '') {
				$this->validData = false;
				$this->dataError = "availability_region on product ".$product." is empty.";
				break;
			}
			$tempData[$key][$this->columns[16]] = $rows[16];

			if( $rows[17] != '0' && $rows[17] != '1' ) {
				$this->validData = false;
				$this->dataError = "featured on product ".$product." has to be 0 or 1.";
				break;
			}
			$tempData[$key][$this->columns[17]] = $rows[17];

			if( $rows[18] != '0' && $rows[18] != '1' ) {
				$this->validData = false;
				$this->dataError = "bestseller on product ".$product." has to be 0 or 1.";
				break;
			}
			$tempData[$key][$this->columns[18]] = $rows[18];

			if( $rows[19] != '0' && $rows[19] != '1' ) {
				$this->validData = false;
				$this->dataError = "popular on product ".$product." has to be 0 or 1.";
				break;
			}
			$tempData[$key][$this->columns[19]] = $rows[19];

			json_decode($rows[20], true);
			if (json_last_error() != JSON_ERROR_NONE) {
				$this->validData = false;
				$this->dataError = "discount on product ".$product." is not a JSON object.";
				break;
			}
			$tempData[$key][$this->columns[20]] = $rows[20];

			if(preg_match_all("/^([0-6][0-9])-([0-1][0-9]|2[0-4])-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-(\d\d\d\d)$/", $rows[21]) == 0) {
				$this->validData = false;
				$this->dataError = "start_discount_date on product ".$product." is not of form mm/hh/dd/MM/yyyy.";
				break;
			}
			else {
				$t = explode('-', $rows[21]);
				if ($time = mktime($t[1], $t[0], 0, $t[3], $t[2], $t[4])) {
					$rows[21] = $time;
				}
				else {
					$this->validData = false;
					$this->dataError = "start_discount_date on product ".$product." is Wrong.";
					break;
				}
			}
			$tempData[$key][$this->columns[21]] = $rows[21];

			if(preg_match_all("/^([0-5][0-9])-([0-1][0-9]|2[0-3])-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-(\d\d\d\d)$/", $rows[22]) == 0) {
				$this->validData = false;
				$this->dataError = "end_discount_date on product ".$product." is not of form mm/hh/dd/MM/yyyy.";
				break;
			}
			else {
				$t = explode('-', $rows[22]);
				if ($time = mktime($t[1], $t[0], 0, $t[3], $t[2], $t[4])) {
					$rows[22] = $time;
				}
				else {
					$this->validData = false;
					$this->dataError = "start_discount_date on product ".$product." is Wrong.";
					break;
				}
			}
			$tempData[$key][$this->columns[22]] = $rows[22];
			$product++;
		}
		$this->data = $tempData;
	}

	public function getData() {
		return $this->data;
	}

	public function updateDB() {
		$option = $this->option;
		try {
			if($option === 'overwrite') {
				$service = $this->service;
				$query = "SELECT overwrite_status FROM `$service` ORDER BY overwrite_status DESC LIMIT 1";
				$this->registry->getObject('db')->executeQuery($query);
				$max_os = $this->registry->getObject('db')->getRows();
				$query = "SELECT * FROM  `$service` ";
				$cacheId = $this->registry->getObject('db')->cacheQuery($query);
				if($max_os['overwrite_status'] <= 3) {
					while ($rows = $this->registry->getObject('db')->resultsFromCache($cacheId)) {
						$os = $rows['overwrite_status'];
						$os = $os+1;
						$this->registry->getObject('db')->updateRecords($this->service, ["overwrite_status" => $os],"id = '".$rows['id']."'", '');
					}
				} else {
					$query = "INSERT INTO `backup_{$this->service}` SELECT * FROM `{$this->service}`";
					$this->registry->getObject('db')->executeQuery($query);
					while ($rows = $this->registry->getObject('db')->resultsFromCache($cacheId)) {
						$this->registry->getObject('db')->deleteRecords($this->service, "id = '".$rows['id']."'", '');
					}
				}
				foreach ($this->data as $key => $rows) {
					$rows['time'] = time();
					if($row['featured'] != 1) {
						$this->registry->getObject('db')->insertRecords($this->service, $rows);
					} else {
						$this->registry->getObject('db')->insertRecords('featured', $rows);
						$this->registry->getObject('db')->insertRecords($this->service, $rows);
					}
				}
				$this->updateBackup();
			}
			elseif($option === 'add') {
				foreach ($this->data as $key => $rows) {
					$rows['time'] = time();
					if($rows['featured'] != 1) {
						$this->registry->getObject('db')->insertRecords($this->service, $rows);
					} else {						
						$this->registry->getObject('db')->insertRecords('featured', $rows);
						$this->registry->getObject('db')->insertRecords($this->service, $rows);
					}
				}
				$this->updateBackup();
			}
			else {
				$this->validData = false;
				$this->dataError = "No option is choosen";
			}
			unlink($this->file);
		}catch(storeException $e) {
			throw new storeException($e->getMessage(), $e->getCode());
		}
	}

	private function updateBackup() {
		$service = $this->service;
		$query = "SELECT * FROM `backup` WHERE service = '$service'";
		$this->registry->getObject('db')->executeQuery($query);
		if($this->registry->getObject('db')->numRows()>=1) {
			$row = array('service'=>$this->service, 'db_update'=>time());
			$this->registry->getObject('db')->updateRecords('backup', $row, "service = '$service'");
		}
		else {
			$row = array('service'=>$this->service, 'db_update'=>time());
			$this->registry->getObject('db')->insertRecords('backup', $row);
		}
	}

}

?>