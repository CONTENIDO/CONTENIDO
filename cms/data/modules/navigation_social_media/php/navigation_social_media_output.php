<?php

/**
 * description: social media links
 *
 * @package Module
 * @subpackage navigation_social_media
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$configIdart = getEffectiveSetting('footer_config', 'idart', 0);

if (0< $configIdart) {

    $article = new cApiArticleLanguage($configIdart, true);

    $url = array(
        'rss' => $article->getContent('CMS_TEXT', 1),
        'facebook' => $article->getContent('CMS_TEXT', 2),
        'googleplus' => $article->getContent('CMS_TEXT', 3),
        'twitter' => $article->getContent('CMS_TEXT', 4),
        'youtube' => $article->getContent('CMS_TEXT', 5),
        'xing' => $article->getContent('CMS_TEXT', 6)
    );

    // use smarty template to output header text
    $tpl = Contenido_SmartyWrapper::getInstance();
    global $force;
    if (1 == $force) {
        $tpl->clearAllCache();
    }
    $tpl->assign('url', $url);
    $tpl->display('get.tpl');

}

?>