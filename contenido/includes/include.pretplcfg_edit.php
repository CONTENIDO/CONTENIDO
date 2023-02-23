<?php

/**
 * This file contains the backend page for editing the template pre configuration.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $idtpl, $notification;

if (!isset($idtpl)) {
    $idtpl = 0;
}
if (!isset($idtplcfg)) {
    $idtplcfg = 0;
}

if ($idtplcfg == 0) {
    // Load template configuration for current template, create it if not done before
    $tplItem = new cApiTemplate($idtpl);
    $idtplcfg = $tplItem->get('idtplcfg');

    if ($idtplcfg == 0) {
        // Create new template configuration entry
        $tplConfColl = new cApiTemplateConfigurationCollection();
        $tplConf = $tplConfColl->create($idtpl);
        $idtplcfg = $tplConf->get('idtplcfg');

        $tplItem->set('idtplcfg', $idtplcfg);
        $tplItem->store();
    }
}

// Do we have $idtpl, $idtplcfg, and is form send?
if ($idtpl != 0 && $idtplcfg != 0 && isset($_POST) && count($_POST) > 0) {
    tplProcessSendContainerConfiguration($idtpl, $idtplcfg, $_POST);
    $notification->displayNotification(cGuiNotification::LEVEL_OK, i18n("Saved changes successfully!"));
}
