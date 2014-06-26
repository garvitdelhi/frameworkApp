<?php
	class template {
		
		private $registry;
		private $page = NULL;
		
		/**
		 * Include our page class, and build a page object to manage the content and structure of the page
		 * @param Object our registry object
		 */
		public function __construct(Registry $registry) {
			$this->registry = $registry;
			if(file_exists(ROOT_DIRECTORY.'/registry/page.class.php')) {
				require_once(ROOT_DIRECTORY.'/registry/page.class.php');
			}
			else {
				throw new storeException(ROOT_DIRECTORY.'/registry/page.class.php not found.',404);
			}
			try {
				$this->page = new Page($this->registry);
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		
		/**
		 * Include all the files passed as the parameter.
		 * @param - file names that are to be included.
		 * sets the content it the page contents.
		 * @return void
		 */
		public function buildFromTemplates($array, $key = 'main') {
			$files = NULL;
			$files = $array;
			$content = '';
			if($files!=NULL) {
				foreach($files as $file) {
					if(strpos($file, 'views/') === false) {
						$file = ROOT_DIRECTORY.'views/'.$file;
					}
					if(file_exists($file)) {
						$content .= file_get_contents($file);
					}
					$this->page->setContent($content,$key);
				}
			}
			else {
				throw new storeException('No arguments found',404);
			}
		}
			
		/**
		 * saves the bit you want to add
		 * @param - String $tag the tag where we insert the template e.g. {hello}
		 * @param - String $bit the template bit (path to file, or just the filename)
		 * @param - array $replacement the replacements that you want to make in that template ['username'=>'garvit']
		 * @return - void
		 */
		 
		 public function addTemplateBit($tag, $file, $replacement=NULL,$key = 'main') {
		 	try {
		 		if(strpos($file, 'views/') === false) {
		 			$file = ROOT_DIRECTORY.'views/'.$file;
			 	}
			 	if(file_exists($file)) {
			 		$this->page->addTemplateBit($tag, $file, $replacement, $key);
			 	}
			 	else {
			 		throw new storeException("$file can't be found", 404);
			 		
			 	}
			 }catch(storeException $e) {
			 	throw new storeException($e->getMessage(),$e->getCode(),$e);
			 }
		 }
		 
		 public function appendContent($content, $key = 'main') {
		 	try {
		 		$newContent = $this->page->getContent($key);
		 		$newContent .= $content;
		 		$this->page->setContent($newContent, $key);
		 	} catch(storeException $e) {
		 		throw new storeException($e->getMessage(),$e->getCode(),$e);
		 	}
		 }
		 
		 /*
		  * replace the bits that in conent that were added earlier
		  */
		 private function replaceBits($key = 'main') {
		 	$bits = $this->page->getBits($key);
		 	if($bits != NULL) {
			 	foreach($bits as $tag=>$tagValue) {
			 		$file = $tagValue['file'];
			 		$fileContent = '';
			 		if(file_exists($file)) {
			 			$fileContent = file_get_contents($file);
			 		}
			 		else {
			 			throw new storeException("{$file} can't be found.",404);
			 		}
			 		$replacements = $tagValue['replacements'];
			 		if($replacements!=NULL) {
				 		foreach($replacements as $replacementTag=>$change) {
				 			$replacementTag = '{'.$replacementTag.'}';
				 			str_replace($replacementTag, $change, $fileContent);
				 		}
				 	}
				 	$newContent = str_replace('{'.$tag.'}', $fileContent, $this->page->getContent($key));
				 	try {
				 		$this->page->setContent($newContent,$key);
				 	}catch(storeException $e) {
				 		throw new storeException($e->getMessage(),$e->getCode,$e);
				 	}
			 	}
			 }
		 }
		 
		 private function replaceTags($pp = false, $key = 'main') {
		 	$tags = NULL;
		 	if($pp == false) {
		 		$tags = $this->page->getTags($key);
		 	}
		 	else {
		 		$tags = $this->page->getPPTags($key);
		 	}
		 	
		 	if($tags != NULL) {
		 		foreach($tags as $tag=>$data) {
		 			if(is_array($data)) {
		 				if($data[0]=='SQL') {
		 					try {
		 						$this->replaceDBTags( $tag, $data[1] );
		 					}catch(storeException $e) {
		 						throw new storeException($e->getMessage(),$e->getCode(),$e);
		 					}
		 				}
		 				elseif($data[0]=='DATA') {
		 					try {
		 						$this->replaceDataTags( $tag, $data[1] );
		 					}catch(storeException $e) {
		 						throw new storeException($e->getMessage(),$e->getCode(),$e);
		 					}
		 				}
		 			}
		 			else {
		    				$newContent = str_replace( '{' . $tag . '}', $data, $this->page->getContent($key) );
		    				$this->page->setContent( $newContent, $key );
		 			}
		 		}
		 	}
		 }
		 
		/**
     		 * Replace content on the page with data from the database
     		 * @param String $tag the tag defining the area of content
     		 * @param int $cacheId the queries ID in the query cache
     		 * @return void
     		 */
    		private function replaceDBTags( $tag, $cacheId, $key = 'main' )
		{
	    		$block = '';
			$blockOld = $this->page->getBlock( $tag, $key );
			$apd = $this->page->getAdditionalParsingData($key);
			$apdkeys = array_keys( $apd );
			// foreach record relating to the query...
			try {
				while ($tags = $this->registry->getObject('db')->resultsFromCache( $cacheId ) )
				{
					$blockNew = $blockOld;
				
					// Do we have APD tags?
					if( in_array( $tag, $apdkeys ) )
					{
						// YES we do!
				        	foreach ($tags as $ntag => $data) 
				       		{
				        		$blockNew = str_replace("{" . $ntag . "}", $data, $blockNew);
					        	// Is this tag the one with extra parsing to be done?
					        	if( array_key_exists( $ntag, $apd[ $tag ] ) )
					        	{
						        	// YES it is
						        	$extra = $apd[ $tag ][$ntag];
						        	// does the tag equal the condition?
						        	if( $data == $extra['condition'] )
						        	{
						        		
							        	// Yep! Replace the extratag with the data
							        	$blockNew = str_replace("{" . $extra['tag'] . "}", $extra['data'], 	$blockNew);
						        	}
						        	else
					        		{
							        	// remove the extra tag - it aint used!
							        	$blockNew = str_replace("{" . $extra['tag'] . "}", '', $blockNew);
						        	}
					        	} 
					        }
					}
					else
					{
						// create a new block of content with the results replaced into it
						foreach ($tags as $ntag => $data) 
					       	{
					        	$blockNew = str_replace("{" . $ntag . "}", $data, $blockNew); 
					        }
					}
					
			        	$block .= $blockNew;
				}
				$pageContent = $this->page->getContent($key);
				// remove the seperator in the template, cleaner HTML
				$newContent = str_replace( '<!-- START ' . $tag . ' -->' . $blockOld . '<!-- END ' . $tag . ' -->', $block, $pageContent );
				// update the page content
				$this->page->setContent( $newContent, $key );
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		
		/**
     		 * Replace content on the page with data from the cache
     		 * @param String $tag the tag defining the area of content
     		 * @param int $cacheId the datas ID in the data cache
     		 * @return void
     		 */
	    	private function replaceDataTags( $tag, $cacheId, $key )
    		{
			try {
	    			$blockOld = $this->page->getBlock( $tag, $key );
				$block = '';
				$tags = $this->registry->getObject('db')->dataFromCache( $cacheId );
			
				foreach( $tags as $key => $tagsdata )
				{
					$blockNew = $blockOld;
					foreach ($tagsdata as $taga => $data) 
				       	{
			        		$blockNew = str_replace("{" . $taga . "}", $data, $blockNew); 
				        }
				        $block .= $blockNew;
				}
	
	
				$pageContent = $this->page->getContent($key);
				$newContent = str_replace( '<!-- START '.$tag.' -->'.$blockOld.'<!-- END '.$tag.' -->', $block, $pageContent );
			}catch(storeException $e) {
				throw new storeException($e->getMessage(),$e->getCode(),$e);
			}
		}
		
   
    		/**
     		 * Get the page object
     		 * @return Object 
     		 */
		public function getPage()
    		{
    			if($this->page!=NULL) {
				return $this->page;
			}
			else {
				throw new storeException('Page doesn\'t exists.',404);
			}
    		}
    		
    		/* Convert an array of data into some tags
     		 * @param array the data 
     		 * @param string a prefix which is added to field name to create the tag name
     		 * @return void
     		 */
    		public function dataToTags( $data, $prefix, $key )
    		{
    			if(isset($data) && isset($prefix)) {
	    			foreach( $data as $key => $content )
					{
		    			$this->page->addTag( $prefix.$key, $content, $key);
		    		}
				}
				else {
					throw new storeException('NULLpointerException $data and $prefix can\'t be NULL.',0);
				}
    		}
    
    		/**
     		 * Take the title we set in the page object, and insert them into the view
     		 */
    		public function parseTitle()
    		{
	    		$newContent = str_replace('<title>', '<title>'. $this->page->getTitle(), $this->page->getContent() );
	    		$this->page->setContent( $newContent );
    		}
    
    		/**
     		 * Parse the page object into some output
     		 * @return void
     		 */
    		public function parseOutput($key = 'main')
    		{
    			try {
	    			$this->replaceBits($key);
				$this->replaceTags(false, $key);
	    			$this->replaceBits($key);
	    			$this->replaceTags(true, $key);
		    		//$this->parseTitle();
		    	}catch(storeException $e) {
		    		throw new storeException($e->getMessage(),$e->getCode(),$e);
		    	}
	    	}

	    	public function printContent($key = 'main') {
	    		$this->parseOutput($key);
	    		return $this->page->getContentToPrint($key);
	    	}
	}

?>
