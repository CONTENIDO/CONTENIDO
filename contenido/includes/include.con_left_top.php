<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Misc. functions of area con
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *  @created 2003-03-26
 *  @modified 2008-06-27, Frederic Schneider, add security fix
 *  @modified 2008-09-08, Ingo van Peeren, improved navigation tree in left bottom frame, expanding/
 *                                         collapsing of navigation tree without reloading (AJAX/
 *                                         javascript solution based on jquery)
 *  @modified 2008-09-18, Ingo van Peeren, moved template-changing to jquery      
 *  @modified 2010-06-16, Timo Trautmann, Fixed a bug wit the Syncselection (there was no right userright check)  
 *
 *   $Id: include.con_left_top.php 1187 2010-08-09 09:43:29Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("includes","functions.str.php");
cInclude("includes","functions.tpl.php");
cInclude('includes', 'functions.lang.php');

$tpl->reset();
global $sess, $frame, $area;
$idcat	= ( isset($_GET['idcat']) && is_numeric($_GET['idcat'])) ? $_GET['idcat'] : -1;

//Get sync options
if (isset($syncoptions))
{
	$syncfrom = (int) $syncoptions;
	$remakeCatTable = true;
}

if (!isset($syncfrom))
{
	$syncfrom = -1;	
}

$syncoptions = $syncfrom;

$tpl->set('s', 'SYNC_LANG', $syncfrom);


##########################################
# Delete a saved search
##########################################
$bShowArticleSearch = false;
if( isset($_GET['delsavedsearch']) )
{
	if( isset($_GET['itemtype']) && sizeof($_GET['itemtype'])>0 && isset($_GET['itemid']) && sizeof($_GET['itemid'])>0 )
	{
		$propertyCollection = new PropertyCollection;
		$propertyCollection->deleteProperties($_GET['itemtype'], $_GET['itemid']);
		$bShowArticleSearch = true;
	}
}

if( isset($_GET['save_search']) && $_GET['save_search'] == 'true') {
	$bShowArticleSearch = true;
}


##########################################
# ARTICLE SEARCH
##########################################
# modified 20/04/2007 by H. Librenz for backend search
$arrDays = array();

for ($i = 0; $i < 32; $i ++) {
	if ($i == 0) {	
		$arrDays[$i] = '--';
	} else {
		$arrDays[$i] = $i;
	}	
}

$arrMonths = array();

for ($i = 0; $i < 13; $i++) {
	if ($i == 0) {
		$arrMonths[$i] = '--';
	} else {
		$arrMonths[$i] = $i;
	}
}

$arrYears = array();

$arrYears[0] = '-----';
$sActualYear = (int) date("Y");

for ($i = $sActualYear-10; $i < $sActualYear+30; $i++) {
	$arrYears[$i] = $i;
}

$arrUsers = array();

$query = "SELECT * FROM ".$cfg['tab']['phplib_auth_user_md5']." ORDER BY realname";

$arrUsers['n/a'] = '-';

$db->query($query);

while ($db->next_record()) {
	$arrUsers[$db->f('username')] = $db->f('realname'); 
}

$arrDateTypes = array();

$arrDateTypes['n/a'] = i18n("Ignore");
$arrDateTypes['created'] = i18n("Date created");
$arrDateTypes['lastmodified'] = i18n("Date modified");
$arrDateTypes['published'] = i18n("Date published");

$articleLink="editarticle";
$oListOptionRow = new cFoldingRow("3498dbba-ed4a-4618-8e49-3a3635396e22", i18n("Article Search"), $articleLink, $bShowArticleSearch);
$tpl->set('s', 'ARTICLELINK', $articleLink);

#Textfeld
$oTextboxArtTitle = new cHTMLTextbox("bs_search_text", $_REQUEST["bs_search_text"], 10);
$oTextboxArtTitle->setStyle('width:135px;');

