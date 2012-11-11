<?php

/**
 * description: social media configurator
 *
 * @package Module
 * @subpackage config_social_media
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

if (cRegistry::isBackendEditMode()) {

	$label = mi18n("LABEL_SOCIAL_MEDIA");

	// get links from content type TEXT with different indexes
    $items = array(
        'rss' => array(
            'name' => mi18n("NAME_RSS"),
            'link' => "CMS_TEXT[1]"
        ),
        'facebook' => array(
            'name' => mi18n("NAME_FACEBOOK"),
            'link' => "CMS_TEXT[2]"
        ),
        'googleplus' => array(
            'name' => mi18n("NAME_GOOGLEPLUS"),
            'link' => "CMS_TEXT[3]"
        ),
        'twitter' => array(
            'name' => mi18n("NAME_TWITTER"),
            'link' => "CMS_TEXT[4]"
        ),
        'youtube' => array(
            'name' => mi18n("NAME_YOUTUBE"),
            'link' => "CMS_TEXT[5]"
        ),
        'xing' => array(
            'name' => mi18n("NAME_XING"),
            'link' => "CMS_TEXT[6]"
        )
    );

    // use smarty template to output header text
    $tpl = Contenido_SmartyWrapper::getInstance();
    global $force;
    if (1 == $force) {
        $tpl->clearAllCache();
    }
    $tpl->assign('label', $label);
    $tpl->assign('items', $items);
    $tpl->display('config_social_media/template/get.tpl');

}

?>