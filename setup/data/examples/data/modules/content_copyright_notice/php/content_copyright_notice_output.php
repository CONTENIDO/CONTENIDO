<?php

/**
 * description: copyright notice
 *
 * @package    Module
 * @subpackage ContentCopyrightNotice
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

    $text = $article->getContent('CMS_HTML', 1);
    $text = str_replace('{year}', date('Y'), $text);

    // use smarty template to output header text
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('text', $text);
    $tpl->display('get.tpl');
}

?>