#Artikel_ID-Feld
$oTextboxArtID = new cHTMLTextbox("bs_search_id", $_REQUEST["bs_search_id"], 10);
$oTextboxArtID->setStyle('width:135px;');

#Date type
$oSelectArtDateType = new cHTMLSelectElement("bs_search_date_type", "bs_search_date_type");
$oSelectArtDateType->autoFill($arrDateTypes);
$oSelectArtDateType->setStyle('width:135px;');
$oSelectArtDateType->setEvent("Change", "toggle_tr_visibility('tr_date_from');toggle_tr_visibility('tr_date_to');");

if ($_REQUEST["bs_search_date_type"] !='') {
	$oSelectArtDateType->setDefault($_REQUEST["bs_search_date_type"]);
} else {
	$oSelectArtDateType->setDefault('n/a');	
}

#DateFrom
$oSelectArtDateFromDay = new cHTMLSelectElement("bs_search_date_from_day");
$oSelectArtDateFromDay->setStyle('width:40px;');
$oSelectArtDateFromDay->autoFill($arrDays);

$oSelectArtDateFromMonth = new cHTMLSelectElement("bs_search_date_from_month");
$oSelectArtDateFromMonth->setStyle('width:40px;');
$oSelectArtDateFromMonth->autoFill($arrMonths);

$oSelectArtDateFromYear = new cHTMLSelectElement("bs_search_date_from_year");
$oSelectArtDateFromYear->setStyle('width:55px;');
$oSelectArtDateFromYear->autoFill($arrYears);

if ($_REQUEST["bs_search_date_from_day"] > 0) {
	$oSelectArtDateFromDay->setDefault($_REQUEST["bs_search_date_from_day"]);
} else {
	$oSelectArtDateFromDay->setDefault(0);	
}

if ($_REQUEST["bs_search_date_from_month"] > 0) {
	$oSelectArtDateFromMonth->setDefault($_REQUEST["bs_search_date_from_month"]);
} else {
	$oSelectArtDateFromMonth->setDefault(0);	
}

if ($_REQUEST["bs_search_date_from_year"] > 0) {
	$oSelectArtDateFromYear->setDefault($_REQUEST["bs_search_date_from_year"]);
} else {
	$oSelectArtDateFromYear->setDefault(0);	
}

#DateTo
$oSelectArtDateToDay = new cHTMLSelectElement("bs_search_date_to_day");
$oSelectArtDateToDay->setStyle('width:40px;');
$oSelectArtDateToDay->autoFill($arrDays);

$oSelectArtDateToMonth = new cHTMLSelectElement("bs_search_date_to_month");
$oSelectArtDateToMonth->setStyle('width:40px;');
$oSelectArtDateToMonth->autoFill($arrMonths);

$oSelectArtDateToYear = new cHTMLSelectElement("bs_search_date_to_year");
$oSelectArtDateToYear->setStyle('width:55px;');
$oSelectArtDateToYear->autoFill($arrYears);

if ($_REQUEST["bs_search_date_to_day"] > 0) {
	$oSelectArtDateToDay->setDefault($_REQUEST["bs_search_date_to_day"]);
} else {
	$oSelectArtDateToDay->setDefault(0);	
}

if ($_REQUEST["bs_search_date_to_month"] > 0) {
	$oSelectArtDateToMonth->setDefault($_REQUEST["bs_search_date_to_month"]);
} else {
	$oSelectArtDateToMonth->setDefault(0);	
}

if ($_REQUEST["bs_search_date_to_year"] > 0) {
	$oSelectArtDateToYear->setDefault($_REQUEST["bs_search_date_to_year"]);
} else {
	$oSelectArtDateToYear->setDefault(0);	
}

#Author
$oSelectArtAuthor = new cHTMLSelectElement("bs_search_author");
$oSelectArtAuthor->setStyle('width:135px;');
$oSelectArtAuthor->autoFill($arrUsers);

