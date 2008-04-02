<?php
/******************************************
* File      :   includes.con_str_overview.php
* Project   :   Contenido
* Descr     :   Displays the structure in
*               the left frame.
*
* Author    :   Jan Lengowski
* Created   :   26.01.2003
* Modified  :   24.04.2003
* Modified	:	24.04.2007 H. Librenz (4fb)
* Modified	:	13.02.2008 A. Lindner (4fb)
*
* (c) four for business AG
*****************************************/

cInclude("classes","class.htmlelements.php");
cInclude("classes","class.ui.php");
cInclude("includes","functions.str.php");
cInclude("includes","functions.tpl.php");
cInclude('includes', 'functions.lang.php');
cInclude("classes", "widgets/class.widgets.foldingrow.php");

$db2 = new DB_Contenido;
$db3 = new DB_Contenido;

$markscript = 'onmouseover="con.over(this)" onmouseout="con.out(this)" onclick="con.click(this)"';
function getExpandCollapseButton ($item)
{
	global $sess, $PHP_SELF, $frame, $area;
	$selflink = "main.php";
	$img = new cHTMLImage;
	
	if (count($item->subitems) > 0)
	{
		if ($item->collapsed == TRUE)
		{
			$expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=". $item->id);

			/*if($item->custom['postid'] == 0)
			{
				$img->setSrc($item->lastnode_icon);
				$img->setAlt(i18n("Close category"));
			}
			else
			{*/
				$img->setSrc($item->collapsed_icon);
				$img->setAlt(i18n("Open category"));
			//}
			return(
				'<a style="margin-top:3px;" href="'.$expandlink.'">'.$img->render().'</a>');
		}
			else {
			$collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=". $item->id);

			if($item->custom['postid'] == 0)
			{
				$img->setSrc($item->lastnode_icon);
				$img->setAlt(i18n("Close category"));
			}
			else
			{
				$img->setSrc($item->expanded_icon);
				$img->setAlt(i18n("Close category"));
			}
			return(
				'<a style="margin-top:5px;" href="'.$collapselink.'">'.$img->render().'</a>');
		}

		if($item->custom['postid'] == 0)
		{
			$img->setSrc($item->lastnode_icon);
			$img->setAlt(i18n("Close category"));
			return($img->render());
		}

	}
 	else {
		/*return '<img src="images/spacer.gif" width="2" height="11">';*/
            $img->setSrc($item->lastnode_icon);
		    $img->setAlt('');
            
            /*
			if($item->custom['postid'] == 0)
			{
				$img->setSrc($item->lastnode_icon);
				$img->setAlt(i18n("Close category"));
			}
			else
			{
				$img->setSrc($item->expanded_icon);
				$img->setAlt(i18n("Close category"));
			}*/
			return($img->render());
	}
}


//Refresh or reset right frames, when a synclang is changed or a category is synchronized
$tpl->reset();

if ($action == "con_synccat" || isset($_GET['refresh_syncoptions']) && $_GET['refresh_syncoptions'] == 'true') {
    $tpl->set('s', 'RELOAD_RIGHT', 'reloadRightFrame();');
} else {
    $tpl->set('s', 'RELOAD_RIGHT', '');
}

if ($action == "con_synccat")
{
	strSyncCategory($syncidcat, $syncfromlang, $lang, $multiple);
	$remakeStrTable = true;
}
	
if ( !is_object($db2) ) $db2 = new DB_Contenido;

if (!isset($remakeStrTable))
{
	$remakeStrTable = false;
}

if (!isset($remakeCatTable))
{
	$remakeCatTable = false;
}

$sess->register("remakeCatTable");
$sess->register("CatTableClient");
$sess->register("CatTableLang");
$sess->register("remakeStrTable");

if (isset($syncoptions))
{
	$syncfrom = $syncoptions;
	$remakeCatTable = true;
}

if (!isset($syncfrom))
{
	$syncfrom = 0;	
}

$sess->register("syncfrom");

