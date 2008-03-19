<?php
/**
* $RCSfile$
*
* Description: Object to build a Contenido Frontend Navigation Breadcrumb
*
* @version 0.2.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-15
* 
* @requires Contenido_Frontend_Navigation_Base
* @todo Add possibility to load subcategories
* }}
*
* $Id$
*/

cInclude('classes', 'Contenido_FrontendNavigation/Contenido_Frontend_Navigation_Base.class.php');

class Contenido_Frontend_Navigation_Breadcrumb extends Contenido_Frontend_Navigation_Base {
    /**
     * @var int
     * @access private
     * @desc Used for breadcrumb loop over tree
     */
    private $_iCurrentLevel;
    
    /**
     * Constructor.
     * @access public
     * @param DB_Contenido $oDb
     * @param array $aCfg
     * @param int $iClient
     * @param int $iLang
     * @return void
     * @author Rudi Bieller
     */
    public function __construct(DB_Contenido $oDb, array $aCfg, $iClient, $iLang, array $aCfgClient) {
        parent::__construct($oDb, $aCfg, $iClient, $iLang, $aCfgClient);
        $this->oCategories = null;
    }
    
    /**
     * Assuming we are in a Sub-Category and need to get the path to it starting at its root.
     * Here, the path starts at root node.
     * @access public
     * @param int $iBaseCategoryId idcat of Sub-Category
     * @param int $iRootLevel Level until which the path should be created
     * @param boolean $bReset If true, will reset internal property $this->oCategories to an empty object
     * @return array
     * @author Rudi Bieller
     * @todo Add possibility to return an array
     */
    public function get($iBaseCategoryId, $iRootLevel = 0, $bReset = false) {
        $this->getBreadcrumb($iBaseCategoryId, $iRootLevel, $bReset);
        $this->oCategories->reverse(); // For a breadcrumb, we start at the main category, not the current one.
        return $this->oCategories;
    }
    
    /**
     * Assuming we are in a Sub-Category and need to get the path to it starting at its root.
     * This method goes recursively until the desired top level is reached and adds a Contenido_Category with each loop.
     * @access protected
     * @param int $iBaseCategoryId idcat of Sub-Category
     * @param int $iRootLevel Level until which the path should be created
     * @param boolean $bReset If true, will reset internal property $this->oCategories to an empty object
     * @return array
     * @author Rudi Bieller
     */
    protected function getBreadcrumb($iBaseCategoryId, $iRootLevel = 0, $bReset = false) {
        // this method calls itself, so check if this happened already
        if ($bReset === true || is_null($this->oCategories) || $this->oCategories->count() == 0) {
            $this->oCategories = new Contenido_Categories($this->oDb, $this->aCfg);
        }
        $iRootLevel = (int) $iRootLevel;
        $iBaseCategoryId = (int) $iBaseCategoryId;
        $sSql = 'SELECT
	                catlang.idcat AS idcat,
	                cat.parentid AS parentid,
					cattree.level as level
	            FROM
	                '.$this->aCfg["tab"]["cat_lang"].' AS catlang,
	                '.$this->aCfg["tab"]["cat"].' AS cat,
					'.$this->aCfg["tab"]["cat_tree"].' AS cattree
	            WHERE
	                catlang.idlang = '.$this->iLang.' AND
					cat.idclient  = '.$this->iClient.' AND
	                cat.idcat = '.$iBaseCategoryId.' AND
	                catlang.idcat     = cat.idcat AND
					cattree.idcat = cat.idcat';
        if ($this->bDbg === true) {
	        $this->oDbg->show($sSql, 'Contenido_Frontend_Navigation_Breadcrumb::getBreadcrumb($iBaseCategoryId, $iRootLevel = 0, $bReset = false): $sSql');
	    }
	    $this->oDb->query($sSql);
	    if ($this->oDb->Errno != 0) {
	        return false;
	    }
	    $this->oDb->next_record();
	    $oContenidoCategory = new Contenido_Category(new DB_Contenido(), $this->aCfg);
	    $oContenidoCategory->load(intval($this->oDb->f('idcat')), true, $this->iLang);
	    $this->oCategories->add($oContenidoCategory, $oContenidoCategory->getIdCat());
	    $this->_iCurrentLevel = (int) $this->oDb->f('level');
	    // if we are not at level 0, loop until we are
	    if ($this->_iCurrentLevel > $iRootLevel) {
	        while ($this->_iCurrentLevel > $iRootLevel) {
	            $this->getBreadcrumb($this->oDb->f('parentid'), $iRootLevel);
	        }
	    }
	    return $this->oCategories;
    }
}
?>