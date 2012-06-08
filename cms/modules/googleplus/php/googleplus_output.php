<?php
/**
 * Description: Google Plus
 *
 * @version   1.0.0
 * @author    unknown
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

//url
$url = "CMS_VALUE[0]";

//layout standard, small, medium, tall
$buttonLayout = "CMS_VALUE[1]";

// show counter
$showCount = "CMS_VALUE[3]";

$tpl = new Template();
if ($buttonLayout == 'standard') {
    $tpl->set('s', 'LAYOUT', '');
} else {
    $tpl->set('s', 'LAYOUT', ' size="' . $buttonLayout . '"');
}

if ($showCount) {
    $tpl->set('s', 'SHOW_COUNT', '');
} else {
    $tpl->set('s', 'SHOW_COUNT', ' annotation="none"');
}

if ($url != '') {
    $tpl->set('s', 'URL', ' url="' . urlencode($url) . '"');
} else {
    $tpl->set('s', 'URL', '');
}

$langObj = new cApiLanguage($lang);
$locale = $langObj->getProperty('language', 'code');

$tpl->set('s', 'LOCALE', $locale);
$tpl->generate('google_plus.html');

?>