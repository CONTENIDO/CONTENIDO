<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Displays structure
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.4
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2003-03-28
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2009-10-14, Dominik Ziegler - added some functionality for "cancel moving tree"
 *   modified 2009-10-15, Dominik Ziegler - removed unnecessary database query for selecting the level (level is already available)
 *   modified 2010-01-30, Ingo van Peeren, some optimization of the amount of db queries for template names and descriptions, see [CON-301]
 *                                         removed use of deprecated methods of class.template.php, see [CON-302]
 *   modified 2010-02-06, Ingo van Peeren, fixed small bug added by last modification
 *   modified 2012-01-17, Mischa Holz, fixed a bug displaying HTML special chars in category names see [CON-470]
 *
 *   $Id: include.str_overview.php 1783 2012-01-17 13:58:08Z mischa.holz $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$debug = false;

$tmp_area = "str";

if ($action == "str_duplicate" &&
	($perm->have_perm_area_action("str", "str_duplicate") ||
	 $perm->have_perm_area_action_item("str", "str_duplicate", $idcat)))
{
	strCopyTree($idcat, $parentid);
}


$oDirectionDb = new DB_contenido();

/**
 * Build a Category select Box containg all categories which user is allowed to create new categories
 *
 * @return String HTML
 */
function buildCategorySelectRights() {
	global $cfg, $client, $lang, $idcat, $perm, $tmp_area;

	$db = new DB_Contenido;

    $oHtmlSelect = new 	cHTMLSelectElement ('idcat', "", "new_idcat");

    $oHtmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
    $oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);


	$sql = "SELECT a.idcat AS idcat, b.name AS name, c.level FROM
	    	   ".$cfg["tab"]["cat"]." AS a, ".$cfg["tab"]["cat_lang"]." AS b,
	    	   ".$cfg["tab"]["cat_tree"]." AS c WHERE a.idclient = '".Contenido_Security::toInteger($client)."'
	    	   AND b.idlang = '".Contenido_Security::toInteger($lang)."' AND b.idcat = a.idcat AND c.idcat = a.idcat
	           ORDER BY c.idtree";

	$db->query($sql);

	$categories = array ();

	while ($db->next_record())
	{
		$categories[$db->f("idcat")]["name"] = $db->f("name");
        $categories[$db->f("idcat")]["idcat"] = $db->f("idcat");

        if ($perm->have_perm_area_action($tmp_area, "str_newcat") || $perm->have_perm_area_action_item($tmp_area, "str_newcat", $db->f("idcat"))) {
            $categories[$db->f("idcat")]["perm"] = 1;
        } else {
            $categories[$db->f("idcat")]["perm"] = 0;
        }

		$categories[$db->f("idcat")]["level"] = $db->f("level");
	}

    $aCategoriesReversed = array_reverse($categories);

    $iLevel = 0;
    foreach ($aCategoriesReversed as $iKeyIdCat => $aValues) {
        if ($aValues['level'] > $iLevel && $aValues['perm']) {
            $iLevel = $aValues['level'];
        } else if ($aValues['level'] < $iLevel) {
            $iLevel = $aValues['level'];
        } else {
            if (!$aValues['perm']) {
                unset($categories[$aValues["idcat"]]);
            }
        }
    }

    $j = 1;
	foreach ($categories as $tmpidcat => $props)
	{
		$spaces = "&nbsp;&nbsp;";

		for ($i = 0; $i < $props["level"]; $i ++)
		{
			$spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}

        $sCategoryname = $props["name"];
        $sCategoryname = capiStrTrimHard($sCategoryname, 30);
        $oHtmlSelectOption = new cHTMLOptionElement($spaces.">".$sCategoryname, $tmpidcat, false, !$props["perm"]);
        $oHtmlSelect->addOptionElement($j, $oHtmlSelectOption);
        $j++;
	}

	return $oHtmlSelect->toHtml();
}

function getExpandCollapseButton ($item, $catName)
{
	global $sess, $PHP_SELF, $frame, $area;
	$selflink = "main.php";

	$img = new cHTMLImage;
    $img->updateAttributes(array ("style" => "padding:4px;"));


	if (count($item->subitems) > 0)
	{
		if ($item->collapsed == true)
		{
			$expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=". $item->id);

			$img->setSrc($item->collapsed_icon);
			$img->setAlt(i18n("Open category"));
            return ('<a href="'.$expandlink.'">'.$img->render().'</a>&nbsp;'.'<a href="'.$expandlink.'">'.$catName.'</a>');
		} else {
			$collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=". $item->id);
			$img->setSrc($item->expanded_icon);
			$img->setAlt(i18n("Close category"));

            return('<a href="'.$collapselink.'">'.$img->render().'</a>&nbsp;'.'<a href="'.$collapselink.'">'.$catName.'</a>');
		}
	} else {
		return '<img src="images/spacer.gif" style="padding:4px;" width="7" height="7">&nbsp;'.$catName;
	}
}

