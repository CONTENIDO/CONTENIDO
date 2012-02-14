<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_HEAD code
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


$tmp = $a_content['CMS_HEAD'][$val];
$tmp = urldecode($tmp);
$tmp = htmlspecialchars($tmp);

if ($edit) {
    // Edit anchor and image
    $editLink = $sess->url("front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_HEAD&typenr=$val&lang=$lang");
    $editAnchor = new cHTMLLink();
    $editAnchor->setClass('CMS_HEAD_' . $val . '_EDIT CMS_LINK_EDIT');
    $editAnchor->setLink("javascript:setcontent('$idartlang','".$editLink."');");

    // Save all content
    $editButton = new cHTMLImage();
    $editButton->setSrc($cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_edithead.gif');
    $editButton->setBorder(0);
    $editButton->setStyleDefinition('margin-right', '2px');

    $editAnchor->setContent($editButton);

    // Process for output with echo
    $finalEditButton = $editAnchor->render();

    $tmp = $tmp . $finalEditButton;
}

$tmp = addslashes($tmp);
$tmp = str_replace("\\'", "'", $tmp);
$tmp = str_replace("\$", '\\$', $tmp);

?>