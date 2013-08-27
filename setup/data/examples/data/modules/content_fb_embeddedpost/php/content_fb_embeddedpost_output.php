<?php

/**
 * Description: Facebook embedded post
 *
 * @package Module
 * @subpackage ContentFbEmbedded Post
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_POST_URL");
    $content = "CMS_LINKEDITOR[200]";
} else {
    $label = NULL;
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