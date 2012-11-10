<?php
/**
 * Description: Piwik output
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

$url  = getEffectiveSetting('stats', 'piwik_url', '');
$site = getEffectiveSetting('stats', 'piwik_site', '');

if ($url != '' && $site != '') {
    $tpl = new cTemplate();

    $tpl->set('s', 'url', $url);
    $tpl->set('s', 'site', $site);
    $tpl->generate('piwik.html');
}

?>