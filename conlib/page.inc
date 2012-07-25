<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Base page setup for CONTENIDO.
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Core
 * @version    1.1.2
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2002-07-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated [2012-05-18] This function is deprecated, use cRegistry::bootstrap() instead */
function page_open($feature) {
    cDeprecated("This function is deprecated, use cRegistry::bootstrap() instead");
    cRegistry::bootstrap($feature);
}

/** @deprecated [2012-05-18] This function is deprecated, use cRegistry::shutdown() instead */
function page_close() {
    cDeprecated("This function is deprecated, use cRegistry::shutdown() instead");
    cRegistry::shutdown();
}

/** @deprecated [2012-05-18] This function is not longer supported. */
function sess_load($session) {
    cDeprecated("This function is not longer supported.");

    reset($session);
    while (list($k, $v) = each($session)) {
        $GLOBALS[$k] = new $v;
        $GLOBALS[$k]->start();
    }
}

/** @deprecated [2012-05-18] This function is not longer supported. */
function sess_save($session) {
    cDeprecated("This function is not longer supported.");

    reset($session);
    while (list(, $v) = each($session)) {
        $GLOBALS[$v]->freeze();
    }
}

?>