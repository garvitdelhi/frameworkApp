<?php
require_once('storeException.class.php');
/**
 * PHP Social Networking
 * @author Michael Peacock
 * Registry Class
 */
class Registry {
	
	/**
	 * Array of objects
	 */
	private $objects;
	
	/**
	 * Array of settings
	 */
	private $settings;
	
	public $log;	
	
    public function __construct(errorlog $log) {
    	$this->log = $log;
    }
    
    /**
     * Create a new object and store it in the registry
     * @param String $object the object file prefix
     * @param String $key pair for the object
     * @return void
     */
    public function createAndStoreObject( $object, $key )
    {
    	if(file_exists(ROOT_DIRECTORY.'registry/'.$object.'.class.php')) {
	    	require_once( $object . '.class.php' );
    		$this->objects[ $key ] = new $object( $this );
    	}
    	else {
    		throw new storeException("{$object} not found.", 0);
    	}
    }
    
    /**
     * Get an object from the registries store
     * @param String $key the objects array key
     * @return Object
     */
    public function getObject( $key )
    {
    	if(isset($this->objects[$key])) {
		return $this->objects[ $key ];
	}
	else {
		throw new storeException("{$key} object is now found",0);
	}
    }
    
    /**
     * Store Setting
     * @param String $setting the setting data
     * @param String $key the key pair for the settings array
     * @return void
     */
    public function storeSetting( $setting, $key )
    {
    	$this->settings[ $key ] = $setting;
    }
    
    /**
     * Get a setting from the registries store
     * @param String $key the settings array key
     * @return String the setting data
     */
    public function getSetting( $key )
    {
    	if(isset($this->settings[$key])) {
		return $this->settings[ $key ];
	}
	else {
		throw new storeException("{$key} key doesn't exists",0);
	}
    }
    
    public function errorPage( $heading, $content )
    {
    	$this->getObject('template')->buildFromTemplates('header.tpl.php', 'message.tpl.php', 'footer.tpl.php');
    	$this->getObject('template')->getPage()->addTag( 'heading', $heading );
    	$this->getObject('template')->getPage()->addTag( 'content', $content );
    }
    
    
    /**
     * Build a URL
     * @param array $urlBits bits of the array
     * @param array $queryString any query string data
     * @return String
     */
    public function buildURL( $urlBits, $queryString=array() )
    {
    	return $this->getObject('url')->buildURL( $urlBits, $queryString, false );
    }
    
    /**
     * Redirect the user to another location, displaying a message during the process
     * @param String $url the URL to redirect the user to
     * @param String $heading the message heading
     * @param String $message the message itself
     * @return void
     */
    public function redirectUser( $url, $heading, $message )
    {
    	$this->getObject('template')->buildFromTemplates('redirect.tpl.php');
    	$this->getObject('template')->getPage()->addTag( 'heading', $heading );
    	$this->getObject('template')->getPage()->addTag( 'message', $message );
    	$this->getObject('template')->getPage()->addTag( 'url', $url );
    	
    }
    
    public function generateToken() {
		$this->getObject('auth')->logout();
		$size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
		$saltString = mcrypt_create_iv($size, MCRYPT_DEV_RANDOM);
		$salt = $this->getObject('hash')->create_hash($saltString);	
		$_SESSION['salt'] = $salt;
		$token = sha1(md5($salt));
		$_SESSION['time'] = time();
		setcookie('token', $token, time()+900, '/', ''); #try last two options as true
     }
    
    
}

?>
