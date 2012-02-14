<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_IMG code
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

// CMS_IMG

$tmp = urldecode($a_content['CMS_IMG'][$val]);

if ($tmp == '' || $tmp == '0') {
    $tmp = '';
} else {
    if (is_numeric($tmp)) {
        $oUplItem = new cApiUpload((int) $tmp);
        if (false !== $oUplItem->get('dirname')) {
            if (is_dbfs($oUplItem->get('dirname'))) {
                $tmp = $cfgClient[$client]['path']['htmlpath'] . 'dbfs.php?file=' . urlencode($oUplItem->get('dirname') . $oUplItem->get('filename'));
            } else {
                $tmp = $cfgClient[$client]['path']['htmlpath'] . $cfgClient[$client]['upload'] . $oUplItem->get('dirname') . $oUplItem->get('filename');
            }
        }
    }

    $tmp = htmlspecialchars($tmp);
    $tmp = urldecode($tmp);
    $tmp = str_replace("'", "\'", $tmp);
}

?>