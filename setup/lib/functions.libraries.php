<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * <Description>
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package CONTENIDO setup
 * @version 0.2
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_IMAGERESIZE_GD
 */
define('E_IMAGERESIZE_GD', 1);
define('CON_IMAGERESIZE_GD', 1);

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_IMAGERESIZE_IMAGEMAGICK
 */
define('E_IMAGERESIZE_IMAGEMAGICK', 2);
define('CON_IMAGERESIZE_IMAGEMAGICK', 2);

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_IMAGERESIZE_CANTCHECK
 */
define('E_IMAGERESIZE_CANTCHECK', 3);
define('CON_IMAGERESIZE_CANTCHECK', 3);

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_IMAGERESIZE_NOTHINGAVAILABLE
 */
define('E_IMAGERESIZE_NOTHINGAVAILABLE', 4);
define('CON_IMAGERESIZE_NOTHINGAVAILABLE', 4);

function checkImageResizer() {
    global $cfg;

    $iGDStatus = isPHPExtensionLoaded('gd');

    if ($iGDStatus == CON_EXTENSION_AVAILABLE) {
        return CON_IMAGERESIZE_GD;
    }

    if (function_exists('imagecreate')) {
        return CON_IMAGERESIZE_GD;
    }

    checkAndInclude($cfg['path']['contenido'] . 'includes/functions.api.images.php');
    if (capiIsImageMagickAvailable()) {
        return CON_IMAGERESIZE_IMAGEMAGICK;
    }

    if ($iGDStatus === CON_EXTENSION_CANTCHECK) {
        return CON_IMAGERESIZE_CANTCHECK;
    } else {
        return CON_IMAGERESIZE_NOTHINGAVAILABLE;
    }
}

?>