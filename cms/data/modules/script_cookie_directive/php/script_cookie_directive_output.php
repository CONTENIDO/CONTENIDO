<?php

global $allowCookie;

$tpl = Contenido_SmartyWrapper::getInstance();

// Check global value
if (!isset($allowCookie)) {

    $allowCookie = 0;

}

// Check value in get, if js is off
if (array_key_exists('acceptCookie', $_GET)) {

    $allowCookie = $_GET['acceptCookie'] === '1'? 1 : 0;
    setcookie('allowCookie', $allowCookie);

} elseif (array_key_exists('allowCookie', $_COOKIE)) {

    // Check value in cookies
    $allowCookie = $_COOKIE['allowCookie'] === '1'? 1 : 0;

}

// Save value
$session = cRegistry::getSession();
$session->register('allowCookie');

// Show notify
if ($allowCookie !== 1) {

    $idart = cRegistry::getArticleId();
    $pageUrl = 'front_content.php?idart=' . $idart . '&acceptCookie=1';

    $tpl->assign('pageUrl', $pageUrl);

    $tpl->display('get.tpl');

}

?>