$syncoptions = $syncfrom;

if (!isset($CatTableClient))
{
	$CatTableClient = 0;
}

if ($CatTableClient != $client)
{
	$remakeCatTable = true;
}

if (!isset($CatTableLang))
{
	$CatTableLang = 0;
}

if ($CatTableLang != $lang)
{
	$remakeCatTable = true;
}

$CatTableClient = $client;
$CatTableLang = $lang;

if ($syncoptions == -1)
{
    $sql = "SELECT
    			a.preid AS preid,
    			a.postid AS postid,
    			a.parentid AS parentid,
                c.idcat AS idcat,
                c.level AS level,
                b.name AS name,
                b.public AS public,
                b.visible AS online,
                d.idtpl AS idtpl,
				b.idlang AS idlang
            FROM
                (".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b,
                ".$cfg["tab"]["cat_tree"]." AS c)
            LEFT JOIN
                ".$cfg["tab"]["tpl_conf"]." AS d
                ON d.idtplcfg = b.idtplcfg
            WHERE
                a.idclient  = '".$client."' AND
                b.idlang    = '".$lang."' AND
                c.idcat     = b.idcat AND
                b.idcat     = a.idcat
            ORDER BY
                c.idtree ASC";
} else {
    $sql = "SELECT
    			a.preid AS preid,
    			a.postid AS postid,
    			a.parentid AS parentid,
                c.idcat AS idcat,
                c.level AS level,
                b.name AS name,
                b.public AS public,
                b.visible AS online,
                d.idtpl AS idtpl,
				b.idlang AS idlang
            FROM
                (".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b,
                ".$cfg["tab"]["cat_tree"]." AS c)
            LEFT JOIN
                ".$cfg["tab"]["tpl_conf"]." AS d
                ON d.idtplcfg = b.idtplcfg
            WHERE
                a.idclient  = '".$client."' AND
                (b.idlang    = '".$lang."' OR
				 b.idlang	 = '".$syncoptions."') AND
                c.idcat     = b.idcat AND
                b.idcat     = a.idcat
            ORDER BY
                c.idtree ASC";	
	
}

$db->query($sql);

if (isset($syncoptions))
{
	$remakeCatTable = true;
}

if (isset($online))
{
	$remakeCatTable = true;
}

if (isset($public))
{
	$remakeCatTable = true;
}

if (isset($idtpl))
{
	$remakeCatTable = true;
}

if (isset($force))
{
	$remakeCatTable = true;
}

