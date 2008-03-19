<?php
/**
* $RCSfile$
*
* Description: Sample on how to use Contenido_Category / Contenido_Categories / Contenido_Category_Language.
* 
* Contenido_Category represets a Contenido Category (yes, indeed) with tbl. "con_cat".
* Optionally it can be loaded with values of "con_cat_lang" which is represented by Contenido_Category_Language.
* If you need a "Collection" of Contenido_Category objects, use Contenido_Categories.
* 
* These objects cannot be used for creating/updating Categories!!!
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-19
* }}
*
* $Id$
*/

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
?>