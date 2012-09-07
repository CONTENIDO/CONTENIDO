<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Sample for using a Contenido_UrlBuilder object
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-02-19
 *   
 *   $Id: Sample.php 738 2008-08-27 10:21:19Z timo.trautmann $: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude('classes', 'UrlBuilder/Contenido_UrlBuilderFactory.class.php');

// build a front_content.php URL
try {
    $aParams = array('idcat' => 1, 'idart' => 5);
    $oUrlBuilder = Contenido_UrlBuilderFactory::getUrlBuilder('front_content');
    $oUrlBuilder->setHttpBasePath($cfgClient[$client]['path']['htmlpath']); // needed if you need an absolute url
    $oUrlBuilder->buildUrl($aParams, true);
    echo $oUrlBuilder->getUrl();
} catch (InvalidArgumentException $e) {
    throw $e;
}

// build a URL with category path with output /path/path/path/index-b-1-2-3.html (where "path" being languagedependent)
try {
    $aParams = array('idcat' => 1, 'lang' => 1, 'level' => 1, 'b' => array(1,2,3));
    $oUrlBuilder = Contenido_UrlBuilderFactory::getUrlBuilder('custom_path');
    $oUrlBuilder->buildUrl($aParams);
    echo $oUrlBuilder->getUrl();
} catch (InvalidArgumentException $e) {
    throw $e;
}

// build a URL with category path with output /path/path/path/rocknroll,goodies,1,2,3.4fb (where "path" being languagedependent)
try {
    $aParams = array('idcat' => 1, 'lang' => 1, 'level' => 1, 'goodies' => array(1,2,3));
	$aConfig = array('prefix' => 'rocknroll', 'suffix' => '.4fb', 'separator' => ',');
    $oUrlBuilder = Contenido_UrlBuilderFactory::getUrlBuilder('custom_path');
    $oUrlBuilder->buildUrl($aParams, false, $aConfig);
    echo $oUrlBuilder->getUrl();
} catch (InvalidArgumentException $e) {
    throw $e;
}
?>