if ($_REQUEST["bs_search_author"] !='') {
	$oSelectArtAuthor->setDefault($_REQUEST["bs_search_author"]);
} else {
	$oSelectArtAuthor->setDefault('n/a');	
}

$oSubmit = new cHTMLButton("submit", i18n("Search"));

$content  = '<div id="artsearch" style="border: 1px solid #B3B3B3; border-top: none; margin:0;padding:0; padding-bottom: 10px;background: '.$cfg['color']['table_dark'].';">';
$content .= '<form action="backend_search.php" method="post" name="backend_search" target="right_bottom" id="backend_search">';


$content .= '<table dir="'.langGetTextDirection($lang).'">';
$content .= '<input type="hidden" name="area" value="'.$area.'">';
$content .= '<input type="hidden" name="frame" value="'.$frame.'">';
$content .= '<input type="hidden" name="contenido" value="'.$sess->id.'">';
$content .= '<input type="hidden" name="speach" value="'.$lang.'">';

$content .= '<tr>';
$content .= '<td style="padding-left: 15px;">'. i18n("Title/Content").'</td>';
$content .= '<td>'.$oTextboxArtTitle->render().'</td>';
$content .= '</tr>';

$content .= '<tr>';
$content .= '<td style="padding-left: 15px;">'. i18n("Article ID").'</td>';
$content .= '<td>'.$oTextboxArtID->render().'</td>';
$content .= '</tr>';

$content .= '<tr>';
$content .= '<td style="padding-left: 15px;">'. i18n("Datum").'</td>';
$content .= '<td><nobr>'.$oSelectArtDateType->render().'</nobr></td>';
$content .= '</tr>';

$content .= '<tr id="tr_date_from" style="display:none;">';
$content .= '<td>'. i18n("Date from").'</td>';
$content .= '<td><nobr>'.$oSelectArtDateFromDay->render().$oSelectArtDateFromMonth->render().$oSelectArtDateFromYear->render().'</nobr></td>';
$content .= '</tr>';

$content .= '<tr id="tr_date_to" style="display:none;">';
$content .= '<td>'. i18n("Date to").'</td>';
$content .= '<td><nobr>'.$oSelectArtDateToDay->render().$oSelectArtDateToMonth->render().$oSelectArtDateToYear->render().'</nobr></td>';
$content .= '</tr>';

$content .= '<tr>';
$content .= '<td style="padding-left: 15px;">'. i18n("Author").'</td>';
$content .= '<td><nobr>'.$oSelectArtAuthor->render().'</nobr></td>';
$content .= '</tr>';

$content .= '<tr>';
$content .= '<td>&nbsp;</td>';
$content .= '<td>'.$oSubmit->render().'</td>';
$content .= '</tr>';
$content .= '</table>';
$content .= '</form>';

/**
 * Saved searches 
 */
$content .= '<div  class="artikel_search">';

$content .= '<div style="font-weight:bold; margin-bottom: 10px;">'.i18n("Saved Searches").':</div>';

$proppy = new PropertyCollection();
$savedSearchList = $proppy->getAllValues('type', 'savedsearch', $auth);

$init_itemid = '';
$init_itemtype = '';
$content .= '<ul class="artikel_search">';

// Recently edited articles search - predefined, not deleteable
$searchRecentlyEdited = "javascript:conMultiLink('right_bottom', 'backend_search.php?area=".$area."&frame=4&contenido=".$sess->id."&recentedit=true'); resetSearchForm();";
$content .= '<li style="margin-bottom: 3px;"><img style="vertical-align:middle; padding-left: 3px;" src="images/delete_inact.gif" /><a style="padding-left: 3px;" href="'.$searchRecentlyEdited.'">'.i18n("Recently edited articles").'</a></li>';

