<?php

/*****************************************
* File      :   $RCSfile: include.mod_new.php,v $
* Project   :   Contenido
* Descr     :   Module left top
*               
* Created   :   21.03.2003
* Modified  :   $Date: 2003/12/28 14:57:11 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.mod_new.php,v 1.7 2003/12/28 14:57:11 timo.hummel Exp $
******************************************/

cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "contenido/class.module.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.ui.php");

$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) 
{
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}

                                   
$tpl->reset();

###############
# List Options
###############
$aSortByOptions		    = array ("name" => i18n("Name"), "type" => i18n("Type"));
$aSortOrderOptions  	= array ("asc" => i18n("Ascending"), "desc" => i18n("Descending"));
$listoplink="listoptions";
$oListOptionRow = new cFoldingRow("e9ddf415-4b2d-4a75-8060-c3cd88b6ff98", i18n("List options"), $listoplink);
$tpl->set('s', 'LISTOPLINK', $listoplink);
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);
$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aSortByOptions);
$oSelectSortBy->setDefault($_REQUEST["sortby"]);
$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

// build list with filter types
$sql = "SELECT
           type
        FROM
           ".$cfg["tab"]["mod"]."
        WHERE
           idclient = '".$client."'
        GROUP BY type";

$db->query($sql);
$aFilterType = array();
$aFilterType["--all--"] = i18n("-- All --");
$aFilterType["--wotype--"] = i18n("-- Without type --");

while ($db->next_record())
{
	if (trim($db->f("type")) != "")
	{
		$aFilterType[$db->f("type")] = $db->f("type");
	}	
}

$oSelectTypeFilter = new cHTMLSelectElement("filtertype");
$oSelectTypeFilter->autoFill($aFilterType);
$oSelectTypeFilter->setDefault($_REQUEST["filtertype"]);
$oTextboxFilter = new cHTMLTextbox("filter", stripslashes($_REQUEST["filter"]), 15);
$content .= '<div style="border: 1px solid #B3B3B3;border-left:none;border-top:none;margin-bottom:1px;">';
// Ye stuff will be done in javascript on apply button
$content .= '<form action="'.$sess->url("main.php").'" id="filter" name="filter" method="get">';
$content .= '<table>';
$content .= '<input type="hidden" name="area" value="mod">';
$content .= '<input type="hidden" name="frame" value="1">';
$content .= '<input type="hidden" name="contenido" value="'.$sess->id.'">';
$content .= '<input type="hidden" name="'.$formcall.'" value="'.$formcall.'">';
$content .= '<input type="hidden" name="page" value="'.$_REQUEST["page"].'">';
$content .= '<tr">';
$content .= '<td style="padding-left:15px;" nowrap>'.i18n("Items / page").'</td>';
$content .= '<td>'.$oSelectItemsPerPage->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td style="padding-left:15px;">'.i18n("Sort by").'</td>';
$content .= '<td>'.$oSelectSortBy->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td style="padding-left:15px;">'.i18n("Sort order").'</td>';
$content .= '<td>'.$oSelectSortOrder->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td style="padding-left:15px;">'.i18n("Type filter").'</td>';
$content .= '<td>'.$oSelectTypeFilter->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td style="padding-left:15px;">'.i18n("Search for").'</td>';
$content .= '<td>'.$oTextboxFilter->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td style="padding-left:15px;">&nbsp;</td>';
$content .= '<td><input type="submit" value="'.i18n("Apply").'" onclick="javascript:execFilter(\''.$sess->id.'\');"</td>';
$content .= '</tr>';
$content .= '</table>';
$content .= '</form>';
$content .= '</div>';
$oListOptionRow->setContentData($content);

#######
# Pager
#######
$cApiModuleCollection	= new cApiModuleCollection;
$cApiModuleCollection->query();
$iItemCount = $cApiModuleCollection->count();

$oPagerLink = new cHTMLLink;
$pagerl="pagerlink";
$tpl->set('s', 'PAGINGLINK', $pagerl);
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", stripslashes($_REQUEST["filter"]));
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
$oPager = new cObjectPager("02420d6b-a77e-4a97-9395-7f6be480f497", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerl);

#################
# New Module Link
#################
$str .= '<div style="height: 2.5em;line-height: 2.5em;border: 1px solid #B3B3B3;padding-left:15px;line-height:100px;"><a style="margin-top:5px;" class="addfunction" target="right_bottom" href="'.$sess->url("main.php?area=mod_edit&frame=4&action=mod_new").'">'.i18n("New module").'</a></div>';

############################
# generate template
############################
$tpl->set('s', 'ACTION', $str.'<table style="margin-top:1px" border="0" cellspacing="0" cellpadding="0" width="100%">'.$oListOptionRow->render().$oPager ->render().'</table>');
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['mod_left_top']);

?>
