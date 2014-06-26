<?php

	class Page {
	
		private $title = 'Shiplock Inc.';
		
		private $registry;
		
		private $content = array();
		
		private $bits = array();
		
		private $postParseTags = array();
		
		private $tags = array();
		
		private $apd =  array();
		
		/*
		 * constructor saves the resisrty.
		 * @param - $registry it is registry object.
		 */
		public function __construc(Registry $registry) {
			if(isset($registry)) {
				$this->registry = $registry;
			}else{
				throw new storeException('Registry object cant be null',404);
			}
		}
		
		/**
     		 * Get the page title from the page
     		 * @return String
     		 */
    		public function getTitle()
    		{
    			return $this->title;
		}
    
    		/**
		 * Set the page title
     		 * @param String $title the page title
     		 * @return void
		 */
    		public function setTitle( $title )
    		{
    			if($title!=NULL) {
	    			$this->title = $title;
	    		}
	    		else {
	    			throw new storeException('NULLpointerException title can\'t be NULL.',0);
	    		}
		}
		/*
		 * saves content.
		 * @param - $content the content you want to save.
		 * @return - void.
		 */
		public function setContent($content, $key = 'main') {
	    		$this->content[$key] = $content;
		}
		
		/*
		 * returns the content saved.
		 * @return - $content.
		 */
		public function getContent($key = 'main') {
			if(isset($this->content[$key])) {
				return $this->content[$key];
			} else {
				return '';
			}
		}
		
		/*
		 * add the replacement and tags.
		 * @param - $tag, the tag you want to replace in content.
		 * @param - $file, the file from which you want to replace the contents.
		 * @param - $replacement, the replacements in that file.
		 * @return - void.
		 */
		public function addTemplateBit($tag, $file, $replacement=NULL, $key = 'main') {
			if(isset($tag)&&isset($file)) {
				$this->bits[$key][$tag] = array('file'=>$file, 'replacements'=>$replacement);
			}
			else {
				throw new storeException('NULLpointerException $tag and $file can\'t be NULL.',0);
			}
		}
		
		/*
		 * @return - $bits
		 */
		public function getBits($key = 'main') {
			if(isset($this->bits[$key])) {
				return $this->bits[$key];
			} else {
				return NULL;
			}
		}
		
		/**
     		 * Add a template tag, and its replacement value/data to the page
     		 * @param String $key the key to store within the tags array
      		 * @param String $data the replacement data (may also be an array)
     		 * @return void
     		 */
    		public function addTag( $tag, $data , $key='main')
		{
			if(isset($tag)&&isset($data)) {
	    			$this->tags[$key][$tag] = $data;
	    		}
	    		else {
				throw new storeException('NULLpointerException $key and $data can\'t be NULL.',0);
			}
    		}
		
		/**
	     	 * Get tags associated with the page
     		 * @return void
	     	 */
		public function getTags($key = 'main')
    		{
    			if(isset($this->tags[$key])) {
	    			return $this->tags[$key];
	    		} else {
	    			return NULL;
	    		}
    		}
    		
    		public function removeTag( $tag, $key = 'main' )
    		{
    			if(isset($tag)) {
    				unset( $this->tags[$key][$tag] );
    			}
    			else {
    				throw new storeException("{$tag} is an undifined Index.",0);
    			}
    		}
    		
    		/**
     		 * Add post parse tags: as per adding tags
     		 * @param String $key the key to store within the array
	     	 * @param String $data the replacement data
     		 * @return void
     		 */
	    	public function addPPTag( $tag, $data, $key='main' )
    		{
    			if(isset($tag)&&isset($data)) {
	    			$this->postParseTags[$key][$tag] = $data;
	    		}
	    		else {
				throw new storeException('NULLpointerException $key and $data can\'t be NULL.',0);
			}
    		}
    		
    		/**
     		 * Get tags to be parsed after the first batch have been parsed
     		 * @return array
     		 */
		public function getPPTags($key = 'main')
    		{
    			if(isset($this->postParseTags[$key])) {
	    			return $this->postParseTags[$key];
	    		} else {
	    			return NULL;
	    		}
    		}
    		
    		/**
		 * Gets a chunk of page content
     		 * @param String the tag wrapping the block ( <!-- START tag --> block <!-- END tag --> )
     		 * @return String the block of content
     		 */
    		public function getBlock( $tag, $key = 'main' )
    		{
    			//echo $tag;
			preg_match ('#<!-- START '. $tag . ' -->(.+?)<!-- END '. $tag . ' -->#si', $this->content[$key], $tor);	
			$tor = str_replace ('<!-- START '. $tag . ' -->', "", $tor[0]);
			$tor = str_replace ('<!-- END '  . $tag . ' -->', "", $tor);		
			return $tor;
	    	}
	    	
	    	/**
	 	 * Adds additional parsing data
	 	 * A.P.D is used in parsing loops.  We may want to have an extra bit of data depending on on iterations value
		 * for example on a form list, we may want a specific item to be "selected"
		 * @param String block the condition applies to
		 * @param String tag within the block the condition applies to
		 * @param String condition : what the tag must equal
		 * @param String extratag : if the tag value = condition then we have an extra tag called extratag
		 * @param String data : if the tag value = condition then extra tag is replaced with this value
		 */
		public function addParsingData($block, $tag, $condition, $extratag, $data, $key = 'main')
		{
			$this->apd[$key][$block] = array($tag => array('condition' => $condition, 'tag' => $extratag, 'data' => $data));
		}
		
		public function getParsingData($key)
    		{
    			if(isset($this->apd[$key])) {
    				return $this->apd[$key];
    			} else {
    				return NULL;
    			}
    		}
    		
    		public function getContentToPrint($key = 'main')
    		{
    			$this->content[$key] = preg_replace ('#{form_(.+?)}#si', '', $this->content[$key]);	
    			$this->content[$key] = preg_replace ('#{nbd_(.+?)}#si', '', $this->content[$key]);	
	    		return $this->content[$key];
    		}
    
	}

?>
