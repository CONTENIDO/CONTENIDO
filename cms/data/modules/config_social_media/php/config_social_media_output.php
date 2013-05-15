<?php

/**
 * description: social media configurator
 *
 * @package Module
 * @subpackage ConfigSocialMedia
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (cRegistry::isBackendEditMode()) {

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
        'xing' => array(
            'name' => mi18n("NAME_XING"),
            'link' => "CMS_TEXT[5]"
        )
    );

    // use smarty template to output header text
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('label', mi18n("LABEL_SOCIAL_MEDIA"));
    $tpl->assign('items', $items);
    $tpl->display('get.tpl');
}

?>