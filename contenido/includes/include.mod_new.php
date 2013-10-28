<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Add new module
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.7.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-03-21
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.mod_new.php 358 2008-06-27 12:53:37Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) 
{
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
   
$tpl->reset();

#################
# New Module Link
#################
$str = '';
if ((int) $client > 0) {
    $str = '<div style="height: 2.5em;line-height: 2.5em;border: 1px solid #B3B3B3;padding-left:15px;line-height:100px;"><a style="margin-top:5px;" class="addfunction" target="right_bottom" href="'.$sess->url("main.php?area=mod_edit&frame=4&action=mod_new").'">'.i18n("New module").'</a></div>';
} else {
    $str = '<div style="height: 2.5em;line-height: 2.5em;border: 1px solid #B3B3B3;padding-left:15px;">'.i18n("No client selected").'</div>';
}

#only show other options, if there is a active client
if ((int) $client > 0) {
    ###############
    # List Options
    ###############
    $aSortByOptions		    = array ("name" => i18n("Name"), "type" => i18n("Type"));
    $aSortOrderOptions  	= array ("asc" => i18n("Ascending"), "desc" => i18n("Descending"));
    $listoplink="listoptions";
    $oListOptionRow = new cFoldingRow("e9ddf415-4b2d-4a75-8060-c3cd88b6ff98", i18n("List options"), $listoplink);
    $tpl->set('s', 'LISTOPLINK', $listoplink);
    $oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
    $oSelectItemsPerPage->autoFill(array(0 => "-- " . i18n("All") . " --", 25 => 25, 50 => 50, 75 => 75, 100 => 100));
    $oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);
    $oSelectSortBy = new cHTMLSelectElement("sortby");
    $oSelectSortBy->autoFill($aSortByOptions);
    $oSelectSortBy->setDefault($_REQUEST["sortby"]);
    $oSelectSortOrder = new cHTMLSelectElement("sortorder");
    $oSelectSortOrder->autoFill($aSortOrderOptions);
    $oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

    $oSelectSearchIn = new cHTMLSelectElement("searchin");
    $oSelectSearchIn->autoFill(array('' => "-- " . i18n("All") . " --", 
                                     'name' => i18n("Modulname"), 
                                     'description' => i18n("Description"), 
                                     'type' => i18n("Type"), 
                                     'input' => i18n("Input"), 
                                     'output' => i18n("Output")));
                                     
    $oSelectSearchIn->setDefault($_REQUEST["searchin"]);

    // build list with filter types
    $sql = "SELECT
               type
            FROM
               ".$cfg["tab"]["mod"]."
            WHERE
               idclient = '".Contenido_Security::toInteger($client)."'
            GROUP BY type";

    $db->query($sql);
    $aFilterType = array();
    $aFilterType["--all--"] = "-- " . i18n("All") . " --";
    $aFilterType["--wotype--"] = "-- " . i18n("Without type") . " --";

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
    $content .= '<form action="javascript:execFilter(\''.$sess->id.'\');" id="filter" name="filter" method="get">';
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
    $content .= '<td style="padding-left:15px;">'.i18n("Search in").'</td>';
    $content .= '<td>'.$oSelectSearchIn->render().'</td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td style="padding-left:15px;">&nbsp;</td>';
    $content .= '<td><input type="submit" value="'.i18n("Apply").'"></td>';
    $content .= '</tr>';
    $content .= '</table>';
    $content .= '</form>';
    $content .= '</div>';
    $oListOptionRow->setContentData($content);

    #######
    # Pager
    #######
    $cApiModuleCollection	= new cApiModuleCollection;
    $cApiModuleCollection->setWhere("idclient", $client);

    $cApiModuleCollection->query();
    $iItemCount = $cApiModuleCollection->count();

    $oPagerLink = new cHTMLLink;
    $pagerl="pagerlink";
    $oPagerLink->setTargetFrame('left_bottom');
    $oPagerLink->setLink("main.php");
    $oPagerLink->setCustom("elemperpage", $elemperpage);
    $oPagerLink->setCustom("filter", stripslashes($_REQUEST["filter"]));
    $oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
    $oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
    $oPagerLink->setCustom("frame", 2);
    $oPagerLink->setCustom("area", $area);
    $oPagerLink->enableAutomaticParameterAppend();
    $oPagerLink->setCustom("contenido", $sess->id);
    $oPager = new cObjectPager("02420d6b-a77e-4a97-9395-7f6be480f497", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerl);

    $tpl->set('s', 'PAGINGLINK', $pagerl);
    

    $tpl->set('s', 'ACTION', $str.'<table style="margin-top:1px" border="0" cellspacing="0" cellpadding="0" width="100%">'.$oListOptionRow->render().$oPager ->render().'</table>');
} else {
    $tpl->set('s', 'PAGINGLINK', '');
    $tpl->set('s', 'ACTION', $str);
    $tpl->set('s', 'LISTOPLINK', '');
}

############################
# generate template
############################
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['mod_left_top']);
?>