function getTemplateSelect() {
    global $client, $cfg, $db;

    $oHtmlSelect = new 	cHTMLSelectElement ('cat_template_select', "", "cat_template_select");

    $oHtmlSelectOption = new cHTMLOptionElement('--- '.i18n("none"). ' ---', 0, false);
    $oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

    $sql = "SELECT
            idtpl,
            name, defaulttemplate
        FROM
            ".$cfg['tab']['tpl']."
        WHERE
            idclient = '".$client."'
        ORDER BY
            name";

    $i = 1;
    if ($db->query($sql)) {
        while ($db->next_record()) {
            $bDefaultTemplate = $db->f('defaulttemplate');
            $oHtmlSelectOption = new cHTMLOptionElement($db->f('name'), $db->f('idtpl'), $bDefaultTemplate);
            $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
            $i++;
        }
    }

    return $oHtmlSelect->toHtml();
}

getTemplateSelect();

$sess->register("remakeStrTable");
$sess->register("StrTableClient");
$sess->register("StrTableLang");

$cancel = $sess->url("main.php?area=$area&frame=$frame");

if (isset($force) && $force == 1) {
    $remakeStrTable = true;
}

if ($StrTableClient != $client)
{
	unset($expandedList);
    $remakeStrTable = true;
}

if ($StrTableLang != $lang)
{
	unset($expandedList);
	$remakeStrTable = true;
}

$StrTableClient = $client;
$StrTableLang = $lang;

if (!isset($idcat) )  $idcat  = 0;
if (!isset($action) ) $action = 0;

function buildTree (&$rootItem, &$items)
{
	global $nextItem, $perm, $tmp_area;

	while ($item_list = each($items))
	{
		list($key, $item) = $item_list;

		unset($newItem);

		$bCheck = false;
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_newtree"); }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_newcat"); }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_makevisible");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_makepublic");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_deletecat");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_moveupcat");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_movedowncat");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_movesubtree");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_renamecat");}
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action("str_tplcfg", "str_tplcfg");}
        if (!$bCheck) { $bCheck = $perm->have_perm_item($tmp_area, $item['idcat']);}

        if ($bCheck) {
			$newItem = new TreeItem($item['name'], $item['idcat'], true);
        } else {
        	$newItem = new TreeItem($item['name'], $item['idcat'], false);
        }

		$newItem->collapsed_icon = 'images/open_all.gif';
		$newItem->expanded_icon = 'images/close_all.gif';
		$newItem->custom['idtree'] = $item['idtree'];
		$newItem->custom['level'] = $item['level'];
		$newItem->custom['idcat'] = $item['idcat'];
		$newItem->custom['idtree'] = $item['idtree'];
		$newItem->custom['parentid'] = $item['parentid'];
        $newItem->custom['alias'] = $item['alias'];
		$newItem->custom['preid'] = $item['preid'];
		$newItem->custom['postid'] = $item['postid'];
		$newItem->custom['visible'] = $item['visible'];
		$newItem->custom['idtplcfg'] = $item['idtplcfg'];
		$newItem->custom['public'] = $item['public'];

		if ($perm->have_perm_item("str", $item['idcat']))
		{
			$newItem->custom['forcedisplay'] = 1;
		}

		if (array_key_exists($key+1, $items))
		{
			$nextItem = $items[$key+1];
		} else {
			$nextItem = 0;
		}

		if (array_key_exists($key-1, $items))
		{
			$lastItem = $items[$key-1];
		} else {
			$lastItem = 0;
		}

		$rootItem->addItem($newItem);

		if ($nextItem['level'] > $item['level'])
		{
			$oldRoot = $rootItem;
			buildTree($newItem, $items);
			$rootItem = $oldRoot;
		}

		if ($nextItem['level'] < $item['level'])
		{
			return;
		}
	}

}

