<?php

/**
 * Backend action file con_meta_deletetype
 *
 * @package Core
 * @subpackage Backend
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if ($perm->have_perm_area_action($area, "con_meta_deletetype") || $perm->have_perm_area_action_item($area, "con_meta_deletetype", $idcat)) {

        $metaTagColl = new cApiMetaTagCollection();
        $metaTagColl->select('idmetatype=' . cSecurity::toInteger($idmetatype));

        // If CONTENIDO found only one entry, delete this metatag
        if ($metaTagColl->count() == 1) {
            $metaTag = $metaTagColl->next();
            $metaTagColl->delete($metaTag->getField('idmetatag'));
        }

        // Delete metatype
        $metaTypeColl = new cApiMetaTypeCollection();
        $metaTypeColl->delete(cSecurity::toInteger($idmetatype));

} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
