<?php

/**
 * This file contains the system purge backend page.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Munkh-Ulzii Balidar
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $contenido, $tpl, $notification;

cInclude('classes', 'class.purge.php');

$action = cRegistry::getAction();
$area = cRegistry::getArea();
$auth = cRegistry::getAuth();
$cfg = cRegistry::getConfig();
$perm = cRegistry::getPerm();

$tpl->reset();

$iClientSelectSize = 4;
$oClient = new cApiClientCollection();
$aAvailableClient = $oClient->getAccessibleClients();

$sInfoMsg = '';

$action = 'do_purge';
if (($action == 'do_purge') && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

if (isset($_POST['send']) && $_POST['send'] == 'store') {
    $aClientToClear = [];
    $aClientName = [];

    if (isset($_POST['selectClient']) && $_POST['selectClient'] == 'all') {
        // selected all clients

        foreach ($aAvailableClient as $iClientId => $aClient) {
            $aClientToClear[] = $iClientId;
        }

    } elseif (isset($_POST['purge_clients']) && is_array($_POST['purge_clients']) && count($_POST['purge_clients']) > 0) {
        // selected multiple clients
        foreach ($_POST['purge_clients'] as $iClientId) {
            $aClientToClear[] = (int)$iClientId;
        }

    } elseif (isset($_POST['purge_clients']) && (int)$_POST['purge_clients'] > 0) {
        // selected single client
        $aClientToClear[] = (int)$_POST['purge_clients'];
    }

    $bError = false;
    $sErrorMsg = '';

    $oPurge = new cSystemPurge();
    if (count($aClientToClear) > 0) {
        // execute the selected actions

        foreach ($aAvailableClient as $iClientId => $aClient) {
            $aClientName[$iClientId] = $aClient['name'];
        }

        foreach ($aClientToClear as $iClientId) {
            $iClientId = (int)$iClientId;
            $aCurrentClientCfg = cRegistry::getClientConfig($iClientId);
            if ($iClientId > 0) {
                if (isset($_POST['conCode']) && $_POST['conCode'] == 1) {
                    if (!$oPurge->resetClientConCode($iClientId)) {
                        $bError = true;
                        $sErrorMsg .= i18n('Client ') . $aClientName[$iClientId] . ': ' .
                            sprintf(i18n('The files in the cache folder %s are not deleted!'), $aCurrentClientCfg['code']['path']) . '<br>';
                    }
                }

                if (isset($_POST['conCatArt']) && $_POST['conCatArt'] == 1) {
                    if (!$oPurge->resetClientConCatArt($iClientId)) {
                        $bError = true;
                        $sErrorMsg .= i18n('Client ') . $aClientName[$iClientId] . ': ' .
                            sprintf(i18n('The %s is not updated!'), $cfg['tab']['cat_art']) . '<br>';
                    }
                }

                if (isset($_POST['clientCache']) && $_POST['clientCache'] == 1) {
                    if (!$oPurge->clearClientCache($iClientId)) {
                        $bError = true;
                        $sErrorMsg .= i18n('Client ') . $aClientName[$iClientId] . ': ' .
                            i18n('The cache is not deleted!') . '<br>';
                    }
                }

                if (isset($_POST['clientLog']) && $_POST['clientLog'] == 1) {
                    if (!$oPurge->clearClientLog($iClientId)) {
                        $bError = true;
                        $sErrorMsg .= i18n('Client ') . $aClientName[$iClientId] . ': ' .
                            i18n('The log is not deleted!') . '<br>';
                    }
                }

                if (isset($_POST['clientHistory']) && $_POST['clientHistory'] == 1) {
                    $bKeep = ($_POST['keepHistory'] == 1 && (int)$_POST['keepHistoryNumber'] > 0) ? true : false;
                    if (!$oPurge->clearClientHistory($iClientId, $bKeep, (int)$_POST['keepHistoryNumber'])) {
                        $bError = true;
                        $sErrorMsg .= i18n('Client ') . $aClientName[$iClientId] . ': ' .
                            i18n('The history is not deleted!') . '<br>';
                    }
                }

                if (isset($_POST['clearVersioning']) && $_POST['clearVersioning'] == 1) {
                    if (!$oPurge->clearClientContentVersioning($iClientId)) {
                        $bError = true;
                        $sErrorMsg .= i18n('Client ') . $aClientName[$iClientId] . ': ' .
                            i18n('The content versioning is not deleted!') . '<br>';
                    }
                }

                if ($sErrorMsg != '') {
                    $sErrorMsg .= '<br>';
                }

            }
        }
    }

    $sContenido = i18n('CONTENIDO: ');

    if (isset($_POST['conInuse']) && $_POST['conInuse'] == 1) {
        if (!$oPurge->resetConInuse()) {
            $bError = true;
            $sErrorMsg .= sprintf(i18n('The entries of %s table are not deleted!'), $cfg['tab']['inuse']) . '<br>';
        }
    }

    if (isset($_POST['conLog']) && $_POST['conLog'] == 1) {
        if (!$oPurge->clearConLog()) {
            $bError = true;
            $sErrorMsg .= i18n('The CONTENIDO log is not cleaned!') . '<br>';
        }
    }

    if (isset($_POST['conCache']) && $_POST['conCache'] == 1) {
        if (!$oPurge->clearConCache()) {
            $bError = true;
            $sErrorMsg .= i18n('The CONTENIDO cache is not deleted!') . '<br>';
        }
    }

    if (isset($_POST['conCronjobs']) && $_POST['conCronjobs'] == 1) {
        if (!$oPurge->clearConCronjob()) {
            $bError = true;
            $sErrorMsg .= i18n('The CONTENIDO cronjobs are not cleaned!') . '<br>';
        }
    }

    if ($bError === false || $sErrorMsg == '') {
        $sInfoMsg = $notification->returnNotification('ok', i18n('The changes were successfully executed.'));
    } else {
        $sErrorComplete = i18n('The changes were not all successfully completed.') . '<br><br>' . $sErrorMsg;
        $sInfoMsg = $notification->returnNotification('error', $sErrorComplete);
    }
}

$oHtmlSelectHour = new  cHTMLSelectElement ('purge_clients[]', '', 'client_select');

foreach ($aAvailableClient as $iClientId => $aClient) {
    $oHtmlSelectOption = new cHTMLOptionElement(conHtmlSpecialChars($aClient['name']), $iClientId, false);
    $oHtmlSelectHour->appendOptionElement($oHtmlSelectOption);
}

$oHtmlSelectHour->setMultiselect();
$oHtmlSelectHour->setSize($iClientSelectSize);
$sSelectClient = $oHtmlSelectHour->toHtml();
$tpl->set('s', 'SELECT_CLIENT', $sSelectClient);

$tpl->set('s', 'TITLE', i18n('System purge'));
$tpl->set('s', 'ERR_MSG_SELECT_CLIENT', i18n('No Client selected!'));

$tpl->set('s', 'CONTENIDO', $contenido);

$tpl->set('s', 'GROUP_CLIENT', i18n('Client'));
$tpl->set('s', 'CLIENT_SELECT_ALL', i18n('all clients'));
$tpl->set('s', 'CLIENT_SELECT', i18n('from list'));
$tpl->set('s', 'CLIENT_CHOOSE', i18n('Select clients'));
$tpl->set('s', 'CON_CODE', i18n('Delete the code cache'));
$tpl->set('s', 'CON_CAT_ART', i18n('Force code generation'));
$tpl->set('s', 'CON_INUSE', sprintf(i18n('Reset the table %s'), $cfg['tab']['inuse']));
$tpl->set('s', 'CLIENT_CACHE', i18n('Clear client cache'));
$tpl->set('s', 'CLIENT_LOG', i18n('Clear client log file'));
$tpl->set('s', 'CLIENT_HISTORY', i18n('Clear client style history'));
$tpl->set('s', 'NUMBER_OF_HISTORY', i18n('Keep last style histories'));
$tpl->set('s', 'CLEAR_CONTENT_VERSIONING', i18n('Delete content versions'));

$tpl->set('s', 'GROUP_CONTENIDO', i18n('CONTENIDO'));
$tpl->set('s', 'CON_LOG', i18n('Clear CONTENIDO log file'));
$tpl->set('s', 'CON_CACHE', i18n('Clear CONTENIDO cache'));
$tpl->set('s', 'CON_CRONJOB', i18n('Reset cronjobs'));

$tpl->set('s', 'BOX_TITLE', i18n('System purge'));
$tpl->set('s', 'BOX_MESSAGE', i18n('These changes can not be cancelled.') . '<br> <br>' . i18n('Do you really want to complete it?'));

$tpl->set('s', 'INFO_MSG_BOX', $sInfoMsg);
$tpl->set('s', 'ERR_MSG_BOX', $notification->returnNotification('error', ''));

$tpl->set('s', 'ERR_MSG_NO_ACTION', i18n('No action selected!'));

$tpl->set('s', 'SUBMIT_TEXT', i18n('Send'));
$tpl->set('s', 'NO_CLIENT_SELECTED', i18n('Please select a client or all clients.'));

if (cString::findFirstPos($auth->auth['perm'], 'sysadmin') === false) {
    $tpl->set('s', 'DEACTIVATED', 'disabled');
} else {
    $tpl->set('s', 'DEACTIVATED', '');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['system_purge']);