function buildTree (&$rootItem, &$items)
{
	global $nextItem, $perm;
	
	while ($item_list = each($items))
	{
		list($key, $item) = $item_list;
		
		unset($newItem);
		$newItem = new TreeItem($item['name'], $item['idcat'],true);
		$newItem->custom['visible'] = $item['visible'];
		$newItem->custom['online'] = $item['visible'];
		$newItem->custom['idtpl'] = $item['idtpl'];
		$newItem->custom['public'] = $item['public'];
		$newItem->custom['level'] = $item['level'];
		$newItem->custom['parentid'] = $item['parentid'];
		$newItem->custom['public'] = $item['public'];
		$newItem->custom['preid'] = $item['preid'];
		$newItem->custom['islast'] = '1';
		
		if (array_key_exists("articles", $item))
		{
			$newItem->custom['articles'] = $item['articles'];
		} else {
			$newItem->custom['articles'] = array();
		}
		
		$newItem->custom['postid'] = $item['postid'];
		$newItem->custom['idlang'] = $item['idlang'];
		
		if ($perm->have_perm_item("con", $item['idcat']))
		{
			$newItem->custom['forcedisplay'] = 1;
		}
		
		if (array_key_exists($key+1, $items))
		{
			$nextItem = $items[$key+1];
		}
		
		if (array_key_exists($key-1, $items))
		{
			$lastItem = $items[$key-1];
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


$items = array();
$addedcats = array();
$count = 0;

$arrIn = array();
while ($db->next_record()) {
	$arrIn[] = $db->f('idcat');
}

$sIn = implode(',',$arrIn);

$sql2 = "SELECT b.idcat, a.idart, idlang FROM ".$cfg["tab"]["art_lang"]." AS a,
							  ".$cfg["tab"]["cat_art"]." AS b
		WHERE b.idcat IN ($sIn) AND (a.idlang = '".$syncoptions."' OR a.idlang = '".$lang."') 
        AND b.idart = a.idart";
$db->query($sql2);

$arrArtCache = array();

while ($db->next_record()) {
	$arrArtCache[$db->f('idcat')][$db->f('idart')][$db->f('idlang')] = 'x';
}

$db->query($sql);

while ($db->next_record()) {
	$entry = array();
	
	$entry['articles'] = false;

	if ($db->f("idlang") == $lang) {
		#    	$sql = "SELECT a.idart, idlang FROM ".$cfg["tab"]["art_lang"]." AS a,
		#    								  ".$cfg["tab"]["cat_art"]." AS b
		#    			WHERE b.idcat = '".$db->f("idcat")."' AND (a.idlang = '".$syncoptions."' OR a.idlang = '".$lang."') 
		#                AND b.idart = a.idart";
		#        $db2->query($sql);
		#        $arts = Array();
		#	
		#		while ($db2->next_record()) {
		#       		$arts[$db2->f("idart")][$db2->f("idlang")] = 1;
		#      	}

        $arts = Array();
      	
		  if (isset($arrArtCache[$db->f("idcat")])) {
      		foreach ($arrArtCache[$db->f("idcat")] as $key => $value) {
      			foreach ($value as $key2 => $value2) {
      				$arts[$key][$key2] = 1;
      			}
      		}
      	}

      	foreach ($arts as $idart => $entry) {
      		if (is_array($entry))
      		{
          		if (!array_key_exists($lang,$entry))
          		{
          			$entry['articles'] = true;	
          			break;
          		}
      		}
      	}
	} 
		
	$entry['idcat'] = $db->f("idcat");
	$entry['level'] = $db->f("level");
	$entry['name'] = htmldecode($db->f("name"));
	$entry['public'] = $db->f("public");
	$entry['online'] = $db->f("online");
	$entry['idtpl'] = $db->f("idtpl");
	$entry['visible'] = $db->f("online");
	$entry['preid'] = $db->f("preid");
	$entry['postid'] = $db->f("postid");
	$entry['idlang'] = $db->f("idlang");
	$entry['parentid'] = $db->f("parentid");
	
	if (array_key_exists($db->f("idcat"),$addedcats))
	{
		if ($db->f("idlang") == $lang)
		{
			$items[$addedcats[$db->f("idcat")]] = $entry;	
		}
	} else {
		$count++;
		$addedcats[$db->f("idcat")] = $count;
		$items[$count] = $entry;
	}
}

/**********************************/
/* Build the tree 								*/
/**********************************/
$rootCatItem = new TreeItem("root",-1);
buildTree($rootCatItem, $items);


/**********************************/
/* Build expanded/contracted list */
/**********************************/
$conexpandedList = unserialize($currentuser->getUserProperty("system","con_cat_expandstate"));

//print "<pre>"; var_dump($conexpandedList); print "</pre>\n";
if (is_array($conexpandedList))
{
	$rootCatItem->markExpanded($conexpandedList);	
}

if (isset($collapse) && is_numeric($collapse))
{
	$rootCatItem->markCollapsed($collapse);
}

if (isset($expand) && is_numeric($expand))
{
	$rootCatItem->markExpanded($expand);
}	

if (isset($expand) && $expand == "all")
{
	$rootCatItem->expandAll(-1);
}

if (isset($collapse) && $collapse == "all")
{
	$rootCatItem->collapseAll(-1);
}

$objects = array();

$rootCatItem->traverse($objects);

$conexpandedList = Array();
$rootCatItem->getExpandedList($conexpandedList);
$currentuser->setUserProperty("system","con_cat_expandstate", serialize($conexpandedList));

/***********************************/
/* vertical lines in category tree */
/***********************************/

// Need a flat tree
$flat_tree = array();
$rootCatItem->traverse($flat_tree);

// add lines to flat tree
$arrFlatIdCache = array();

foreach($flat_tree as $key => $value)
{
	verticalTreeLines($flat_tree, $key);	
}

// sync flat tree
iterateTree($rootCatItem, $flat_tree);

function iterateTree(&$rootItems, &$flat_tree)
{
	foreach ($rootItems->subitems as $treeKey => $treeItem)
	{
		foreach($flat_tree as $value)
		{
			if($treeItem->id == $value->id)
			{
			$rootItems->subitems[$treeKey]->custom['vertline']=$value->custom['vertline'];
			}
		}
		
		iterateTree($treeItem, $flat_tree);
	}
}		

function hasMultipleNodesOneLevelUp($curItem)
{
	$pos=0;
	
	foreach($flat_tree as $key => $value)
	{
		if($curItem->id == $value->id)
		{
			$pos = $key;
			break;
		}
	}
	
	$countLevel=0;
	$i=$pos+1;
	while($flat_tree[$i]->level >= ($curItem->level))
	{
		if($flat_tree[$i]->level == ($curItem->level)+1)
		{
			$countLevel++;
		}
		
		$i++;
	}
	
	if($countLevel>1)
	{
		return TRUE;
	}
	else
	{
		return FALSE;
	}
}

function verticalTreeLines(&$flat_tree, $pos)
{
	$retList = array();
	global $conexpandedList;
	global $rootCatItem;
	global $flat_tree;
	global $arrFlatIdCache;

	$hasPostid = FALSE;
	
	foreach($flat_tree as $key => $value)
	{
		// look for position of current node
		if($key == $pos)
		{
			// start iterating
			for($i=$pos+1; $i < sizeof($flat_tree); $i++)
			{
				// check & set for postid on level + 1
				if($flat_tree[$i]->level == ($value->level)+1)
				{
					if($flat_tree[$i]->custom['postid'] != 0)
					{
						$hasPostid = TRUE;
					}
					else
					{
						$hasPostid = FALSE;
					}
				}
				
				// skip nodes from level+1 because they dont need a vertical line
				if(	$flat_tree[$i]->level > ($value->level)+1)
				{
					// skip collapsed
					$flat_tree_id = $flat_tree[$i]->id;
					$arrFlatIdCache[$flat_tree_id]['counter']++;
					if (!isset($arrFlatIdCache[$flat_tree_id]['cache'])) {
						$arrFlatIdCache[$flat_tree_id]['cache'] = $rootCatItem->hasCollapsedNode(	$flat_tree_id);
					}

					if( !$arrFlatIdCache[$id]['cache'] ) {
						// stop if reached last node on current level
						// end condition: has no successor && is on same level as start node
						if(	$flat_tree[$i]->custom['postid'] 	== 0 && 
								$flat_tree[$i]->custom['level'] 	== ($value->level))
						{
							$flat_tree[$i]->custom['vertline'][]=$value->level;
						}
						// found what I'm looking for :-)
						else
						{
							// check if we have a successor for currentroot+1 node - only then we have a vertical line for current root  
							if($hasPostid)
							{
								// only add if we haven't done so already
								if($flat_tree[$i]->custom['vertline'] != NULL)
								{
									if(!(in_array($value->level,$flat_tree[$i]->custom['vertline'])))
									{
										$flat_tree[$i]->custom['vertline'][]=$value->level;
									}
								}
								// array is empty, add anyway
								else
								{
									$flat_tree[$i]->custom['vertline'][]=$value->level;
								}
							}
							else
							{
								//skipping
							}
						}
					}
					else
					{
						//skipping
					}
				}
				else
				{
					//skipping
				}
			}
		}
	}
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleCategoryList.ListItems");

if ($_cecIterator->count() > 0)
{
	while ($chainEntry = $_cecIterator->next())
	{
	    $listItem = $chainEntry->execute();
	    
	    if (is_array($listItem))
	    {
		    if (!array_key_exists("expandcollapseimage", $listItem) || $listItem["expandcollapseimage"] == "")
		    {
				$collapseImage = '<img src="images/spacer.gif" width="11" height="11">';	    		
		    } else {
		    	$collapseImage = $listItem["expandcollapseimage"];	
		    }
		    
		    if (!array_key_exists("image", $listItem) || $listItem["image"] == "")
		    {
				$image = '<img src="images/spacer.gif">';	    		
		    } else {
		    	$image = $listItem["image"];	
		    }		
		    
		    if (!array_key_exists("id", $listItem) || $listItem["id"] == "")
		    {
				$id = rand();	    		
		    } else {
		    	$id = $listItem["id"];	
		    }	
		    
			if (array_key_exists("markable", $listItem))
		    {
				if ($listItem["markable"] == true)
				{
					$mmark = $markscript;	
				} else {
					$mmark = "";	
				}
		    } else {
		    	$mmark = "";
		    }	
	    }	    
			    
	}
}

$tpl->set('s', 'DIRECTION', 'dir="' . langGetTextDirection($lang) . '"');

$languages = getLanguageNamesByClient($client);

/******************************/
/* Expand all / Collapse all */
/******************************/
$selflink = "main.php";
$expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=all&syncoptions=$syncoptions");
$collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=all&syncoptions=$syncoptions");
$collapseimg = '<a href="'.$collapselink.'" alt="'.i18n("Close all categories").'" title="'.i18n("Close all categories").'"><img src="images/but_minus.gif" border="0"></a>';
$expandimg = '<a href="'.$expandlink.'" alt="'.i18n("Open all categories").'" title="'.i18n("Open all categories").'"><img src="images/but_plus.gif" border="0"></a>';
$allLinks = $expandimg .'<img src="images/spacer.gif" width="3">'.$collapseimg;
$text_direction = langGetTextDirection($lang);

#Check global rights
$check_global_rights = $perm->have_perm_area_action("con", "con_makestart");
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editart", "con_edit");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editart", "con_saveart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editcontent", "con_editart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editart", "con_newart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_deleteart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_makeonline");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_tplcfg_edit");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_makecatonline");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_changetemplate");}

unset($objects[0]);

foreach ($objects as $key=>$value) {
	$cfgdata = '';
	#Check rights per cat
	if (!$check_global_rights) {
		$check_rights = false;
		
		/*#Check if any rights are applied to current user or his groups - variable $tmp_userstring does not exist in this context
		$sql = "SELECT *
				FROM ".$cfg["tab"]["rights"]."
				WHERE user_id IN ('".$tmp_userstring."') AND idclient = '$client' AND idlang = '$lang' AND idcat = '".$value->id."'";
		$db->query($sql);

		if ($db->num_rows() != 0) {
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con_editart", "con_edit",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con_editart", "con_saveart",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con", "con_deleteart",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con_editcontent", "con_editart",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con_editart", "con_newart",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con", "con_makestart",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con", "con_makeonline",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con", "con_tplcfg_edit",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con", "con_makecatonline",$value->id);}
			if (!$check_rights) {$check_rights = $perm->have_perm_area_action_item("con", "con_changetemplate",$value->id);}
		}*/
	} else {
		$check_rights = true;
	}

	if (!$check_rights) {
         $check_rights = $value->isCustomAttributeSet("forcedisplay");
	}

	if ($check_rights) {
		if ($value->custom['parentid'] == 0) {			
      #$tpl->set('d', 'COLLAPSE', '');
			#$tpl->set('d', 'IMAGE', '');
			#$tpl->set('d', 'CAT', '&nbsp;'); 
			#$tpl->next();
		}
		
        $idcat = $value->id;
        $level = $value->level - 1;
        $name = $value->name;

        # Indent for every level
        $cnt = $value->level - 1;
        $indent = 0;

		$tpl->set('d', 'COLLAPSE', getExpandCollapseButton($value));
        for ($i = 0; $i < $cnt; $i ++) {
            $indent += 5;
        }

        # create javascript multilink
        $tmp_mstr = '<a href="javascript://" title="idcat'.'&#58; '.$idcat.'" onclick="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

				$idtpl = ( $value->custom['idtpl'] != '' ) ? $value->custom['idtpl'] : 0;
		
        $mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=$area&frame=3&idcat=$idcat&idtpl=$idtpl"),
                                   'right_bottom',
                                   $sess->url("main.php?area=$area&frame=4&idcat=$idcat&idtpl=$idtpl"),
//                                   'left_top',
//                                   $sess->url("main.php?area=$area&frame=1&idcat=$idcat&idtpl=$idtpl"),
                                   $name);

		if (($value->custom["idlang"] != $lang) || ($value->custom['articles'] == true))
		{
	        $bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light_sync"] : $cfg["color"]["table_dark_sync"];
            $tpl->set('d', 'CSS_CLASS', 'class="con_sync"');
		} else {
			$bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light"] : $cfg["color"]["table_dark"];
            $tpl->set('d', 'CSS_CLASS', 'row');
		}

		$check_rights = $perm->have_perm_area_action_item("con", "con_changetemplate",$value->id);
		if (!$check_rights)
		{
			$check_rights = $perm->have_perm_area_action("con", "con_changetemplate");
		}

		if ($check_rights)
		{
            $changetemplate = 1;
            
      	} else {
      		$changetemplate = 0;
      		
      	}
      	
		$check_rights = $perm->have_perm_area_action_item("con", "con_makecatonline",$value->id);
		if (!$check_rights)
		{
			$check_rights = $perm->have_perm_area_action("con", "con_makecatonline");
      	}

       	if ($check_rights)
       	{
       		$onoffline = 1;
       	} else {
       		$onoffline = 0;
       	}
       	

		$check_rights = $perm->have_perm_area_action_item("con", "con_makepublic",$value->id);
		if (!$check_rights)
		{
			$check_rights = $perm->have_perm_area_action("con", "con_makepublic");
		}
		
		if ($check_rights)
		{
			$makepublic = 1;
		} else {
			$makepublic = 0;
		}
        
        $check_rights = $perm->have_perm_area_action_item("con", "con_tplcfg_edit", $value->id);
		if (!$check_rights)
		{
			$check_rights = $perm->have_perm_area_action("con", "con_tplcfg_edit");
		}
		
		if ($check_rights)
		{
			$templateconfig = 1;
		} else {
			$templateconfig = 0;
		}
       	
       	if ($value->custom["idlang"] == $lang)
       	{
           	/* Build cfgdata string */
            $cfgdata = $idcat."-".$idtpl."-".$value->custom['online']."-".$value->custom['public']."-".
            		   $changetemplate ."-".
            	       $onoffline ."-".
            	       $makepublic."-".$templateconfig;
       	} else {
       		$cfgdata = "";
       	}       	

        # Select the appropriate folder-
        # image depending on the structure
        # properties
		if ($syncoptions == -1)
		{
			if ($cfg["is_start_compatible"] == true)
			{
                $sql2 = "SELECT
                            c.is_start AS is_start,
                            a.online AS online,
    						a.idlang AS idlang
                        FROM
                            ".$cfg["tab"]["art_lang"]." AS a,
                            ".$cfg["tab"]["art"]." AS b,
                            ".$cfg["tab"]["cat_art"]." AS c
                        WHERE
                            a.idlang = ".$lang." AND
                            a.idart = b.idart AND
                            b.idclient = '".$client."' AND
                            b.idart = c.idart AND
                            c.idcat = '".$idcat."'";
			} else {
                $sql2 = "SELECT
                            a.online AS online,
    						a.idlang AS idlang,
							a.idartlang AS idartlang
                        FROM
                            ".$cfg["tab"]["art_lang"]." AS a,
                            ".$cfg["tab"]["art"]." AS b,
                            ".$cfg["tab"]["cat_art"]." AS c
                        WHERE
                            a.idlang = ".$lang." AND
                            a.idart = b.idart AND
                            b.idclient = '".$client."' AND
                            b.idart = c.idart AND
                            c.idcat = '".$idcat."'";				
				
			}
		} else {
			if ($cfg["is_start_compatible"] == true)
			{
                $sql2 = "SELECT
                            c.is_start AS is_start,
                            a.online AS online,
    						a.idlang AS idlang
                        FROM
                            ".$cfg["tab"]["art_lang"]." AS a,
                            ".$cfg["tab"]["art"]." AS b,
                            ".$cfg["tab"]["cat_art"]." AS c
                        WHERE
                            a.idart = b.idart AND
                            b.idclient = '".$client."' AND
                            b.idart = c.idart AND
                            c.idcat = '".$idcat."'";
			} else {
                $sql2 = "SELECT
							a.idartlang AS idartlang,
                            a.online AS online,
    						a.idlang AS idlang
                        FROM
                            ".$cfg["tab"]["art_lang"]." AS a,
                            ".$cfg["tab"]["art"]." AS b,
                            ".$cfg["tab"]["cat_art"]." AS c
                        WHERE
                            a.idart = b.idart AND
                            b.idclient = '".$client."' AND
                            b.idart = c.idart AND
                            c.idcat = '".$idcat."'";				
			}
		}			
			
			

        $db2->query($sql2);

        $no_start   = true;
        $no_online  = true;

        while ( $db2->next_record() ) {

			if ($cfg["is_start_compatible"] == true)
			{
                if ( $db2->f("is_start") == 1 )
                {
                    $no_start = false;
                }
			} else {
				$no_start = isStartArticle($db->f("idartlang"), $idcat, $lang, $db3);
			}

            if ( $db2->f("online") == 1 ) {
                $no_online = false;
            }

			if (!$no_start&&!$no_online) {
				#Exit loop if both vars are already false
				break;
			}
        }

        if ( $value->custom['online'] == 1 ) {
            # Category is online

            if ( $value->custom['public'] == 0 ) {
                # Category is locked
                if ( $no_start || $no_online ) {
                    # Error found
                    $tmp_img = "folder_on_error_locked.gif";

                } else {
                    # No error found
                    $tmp_img = "folder_on_locked.gif";

                }

            } else {
                # Category is public
                if ( $no_start || $no_online ) {
                    # Error found
                    $tmp_img = "folder_on_error.gif";

                } else {
                    # No error found
                    $tmp_img = "folder_on.gif";

                }
            }

        } else {
            # Category is offline

            if ( $value->custom['public'] == 0 ) {
                # Category is locked
                if ( $no_start || $no_online ) {
                    # Error found
                    $tmp_img = "folder_off_error_locked.gif";

                } else {
                    # No error found
                    $tmp_img = "folder_off_locked.gif";

                }

            } else {
                # Category is public
                if ( $no_start || $no_online ) {
                    # Error found
                    $tmp_img = "folder_off_error.gif";

                } else {
                    # No error found
                    $tmp_img = "folder_off.gif";

                }
            }
        }

        $bIsSyncable = false;
		if ($value->custom["idlang"] != $lang)
       	{
       		/* Fetch parent id and check if it is syncronized */
       		$sql = "SELECT parentid FROM %s WHERE idcat = '%s'";
       		$db->query(sprintf($sql, $cfg["tab"]["cat"], $idcat));
       		if ($db->next_record())
       		{
       			if ($db->f("parentid") != 0)
       			{
       				$parentid = $db->f("parentid");
       				$sql = "SELECT idcatlang FROM %s WHERE idcat = '%s' AND idlang = '%s'";
       				$db->query(sprintf($sql, $cfg["tab"]["cat_lang"], $parentid, $lang));
       				
       				if ($db->next_record())
       				{
								//$img_folder .= sprintf('<a href="%s"><img src="images/%s" alt="%s" title="%s"></a>', $sess->url("main.php?area=$area&frame=$frame&action=con_synccat&syncfromlang=$syncoptions&syncidcat=$idcat"), "but_sync_cat.gif", i18n("Copy to current language"),i18n("Copy to current language"));       					
                                $tmp_img = "but_sync_cat_off.gif";
                                $bIsSyncable = true;
                    }
       				
       			} else {
       				//$img_folder .= sprintf('<a href="%s"><img src="images/%s" alt="%s" title="%s"></a>', $sess->url("main.php?area=$area&frame=$frame&action=con_synccat&syncfromlang=$syncoptions&syncidcat=$idcat"), "but_sync_cat.gif", i18n("Copy to current language"),i18n("Copy to current language"));	
                    $tmp_img = "but_sync_cat_off.gif";
                    $bIsSyncable = true;
                }	
       		}
       	}
        
        //Last param defines if cat is syncable or not, all other rights are disabled at this point
        if ($bIsSyncable) {
            if ($cfgdata != '') {
                $cfgdata .= '-1';
            } else {
                $cfgdata = $idcat."-".$idtpl."-".$value->custom['online']."-".$value->custom['public'].
                       "-0-0-0-0-1";
            }
        } else {
            if ($cfgdata != '') {
                $cfgdata .= '-0';
            } else {
                $cfgdata = $idcat."-".$idtpl."-".$value->custom['online']."-".$value->custom['public'].
                       "-0-0-0-0-0";
            }
        }
        
        $img_folder = sprintf('<img style="margin: 0px 2px 7px 0px;" src="images/dash.gif"><img src="images/%s" alt="">', $tmp_img);

				
				/**
				 * preparing the vertical lines
				 **/
 				$imgVertLine = new cHTMLImage;
				$imgVertLine->setSrc("images/vert.gif");
				
				$vert_lines=NULL;
				$itemlevels = $value->custom['vertline'];
				$lvl=$value->level;
				$indent="0";
				
				
				if($value->level >= 2)
				{
					if($value->custom['vertline'] != NULL)
					{
						$indent=8;
					}
				}
				
				// iterate over level
				for($i=0; $i < (($value->level)-1); $i++)
				{
				// set vert line if we should do so for this level
					if($itemlevels != NULL)
					{
						if(in_array($i, $itemlevels))
						{
							$val="";
							
							if($value->level>=1 && $i >= 1)
							{
								$dist="margin-left:8px";
								
								$val=array('style' => $dist);
								$imgVertLine->setAttributes($val);
							}
							
							$vert_lines.="<td>";
							$vert_lines.=$imgVertLine->render();
							$vert_lines.="</td>";
						}
						else
						{
							$vert_lines.="<td>";
							$vert_lines.="<img src=\"images/spacer.gif\" width=\"24\" height=\"10\">";
							$vert_lines.="</td>";
						}
					}
					else
					{
						$vert_lines.="<td>";
						$vert_lines.="<img src=\"images/spacer.gif\" width=\"24\" height=\"10\">";
						$vert_lines.="</td>";
					}
				}

				
				/* Build Tree */
        $tpl->set('d', 'IMAGE',     $img_folder);
        $tpl->set('d', 'CFGDATA',   $cfgdata);
        $tpl->set('d', 'BGCOLOR',   $bgcolor);
        $tpl->set('d', 'CAT',       $mstr);
        $tpl->set('d', 'MARK',			$markscript);
        $tpl->set('d', 'VERT',			$vert_lines);
        $tpl->set('d', 'INDENT',		$indent);
        
        // DIRECTION
				$tpl->set('d', 'DIRECTION', 'dir="' . $text_direction . '"');
        
        $tpl->next();

    } // end if have_perm

} // end while


$tpl->set('s', 'SID', $sess->id);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_str_overview']);
?>