if ( $perm->have_perm_area_action($area) ) {

    $sql = "SELECT
                idtree, A.idcat, level, name, parentid, preid, postid, visible, public, idtplcfg, C.urlname as alias
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat     = B.idcat AND
                B.idcat     = C.idcat AND
                C.idlang    = '".Contenido_Security::toInteger($lang)."' AND
                B.idclient  = '".Contenido_Security::toInteger($client)."'
            ORDER BY
                idtree";

    # Debug info
    if ( $debug ) {

        echo "<pre>";
        echo $sql;
        echo "</pre>";

    }

    $db->query($sql);

	$bIgnore = false;
	$iIgnoreLevel = 0;

	$items = array();
	while ($db->next_record())
	{
        $bSkip = false;

		if ($bIgnore == true && $iIgnoreLevel >= $db->f("level")) {
			$bIgnore = false;
		}

		if ($db->f("idcat") == $movesubtreeidcat) {
			$bIgnore = true;
			$iIgnoreLevel = $db->f("level");
			$sMoveSubtreeCatName = $db->f("name");
		}

        if ($iCurLevel == $db->f("level")) {
            if ($iCurParent != $db->f("parentid")) {
                $bSkip = true;
            }
        } else {
            $iCurLevel = $db->f("level");
            $iCurParent = $db->f("parentid");
        }

		if ($bIgnore == false && $bSkip == false) {
			$entry = array();
			$entry['idtree'] = $db->f("idtree");
			$entry['idcat'] = $db->f("idcat");
			$entry['level'] = $db->f("level");
			$entry['name'] = htmldecode($db->f("name"));
			$entry['alias'] = htmldecode($db->f("alias"));
			$entry['parentid'] = $db->f("parentid");
			$entry['preid'] = $db->f("preid");
			$entry['postid'] = $db->f("postid");
			$entry['visible'] = $db->f("visible");
			$entry['public'] = $db->f("public");
			$entry['idtplcfg'] = $db->f("idtplcfg");

			array_push($items, $entry);
		}
	}

	$rootStrItem = new TreeItem("root",-1);
	$rootStrItem->collapsed_icon = 'images/open_all.gif';
	$rootStrItem->expanded_icon = 'images/close_all.gif';

	buildTree($rootStrItem, $items);

	$expandedList = unserialize($currentuser->getUserProperty("system","cat_expandstate"));

	if (is_array($expandedList))
	{
		$rootStrItem->markExpanded($expandedList);
	}

	if (isset($collapse) && is_numeric($collapse))
	{
		$rootStrItem->markCollapsed($collapse);
	}

	if (isset($expand) && is_numeric($expand))
	{
		$rootStrItem->markExpanded($expand);
	}

    if (isset($expand) && $expand == "all")
    {
    	$rootStrItem->expandAll(-1);
    }

    if (isset($collapse) && $collapse == "all")
    {
    	$rootStrItem->collapseAll(-1);
    }

	if ($action === "str_newcat")
	{
		$rootStrItem->markExpanded($idcat);
	}

	$expandedList = Array();
	$objects = array();

	$rootStrItem->traverse($objects);

	$rootStrItem->getExpandedList($expandedList);
	$currentuser->setUserProperty("system","cat_expandstate", serialize($expandedList));

    # Reset Template
    $tpl->reset();
    $tpl->set('s', 'SID', $sess->id);
    $tpl->set('s', 'AREA', $area);
    $tpl->set('s', 'FRAME', $frame);

    $_cecIterator = $_cecRegistry->getIterator("Contenido.CategoryList.Columns");

	$listColumns = array();
	if ($_cecIterator->count() > 0)
	{

		while ($chainEntry = $_cecIterator->next())
		{
		    $tmplistColumns = $chainEntry->execute(array());

		    if (is_array($tmplistColumns))
		    {
		    	$listColumns = array_merge($listColumns, $tmplistColumns);
		    }
		}


		foreach ($listColumns as $content)
		{
			// Header for additional columns
			$additionalheaders[] = '<td class="header" nowrap="nowrap">'.$content.'</td>';
		}

		$additionalheader = implode("", $additionalheaders);
	} else {
		$additionalheader = "";
	}

	$tpl->set('s', 'ADDITIONALHEADERS', $additionalheader);

	// We don't want to show our root
	unset($objects[0]);

    $selflink = "main.php";
    $expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=all&syncoptions=$syncoptions");
    $collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=all&syncoptions=$syncoptions");
    $collapseimg =
          '<a class="black" href="'.
            $collapselink.
            '" alt="'.i18n("Close all categories").
            '" title="'.i18n("Close all categories").'">
            <img src="images/close_all.gif">&nbsp;'.i18n("Close all categories").
          '</a>';
    $expandimg =
          '<a class="black" href="'.
            $expandlink.
            '" alt="'.i18n("Open all categories").
            '" title="'.i18n("Open all categories").'">
            <img src="images/open_all.gif">&nbsp;'.i18n("Open all categories").
          '</a>';


    $tpl->set('s', 'COLLAPSE_ALL', $collapseimg);
    $tpl->set('s', 'EXPAND_ALL', $expandimg);
    $sMouseover = 'onmouseover="str.over(this)" onmouseout="str.out(this)" onclick="str.click(this)"';

    //Fill inline edit table row
    $tpl->set('s', 'SUM_COLUMNS_EDIT', 14+count($listColumns));
    $tpl->set('s', 'ACTION_EDIT_URL', $sess->url("main.php?frame=$frame"));
    $tpl->set('s', 'SRC_CANCEL', $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'but_cancel.gif');
    $tpl->set('s', 'SRC_OK', $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'but_ok.gif');
    $tpl->set('s', 'HREF_CANCEL', "javascript:handleInlineEdit(0)");
    $tpl->set('s', 'LABEL_ALIAS_NAME', i18n("Alias"));
    $tpl->set('s', 'TEMPLATE_URL', $sess->url("main.php?area=str_tplcfg&frame=$frame"));
    $message = addslashes(i18n("Do you really want to duplicate the following category:<br><br><b>%s</b><br><br>Notice: The duplicate process can take up to several minutes, depending on how many subitems and articles you've got."));
    $tpl->set('s', 'DUPLICATE_MESSAGE', $message);
    $tpl->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following category:<br><br><b>%s</b>"));

    $bAreaAddNewCategory = false;

    $aInlineEditData = array();

    $sql = "SELECT
		    	idtplcfg, idtpl
		    FROM
		        ".$cfg["tab"]["tpl_conf"];
	$db->query($sql);
	$aTplconfigs = array();
	while ($db->next_record()) {
        $aTplconfigs[$db->f('idtplcfg')] = $db->f('idtpl');
    }

    $sql = "SELECT
		    	name, description, idtpl
		    FROM
		    	".$cfg["tab"]["tpl"];

    $db->query($sql);
	$aTemplates = array();
	while ($db->next_record()) {
        $aTemplates[$db->f('idtpl')] = array(
          'name' => $db->f('name'),
          'description' => $db->f('description')
        );
    }

    foreach ($objects as $key=>$value) {
        // check if there area any permission for this $idcat   in the mainarea 6 (=str) and there subareas
        $bCheck = false;
		if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_newtree"); }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_newcat") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_makevisible"); }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_makepublic") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_deletecat") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_moveupcat") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_movedowncat") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_movesubtree") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action($tmp_area, "str_renamecat") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_area_action("str_tplcfg", "str_tplcfg") ; }
        if (!$bCheck) { $bCheck = $perm->have_perm_item($tmp_area, $value->id) ; }
        if (!$bCheck) { $bCheck = $value->isCustomAttributeSet("forcedisplay") ; }

		if ($bCheck) {

            //Insert empty row
            if ( $value->custom['level'] == 0 && $value->custom['preid'] != 0 ) {

                $tpl->set('d', 'BGCOLOR', '#FFFFFF');
                $tpl->set('d', 'BGCOLOR_EDIT', '#F1F1F1');
                $tpl->set('d', 'ALIAS', '&nbsp;');
                $tpl->set('d', 'INDENT', '3px');
                $tpl->set('d', 'RENAMEBUTTON', '&nbsp;');
                $tpl->set('d', 'NEWCATEGORYBUTTON', '&nbsp;');
                $tpl->set('d', 'VISIBLEBUTTON', '&nbsp;');
                $tpl->set('d', 'PUBLICBUTTON', '&nbsp;');
                $tpl->set('d', 'DELETEBUTTON', '&nbsp;');
                $tpl->set('d', 'UPBUTTON', '&nbsp;');
                $tpl->set('d', 'COLLAPSE_CATEGORY_NAME', '&nbsp;');
                $tpl->set('d', 'TPLNAME', '&nbsp;');
                $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
                $tpl->set('d', 'DOWNBUTTON', '&nbsp;');
                $tpl->set('d', 'SHOW_MOUSEOVER', '');
                $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', '');
                $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', '');
                $tpl->set('d', 'TPLDESC', '');
                $tpl->set('d', 'DUPLICATEBUTTON', '&nbsp;');
                $tpl->set('d', 'TEMPLATEBUTTON', '&nbsp;');
                $tpl->set('d', 'MOUSEOVER', '');
                $tpl->set('d', 'SUM_COLUMNS_EDIT', 14+count($listColumns));
                $tpl->set('d', 'CATID', '');
                $tpl->set('d', 'ACTION_EDIT_URL', '');
                $tpl->set('d', 'INPUT_CATEGORY', '');
                $tpl->set('d', 'LABEL_ALIAS_NAME', '');
                $tpl->set('d', 'HREF_CANCEL', '');
                $tpl->set('d', 'SRC_CANCEL', '');
                $tpl->set('d', 'DIRECTION', '');
                $tpl->set('d', 'SRC_OK', '');
                $tpl->set('d', 'VALUE_ALIAS_NAME', '');
                $tpl->set('d', 'HEIGTH', 'height:5px;');
                $tpl->set('d', 'BORDER_CLASS', 'str-style-b');

                $additionalColumns = array();

				foreach ($listColumns as $content)
				{
					// Content rows
					$additionalColumns[] = '<td style="border: 0px; border-bottom:1px; border-right: 1px; border-color: #B3B3B3; border-style: solid;" nowrap="nowrap">&nbsp;</td>';
				}
				$tpl->set('d', 'ADDITIONALCOLUMNS', implode("", $additionalColumns));
                $tpl->next();
            }


            $bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light"] : $cfg["color"]["table_dark"];

            $tpl->set('d', 'BGCOLOR', $bgcolor);
            $tpl->set('d', 'BGCOLOR_EDIT', '#F1F1F1');
            $tpl->set('d', 'HEIGTH', 'height:25px');
            $tpl->set('d', 'BORDER_CLASS', 'str-style-c');

            $spaces = "";

            $tpl->set('d', 'INDENT', ($value->custom['level'] * 16) . "px");
            $sCategoryname = $value->name;
            if (strlen($value->name) > 30) {
                $sCategoryname = capiStrTrimHard($sCategoryname, 30);
            }

            //$tpl->set('d', 'CATEGORY', $sCategoryname);
            if (strlen($value->name) > 30) {
                $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', 'onmouseover="Tip(\''.$value->name.'\', BALLOON, true, ABOVE, true);"');
            } else {
                $tpl->set('d', 'SHOW_MOUSEOVER_CATEGORY', '');
            }

            $tpl->set('d', 'COLLAPSE_CATEGORY_NAME', getExpandCollapseButton($value, $sCategoryname));
            if ($value->custom['alias']) {
                    $sCategoryalias = $value->custom['alias'];
                    if (strlen($value->custom['alias']) > 30) {
                        $sCategoryalias = capiStrTrimHard($sCategoryalias, 30);
                    }
                    $tpl->set('d', 'ALIAS', $sCategoryalias);
                    if (strlen($value->custom['alias']) > 30) {
                        $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', 'onmouseover="Tip(\''.$value->custom['alias'].'\', BALLOON, true, ABOVE, true);"');
                    } else {
                        $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', '');
                    }
            } else {
                     $tpl->set('d', 'SHOW_MOUSEOVER_ALIAS', '');
                     $tpl->set('d', 'ALIAS', '&nbsp;');
            }

			$template = $aTemplates[$aTplconfigs[$value->custom['idtplcfg']]]['name'];
			$templateDescription = $aTemplates[$aTplconfigs[$value->custom['idtplcfg']]]['description'];

            $descString = '';

						if ($template == "")
            {
                $template = '--- '.i18n("none").' ---';
            }

            // Description for hover effect
			$descString = '<b>'.$template.'</b>';

            if( sizeof($templateDescription)>0 )
			{
				$descString .= '<br>'.$templateDescription;
			}

            $sTemplatename = $template;
            if (strlen($template) > 20) {
                $sTemplatename = capiStrTrimHard($sTemplatename, 20);
            }

            $tpl->set('d', 'TPLNAME', $sTemplatename);
            $tpl->set('d', 'TPLDESC', $descString);

            if($perm->have_perm_area_action($tmp_area, "str_renamecat") || $perm->have_perm_area_action_item($tmp_area, "str_renamecat", $value->id)) {
                $bPermRename = 1;
            }else{
                $bPermRename = 0;
            }

            if ($perm->have_perm_area_action("str_tplcfg", "str_tplcfg") || $perm->have_perm_area_action_item("str_tplcfg","str_tplcfg",$value->id))
            {
                $bPermTplcfg = 1;
            } else {
                $bPermTplcfg = 0;
            }

            $aRecord = array();
            $sCatName = $value->name;

            $aRecord['catn'] = str_replace('\'', '\\\'', $sCatName);
            $sAlias = $value->custom['alias'];
            $aRecord['alias'] = str_replace('\'', '\\\'', $sAlias);
            $aRecord['idtplcfg'] = $value->custom['idtplcfg'];
            $aRecord['pName'] = $bPermRename;
            $aRecord['pTplcfg'] = $bPermTplcfg;
            $aInlineEditData[$value->id] = $aRecord;

            $tpl->set('d', 'RENAMEBUTTON', "<a class=\"action\" href=\"javascript:handleInlineEdit(".$value->id.");\"><img src=\"".$cfg["path"]["images"]."but_todo.gif\" id=\"cat_".$value->id."_image\"></a>");
            $tpl->set('d', 'CATID', $value->id);

            if (strlen($template) > 20) {
                $tpl->set('d', 'SHOW_MOUSEOVER', 'onmouseover="Tip(\''.$descString.'\', BALLOON, true, ABOVE, true);"');
            } else {
                $tpl->set('d', 'SHOW_MOUSEOVER', '');
            }

            $tpl->set('d', 'MOUSEOVER', $sMouseover);

            if($perm->have_perm_area_action($tmp_area, "str_newcat") || $perm->have_perm_area_action_item($tmp_area, "str_newcat", $value->id)) {
               $bAreaAddNewCategory = true;
            }

            if($perm->have_perm_area_action($tmp_area, "str_makevisible") || $perm->have_perm_area_action_item($tmp_area,"str_makevisible",$value->id)) {
                if ($value->custom['visible'] == 1) {
                    $tpl->set('d', 'VISIBLEBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_makevisible&frame=$frame&idcat=".$value->id."&visible=".$value->custom['visible'])."#clickedhere\"><img src=\"images/online.gif\"></a>");
                } else {
                    $tpl->set('d', 'VISIBLEBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_makevisible&frame=$frame&idcat=".$value->id."&visible=".$value->custom['visible'])."#clickedhere\"><img src=\"images/offline.gif\"></a>");
                }
            } else {
                $tpl->set('d', 'VISIBLEBUTTON', '&nbsp;');
            }

            if($perm->have_perm_area_action($tmp_area, "str_makepublic") || $perm->have_perm_area_action_item($tmp_area,"str_makepublic",$value->id)) {
                if ($value->custom['public'] == 1) {
                    $tpl->set('d', 'PUBLICBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_makepublic&frame=$frame&idcat=".$value->id."&public=".$value->custom['public'])."#clickedhere\"><img src=\"images/folder_delock.gif\"></a>");
                } else {
                    $tpl->set('d', 'PUBLICBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_makepublic&frame=$frame&idcat=".$value->id."&public=".$value->custom['public'])."#clickedhere\"><img src=\"images/folder_lock.gif\"></a>");
                }
            } else {
                $tpl->set('d', 'PUBLICBUTTON', '&nbsp;');
            }

            $hasChildren = strNextDeeper($value->id);
            $hasArticles = strHasArticles($value->id);
            if(($hasChildren == 0) && ($hasArticles == false) &&($perm->have_perm_area_action($tmp_area, "str_deletecat") || $perm->have_perm_area_action_item($tmp_area,"str_deletecat",$value->id))) {

                $delete = '<a href="javascript://" onclick="confDel('.$value->id.','.$value->custom['parentid'].', \''.conHtmlSpecialChars($value->name).'\')">'."<img src=\"".$cfg["path"]["images"]."delete.gif\"></a>";
                $tpl->set('d', 'DELETEBUTTON', $delete);
            } else {
                $message = i18n("No permission");

                if ($hasChildren)
                {
                    $button = 'delete_inact_h.gif';
                }

                if ($hasArticles)
                {
                    $button = 'delete_inact_g.gif';
                }
                if ($hasChildren && $hasArticles)
                {
                    $button = 'delete_inact.gif';
                }


                $tpl->set('d', 'DELETEBUTTON', '<img src="'.$cfg["path"]["images"].$button.'">');
            }

            if($perm->have_perm_area_action($tmp_area, "str_moveupcat") || $perm->have_perm_area_action_item($tmp_area,"str_moveupcat",$value->id)) {

                $rand = rand();

                if ($value->custom['parentid']==0 && $value->custom['preid']==0) {
                    $tpl->set('d', 'UPBUTTON', "<img src=\"images/folder_moveup_inact.gif\">");
                } else {
                    if ($value->custom['preid']!=0) {
						$tpl->set('d', 'UPBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_moveupcat&frame=$frame&idcat=".$value->id."&rand=$rand")."#clickedhere\"><img src=\"images/folder_moveup.gif\"></a>");
                	} else {
                		$tpl->set('d', 'UPBUTTON', "<img src=\"images/folder_moveup_inact.gif\">");
                	}
				}
            } else {
                $tpl->set('d', 'UPBUTTON', "<img src=\"images/folder_moveup_inact.gif\">");
            }

            if($perm->have_perm_area_action($tmp_area, "str_movedowncat") || $perm->have_perm_area_action_item($tmp_area,"str_movedowncat",$value->id)) {

                $rand = rand();

                if ($value->custom['postid']==0) {
                    $tpl->set('d', 'DOWNBUTTON', "<img src=\"images/folder_movedown_inact.gif\">");
                } else {
                    $tpl->set('d', 'DOWNBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_movedowncat&frame=$frame&idcat=".$value->id."&rand=$rand")."#clickedhere\"><img src=\"images/folder_movedown.gif\"></a>");
                }
            } else {
                $tpl->set('d', 'DOWNBUTTON', "<img src=\"images/folder_movedown_inact.gif\">");
            }

            if (($action === "str_movesubtree") && (!isset($parentid_new)))
            {
                if($perm->have_perm_area_action($tmp_area, "str_movesubtree") || $perm->have_perm_area_action_item($tmp_area,"str_movesubtree",$value->id))
                {
                    if ($value->id == $idcat)
                    {
                        $tpl->set('d', 'MOVEBUTTON', "<a name=#movesubtreehere><a href=\"".$sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=$idcat&parentid_new=0")."\"><img src=\"".$cfg["path"]["images"]."but_move_subtree_main.gif\"></a>");
                    } else {
                            $allowed = strMoveCatTargetallowed($value->id, $idcat);
                            if ($allowed == 1)
                            {
                                   $tpl->set('d', 'MOVEBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=$idcat&parentid_new=".$value->id)."\"><img src=\"".$cfg["path"]["images"]."but_move_subtree_target.gif\"></a>");
                            } else {
                                   $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
                            }
                    }
                } else {
                    $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
                }
            } else {
                if($perm->have_perm_area_action($tmp_area, "str_movesubtree") || $perm->have_perm_area_action_item($tmp_area,"str_movesubtree",$value->id)) {
                    $tpl->set('d', 'MOVEBUTTON', "<a href=\"".$sess->url("main.php?area=$area&action=str_movesubtree&frame=$frame&idcat=".$value->id)."#movesubtreehere\"><img src=\"".$cfg["path"]["images"]."but_move_subtree.gif\"></a>");
                }else{
                    $tpl->set('d', 'MOVEBUTTON', '&nbsp;');
                }
            }

            if ($perm->have_perm_area_action("str", "str_duplicate") || $perm->have_perm_area_action_item("str", "str_duplicate", $value->id))
            {
                $duplicate = '<a href="javascript://" onclick="confDupl('.$value->id.','.$value->custom['parentid'].', \''.conHtmlSpecialChars($value->name).'\')">'."<img src=\"".$cfg["path"]["images"]."folder_duplicate.gif\"></a>";

            	$tpl->set('d', 'DUPLICATEBUTTON', $duplicate);
            } else {
            	$tpl->set('d', 'DUPLICATEBUTTON', '&nbsp;');
            }

            // DIRECTION
			cInclude('includes', 'functions.lang.php');
			$tpl->set('d', 'DIRECTION', 'dir="' . langGetTextDirection($lang, $oDirectionDb) . '"');

            $columns = array();

            foreach ($listColumns as $key => $content)
			{
					$columnInfo = array();
        			$_cecIterator = $_cecRegistry->getIterator("Contenido.CategoryList.RenderColumn");

					$columnContents = array();

					if ($_cecIterator->count() > 0)
					{
						while ($chainEntry = $_cecIterator->next())
						{
						    $columnContents[]  = $chainEntry->execute($value->id, $key);
						}
					} else {
						$columnContents[] = '';
					}

                    $columns[] = '<td class="str-style-c">'.implode("", $columnContents).'</td>';
			}

			$tpl->set('d', 'ADDITIONALCOLUMNS', implode("", $columns));
            $tpl->next();
        }//end if -> perm
    }

    $jsDataArray = "";
    foreach ($aInlineEditData as $iIdCat => $aData) {
        $aTmp = array();
        foreach ($aData as $aKey => $aValue) {
            array_push($aTmp, $aKey."':'".$aValue);
        }
        $jsDataArray.= "tmpObject = new Object();
                        tmpObject = {'".implode("', '", $aTmp)."'};
                        dataArray[$iIdCat] = tmpObject;
                        ";
    }

    $tpl->set('s', 'JS_DATA', $jsDataArray);

	$string = markSubMenuItem(0, true);

    //Set DHTML generic Values
    $sImagepath = $cfg["path"]["images"];
    $tpl->set('s', 'SUM_COLUMNS', 14+count($listColumns));
    $tpl->set('s', 'HREF_ACTION', $sess->url("main.php?frame=$frame"));
    $tpl->set('s', 'CON_IMAGES', $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]);

    //Generate input fields for category new layer and category edit layer
    $oSession = new cHTMLHiddenField ($sess->name, $sess->id);
    $oActionEdit = new cHTMLHiddenField ('action', 'str_renamecat');
    $oIdcat = new cHTMLHiddenField ('idcat');

    $tpl->set('s', 'INPUT_SESSION', $oSession->render());
    $tpl->set('s', 'INPUT_ACTION_EDIT', $oActionEdit->render());
    $tpl->set('s', 'INPUT_IDCAT', $oIdcat->render());

    $oVisible = new cHTMLHiddenField ('visible', 0, 'visible_input');
    $oPublic = new cHTMLHiddenField ('public', 1, 'public_input');
    $oTemplate = new cHTMLHiddenField ('idtplcfg', 0, 'idtplcfg_input');

    $tpl->set('s', 'INPUT_VISIBLE', $oVisible->render());
    $tpl->set('s', 'INPUT_PUBLIC', $oPublic->render());
    $tpl->set('s', 'INPUT_TEMPLATE', $oTemplate->render());

    $oCatName = new cHTMLTextbox ('categoryname', '', '', '', 'cat_categoryname');
    $oCatName->setStyle('width:150px; vertical-align:middle;');
    $tpl->set('s', 'INPUT_CATNAME_NEW', $oCatName->render());

    $oAlias = new cHTMLTextbox ('categoryalias');
    $oAlias->setStyle('width:150px; vertical-align:middle;');
    $tpl->set('s', 'INPUT_ALIAS_NEW', $oAlias->render());

    $oNewCatName = new cHTMLTextbox ('newcategoryname');
    $oNewCatName->setStyle('width:150px; vertical-align:middle;');
    $tpl->set('s', 'INPUT_CATNAME_EDIT', $oNewCatName->render());

    $oNewAlias = new cHTMLTextbox ('newcategoryalias');
    $oNewAlias->setStyle('width:150px; vertical-align:middle;');
    $tpl->set('s', 'INPUT_ALIAS_EDIT', $oNewAlias->render());

    $sCategorySelect = buildCategorySelectRights('idcat', '');

    # Show Layerbutton for adding new Cateogries and set options according to Permisssions
    if (($perm->have_perm_area_action($tmp_area,"str_newtree") ||
        $perm->have_perm_area_action($tmp_area,"str_newcat") ||
        $bAreaAddNewCategory)
        && (int) $client > 0 && (int) $lang > 0) {
        $tpl->set('s', 'NEWCAT', $string . "<a class=\"black\" id=\"new_tree_button\" href=\"javascript:showNewForm();\"><img src=\"images/folder_new.gif\">&nbsp;".i18n("Create new category")."</a>");
        if ($perm->have_perm_area_action($tmp_area,"str_newtree")) {
            if ($perm->have_perm_area_action($tmp_area,"str_newcat") || $bAreaAddNewCategory) {
                $tpl->set('s', 'PERMISSION_NEWTREE', '');
                $oActionNew = new cHTMLHiddenField ('action', 'str_newcat', 'cat_new_action');
            } else {
                $tpl->set('s', 'PERMISSION_NEWTREE', 'disabled checked');
                $oActionNew = new cHTMLHiddenField ('action', 'str_newcat', 'str_newtree');
            }
            $tpl->set('s', 'INPUT_ACTION_NEW', $oActionNew->render());
            $tpl->set('s', 'PERMISSION_NEWTREE_DISPLAY', 'block');

        } else {
            $oActionNew = new cHTMLHiddenField ('action', 'str_newcat', 'cat_new_action');
            $tpl->set('s', 'PERMISSION_NEWTREE', 'disabled');
            $tpl->set('s', 'PERMISSION_NEWTREE_DISPLAY', 'none');
            $tpl->set('s', 'NEW_ACTION', 'str_newcat');
            $tpl->set('s', 'INPUT_ACTION_NEW', $oActionNew->render());
        }

        if ($perm->have_perm_area_action($tmp_area,"str_newcat") || $bAreaAddNewCategory) {
            $tpl->set('s', 'CATEGORY_SELECT', $sCategorySelect);
            $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'block');
        } else {
            $tpl->set('s', 'CATEGORY_SELECT', '');
            $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'none');
        }

        if ($perm->have_perm_area_action("str_tplcfg", "str_tplcfg")) {
            $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '<a href="javascript:showTemplateSelect();"><img src="'.$sImagepath.'template_properties.gif" id="cat_category_select_button" title="'.i18n("Configure category").'" alt="'.i18n("Configure category").'"></a>');
            $tpl->set('s', 'SELECT_TEMPLATE', getTemplateSelect());
        } else {
            $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '<img src="'.$sImagepath.'template_properties_off.gif" id="cat_category_select_button" title="'.i18n("Configure category").'" alt="'.i18n("Configure category").'">');
            $tpl->set('s', 'SELECT_TEMPLATE', '');
        }

        if ($perm->have_perm_area_action($tmp_area, "str_makevisible")) {
            $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '<a href="javascript:changeVisible();"><img src="'.$sImagepath.'offline.gif" id="visible_image" title="'.i18n("Make online").'" alt="'.i18n("Make online").'"></a>');
        } else {
            $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '<img src="'.$sImagepath.'offline_off.gif" id="visible_image" title="'.i18n("Make online").'" alt="'.i18n("Make online").'">');
        }

        if ($perm->have_perm_area_action($tmp_area, "str_makepublic")) {
            $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '<a href="javascript:changePublic();"><img src="'.$sImagepath.'folder_delock.gif" id="public_image" title="'.i18n("Protect category").'" alt="'.i18n("Protect category").'"></a>');
        } else {
            $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '<img src="'.$sImagepath.'folder_delocked.gif" id="public_image" title="'.i18n("Protect category").'" alt="'.i18n("Protect category").'">');
        }
    } else {
        $tpl->set('s', 'NEWCAT', $string);

        $tpl->set('s', 'PERMISSION_NEWTREE', 'disabled');
        $tpl->set('s', 'PERMISSION_NEWTREE_DISPLAY', 'none');

        $tpl->set('s', 'CATEGORY_SELECT', '');
        $tpl->set('s', 'PERMISSION_NEWCAT_DISPLAY', 'none');

        $tpl->set('s', 'TEMPLATE_BUTTON_NEW', '');
        $tpl->set('s', 'MAKEVISIBLE_BUTTON_NEW', '');
        $tpl->set('s', 'MAKEPUBLIC_BUTTON_NEW', '');

        $tpl->set('s', 'NEW_ACTION', 'str_newcat');
        $tpl->set('s', 'SELECT_TEMPLATE', '');
    }

    # Generate template
	$clang = new Language;
	$clang->loadByPrimaryKey($lang);

	if ( $movesubtreeidcat != 0 ) {
		if ( strlen ( $sMoveSubtreeCatName ) > 30 ) {
			$sLimiter = "...";
		} else {
			$sLimiter = "";
		}
		$sButtonDesc = sprintf(i18n("Cancel moving %s"), '"' . substr( $sMoveSubtreeCatName, 0, 30) . $sLimiter . '"');
		$tpl->set('s', 'CANCEL_MOVE_TREE', '<a class="black" id="cancel_move_tree_button" href="javascript:cancelMoveTree(\'' . $movesubtreeidcat . '\');"><img src="images/but_cancel.gif" alt="'.$sButtonDesc.'" />&nbsp;'.$sButtonDesc.'</a>');
	} else {
		$tpl->set('s', 'CANCEL_MOVE_TREE', '');
	}

	$tpl->setEncoding($clang->get("encoding"));
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['str_overview']);
}
?>
