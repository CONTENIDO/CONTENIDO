<?php
/**
 * Description: Piwik Tracking
 *
 * @package Module
 * @subpackage ScriptTrackerPiwik
 * @author simon.sprankel@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

$url = getEffectiveSetting('stats', 'piwik_url', '');
$site = getEffectiveSetting('stats', 'piwik_site', '');

if (0 < cString::getStringLength(trim($url)) && 0 < cString::getStringLength(trim($site)) && cRegistry::isTrackingAllowed() && !cRegistry::isBackendEditMode()) {
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('url', $url);
    $tpl->assign('site', $site);
    $tpl->display('get.tpl');
}

?>