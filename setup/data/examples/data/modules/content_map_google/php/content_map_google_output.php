<?php

/**
 * description: google map
 *
 * @package Module
 * @subpackage ContentMapGoogle
 * @author alexander.scheider@4fb.de
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$tpl = cSmartyFrontend::getInstance();
$tpl->assign('isBackendEditMode', cRegistry::isBackendEditMode());
$tpl->assign('trans', array(
    'header' => mi18n("HEADER"),
    'address' => mi18n("ADDRESS"),
    'latitude' => mi18n("LATITUDE"),
    'longitude' => mi18n("LONGITUDE"),
    'markerTitle' => mi18n("MARKER_TITLE"),
    'wayDescription' => mi18n("WAY_DESCRIPTION")
));
$tpl->assign('header', "CMS_HTMLHEAD[600]");
$tpl->assign('address', "CMS_HTML[601]");
$tpl->assign('lat', "CMS_TEXT[602]");
$tpl->assign('lng', "CMS_TEXT[603]");
$tpl->assign('googlekey', conHtmlEntityDecode(getEffectiveSetting('maps', 'googlekey')));
$tpl->assign('markerTitle', "CMS_HTML[604]");
$tpl->assign('way', "CMS_HTML[605]");
$tpl->display('get.tpl');

?>