<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_SIMPLELINKEDIT code
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

// CMS_SIMPLELINKEDIT

$tmp = '';

if ($edit) {
    // Edit anchor and image
    $editLink = $sess->url("front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_SIMPLELINK&typenr=$val");
    $editAnchor = new cHTMLLink();
    $editAnchor->setClass('CMS_SIMPLELINKEDIT_' . $val . '_EDIT CMS_LINK_EDIT');
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

    $tmp = $finalEditButton;
}


?>