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
    $tpl = new cTemplate();

    $tpl->set('s', 'url', $url);
    $tpl->set('s', 'site', $site);
    $tpl->generate('piwik.html');
}

?>