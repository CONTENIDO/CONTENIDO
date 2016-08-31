<?php
/**
 * Description: Piwik Tracking
 *
 * @package Module
 * @subpackage ScriptTrackerPiwik
 * @author simon.sprankel@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$url = getEffectiveSetting('stats', 'piwik_url', '');
$site = getEffectiveSetting('stats', 'piwik_site', '');

if (0 < strlen(trim($url)) && 0 < strlen(trim($site)) && cRegistry::isTrackingAllowed() && !cRegistry::isBackendEditMode()) {
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('url', $url);
    $tpl->assign('site', $site);
    $tpl->display('get.tpl');
}

?>