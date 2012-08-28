<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_IMGEDIT code
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


$tmp = '';

if ($edit) {
    // show deprecation warning
    cDeprecated('Do not use CMS_IMGEDIT any more - use CMS_IMGEDITOR instead!');
    $cNotification = new Contenido_Notification();
    $notification = $cNotification->messageBox(Contenido_Notification::LEVEL_WARNING, 'Sie benutzen einen veralteten Content-Typen (CMS_IMGEDIT). Dieser Content-Typ wird in einer sp�teren Version von CONTENIDO nicht mehr unterst�tzt. Bitte wechseln Sie auf den neuen Content-Typen CMS_IMGEDITOR.');
    $notification = addslashes($notification);
    $notification = str_replace("\\'", "'", $notification);
    $notification = str_replace('\$', '\\$', $notification);
    $tmp .= $notification;

    $backendUrl = cRegistry::getBackendUrl();

    // Edit anchor and image
    $editLink = $sess->url($backendUrl . 'external/backendedit/' . "front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_IMG&typenr=$val&lang=$lang");
    $editAnchor = new cHTMLLink();
    $editAnchor->setClass('CMS_IMGEDIT_' . $val . '_EDIT CMS_LINK_EDIT');
    $editAnchor->setLink("javascript:setcontent('$idartlang','".$editLink."');");

    // Save all content
    $editButton = new cHTMLImage();
    $editButton->setSrc($backendUrl . $cfg['path']['images'] . 'but_editimage.gif');
    $editButton->setBorder(0);

    $editAnchor->setContent($editButton);

    // Process for output with echo
    $finalEditButton = $editAnchor->render();
    $finalEditButton = addslashes($finalEditButton);
    $finalEditButton = str_replace("\\'", "'", $finalEditButton);

    $tmp = $tmp . $finalEditButton;
}

?>