<?php 
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Integration of smarty as plugin
 * 
 * 
 *
 * @package    Contenido Template classes
 * @version    1.3.0
 * @author     Andreas Dieter
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 * 		created 	2010-07-22
 *		modified 	2010-08-17		prevent users from clone object
 *		modified 	2010-08-26		added sanity check
 *		modified	2010-09-06		fixed reset function
 *		modified 	2011-02-03		added class for contenido backend		
 * }}
 * 
 */
if(!defined('CON_FRAMEWORK')) {
 die('Illegal call');
}
$client = (isset($client)) ? $client : $load_client;
rereadClients();

plugin_include('smarty', 'classes/class.Contenido_SmartyWrapper.php');
#plugin_include('smarty', 'classes/class.Contenido_Backend_SmartyWrapper.php');

try {
	new Contenido_SmartyWrapper( $cfg, $cfgClient[$client], true );
} catch ( Exception $e ) {
	die( $e->getMessage() );
}

?>