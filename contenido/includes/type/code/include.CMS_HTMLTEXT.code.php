<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_HTMLTEXT code
 *
 * NOTE: This file will be included by the code generator while processing CMS tags in layout.
 * It runs in a context of a function and requires some predefined variables!
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2012-02-14
 *
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


cInclude('includes', 'functions.lang.php');

$content = $a_content['CMS_HTMLTEXT'][$val];
$content = htmldecode($content);
$content = strip_tags($content);

$content = str_replace('&nbsp;', ' ', $content);

$content = conHtmlSpecialChars($content);
if ($content == '') {
    $content = '&nbsp;';
}

$content = nl2br($content);

if ($edit) {
    // show deprecation warning
    cDeprecated('Do not use CMS_HTMLTEXT any more - use CMS_TEXT instead!');
    $cNotification = new Contenido_Notification();
    $notification = $cNotification->messageBox(Contenido_Notification::LEVEL_WARNING, 'Sie benutzen einen veralteten Content-Typen (CMS_HTMLTEXT). Dieser Content-Typ wird in einer sp�teren Version von CONTENIDO nicht mehr unterst�tzt. Bitte wechseln Sie auf den neuen Content-Typen CMS_TEXT.');
    $tmp = $notification;

    $div = new cHTMLDiv();
    $div->setID('HTMLTEXT_' . $_typeItem->idtype . '_' . $val);
    $div->setEvent('focus', "this.style.border='1px solid #bb5577'");
    $div->setEvent('blur', "this.style.border='1px dashed #bfbfbf'");
    $div->appendStyleDefinition('border', '1px dashed #bfbfbf');
    $div->updateAttributes(array('contentEditable' => 'true'));
    $div->appendStyleDefinition('direction', langGetTextDirection($lang));

    $backendUrl = cRegistry::getBackendUrl();

    $editlink = new cHTMLLink();
    $editlink->setClass('CMS_HTMLTEXT_' . $val . '_EDIT CMS_LINK_EDIT');
    $editlink->setLink($sess->url($backendUrl . 'external/backendedit/' . "front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_HTMLTEXT&typenr=$val&lang=$lang"));

    $editimg = new cHTMLImage();
    $editimg->setSrc($backendUrl . $cfg['path']['images'] . 'but_edittext.gif');

    $savelink = new cHTMLLink();
    $savelink->setClass('CMS_HTMLTEXT_' . $val . '_SAVE  CMS_LINK_SAVE');
    $savelink->setLink("javascript:setcontent('$idartlang','0')");

    $saveimg = new cHTMLImage();
    $saveimg->setSrc($backendUrl . $cfg['path']['images'] . 'but_ok.gif');

    $savelink->setContent($saveimg);

    $editlink->setContent($editimg);

    $div->setContent($content);

    $tmp .= implode('', array($div->render(), $editlink->render(), ' ', $savelink->render()));
} else {
    $tmp = $content;
}

$tmp = addslashes($tmp);
$tmp = str_replace("\\'", "'", $tmp);
$tmp = str_replace('$', '\\$', $tmp);

?>