// My articles search - predefined, not deleteable
$searchMyArticles = "javascript:conMultiLink('right_bottom', 'backend_search.php?area=".$area."&frame=4&contenido=".$sess->id."&myarticles=true'); resetSearchForm();";
$content .= '<li style="margin-bottom: 3px;"><img style="vertical-align:middle;padding-left: 3px;" src="images/delete_inact.gif" /><a style="padding-left: 3px;" href="'.$searchMyArticles.'">'.i18n("My articles").'</a></li>';

// Workflow
$link = $sess->url("main.php?area=con_workflow&frame=4");
$sWorflowLink = 'conMultiLink(\'right_bottom\', \''.$link.'\'); resetSearchForm();';
$content .= '<li style="margin-bottom: 3px;"><img style="vertical-align:middle;padding-left: 3px;" src="images/delete_inact.gif" /><a style="padding-left: 3px;" href="javascript:'.$sWorflowLink.'">'.i18n("Workflow").'</a></li>';

foreach ($savedSearchList as $value)
{
	if( ($init_itemid != $value['itemid']) && ($init_itemtype != $value['itemtype']) )
	{
		$init_itemid = $value['itemid'];
		$init_itemtype = $value['itemtype'];
		
		// Create delete icon
		$deleteSearch = "javascript:conMultiLink('left_top', 'main.php?area=".$area."&frame=1&delsavedsearch=true&contenido=".$sess->id."&itemid=".$value['itemid']."&itemtype=".$value['itemtype']."')";
		$content .= '<li style="margin-bottom: 3px;">';
		$content .= '<a  href="'.$deleteSearch.'"><img style="padding-left: 3px; vertical-align:middle;" src="images/delete.gif" /></a>';
		
		// create new link
		$savedSearchLink = "javascript:conMultiLink('right_bottom', 'backend_search.php?area=".$area."&frame=4&contenido=".$sess->id."&itemid=".$value['itemid']."&itemtype=".$value['itemtype']."')";
		$content .= '<a style="padding-left: 3px;" href="'.$savedSearchLink.'">';
	}
	// Name the link
	if($value['name'] == 'save_name')
	{
		$content .= $value['value'] . '</a>';
		$content .= '</li>';
	}
}
$content .= '</ul>';
$content .= '</div>';
$content .= '</div>';

$oListOptionRow->setContentData($content);

$sSelfLink = 'main.php?area=' . $area . '&frame=2&' . $sess->name . "=" . $sess->id;
$tpl->set('s', 'SELFLINK', $sSelfLink);

$tpl->set('s', 'SEARCH', $oListOptionRow->render());

##########################################
# Category
##########################################
$sql = "SELECT
            idtpl,
            name
        FROM
            ".$cfg['tab']['tpl']."
        WHERE
            idclient = '".Contenido_Security::toInteger($client)."'
        ORDER BY
            name";

$db->query($sql);

$tpl->set('s', 'ID',        'oTplSel');
$tpl->set('s', 'CLASS',     'text_medium');
$tpl->set('s', 'OPTIONS',   '');
$tpl->set('s', 'SESSID',    $sess->id);
$tpl->set('s', 'BELANG', $belang);

$tpl->set('d', 'VALUE',     '0');
$tpl->set('d', 'CAPTION',   i18n("Choose template"));
$tpl->set('d', 'SELECTED',  '');
$tpl->next();

$tpl->set('d', 'VALUE',     '0');
$tpl->set('d', 'CAPTION',   '--- '. i18n("none"). ' ---');
$tpl->set('d', 'SELECTED',  '');
$tpl->next();

$categoryLink="editcat";
$editCategory = new cFoldingRow("3498dbbb-ed4a-4618-8e49-3a3635396e22", i18n("Edit Category"), $categoryLink);

