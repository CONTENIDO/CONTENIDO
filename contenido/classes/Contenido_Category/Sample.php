<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Sample on how to use Contenido_Category / Contenido_Categories / Contenido_Category_Language.
 * Sample on how to use Contenido_CategoryArticle
 *
 * Contenido_Category represets a Contenido Category (yes, indeed) with tbl. "con_cat".
 * Optionally it can be loaded with values of "con_cat_lang" which is represented by Contenido_Category_Language.
 * If you need a "Collection" of Contenido_Category objects, use Contenido_Categories.
 *
 * These objects cannot be used for creating/updating Categories!!!
 *
 * Contenido_CategoryArticle offers utility functions to get articles of 1 or more categories.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *  created 2008-02-19
 *  modified 2008-08-25 Added samples for Contenido_CategoryArticle
 *  $Id: Sample.php 851 2008-10-01 13:35:25Z rudi.bieller $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// SAMPLE Contenido_Category ###

cInclude('classes', 'Contenido_Category/Contenido_Category.class.php');

try {
	// load a single category
	$oConCat = new Contenido_Category($db, $cfg);
	//$oConCat->setloadSubCategories(true, 2); // will load subcategories of this idcat until given level
	$oConCat->load(1, true, $lang); // also load lang
	echo $oConCat->getIdCat().' :'.$oConCat->getCategoryLanguage()->getName().'<br />';

	// load several categories
	$oConCats = new Contenido_Categories($db, $cfg);
	$oConCats->load(array(1,2,5,10), true, $lang);
	// add a category
	$oConCats->add($oConCat);
	// see how many we've got
	$iNumCats = $oConCats->count();
	// sort cats in reverse order
	$oConCats->reverse();

	foreach ($oConCats as $oConCat) {
	    echo $oConCat->getIdCat().' :'.$oConCat->getCategoryLanguage()->getName().'<br />';
	}
} catch (InvalidArgumentException $eI) {
    echo 'Some error occured: ' . $eI->getMessage() . ': ' . $eI->getFile() . ' at line '.$eI->getLine() . ' ('.$eI->getTraceAsString().')';
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}

// SAMPLE Contenido_CategoryArticle ###

cInclude('classes', 'Contenido_Category/Contenido_Category_Articles.class.php');

try {
    $oConCatArt = new Contenido_Category_Articles($db, $cfg, $client, $lang);
    // ###
    // get start article of 1 given category
    // ###
    $oStartArticle = $oConCatArt->getStartArticleInCategory(3);
    echo '<p>Article object of start article: <textarea rows="20" cols="60" style="font-size:11px;">'.print_r($oStartArticle, true).'</textarea>';
    $iStartArticle = $oConCatArt->getStartArticleInCategory(3)->getField('idart');
    echo '<p>idart of start article: '.intval($iStartArticle).'</p>';
    // ###
    // get start articles of several given categories
    // ###
    $aStartArticles = $oConCatArt->getStartArticlesInCategoryRange(array(3,4,5,6,7,8));
    foreach ($aStartArticles as $oArticle) {
        $iStartArticle = $oArticle->getField('idart');
        $sStartTitle = $oArticle->getField('title');
        echo '<p>Start article idart of idcat range: '.strval($iStartArticle).' / '.print_r(array(3,4,5,6,7,8), true).'</p>';
    }
    // ###
    // get non start article of 1 given category
    // ###
    $aNonStartArticles = $oConCatArt->getNonStartArticlesInCategory(3, 'created', 'asc');
    foreach ($aNonStartArticles as $oArticle) {
        $iNonStartArticle = $oArticle->getField('idart');
        $sNonStartTitle = $oArticle->getField('title');
        echo '<p>Non start article idart of idcat: '.strval($iNonStartArticle).' / 3</p>';
    }
    // ###
    // get articles of 1 given category, online and offline
    // ###
    $aArticlesOfCategory = $oConCatArt->getArticlesInCategory(3, 'sortorder', 'DESC', true);
    foreach ($aArticlesOfCategory as $oArticle) {
        $iArticle = $oArticle->getField('idart');
        $sTitle = $oArticle->getField('title');
        echo '<p>article idart of current article: '.strval($iArticle).' / '.$sTitle.'</p>';
    }
    // ###
    // get articles of several given categories, online and offline
    // ###
    $aArticlesOfCategory = $oConCatArt->getArticlesInCategoryRange(array(3,4,5,6,7), 'sortorder', 'DESC', true);
    foreach ($aArticlesOfCategory as $oArticle) {
        $iArticle = $oArticle->getField('idart');
        $sTitle = $oArticle->getField('title');
        echo '<p>article idart of current article: '.strval($iArticle).' / '.$sTitle.'</p>';
    }
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}
?>