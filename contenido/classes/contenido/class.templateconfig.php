<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Template access class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-08-04
 *
 *   $Id: class.templateconfig.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");


class cApiTemplateConfigurationCollection extends ItemCollection
{
	function cApiTemplateConfigurationCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["tpl_conf"], "idtplcfg");
		$this->_setItemClass("cApiTemplateConfiguration");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
	
    function delete ($idtplcfg) {
        $item = parent::delete($idtplcfg);
        $oContainerConfCollection = new cApiContainerConfigurationCollection ("idtplcfg = '$idTplcfgStandard'");
        $aDelContainerConfIds = array();
        while ($oContainerConf = $oContainerConfCollection->next()) {
            array_push($aDelContainerConfIds, $oContainerConf->get('idcontainerc'));
        }
        
        foreach($aDelContainerConfIds as $iDelContainerConfId) {
            $oContainerConfCollection->delete($iDelContainerConfId);
        }
    }
    
	function create ($idtpl)
	{
        global $auth;

		$item = parent::create();
		$item->set("idtpl", $idtpl);
        $item->set("author", $auth->auth['uname']);
        $item->set("status", 0);
        $item->set("created", date('YmdHis'));
        $item->set("lastmodified", '0000-00-00 00:00:00');
		$item->store();
        
		$iNewTplCfgId = $item->get("idtplcfg");
        
        #if there is a preconfiguration of template, copy its settings into templateconfiguration
        $templateCollection = new cApiTemplateCollection("idtpl = '$idtpl'");
        
        if ($template = $templateCollection->next()) {
            $idTplcfgStandard = $template->get("idtplcfg");
            if ($idTplcfgStandard > 0) {
                $oContainerConfCollection = new cApiContainerConfigurationCollection ("idtplcfg = '$idTplcfgStandard'");
                $aStandardconfig = array();
                while ($oContainerConf = $oContainerConfCollection->next()) {
                    $aStandardconfig[$oContainerConf->get('number')] = $oContainerConf->get('container');
                }
                
                foreach ($aStandardconfig as $iContainernumber => $sContainer) {
                    $oContainerConfCollection->create($iNewTplCfgId, $iContainernumber, $sContainer);
                }
            }
        }
        
		return ($item);
	}
	
}

class cApiTemplateConfiguration extends Item
{
	function cApiTemplateConfiguration ($idtplcfg = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["tpl_conf"], "idtplcfg");
		$this->setFilters(array(), array());
		
		if ($idtplcfg !== false)
		{
			$this->loadByPrimaryKey($idtplcfg);	
		}
	}
}

?>