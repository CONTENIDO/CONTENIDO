<?php
/***********************************************
* Plugin Contacts Management
*
* File			:	include.edit_contact_forms.left_top.php
* Version		:	1.0	
*
* Author		:	Maxim Spivakovsky
* Copyright		:	four for business AG
* Created		:	06-03-2006
* Modified		:	06-03-2006
************************************************/

$oUi = new UI_Left_Top;
$oLink = new Link;

if($perm->have_perm_area_action('contacts', 'contact_type_create')) {
    $oLink->setMultilink($area, "", $area, 'contact_type_create');
	$oLink->setAlt(i18n('Create new contact type', "contacts"));
	$oLink->setContent(i18n('Create new contact type', "contacts"));
	$oLink->updateAttributes(array('class' => 'addfunction'));
	$oLink->attributes;
	$oUi->setLink($oLink);
}

$oUi->render();
?>