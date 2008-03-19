<?php

function pica_RegisterCustomTab ()
{
	return array("con_contentallocation");	
}

function pica_GetCustomTabProperties ($sIntName)
{
	if ($sIntName == "con_contentallocation")
	{
		return array("con_contentallocation", "con_edit", "");
	}	
}

function pica_ArticleListActions ($aActions)
{
	$aTmpActions["con_contentallocation"] = "con_contentallocation";
	
	return $aTmpActions + $aActions;
}

function pica_RenderArticleAction ($idcat, $idart, $idartlang, $actionkey)
{
	global $sess;
	
	if ($actionkey == "con_contentallocation")
	{
 		return '<a title="'.i18n("Content Allocation").'" alt="'.i18n("Content Allocation").'" href="'.$sess->url('main.php?area=con_contentallocation&action=con_edit&idart='.$idart.'&idartlang='.$idartlang.'&idcat='.$idcat.'&frame=4').'"><img src="plugins/content_allocation/images/call_contentallocation.gif"></a>';
 	
	} else {
		return "";	
	}
}
?>