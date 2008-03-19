<?php
/*****************************************
* File      :   $RCSfile: include.mod_history.php,v $
* Project   :   Contenido
* Descr     :   Module history
*
* Author    :   Timo A. Hummel
*               
* Created   :   11.12.2003
* Modified  :   $Date: 2006/10/06 00:05:26 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.mod_history.php,v 1.14 2006/10/06 00:05:26 bjoern.behrens Exp $
******************************************/

cInclude("classes","contenido/class.module.history.php");
cInclude("classes","class.ui.php");
cInclude("classes","class.htmlelements.php");
cInclude("includes","functions.mod.php");

if (!$perm->have_perm_area_action_item("mod_edit","mod_edit",$idmod))
{
	$link = new cHTMLLink;
	$link->setCLink("mod_translate", 4, "");
	$link->setCustom("idmod", $idmod);
	
	header("Location: ".$link->getHREF());
	
} else {

	if (!isset($idmodhistory))
	{
		$idmodhistory = 0;
	}
	
	if (getEffectiveSetting("modules", "disable-history", "false") !== "true")
    {
    
        if ($action == "mod_history_takeover")
        {
        	$mod = new cApiModuleHistory;
        	$mod->loadByPrimaryKey($idmodhistory);
        	
        	$idmod = modEditModule($idmod, $mod->get("name"), addslashes($mod->get("description")), addslashes($mod->get("input")), addslashes($mod->get("output")), addslashes($mod->get("template")), addslashes($mod->get("type")));      
        }
        
        if ($action == "mod_history_clear")
        {
        	$modhistory = new cApiModuleHistoryCollection;
        	$modhistory->select("idmod = '$idmod'");
        	
        	while ($mod = $modhistory->next())
        	{
        		$modhistory->delete($mod->get("idmodhistory"));	
        	}
        }
        
        $page = new UI_Page;
        
        $form = new UI_Table_Form("mod_history");
        $form->setVar("area","mod_history");
        $form->setVar("frame", $frame);
        $form->setVar("idmod",$idmod);
        
        $form2 = new UI_Table_Form("mod_display");
        $form2->setVar("area","mod_history");
        $form2->setVar("frame", $frame);
        $form2->setVar("idmod",$idmod);    
        
        $modList = new cApiModuleHistoryCollection;
        $modList->select("idmod = '$idmod'","","changed DESC");
        
        $form->addHeader(i18n("Module history"));
        
        $select = new cHTMLSelectElement("idmodhistory");
        
        $archiveAvailable = false;
        
        while ($mod = $modList->next())
        {
        	$archiveAvailable = true;
        	$option = new cHTMLOptionElement(date("d.m.Y H:i:s",$mod->get("changed")), $mod->get("idmodhistory"));
        	
        	if ($idmodhistory == 0)
        	{
        		$idmodhistory = $mod->get("idmodhistory");
        	}
        	
        	$select->addOptionElement($mod->get("idmodhistory"),$option);
        }
        
        $form2->setVar("idmodhistory", $idmodhistory);
        
        $select->setDefault($idmodhistory);
        
        $form->add("Show History Entry", $select->render());
        
        $mod = new cApiModuleHistory;
        $mod->loadByPrimaryKey($idmodhistory);
        
        $user = new User;
        $user->loadUserByUserID($mod->get("changedby"));
        
        $form2->add("Changed by", $user->getField("realname"));
        $form2->addHeader(i18n("Module data"));
        $descr  = new cHTMLTextarea("descr", htmlspecialchars($mod->get("description")), 120,5);
        $input  = new cHTMLTextarea("input", htmlspecialchars($mod->get("input")), 120,15);
        $output = new cHTMLTextarea("output", htmlspecialchars($mod->get("output")), 120,15);
        
        $form2->add(i18n("Name"), $mod->get("name") );
        $form2->add(i18n("Type"), $mod->get("type") );
        $form2->add(i18n("Description"), $descr->render() );
        $form2->add(i18n("Input"), $input->render() );
        $form2->add(i18n("Output"), $output->render() );
    
    	$form->unsetActionButton("submit");
    	$form2->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c", "mod_history_takeover");
    	$form2->unsetActionButton("submit");
    	
    	$form->setActionButton("clearhistory", "images/but_delete.gif", i18n("Clear module history"), "c", "mod_history_clear");
    	$form->setConfirm("clearhistory", i18n("Clear module history"), i18n("Do you really want to clear the module history?")."<br><br>".i18n("Note: This only affects the current module."));
    	$form->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");
    	
        if ($archiveAvailable)
        {
        	$page->setContent($form->render()."<br>".$form2->render());
        } else {
       		$page->setContent(i18n("No history available"));
        }
        
        $page->setMessageBox();
        $page->render();
    } else {
    	$page = new UI_Page;
  		$page->setContent(i18n("Module history disabled by system administrator"));
  		$page->render();
   	}
}
    
?>