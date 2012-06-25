<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_IMAGE code
 *
 * NOTE: This file will be included by the code generator while processing CMS tags in layout.
 * It runs in a context of a function and requires some predefined variables!
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Fulai Zhang <fulai.zhang@4fb.de>
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


if($a_content['CMS_IMAGE'][$val]){
    $tmp = $a_content['CMS_IMAGE'][$val];
} else {
    $tmp = $a_content['CMS_IMG'][$val];
}
$oCmsImage = new Cms_Image($tmp, $val, $idartlang, $editLink, $cfg, $db, $belang, $client, $lang, $cfgClient, $sess, $idart, $idcat);

if ($edit) {
    $tmp = $oCmsImage->getAllWidgetEdit();
} else {
    $tmp = $oCmsImage->getAllWidgetView();
}

?>