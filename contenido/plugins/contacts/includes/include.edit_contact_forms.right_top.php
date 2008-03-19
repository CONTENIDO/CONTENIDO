<?php
/***********************************************
* Plugin Contacts Management
*
* File			:	include.edit_contact_forms.right_top.php
* Version		:	1.0	
*
* Author		:	Maxim Spivakovsky
* Copyright		:	four for business AG
* Created		:	06-03-2006
* Modified		:	06-03-2006
************************************************/

if($_REQUEST['idcontacttype']) {
    $aMenuElements = array(i18n('Overview', "contacts") => 'contact_type_details', i18n('Properties', "contacts") => 'contact_properties_overview');
	foreach($aMenuElements as $sCaption => $sAction) {
		if(!$perm->have_perm_area_action($area, $sAction)) {
			unset($aMenuElements[$sCaption]);
		}
	}
	
	if(count($aMenuElements) > 0) {
		foreach($aMenuElements as $sCaption => $sAction) {
			$tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
			$tpl->set("d", "CLASS", '');
			$tpl->set("d", "OPTIONS", '');
			$tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$area&frame=4&action=".$sAction."&idcontacttype=".$_REQUEST['idcontacttype']).'">'.$sCaption.'</a>');
			$tpl->next();
		}
   
	    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
	    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
	}
	else {
	    cInclude("templates", $cfg["templates"]["right_top_blank"]);
	}

}
else {
    cInclude("templates", $cfg["templates"]["right_top_blank"]);
}

?>