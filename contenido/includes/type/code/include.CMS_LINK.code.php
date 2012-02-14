<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_LINK code
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


$tmp = urldecode($a_content['CMS_LINK'][$val]);

if (is_numeric($tmp)) {
    // Internal link
    $tmp = 'front_content.php?idcatart=' . $tmp . '&client=' . $client . '&lang=' . $lang;
    if ($edit) {
        $tmp = $sess->url("$tmp");
    }
} else {
    // External link
    if (!preg_match('/^(http|https|ftp|telnet|gopher):\/\/((?:[a-zA-Z0-9_-]+\.?)+):?(\d*)/', $tmp)) {
        // it's a relative link, or an absolute link with unsupported protocol
        if (substr($tmp, 0, 4) == 'www.' || $tmp == '') { // only check if it could be a domainname
            $tmp = 'http://' . $tmp;
        }
    }
}

?>