<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Recursive loop over given category for building a sitemap navigation
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
class Contenido_Sitemap_Util {
    /**
     * Recursive Loop over all (sub)categories.
     * Each level will be assigned a css class sitemapStandardLevel_x
     *
     * @param Contenido_Category $oCategory
     * @param Contenido_FrontendNavigation $oFrontendNavigation
     * @param Template $oTpl
     * @param string $sUrlStyle
     * @param array $aCfg
     * @param int $iLang
     */
    public static function loopCats(Contenido_Category $oCategory, Contenido_FrontendNavigation $oFrontendNavigation, Template $oTpl, $sUrlStyle, array $aCfg, $iLang) {
    	// display current item
    	$iItemLevel = $oFrontendNavigation->getLevel($oCategory->getIdCat());
    	$aParams = array('a' => $oCategory->getIdCat(), 
    					'idcat' => $oCategory->getIdCat(), // needed to build category path
    					'lang' => $iLang, // needed to build category path
    					'level' => 0); // needed to build category path
    	// fill template with values
    	$oTpl->set('d', 'name', $oCategory->getCategoryLanguage()->getName());
    	$oTpl->set('d', 'css_level', $iItemLevel);
    	$oTpl->set('d', 'url', $oFrontendNavigation->getUrl($aParams, $sUrlStyle, $aCfg));
    	$oTpl->next();
    	// check if current item has sub-items
    	if ($oCategory->getSubCategories()->count() > 0) {
    		$oSubCategories = $oCategory->getSubCategories();
    		foreach ($oSubCategories as $oSubCategory) {
    			self::loopCats($oSubCategory, $oFrontendNavigation, $oTpl, $sUrlStyle, $aCfg, $iLang);
    		}
    	}
    }
}
?>