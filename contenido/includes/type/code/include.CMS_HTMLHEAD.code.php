<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_HTMLHEAD code
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


$tmp = $a_content['CMS_HTMLHEAD'][$val];
$tmp = urldecode($tmp);

cInclude('includes', 'functions.lang.php');

if ($edit) {
    if ($tmp == '') {
        $tmp = '&nbsp;';
    }

    $insiteEditingDIV = new cHTMLDiv();
    $insiteEditingDIV->setId('HTMLHEAD_' . $_typeItem->idtype . '_' . $val);
    $insiteEditingDIV->setEvent('Focus', "this.style.border='1px solid #bb5577';");
    $insiteEditingDIV->setEvent('Blur', "this.style.border='1px dashed #bfbfbf';");
    $insiteEditingDIV->setStyleDefinition('border', '1px dashed #bfbfbf');
    $insiteEditingDIV->setStyleDefinition('direction', langGetTextDirection($lang));
    $insiteEditingDIV->updateAttributes(array('contentEditable' => 'true'));
    $insiteEditingDIV->setContent('_REPLACEMENT_');

    // Edit anchor and image
    $editLink = $sess->url("front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_HTMLHEAD&typenr=$val");
    $editAnchor = new cHTMLLink();
    $editAnchor->setClass('CMS_HTMLHEAD_'.$val.'_EDIT CMS_LINK_EDIT');
    $editAnchor->setLink("javascript:setcontent('$idartlang','" . $editLink . "');");

    $editButton = new cHTMLImage();
    $editButton->setSrc($cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_edithead.gif');
    $editButton->setBorder(0);
    $editButton->setStyleDefinition('margin-right', '2px');

    $editAnchor->setContent($editButton);

    // Save anchor and image
    $saveAnchor = new cHTMLLink();
    $saveAnchor->setClass('CMS_HTMLHEAD_' . $val . '_SAVE CMS_LINK_SAVE');
    $saveAnchor->setLink("javascript:setcontent('$idartlang','0')");

    $saveButton = new cHTMLImage();
    $saveButton->setSrc($cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_ok.gif');
    $saveButton->setBorder(0);

    $saveAnchor->setContent($saveButton);

    // Process for output with echo
    $finalEditButton = $editAnchor->render();

    $finalEditingDiv = $insiteEditingDIV->render();

    $finalEditingDiv = str_replace('_REPLACEMENT_', $tmp, $finalEditingDiv);

    $finalSaveButton = $saveAnchor->render();

    $tmp = $finalEditingDiv . $finalEditButton . $finalSaveButton;
}

$tmp = addslashes($tmp);
$tmp = str_replace("\\'", "'", $tmp);
$tmp = str_replace("\$", '\\$', $tmp);

?>