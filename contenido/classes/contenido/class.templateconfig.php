<?php
/*****************************************
* File      :   $RCSfile: class.templateconfig.php,v $
* Project   :   Contenido
* Descr     :   Template access class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.templateconfig.php,v 1.3 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/
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