while ($db->next_record()) {
    $tplname = $db->f('name');

    if (strlen($tplname) > 18)
    {
        $tplname = substr($tplname, 0, 15) . "...";
    }
    $tpl->set('d', 'VALUE', $db->f('idtpl'));
    $tpl->set('d', 'CAPTION', $tplname);
    $tpl->set('d', 'SELECTED', '');
    $tpl->next();
}
// Template Dropdown
$editCat  = '<div style="height:110px;padding-top:5px; padding-left: 17px; margin-bottom:-1px; border-right:1px solid #B3B3B3">';
$editCat	.= i18n("Template") . ":<br />";
$editCat	.= '<div style="">';
$editCat	.= $tpl->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
$editCat	.='<a id="changetpl" href="#"><img style="vertical-align: middle;" src="images/submit.gif" border="0"></a><br />';
$editCat	.= '</div>';
// Category
$editCat	.= '<div style="margin: 5px 0 5px 0;">';
$tpl->set('s', 'CAT_HREF', $sess->url("main.php?area=con_tplcfg&action=tplcfg_edit&frame=4&mode=art").'&idcat=');
$tpl->set('s', 'IDCAT', $idcat);
$editCat	.= '<div id="oTemplatecfg_label"><a href="javascript:configureCategory();"><img style="vertical-align: middle;" id="oTemplatecfg" vspace="3" hspace="2" src="'.$cfg["path"]["images"].'but_cat_conf2.gif" border="0" title="'.i18n("Configure category").'" alt="'.i18n("Configure category").'"><a>';
$editCat	.= '<a href="javascript:configureCategory();">'.i18n("Configure category").'</a></div>';
// Online / Offline
$editCat	.= '<div id="oOnline_label"><a href="#"><img style="vertical-align: middle;" id="oOnline" src="images/offline.gif" vspace="2" hspace="2" border="0" title="'.i18n("Online / Offline").'" alt="'.i18n("Online / Offline").'"></a>';
$editCat	.= '<a href="#">'.i18n("Online / Offline").'</a></div>';
// Lock / Unlock
$editCat	.= '<div id="oLock_label"><a href="#"><img style="vertical-align: middle;" id="oLock" src="images/folder_lock.gif" vspace="2" hspace="2"  border="0" title="'.i18n("Lock / Unlock").'" alt="'.i18n("Lock / Unlock").'"></a>';
$editCat	.= '<a href="#">'.i18n("Lock / Unlock").'</a></div>';
$editCat	.= '<br />';
$editCat .= '</div>';
$editCat .= '</div>';

$editCategory->setContentData($editCat);

$tpl->set('s', 'EDIT', $editCategory->render());
$tpl->set('s', 'CATEGORYLINK', $categoryLink);


#####################################
# Collapse / Expand / Config Category
#####################################
$selflink = "main.php";
$expandlink = $sess->url($selflink . "?area=$area&frame=2&expand=all");
$collapselink = $sess->url($selflink . "?area=$area&frame=2&collapse=all");
$collapseimg 	= '<a target="left_bottom" class="black" id="collapser" href="'.$collapselink.'" alt="'.i18n("close all").'" title="'.i18n("Close all categories").'"><img src="images/close_all.gif" border="0">&nbsp;'.i18n("close all").'</a>';
$expandimg 		= '<a target="left_bottom"class="black" id="expander" href="'.$expandlink.'" alt="'.i18n("open all").'" title="'.i18n("Open all categories").'"><img src="images/open_all.gif" border="0">&nbsp;'.i18n("open all").'</a>';
$tpl->set('s', 'MINUS', $collapseimg);
$tpl->set('s', 'PLUS', $expandimg);

