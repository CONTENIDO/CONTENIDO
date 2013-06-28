<?php
/**
 * Description: Cookie Directive
 *
 * @package Module
 * @subpackage ScriptCookieDirective
 * @version SVN Revision $Rev:$
 *
 * @author ilia.schwarz
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!$contenido) {

    $session = cRegistry::getSession();

    if (array_key_exists('acceptCookie', $_GET)) {
        // Check value in get, if js is off
        $allowCookie = $_GET['acceptCookie'] === '1'? 1 : 0;
        setcookie('allowCookie', $allowCookie);

        // Save value
        $session->register('allowCookie');
    } elseif (array_key_exists('allowCookie', $_COOKIE)) {
        // Check value in cookies
        $allowCookie = $_COOKIE['allowCookie'] === '1'? 1 : 0;

        // Save value
        $session->register('allowCookie');
    }

    // Show notify
    if (!isset($allowCookie)) {

        $tpl = cSmartyFrontend::getInstance();

        // build translations
        $tpl->assign('trans', array(
            'title' => mi18n("TITLE"),
            'infoText' => mi18n("INFOTEXT"),
            'userInput' => mi18n("USERINPUT"),
            'accept' => mi18n("ACCEPT"),
            'decline' => mi18n("DECLINE")
        ));

        // build accept url
        $tpl->assign('pageUrlAccept', cUri::getInstance()->build(array(
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId(),
            'acceptCookie' => 1
        ), true));

        // build deny url
        $tpl->assign('pageUrlDeny', cUri::getInstance()->build(array(
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId(),
            'acceptCookie' => 0
        ), true));

        $tpl->display('get.tpl');

    }
}
?>