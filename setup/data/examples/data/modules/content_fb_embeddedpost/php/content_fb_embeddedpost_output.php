<?php

/**
 * Description: Facebook embedded post
 *
 * Example URLs:
 * https://www.facebook.com/cms.contenido/posts/567068046687531
 * https://www.facebook.com/photo.php?fbid=567068046687531&amp;set=a.567067956687540.1073741828.153315271396146&amp;type=1
 *
 * @package Module
 * @subpackage ContentFbEmbedded Post
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_POST_URL");
    $content = "CMS_LINKEDITOR[200]";
} else {
    $label = '';
    // get URL from content type
    $url = "CMS_LINK[200]";
    // $url = 'https://www.facebook.com/jollife/posts/492440004166424';
    if (in_array(getEffectiveSetting('fb-sdk', 'html5'), explode('|', '1|true|on'))) {
        // HTML5 style (preferred)
        $content = '<div class="fb-post" data-href="' . $url . '"></div>';
    } else {
        // XHTML style (requires <html xmlns:fb="http://ogp.me/ns/fb#">)
        $content = '<fb:post href="' . $url . '"></fb:post>';
    }
}

// render smarty template
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label', $label);
$tpl->assign('content', $content);
$tpl->display('get.tpl');

?>