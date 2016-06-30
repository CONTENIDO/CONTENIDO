<?php
/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

define('CON_IMAGERESIZE_GD', 1);

define('CON_IMAGERESIZE_IMAGEMAGICK', 2);

define('CON_IMAGERESIZE_CANTCHECK', 3);

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
    if (cApiIsImageMagickAvailable()) {
        return CON_IMAGERESIZE_IMAGEMAGICK;
    }

    if ($iGDStatus === CON_EXTENSION_CANTCHECK) {
        return CON_IMAGERESIZE_CANTCHECK;
    } else {
        return CON_IMAGERESIZE_NOTHINGAVAILABLE;
    }
}

?>