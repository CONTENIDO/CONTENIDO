<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_LINKDESCR code
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


$tmp = $a_content['CMS_LINKDESCR'][$val];
$tmp = htmlspecialchars($tmp);
$tmp = str_replace("'", "\'", $tmp);

if ($edit) {
    // Edit anchor and image
    $editLink = $sess->url("front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_LINK&typenr=$val");
    $editAnchor = new cGuiLink();
    $editAnchor->setClass('CMS_LINKDESCR_' . $val . '_EDIT CMS_LINK_EDIT');
    $editAnchor->setLink("javascript:setcontent('$idartlang','".$editLink."');");

    // Save all content
    $editButton = new cHTMLImage();
    $editButton->setSrc($cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_editlink.gif');
    $editButton->setBorder(0);
    $editAnchor->setContent($editButton);

    // Process for output with echo
    $finalEditButton = $editAnchor->render();
    $finalEditButton = addslashes($finalEditButton);
    $finalEditButton = str_replace("\\'", "'", $finalEditButton);

    $tmp = $tmp . $finalEditButton;
}

?>