<?php
session_start();
ob_start();
DEFINE("ROOT_DIRECTORY", dirname( __FILE__ ) ."/" );
require_once(ROOT_DIRECTORY.'registry/registry.class.php');
require_once(ROOT_DIRECTORY.'registry/errorlog.class.php');
$log = new errorlog();
$registry = new Registry($log);
try {
	$registry->createAndStoreObject('mysqldb','db');
	$registry->createAndStoreObject( 'template', 'template' );
	$registry->createAndStoreObject('urlprocessor','url');
	$registry->createAndStoreObject('authenticate','auth');
	$registry->createAndStoreObject('passwordHash','hash');
	$registry->createAndStoreObject('mail','mail');
	$registry->getObject('db')->newConnection('localhost','database_connect','dbstore123!@#','store');
	$settingsSQL = "SELECT * FROM backend_settings WHERE 1=1";
	$registry->getObject('db')->executeQuery($settingsSQL);
	
	while( $setting = $registry->getObject('db')->getRows() )
	{
		$registry->storeSetting( $setting['value'], $setting['key'] );
	}
	
	if(!isset($_COOKIE['token'])) {
		$registry->generateToken();
	}
	elseif(sha1(md5($_SESSION['salt'])) != $_COOKIE['token']) {
		$registry->generateToken();
	}

} catch(storeException $e) {
	$log->logError($e->completeException());
	echo 'Some Problem Occured please try again later.';
	echo '<br><pre>'.$registry->log->getMessage().'</pre>';
	exit;
}
try {
	$registry->createAndStoreObject('accessControl','accessControl');
	$registry->getObject('url')->getURLData();
	$registry->getObject('template')->getPage()->addTag('error','');
	$registry->getObject('template')->getPage()->addTag('warning','');
	$registry->getObject('template')->getPage()->addTag('info','');
	$registry->getObject('template')->getPage()->addTag('success','');
	$registry->getObject('template')->getPage()->addPPTag('siteurl',$registry->getSetting('siteurl'));
	$registry->getObject('auth')->checkForAuthentication();
} catch(storeException $e) {
	$log->logError($e->completeException());
	echo 'Some Problem Occured please try again later.';
	echo '<br><pre>'.$e->getMessage().'</pre>';
	exit;
}
try {
	$controller = $registry->getObject('url')->getURLBit(0);
	$controllers = array();
	$controllersSQL = "SELECT * FROM backend_controllers WHERE active=1 AND priority = '0'";
	$registry->getObject('db')->executeQuery( $controllersSQL );
	while( $cttrlr = $registry->getObject('db')->getRows() )
	{
		$controllers[] = $cttrlr['controller'];
	}
	if( in_array( $controller, $controllers ) )
	{
		$registry->getObject('auth')->checkForAuthentication();
		//if($registry->getObject('auth')->isloggedIn()) {
			require_once( ROOT_DIRECTORY . 'controller/' . $controller . '/controller.php');
			$controllerInc = $controller.'controller';
			$controller = new $controllerInc( $registry, true );
		//}
	}
	elseif($controller === '') {
		$registry->getObject('auth')->checkForAuthentication();
		if(!$registry->getObject('auth')->isloggedIn()) {
			$controller = 'home';
			require_once( ROOT_DIRECTORY . 'controller/' . $controller . '/controller.php');
			$controllerInc = $controller.'controller';
			$controller = new $controllerInc( $registry, true );
		}
	}
}  catch(storeException $e) {
	$log->logError($e->completeException());
	$controller = 'home';
	require_once( ROOT_DIRECTORY . 'controller/' . $controller . '/controller.php');
	$controllerInc = $controller.'controller';
	$controller = new $controllerInc( $registry, true );
}
try {

	$registry->getObject('template')->parseOutput();
	print $registry->getObject('template')->getPage()->getContentToPrint();
} catch(storeException $e) {
	$log->logError($e->completeException());
	echo 'Some Problem Occured please try again later.';
	echo '<br><pre>'.$e->getMessage().'</pre>';
	exit;
}
exit;
?>
