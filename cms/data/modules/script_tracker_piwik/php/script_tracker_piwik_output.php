<?php
/**
 * Description: Piwik Tracking
 *
 * @package Module
 * @subpackage content_header_first
 * @version SVN Revision $Rev:$
 * @author unkown
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

$url  = getEffectiveSetting('stats', 'piwik_url', '');
$site = getEffectiveSetting('stats', 'piwik_site', '');

if ($url != '' && $site != '' && cRegistry::isTrackingAllowed()) {
	$tpl = Contenido_SmartyWrapper::getInstance();
	global $force;
	if (1 == $force) {
	    $tpl->clearAllCache();
	}
    $tpl->assign('url', $url);
    $tpl->assign('site', $site);
    $tpl->display('script_tracker_piwik/template/get.tpl');
}

?>