/**************/
/*  SYNCSTUFF */
/**************/
$languages = getLanguageNamesByClient($client);
if (count($languages) > 1 && $perm->have_perm_area_action($area, "con_synccat")) {
	$sListId = 'sync';
	$oListOptionRow = new cFoldingRow("4808dbba-ed4a-4618-8e49-3a3635396e22", i18n("Synchronize from"), $sListId);
	
	if (($syncoptions > 0) && ($syncoptions != $lang)) {
		$oListOptionRow->setExpanded (true);
	}
	
    #'dir="' . langGetTextDirection($lang) . '"');

    $selectbox = new cHTMLSelectElement("syncoptions");
    
    $option = new cHTMLOptionElement("-- ".i18n("None")." --", -1);
    $selectbox->addOptionElement(-1, $option);
    
    foreach ($languages as $languageid => $languagename)
    {
    	if ($lang != $languageid && $perm->have_perm_client_lang($client, $languageid))
    	{
    		$option = new cHTMLOptionElement($languagename . " (".$languageid.")",$languageid);
    		$selectbox->addOptionElement($languageid, $option);
    	}
    }
    
    $selectbox->setDefault($syncoptions);
    $form = new UI_Form("syncfrom");
    $form->setVar("area",$area);
    $form->setVar("frame", $frame);
    $form->add("sel", $selectbox->render());
    $link = $sess->url("main.php?area=".$area."&frame=2").'&syncoptions=';
    $sJsLink = 'conMultiLink(\'left_bottom\', \''.$link.'\'+document.getElementsByName(\'syncoptions\')[0].value+\'&refresh_syncoptions=true\');';
    $tpl->set('s', 'UPDATE_SYNC_REFRESH_FRAMES', $sJsLink);	
   
    $form->add("submit", '<img style="vertical-align:middle; margin-left:5px;" onMouseover="this.style.cursor=\'pointer\'" onclick="updateCurLanguageSync();" src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'submit.gif">');

	$sSyncButton = '<div id="sync_cat_single" style="display:none;"><a href="javascript:generateSyncAction(0);"><img style="vertical-align: middle;" src="images/but_sync_cat.gif" vspace="2" hspace="2" border="0" title="'.i18n("Copy to current language").'" alt="'.i18n("Copy to current language").'"></a>';
	$sSyncButton .= '<a href="javascript:generateSyncAction(0);">'.i18n("Copy to current language").'</a></div>';
    $sSyncButtonMultiple = '<div id="sync_cat_multiple" style="display:none;"><a href="javascript:generateSyncAction(1);"><img style="vertical-align: middle;" src="images/but_sync_cat.gif" vspace="2" hspace="2" border="0" title="'.i18n("Also copy subcategories").'" alt="'.i18n("Also copy subcategories").'"></a>';
	$sSyncButtonMultiple .= '<a href="javascript:generateSyncAction(1);">'.i18n("Also copy subcategories").'</a></div>';
	
    $content = '<table style="padding:3px; margin-left:12px; border-right: 1px solid #B3B3B3;" width="100%" border="0" dir="'.langGetTextDirection($lang).'">
	                <tr>
				        <td>'.$form->render().'</td>
				    </tr>
					<tr>
				        <td>'.$sSyncButton.$sSyncButtonMultiple.'</td>
				    </tr>
				</table>';	

    $oListOptionRow->setContentData($content);

    $tpl->set('s', 'SYNCRONIZATION',$oListOptionRow->render());
    $tpl->set('s', 'SYNCLINK', $sListId);
	$sSyncLink = $sess->url($selflink . "?area=$area&frame=2&action=con_synccat");
	$tpl->set('s', 'SYNC_HREF', $sSyncLink);	

} else {
    $tpl->set('s', 'SYNCRONIZATION','');
    $tpl->set('s', 'SYNCLINK',$sListId);
	$tpl->set('s', 'SYNC_HREF', '');	
}

/*
 * necessary for expanding/collapsing of navigation tree per javascript/AJAX (I. van Peeren)
 */ 
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'SESSION', $contenido);
$tpl->set('s', 'AJAXURL', $cfg['path']['contenido_fullhtml'].'ajaxmain.php');

##########################################
# Help
##########################################
$tpl->set('s', 'HELPSCRIPT', setHelpContext("con"));
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_left_top']);

?>