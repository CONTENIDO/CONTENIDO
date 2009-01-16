<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Recursive loop over given category for building a frontend navigation
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2009-01-15
 *
 *   $Id$
 * }}
 *
 */

class Contenido_NavMain_Util {
    /**
     * Recursive Loop over all (sub)categories.
     * Each level will be assigned a css class navmainStandardLevel_x
     *
     * @param Contenido_Category $oCategory
     * @param Contenido_FrontendNavigation $oFrontendNavigation
     * @param Template $oTpl
     * @param string $sUrlStyle
     * @param array $aCfg
     * @param int $iLang
     */
    public static function loopCats(Contenido_Category $oCategory, Contenido_FrontendNavigation $oFrontendNavigation, Template $oTpl, array $aCfg, $iLang, array $aLevelInfo, $iCurrentPageIdcat) {
    	// display current item
    	$iItemLevel = $oFrontendNavigation->getLevel($oCategory->getIdCat());
    	if (!isset($aLevelInfo[$oCategory->getIdCat()])) {
    		$aLevelInfo[$oCategory->getIdCat()] = array();
    	}
    	$oCurrentSubcategories = $oFrontendNavigation->getSubCategories($oCategory->getIdCat());
    	$aLevelInfo[$oCategory->getIdCat()]['has_children'] = $oCurrentSubcategories->count() > 0;
    	$aLevelInfo[$oCategory->getIdCat()]['first_child_item'] = -1;
    	$aLevelInfo[$oCategory->getIdCat()]['last_child_item'] = -1;
        $bMarkActive = $oCategory->getIdCat() == $iCurrentPageIdcat || $oFrontendNavigation->isInPathToRoot($oCategory->getIdCat(), $iCurrentPageIdcat);
    	if ($oCurrentSubcategories->count() > 0) {
    		$aLevelInfo[$oCategory->getIdCat()]['first_child_item'] = $oCurrentSubcategories[0]->getIdCat();
    		$aLevelInfo[$oCategory->getIdCat()]['last_child_item'] = $oCurrentSubcategories[$oCurrentSubcategories->count()-1]->getIdCat();
    	}
    	// this is just for sample client - modify to your needs!
    	if ($aCfg['url_builder']['name'] == 'front_content') {
    	    $aParams = array('lang' => $iLang);
    	} else {
        	$aParams = array('a' => $oCategory->getIdCat(), 
        					'idcat' => $oCategory->getIdCat(), // needed to build category path
        					'lang' => $iLang, // needed to build category path
        					'level' => 0); // needed to build category path
    	}
    	// fill template with values
    	$oTpl->set('d', 'name', $oCategory->getCategoryLanguage()->getName());
    	$oTpl->set('d', 'css_level', $iItemLevel);
    	$oTpl->set('d', 'css_first_item', ($aLevelInfo[$oCategory->getIdParent()]['first_child_item'] == $oCategory->getIdCat() ? ' first' : ''));
    	$oTpl->set('d', 'css_last_item', ($aLevelInfo[$oCategory->getIdParent()]['last_child_item'] == $oCategory->getIdCat() ? ' last' : ''));
    	$oTpl->set('d', 'css_active_item', ($bMarkActive === true ? ' active' : ''));
    	try {
    	   $oTpl->set('d', 'url', Contenido_Url::getInstance()->build($aParams));
    	} catch (InvalidArgumentException $e) {
    	    $oTpl->set('d', 'url', '#');
    	}
    	$oTpl->next();
    	// check if current item has sub-items to be displayed
    	$bShowFollowUps = ($oCategory->getIdCat() == $iCurrentPageIdcat || $oFrontendNavigation->isInPathToRoot($oCategory->getIdCat(), $iCurrentPageIdcat))
    							? true : false;
    	if ($bShowFollowUps === true && $oCurrentSubcategories->count() > 0) {
    		$oSubCategories = $oCurrentSubcategories;
    		foreach ($oSubCategories as $oSubCategory) {
    			self::loopCats($oSubCategory, $oFrontendNavigation, $oTpl, $aCfg, $iLang, $aLevelInfo, $iCurrentPageIdcat);
    		}
    	}
    }
}
?>