<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_SWF code
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
 *   $Id$:
 * }}
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


$tmp = $a_content['CMS_SWF'][$val];

if ($tmp == '' || $tmp == '0') {
    $tmp = '';
} else {
    if (is_numeric($tmp)) {
        $oUplItem = new cApiUpload((int) $tmp);
        if (false !== $oUplItem->get('dirname') && $oUplItem->get('filetype') == 'swf') {
            if (cApiDbfs::isDbfs($oUplItem->get('dirname'))) {
                $tmp_swf = $cfgClient[$client]['path']['htmlpath'] . 'dbfs.php?file=' . urlencode($oUplItem->get('dirname') . $oUplItem->get('filename'));
            } else {
                $tmp_swf = $cfgClient[$client]['path']['htmlpath'] . $cfgClient[$client]['upload'] . $oUplItem->get('dirname') . $oUplItem->get('filename');
            }

            $aImgSize = @getimagesize($tmp_swf);
            $width  = $aImgSize[0];
            $height = $aImgSize[1];

            $tmp = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
                        codebase="http://download.macromedia.com
                        /pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0"
                        width="' . $width . '" height="' . $height . '" id="movie" align="">
                        <param name="movie" value="' . $tmp_swf . '">
                        <embed src="' . $tmp_swf . '" quality="high" width="' . $width . '"
                            height="' . $height . '" name="movie" align="" type="application/x-shockwave-flash"
                            pluginspage="http://www.macromedia.com/go/getflashplayer">
                    </object>';
        }
    }
}

if ($edit) {
    // Edit anchor and image
    $editLink = $sess->url($cfg['path']['contenido_fullhtml'] . 'external/backendedit/' . "front_content.php?action=10&idcat=$idcat&idart=$idart&idartlang=$idartlang&type=CMS_SWF&typenr=$val");
    $editAnchor = new cGuiLink();
    $editAnchor->setClass('CMS_SWF_' . $val . '_EDIT CMS_LINK_EDIT');
    $editAnchor->setLink("javascript:setcontent('$idartlang','".$editLink."');");

    // Save all content
    $editButton = new cHTMLImage();
    $editButton->setSrc($cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] . 'but_editswf.gif');
    $editButton->setBorder(0);

    $editAnchor->setContent($editButton);

    // Process for output with echo
    $finalEditButton = $editAnchor->render();

    $tmp = '<table cellspacing="0" cellpadding="0" border="0"><tr><td>' . $tmp . '</td></tr><tr><td></td></tr></table>' . $finalEditButton;
}

$tmp = addslashes($tmp);
$tmp = str_replace("\\'", "'", $tmp);

?>