<?php

/**
 * description: social media links
 *
 * @package    Module
 * @subpackage NavigationSocialMedia
 * @author     marcus.gnass@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$configIdart = getEffectiveSetting('footer_config', 'idart', 0);

if (0 < $configIdart) {
    $article = new cApiArticleLanguage($configIdart);

    $url = [
        'rss' => $article->getContent('CMS_TEXT', 1),
        'facebook' => $article->getContent('CMS_TEXT', 2),
        'googleplus' => $article->getContent('CMS_TEXT', 3),
        'twitter' => $article->getContent('CMS_TEXT', 4),
        'xing' => $article->getContent('CMS_TEXT', 5),
    ];

    // use smarty template to output header text
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('url', $url);
    $tpl->display('get.tpl');
}

?>