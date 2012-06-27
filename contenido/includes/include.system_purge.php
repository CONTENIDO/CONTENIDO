<?php

/**
 * Project: CONTENIDO
 *
 * Description:
 * CONTENIDO Purge include file to reset some datas(con_code, con_cat_art) and files (log, cache, history)
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Munkh-Ulzii Balidar
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.8.12
 *
 * {@internal
 *   created  2010-01-11
 *   modified 2010-08-03, added check for the permission
 *     modified 2010-12-14, Munkh-Ulzii Balidar, changed the select box name
 *                            to 'purge_clients' for security, see [CON-375]
 *   $Id$:
 * }
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('classes', 'class.purge.php');

$tpl->reset();

$iClientSelectSize = 4;
$oClient = new cApiClientCollection();
$aAvailableClient = $oClient->getAvailableClients();

$sInfoMsg = '';

$action = 'do_purge';
if (($action == "do_purge") && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $notification->displayNotification("error", i18n("Permission denied"));
} else {
    if (isset($_POST['send']) && $_POST['send'] == 'store') {
        $aClientToClear = array();
        $aClientName = array();

        if (isset($_POST['selectClient']) && $_POST['selectClient'] == 'all') {
            // selected all clients

            foreach ($aAvailableClient as $iClientId => $aClient) {
                $aClientToClear[] = $iClientId;
            }

        } else if (isset($_POST['purge_clients']) && is_array($_POST['purge_clients']) && count($_POST['purge_clients']) > 0) {
            // selected multiple clients
            foreach ($_POST['purge_clients'] as $iClientId) {
                $aClientToClear[] = (int)$iClientId;
            }

        } else if (isset($_POST['purge_clients']) && (int)$_POST['purge_clients'] > 0) {
            // selected single client
            $aClientToClear[] = (int)$_POST['purge_clients'];
        }

        $oPurge = new Purge($db, $cfg, $cfgClient);
        if (count($aClientToClear) > 0) {
            // execute the selected actions

            foreach ($aAvailableClient as $iClientId => $aClient) {
                $aClientName[$iClientId] = $aClient['name'];
            }

            $bError = false;
            $sErrorMsg = '';

            foreach ($aClientToClear as $iClientId) {
                $iClientId = (int) $iClientId;
                if ($iClientId > 0) {
                    if (isset($_POST['conCode']) && $_POST['conCode'] == 1) {
                        if (!$oPurge->resetClientConCode($iClientId)) {
                            $bError = true;
                            $sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' .
                                   sprintf(i18n("The entries of %s table are not deleted!"), $cfg['tab']['code']) . "<br />";
                        }
                    }

                    if (isset($_POST['conCatArt']) && $_POST['conCatArt'] == 1) {
                        if (!$oPurge->resetClientConCatArt($iClientId)) {
                            $bError = true;
                            $sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' .
                                   sprintf(i18n("The %s is not updated!"), $cfg['tab']['cat_art']) . "<br />";
                        }
                    }

                    if (isset($_POST['clientCache']) && $_POST['clientCache'] == 1) {
                        if (!$oPurge->clearClientCache($iClientId)) {
                            $bError = true;
                            $sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' .
                                   i18n("The cache is not deleted!") . "<br />";
                        }
                    }

                    if (isset($_POST['clientLog']) && $_POST['clientLog'] == 1) {
                        if (!$oPurge->clearClientLog($iClientId)) {
                            $bError = true;
                            $sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' .
                                   i18n("The log is not deleted!") . "<br />";
                        }
                    }

                    if (isset($_POST['clientHistory']) && $_POST['clientHistory'] == 1) {
                        $bKeep = ($_POST['keepHistory'] == 1 && (int) $_POST['keepHistoryNumber'] > 0) ? true : false;
                        if (!$oPurge->clearClientHistory($iClientId, $bKeep, (int) $_POST['keepHistoryNumber'])) {
                            $bError = true;
                            $sErrorMsg .= i18n("Client ") . $aClientName[$iClientId] . ': ' .
                                   i18n("The history is not deleted!") . "<br />";
                        }
                    }

                    if ($sErrorMsg != '') {
                        $sErrorMsg .= "<br />";
                    }

                }
            }
        }

        $sContenido = i18n("CONTENIDO: ");

        if (isset($_POST['conInuse']) && $_POST['conInuse'] == 1) {
            if (!$oPurge->resetConInuse()) {
                $bError = true;
                $sErrorMsg .= sprintf(i18n("The entries of %s table are not deleted!"), $cfg['tab']['inuse']) . "<br />";
            }
        }

        if (isset($_POST['conPHPLibActiveSession']) && $_POST['conPHPLibActiveSession'] == 1) {
            if (!$oPurge->resetPHPLibActiveSession()) {
                $bError = true;
                $sErrorMsg .= sprintf(i18n("The entries of %s table are not deleted!"), $cfg['tab']['phplib_active_sessions']) .
                     "<br />";
            }
        }

        if (isset($_POST['conLog']) && $_POST['conLog'] == 1) {
            if (!$oPurge->clearConLog()) {
                $bError = true;
                $sErrorMsg .= i18n("The CONTENIDO log is not cleaned!") . "<br />";
            }
        }

        if (isset($_POST['conCache']) && $_POST['conCache'] == 1) {
            if (!$oPurge->clearConCache()) {
                $bError = true;
                $sErrorMsg .= i18n("The CONTENIDO cache is not deleted!") . "<br />";
            }
        }

        if (isset($_POST['conCronjobs']) && $_POST['conCronjobs'] == 1) {
            if (!$oPurge->clearConCronjob()) {
                $bError = true;
                $sErrorMsg .= i18n("The CONTENIDO cronjobs are not cleaned!") . "<br />";
            }
        }

        if ($bError === false || $sErrorMsg == '') {
            $sInfoMsg = $notification->returnNotification("info", i18n("The changes were successfully executed."));
        } else {
            $sErrorComplete = i18n("The changes were not all successfully completed.") . "<br /><br />" . $sErrorMsg;
            $sInfoMsg = $notification->returnNotification("error", $sErrorComplete);
        }
    }

    $oHtmlSelectHour = new  cHTMLSelectElement ('purge_clients[]', '', 'client_select');

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

    $tpl->set('s', 'TITLE', i18n("System purge"));
    $tpl->set('s', 'ERR_MSG_SELECT_CLIENT', i18n("No Client selected!"));

    $tpl->set('s', 'CONTENIDO', $contenido);

    $tpl->set('s', 'GROUP_CLIENT', i18n("Client"));
    $tpl->set('s', 'CLIENT_SELECT_ALL', i18n("all clients"));
    $tpl->set('s', 'CLIENT_SELECT', i18n("from list"));
    $tpl->set('s', 'CLIENT_CHOOSE', i18n("Select clients"));
    $tpl->set('s', 'CON_CODE', sprintf(i18n("Reset the table %s"), $cfg['tab']['code']));
    $tpl->set('s', 'CON_CAT_ART', sprintf(i18n("Activate the code generation in %s"), $cfg['tab']['code'], $cfg['tab']['cat_art']));
    $tpl->set('s', 'CON_INUSE', sprintf(i18n("Reset the table %s"), $cfg['tab']['inuse']));
    $tpl->set('s', 'CLIENT_CACHE', i18n("Clear client cache"));
    $tpl->set('s', 'CLIENT_LOG', i18n("Clear client log file"));
    $tpl->set('s', 'CLIENT_HISTORY', i18n("Clear client history"));
    $tpl->set('s', 'NUMBER_OF_HISTORY', i18n("Keep last histories"));

    $tpl->set('s', 'GROUP_CONTENIDO', i18n("CONTENIDO"));
    $tpl->set('s', 'CON_LOG', i18n("Clear CONTENIDO log file"));
    $tpl->set('s', 'CON_ACTIVE_SESSION', sprintf(i18n("Reset the table %s"), $cfg['tab']['phplib_active_sessions']));
    $tpl->set('s', 'CON_CACHE', i18n("Clear CONTENIDO cache"));
    $tpl->set('s', 'CON_CRONJOB', i18n("Reset cronjobs"));

    $tpl->set('s', 'BOX_TITLE', i18n("System purge"));
    $tpl->set('s', 'BOX_MESSAGE', i18n("These changes can not be cancelled.") . '<br /> <br />' . i18n("Do you really want to complete it?"));

    $tpl->set('s', 'INFO_MSG_BOX', $sInfoMsg);
    $tpl->set('s', 'ERR_MSG_BOX', $notification->returnNotification("error", ''));

    $tpl->set('s', 'ERR_MSG_NO_ACTION', i18n("No action selected!"));

    $tpl->set('s', 'SUBMIT_TEXT', i18n("Send"));
    $tpl->set('s', 'NO_CLIENT_SELECTED', i18n("Please select a client or all clients."));

    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['system_purge']);

}


?>