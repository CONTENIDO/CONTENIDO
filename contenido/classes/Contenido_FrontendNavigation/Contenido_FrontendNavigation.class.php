<?php
/**
* $RCSfile$
*
* Description: Object to build a Contenido Frontend Navigation 
*
* @version 0.2.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-15
* modified 2008-04-25 added method getLevel() and property aLevel, modified loadSubCategories() accordingly
* 
* @requires Contenido_FrontendNavigation_Base
* }}
*
* $Id$
*/

cInclude('classes', 'Contenido_FrontendNavigation/Contenido_FrontendNavigation_Base.class.php');

class Contenido_FrontendNavigation extends Contenido_FrontendNavigation_Base {
    /**
     * @var obj
     * @access protected
     */
    protected $oAuth; // for validating against fe-authentication
    
    /**
     * @var array
     * @access protected
     */
    protected $aLevel;
    
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
    }
    
    /**
     * Load Subcategories of a given Category-ID.
     * If you need Categories by FrontendPermission, you need to call method setAuth() before (!) calling loadSubCategories().
     * loadSubCategories() then automatically checks against FrontendPermission.
     * @access protected
     * @param int $iIdcat
     * @param boolean $bAsObjects If set to true, will load Subcategories as objects, otherwise as Array.
     * @param boolean $bWithSubCategories Set to true to also load subcategories of loaded SubCategories
     * @param int $iSubCategoriesLoadDepth Up to shich level should SubCategories be loaded. Defaults to 3 for a 3-level Navigation.
     * @return boolean
     * @author Rudi Bieller
     */
    protected function loadSubCategories($iIdcat, $bAsObjects = true, $bWithSubCategories = false, $iSubCategoriesLoadDepth = 3) {
        $iIdcat = (int) $iIdcat;
        $bUseAuth = (is_null($this->oAuth) || 
                        (get_class($this->oAuth) != 'Auth' && get_class($this->oAuth) != 'Contenido_Frontend_Challenge_Crypt_Auth')) 
                        ? false 
                        : true;
        $sFieldsToSelect = 'cattree.idcat, cattree.level';
        if ($bUseAuth === true) { // adapted from FrontendNavigation by Willi Man
            $sFieldsToSelect = 'cattree.idcat, cattree.level, catlang.public, catlang.idcatlang';
       		// load needed classes
            cInclude("classes","class.frontend.permissions.php");
			cInclude("classes","class.frontend.groups.php");
			cInclude("classes","class.frontend.users.php");
			// load user's frontendgroups if any
        	if((strlen($this->oAuth->auth['uid']) > 0) && ($this->oAuth->auth['uid'] != 'nobody')) {
				$oFrontendGroupMemberCollection = new FrontendGroupMemberCollection();
				$oFrontendGroupMemberCollection->setWhere('idfrontenduser', $this->oAuth->auth['uid']);
				$oFrontendGroupMemberCollection->addResultField('idfrontendgroup');
				$oFrontendGroupMemberCollection->query();
				// Fetch all groups the user belongs to (no goup, one group, more than one group).
				$aFeGroups = array();
				while($oFEGroup = $oFrontendGroupMemberCollection->next()) {
					$aFeGroups[] = $oFEGroup->get("idfrontendgroup");
				}
				$iNumFeGroups = count($aFeGroups);
			}
			// initialize fe-permission object
            $oFrontendPermissionCollection = new FrontendPermissionCollection();
        }
        $sSqlPublic = $bUseAuth === true ? '' : 'catlang.public   = 1 AND';
        $sSql = 'SELECT
					'.$sFieldsToSelect.'
				FROM
					'.$this->aCfg["tab"]["cat_tree"].' AS cattree,
					'.$this->aCfg["tab"]["cat"].' AS cat,
					'.$this->aCfg["tab"]["cat_lang"].' AS catlang
				WHERE
					cattree.idcat    = cat.idcat AND
					cat.idcat    = catlang.idcat AND
					cat.idclient = '.$this->iClient.' AND
					catlang.idlang   = '.$this->iLang.' AND
					catlang.visible  = 1 AND ' . 
                    $sSqlPublic . '
					cat.parentid = '.$iIdcat.'
				ORDER BY
					cattree.idtree';
	    if ($this->bDbg === true) {
	        $this->oDbg->show($sSql, 'Contenido_FrontendNavigation::loadSubCategories($iIdcat, $bAsObjects = true): $sSql');
	    }
	    $this->oDb->query($sSql);
	    if ($this->oDb->Errno != 0) {
	        return false;
	    }
        $this->aCategories = array();
	    while ($this->oDb->next_record()) {
	        // check against fe-auth
	        if ($bUseAuth === true && $this->oDb->f('public') == 0) {
	            if ($iNumFeGroups > 0) {
	                for ($i = 0; $i < $iNumFeGroups; $i++) {
						if($oFrontendPermissionCollection->checkPerm($aFeGroups[$i], 'category', 'access', $this->oDb->f('idcatlang'), true)) {
							$this->aCategories[] = (int) $this->oDb->f('idcat');
							$this->aLevel[(int) $this->oDb->f('idcat')] = (int) $this->oDb->f('level');
							break;
						}
					}
	            }
	        } else {
	            $this->aCategories[] = (int) $this->oDb->f('idcat');
	            $this->aLevel[(int) $this->oDb->f('idcat')] = (int) $this->oDb->f('level');
	        }
	    }
	    if ($bAsObjects === true) {
		    $oCategories = new Contenido_Categories(new DB_Contenido(), $this->aCfg);
		    $oCategories->setDebug($this->bDbg, $this->sDbgMode);
	        $oCategories->setIdLang($this->iLang);
	        $oCategories->setloadSubCategories($bWithSubCategories, $iSubCategoriesLoadDepth);
	        $oCategories->load($this->aCategories, true, $this->iLang);
	        $this->oCategories = $oCategories;
	    }
    }

    /**
     * Load and return Subcategories of a given Category.
     * If you need Categories by FrontendPermission, you need to call method setAuth() before (!) calling loadSubCategories().
     * loadSubCategories() then automatically checks against FrontendPermission.
     * @access public
     * @param int $iIdcat
     * @param boolean $bAsObjects If set to true, will load Subcategories as objects, otherwise as Array.
     * @param boolean $bWithSubCategories Set to true to also load subcategories of loaded SubCategories
     * @param int $iSubCategoriesLoadDepth Up to shich level should SubCategories be loaded. Defaults to 3 for a 3-level Navigation.
     * @return mixed Contenido_Categories or Array, depending on value for $bAsObjects
     * @author Rudi Bieller
     */
    public function getSubCategories($iIdcat, $bAsObjects = true, $bWithSubCategories = false, $iSubCategoriesLoadDepth = 3) {
        $this->loadSubCategories($iIdcat, $bAsObjects, $bWithSubCategories, $iSubCategoriesLoadDepth);
        return $bAsObjects === true ? $this->oCategories : $this->aCategories;
    }
    
    /**
     * Get Level of a given idcat. If idcat wasn't loaded yet, level will be queried.
     * @access public
     * @param int $iIdcat
     * @return int Level of requested idcat. In case of an error, -1 is returned.
     */
    public function getLevel($iIdcat) {
        if (isset($this->aLevel[intval($iIdcat)])) {
            return $this->aLevel[intval($iIdcat)];
        }
        $sSql = 'SELECT level FROM ' . $this->aCfg["tab"]["cat_tree"] . ' WHERE idcat = ' . intval($iIdcat);
        $this->oDb->query($sSql);
        if ($this->oDb->Errno != 0) {
	        return -1;
	    }
	    if ($this->oDb->num_rows() > 0) {
	        $this->oDb->next_record();
	        return intval($this->oDb->f('level'));
	    }
        return -1;
    }
    
    /**
     * Set internal property for Auth object to load only those categories the FE-User has right to see.
     * Use this method if you have protected Categories and need to check agains FrontendUser Rights.
     * @access public
     * @param Auth $oAuth
     * @return void
     * @author Rudi Bieller
     */
    public function setAuth(Auth $oAuth) {
        $this->oAuth = $oAuth;
    }
}
?>