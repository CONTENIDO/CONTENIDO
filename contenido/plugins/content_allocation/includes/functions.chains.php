<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Chains for Content Allocation
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend plugins
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id: functions.chains.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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