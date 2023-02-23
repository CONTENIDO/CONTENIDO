<?php

/**
 * Description: Cookie Directive
 *
 * @package Module
 * @subpackage ScriptCookieDirective
 * @author ilia.schwarz
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

if (!cRegistry::getBackendSessionId()) {

    $session = cRegistry::getSession();
    $params = session_get_cookie_params();

    if (array_key_exists('acceptCookie', $_GET)) {
        // Check value in get, if js is off
        $allowCookie = $_GET['acceptCookie'] === '1'? 1 : 0;
        setcookie('allowCookie', $allowCookie, 0, $params['path'], $params['domain'], $params['secure'], $params['httponly']);

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
        $url = cUri::getInstance();

        // build translations
        $tpl->assign('trans', [
            'title' => mi18n("TITLE"),
            'infoText' => mi18n("INFOTEXT"),
            'userInput' => mi18n("USERINPUT"),
            'accept' => mi18n("ACCEPT"),
            'decline' => mi18n("DECLINE")
        ]);

        // build accept url
        $acceptUrl = $url->appendParameters($url->build([
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId(),
            'acceptCookie' => 1
        ], true), $_GET);
        $tpl->assign('pageUrlAccept', $acceptUrl);

        // build deny url
        $denyUrl = $url->appendParameters($url->build([
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId(),
            'acceptCookie' => 0
        ], true), $_GET);
        $tpl->assign('pageUrlDeny', $denyUrl);

        $tpl->display('get.tpl');

    }
}

?>