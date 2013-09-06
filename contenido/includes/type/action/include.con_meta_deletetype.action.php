<?php
/**
 * Backend action file con_meta_deletetype
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Mischa Holz
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if ($perm->have_perm_area_action("con", "con_meta_deletetype") || $perm->have_perm_area_action_item("con", "con_meta_deletetype", $idcat)) {
    $oMetaTypeColl = new cApiMetaTypeCollection();
    $oMetaTypeColl->delete((int)$idmetatype);

    $oMetaTagColl = new cApiMetaTagCollection();
    $metaTag = $oMetaTagColl->fetchByArtLangAndMetaType((int)$idartlang, (int)$idmetatype);
    $oMetaTagColl->delete($metaTag->getField('idmetatag'));
} else {
    $notification->displayNotification("error", i18n("Permission denied"));
}
