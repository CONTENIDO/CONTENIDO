<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Generate base href for multiple client domains  
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Frontend classes
 * @version    1.0
 * @author     Andreas Lindner, 4fb AG
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-07-31
 *   $Id: 
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

function cecCreateBaseHref () {

	global $cfg, $client;
	
	cInclude('classes', 'contenido/class.client.php');
	
	$oClient = new cApiClient($client);
	$arr_settings = $oClient->getProperties();
	
	foreach ($arr_settings as $arr_client) {
		if ( $arr_client["type"] == "client" && strstr($arr_client["name"],"frontend_path") !== false ) {
			$arr_urlsettings = parse_url($arr_client["value"]);

			if ($arr_urlsettings["host"] == $_SERVER['HTTP_HOST'] || 
				("www." . $arr_urlsettings["host"] ) == $_SERVER['HTTP_HOST'] || 
				$arr_urlsettings["host"]  ==  "www." . $_SERVER['HTTP_HOST'] ) {
					$str_base_uri = $arr_client["value"];
					return $str_base_uri;
			}
		}
	} 
}
?>
