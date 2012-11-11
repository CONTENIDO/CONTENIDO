<?php

/**
 * description: copyright notice
 *
 * @package Module
 * @subpackage content_copyright_notice
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

$configIdart = getEffectiveSetting('footer_config', 'idart', 0);

if (0< $configIdart) {

    $article = new cApiArticleLanguage($configIdart, true);
    $text = $article->getContent('CMS_HTML', 1);

    // use smarty template to output header text
    $tpl = Contenido_SmartyWrapper::getInstance();
    global $force;
    if (1 == $force) {
        $tpl->clearAllCache();
    }
    $tpl->assign('text', $text);
    $tpl->display('content_copyright_notice/template/get.tpl');

}

?>