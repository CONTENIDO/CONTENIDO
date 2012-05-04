<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Chains for Tagging
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Plugins
 * @subpackage Tagging
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id: functions.chains.php 2101 2012-04-03 12:46:11Z mischa.holz $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

function pica_RegisterCustomTab ()
{
	return array("con_tagging");	
}

function pica_GetCustomTabProperties ($sIntName)
{
	if ($sIntName == "con_tagging")
	{
		return array("con_tagging", "con_edit", "");
	}	
}

function pica_ArticleListActions ($aActions)
{
	$aTmpActions["con_tagging"] = "con_tagging";
	
	return $aTmpActions + $aActions;
}

function pica_RenderArticleAction ($idcat, $idart, $idartlang, $actionkey)
{
	global $sess;
	
	if ($actionkey == "con_tagging")
	{
 		return '<a title="'.i18n("Tagging", 'tagging').'" alt="'.i18n("Tagging", 'taging').'" href="'.$sess->url('main.php?area=con_tagging&action=con_edit&idart='.$idart.'&idartlang='.$idartlang.'&idcat='.$idcat.'&frame=4').'"><img src="plugins/tagging/images/call_contentallocation.gif"></a>';
 	
	} else {
		return "";	
	}
}
?>