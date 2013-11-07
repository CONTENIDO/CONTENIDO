<?php
/**
 * This file contains the backend page for editing the template pre configuration.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idtplcfg)) {
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

if (isset($idtplcfg)) {
    tplProcessSendContainerConfiguration($idtpl, $idtplcfg, $_POST);

    // Is form send
    if ($x > 0) {
        $notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n("Saved changes successfully!"));
    }
}

?>