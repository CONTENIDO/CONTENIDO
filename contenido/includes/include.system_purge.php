<?php

/**
 * Description: 
 * Contenido Purge include file to reset some datas(con_code, con_cat_art) and files (log, cache, history)
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Munkh-Ulzii Balidar
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.8.12
 *
 * $Id: 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('classes', 'class.purge.php');

$tpl->reset();

$iClientSelectSize = 4;

$sInfoMsg = '';

if (isset($_POST['send']) && $_POST['send'] == 'store') {
	$aClientToClear = array();
	$aClientName = array();
	
	// all or selected clients
	if (isset($_POST['selectClient']) && $_POST['selectClient'] == 'all') {
		$oClient = new Client();
		$aAvailableClient = $oClient->getAvailableClients();
		foreach ($aAvailableClient as $iClientId => $aClient) {
			$aClientToClear[] = $iClientId;
			$aClientName[$iClientId] = $aClient['name'];
		}
	} else if (is_array($_POST['client']) && count($_POST['client']) > 0){
		$aClientToClear = $_POST['client'];
		$oClient = new Client();
		$aAvailableClient = $oClient->getAvailableClients();
		foreach ($aAvailableClient as $iClientId => $aClient) {
			$aClientName[$iClientId] = $aClient['name'];
		}
	}
	
	$bError = false;
	$sErrorMsg = '';
	$oPurge = new Purge($db, $cfg, $cfgClient);
	
	foreach ($aClientToClear as $iClientId) {
		$iClientId = (int) $iClientId;
		if ($iClientId > 0) { 
			if (isset($_POST['conCode']) && $_POST['conCode'] == 1) {
				if (!$oPurge->resetClientConCode($iClientId)) {
					$bError = true;
					$sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' . i18n("The concode entries are not deleted!") . "<br />";
				}
			} 
			
			if (isset($_POST['conCatArt']) && $_POST['conCatArt'] == 1) {
				if (!$oPurge->resetClientConCatArt($iClientId)) {
					$bError = true;
					$sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' . i18n("The con_cat_art entries are not deleted!") . "<br />";
				}
			}
			
			if (isset($_POST['clientCache']) && $_POST['clientCache'] == 1) {
				if (!$oPurge->clearClientCache($iClientId)) {
					$bError = true;
					$sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' . i18n("The cache is not deleted!") . "<br />";
				}
			}
			
			if (isset($_POST['clientLog']) && $_POST['clientLog'] == 1) {
				if (!$oPurge->clearClientLog($iClientId)) {
					$bError = true;
					$sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' . i18n("The log is not deleted!") . "<br />";
				}
			}
			
			if (isset($_POST['clientHistory']) && $_POST['clientHistory'] == 1) {
				$bKeep = ($_POST['keepHistory'] == 1 && (int) $_POST['keepHistoryNumber'] > 0) ? true : false;
				if (!$oPurge->clearClientHistory($iClientId, $bKeep, (int) $_POST['keepHistoryNumber'])) {
					$bError = true;
					$sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' . i18n("The history is not deleted!") . "<br />";
				}
			}
			
			if ($sErrorMsg != '') {
				$sErrorMsg .= "<br />";
			}
			
		}
		
	} 
	
	$sContenido = i18n("Contenido: ");
	
	if (isset($_POST['conInuse']) && $_POST['conInuse'] == 1) {
		if (!$oPurge->resetConInuse()) {
			$bError = true;
			$sErrorMsg .= i18n("The entries of con_inuse table are not deleted!") . "<br />";
		}
	}
	
	if (isset($_POST['conPHPLibActiveSession']) && $_POST['conPHPLibActiveSession'] == 1) {
		if (!$oPurge->resetPHPLibActiveSession()) {
			$bError = true;
			$sErrorMsg .= i18n("The entries of con_phplib_active_sessions table are not deleted!") . "<br />";
		}
	}
	
	if (isset($_POST['conLog']) && $_POST['conLog'] == 1) {
		if (!$oPurge->clearConLog()) {
			$bError = true;
			$sErrorMsg .= i18n("The contenido log is not deleted!") . "<br />";
		}
	}
	
	if (isset($_POST['conCache']) && $_POST['conCache'] == 1) {
		if (!$oPurge->clearConCache()) {
			$bError = true;
			$sErrorMsg .= i18n("The contenido cache is not deleted!") . "<br />";
		}
	}
	
	if (isset($_POST['conCronjobs']) && $_POST['conCronjobs'] == 1) {
		if (!$oPurge->clearConCronjob()) {
			$bError = true;
			$sErrorMsg .= i18n("The contenido cronjobs are not cleaned!") . "<br />";
		}
	}
	
	if ($bError === false) {
		$sInfoMsg = $notification->returnNotification("info", mi18n("The changes were successfully executed."));
	} else {
		$sErrorComplete = mi18n("The changes were not all successfully completed.") . "<br /><br />" . $sErrorMsg;
		$sInfoMsg = $notification->returnNotification("error", $sErrorComplete);
	} 
		
} 

$oClient = new Client();
$aAvailableClient = $oClient->getAvailableClients();

$oHtmlSelectHour = new  cHTMLSelectElement ('client[]', '', 'client_select');

$i = 0;
foreach ($aAvailableClient as $iClientId => $aClient) {
	$oHtmlSelectOption = new cHTMLOptionElement($aClient['name'], $iClientId, false);
	$oHtmlSelectHour->addOptionElement($i, $oHtmlSelectOption);
	$i++;
}

$oHtmlSelectHour->setMultiselect();
$oHtmlSelectHour->setSize($iClientSelectSize);
$sSelectClient = $oHtmlSelectHour->toHtml();
$tpl->set('s', 'SELECT_CLIENT', $sSelectClient);	

$tpl->set('s', 'TITLE', i18n("System clean"));
$tpl->set('s', 'ERR_MSG_SELECT_CLIENT', i18n("It is not selected a client!"));	

$tpl->set('s', 'CONTENIDO', $contenido);	

$tpl->set('s', 'GROUP_CLIENT', i18n("Client"));	 
$tpl->set('s', 'CLIENT_SELECT_ALL', i18n("Select all clients"));	
$tpl->set('s', 'CLIENT_SELECT', i18n("Select clients from list"));	
$tpl->set('s', 'CLIENT_CHOOSE', i18n("Select clients"));	
$tpl->set('s', 'CON_CODE', i18n("Reset the table con_code"));	
$tpl->set('s', 'CON_CAT_ART', i18n("Reset the table con_cat_art"));	
$tpl->set('s', 'CON_INUSE', i18n("Reset the table con_inuse"));
$tpl->set('s', 'CLIENT_CACHE', i18n("Clear client cache"));	
$tpl->set('s', 'CLIENT_LOG', i18n("Clear client log file"));	
$tpl->set('s', 'CLIENT_HISTORY', i18n("Clear client history"));	
$tpl->set('s', 'NUMBER_OF_HISTORY', i18n("Keep last histories:"));	

$tpl->set('s', 'GROUP_CONTENIDO', i18n("Contenido"));	
$tpl->set('s', 'CON_LOG', i18n("Clear contenido log file"));	
$tpl->set('s', 'CON_ACTIVE_SESSION', i18n("Reset the table con_phplib_active_sessions"));	
$tpl->set('s', 'CON_CACHE', i18n("Clear contenido cache"));	
$tpl->set('s', 'CON_CRONJOB', i18n("Reset cronjobs"));	

$tpl->set('s', 'BOX_TITLE', i18n("System clear"));	
$tpl->set('s', 'BOX_MESSAGE', i18n("These changes can not be canceled. <br /> <br /> Do you really want to complete it?"));	
	
$tpl->set('s', 'INFO_MSG_BOX', $sInfoMsg);
$tpl->set('s', 'ERR_MSG_BOX', $notification->returnNotification("error", ''));

$tpl->set('s', 'SUBMIT_TEXT', i18n("Send"));

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['system_purge']);

?>