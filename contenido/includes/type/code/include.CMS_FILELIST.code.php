<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_FILELIST code
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
 *   $Id: $:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// CMS_FILELIST

$tmp = $a_content['CMS_FILELIST'][$val];

$oCmsFileList = new Cms_FileList($tmp, $val, $idartlang, $editLink, $cfg, $db, $belang, $client, $lang, $cfgClient, $sess);

if ($edit) {
    $tmp = $oCmsFileList->getAllWidgetEdit();
} else {
    $tmp = $oCmsFileList->getAllWidgetView();
}


?>