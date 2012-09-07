<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Sample on how to use Contenido_FrontendNavigation.
 * This will show you how to create a standard Frontend-Navigation and a standard Breadcrumb-Navigation
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
 *   $Id: Sample.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


// #####################################################################################################################
// ########### standard navigation
// ########### retrieve subcategories of a given category and output them with idcat and name
// #####################################################################################################################
cInclude('classes', 'Contenido_FrontendNavigation/Contenido_FrontendNavigation.class.php');

try {
	$oFeNav = new Contenido_FrontendNavigation($db, $cfg, $client, $lang, $cfgClient);
	$oContenidoCategories = $oFeNav->getSubCategories($idcat, true); // use some valid idcat of "home" or whatever
    //$oContenidoCategories = $oFeNav->getSubCategories($idcat, true, true, 2); // for loading subcategories up to level 2
    if ($oContenidoCategories->count() > 0) {
		foreach ($oContenidoCategories as $oContenidoCategory) {
		    // output idcat and name of cat
		    echo '<p>'.$oContenidoCategory->getIdCat().' | '.$oContenidoCategory->getCategoryLanguage()->getName().'</p>';
		}
    }
} catch (InvalidArgumentException $eI) {
    echo 'Some error occured: ' . $eI->getMessage() . ': ' . $eI->getFile() . ' at line '.$eI->getLine() . ' ('.$eI->getTraceAsString().')';
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}


// #####################################################################################################################
// ########### breadcrumb navigation
// ########### retrieve breadcrumb from a given category up to a given level and output the categories with idcat and name
// #####################################################################################################################
cInclude('classes', 'Contenido_FrontendNavigation/Contenido_FrontendNavigation_Breadcrumb.class.php');

try {
	$oBreadcrumb = new Contenido_FrontendNavigation_Breadcrumb($db, $cfg, $client, $lang, $cfgClient);
	$oBreadCategories = $oBreadcrumb->get($idcat);
	foreach ($oBreadCategories as $oBreadCategory) {
		echo '<p>Bread '.$oBreadCategory->getIdCat().', '.$oBreadCategory->getCategoryLanguage()->getName().'</p>';
	}
} catch (InvalidArgumentException $eI) {
    echo 'Some error occured: ' . $eI->getMessage() . ': ' . $eI->getFile() . ' at line '.$eI->getLine() . ' ('.$eI->getTraceAsString().')';
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}


// #####################################################################################################################
// ########### standard navigation with some funky URLs
// ########### retrieve subcategories of a given category and output them linked with different styles:
// ########### front_content.php?idcat=1
// ########### index-a-1.html
// ########### cat1/cat2/index-a-1.html
// ########### http://www.someurl.com/index-b-13-91.html
// ########### /path/path/path/rocknroll,goodies,1,2,3.4fb
// #####################################################################################################################
cInclude('classes', 'Contenido_FrontendNavigation/Contenido_FrontendNavigation.class.php');

// build Navigation with different types of URL style
$aUrlStyleFunky = array('prefix' => 'rocknroll', 'suffix' => '.4fb', 'separator' => ','); // to create some other style of url
try {
	$oFeNav = new Contenido_FrontendNavigation($db, $cfg, $client, $lang, $cfgClient);
	$oContenidoCategories = $oFeNav->getSubCategories(12, true); // use some valid idcat of "home" or whatever
    if ($oContenidoCategories->count() > 0) {
		foreach ($oContenidoCategories as $oContenidoCategory) {
		    // get needed data
		    $iIdcat = $oContenidoCategory->getIdCat();
		    $iParentIdcat = $oContenidoCategory->getIdParent();
		    $sCatName = $oContenidoCategory->getCategoryLanguage()->getName();
		    
		    // -> front_content.php?idcat=1
			$sUrl1 = '<a href="'.$oFeNav->getUrl(array('idcat' => $iIdcat), 'front_content').'" target="_blank">click1</a>';
			// -> index-a-1.html
			$sUrl2 = '<a href="'.$oFeNav->getUrl(array('a' => $iIdcat), 'custom').'" target="_blank">click2</a>';
			// -> cat1/cat2/index-a-1.html
			$sUrl3 = '<a href="'.$oFeNav->getUrl(array('a' => $iIdcat, 
														'idcat' => $iIdcat, // needed to build category path
														'lang' => $lang, // needed to build category path
														'level' => 0) // needed to build category path
													).'" target="_blank">click3</a>';
			// -> http://someurl.com/path0/path1/index-b-13-91.html
			$sUrl4 = '<a href="'.$oFeNav->getUrl(array('b' => array('13','91')), 'custom', array(), true).'" target="_blank">click4</a>';
			// -> /cat0/cat1/cat1/rocknroll,members,1,2,3.4fb (where "cat" being languagedependent)
            $sUrl5 = '<a href="'.$oFeNav->getUrl(
                                                array('idcat' => $iIdcat, 'lang' => $lang, 'level' => 1, 'members' => array($iIdcat,$iSomeIdart)), 
                                                'custom_path', 
                                                $aUrlStyleFunky).
                                    '" target="_blank">click5</a>';
            echo '<p><strong>idcat: '.$iIdcat.' | '.
					'parent idcat: ' . $iParentIdcat.' | '.
					'category name: ' . $sCatName.' | '.
					'URL: '.$sUrl1.' - '.$sUrl2.' - '.$sUrl3.' - '.$sUrl4.' - '.$sUrl5.'</strong></p>';
		}
    }
} catch (InvalidArgumentException $eI) {
    echo 'Some error occured: ' . $eI->getMessage() . ': ' . $eI->getFile() . ' at line '.$eI->getLine() . ' ('.$eI->getTraceAsString().')';
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}
?>