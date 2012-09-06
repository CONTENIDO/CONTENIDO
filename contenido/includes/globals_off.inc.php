<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Makes available those super global arrays that are made available in versions of PHP after v4.1.0
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Martin Horwath
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 */

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_STRIPSLASHES
 */
define('CONTENIDO_STRIPSLASHES', (get_magic_quotes_gpc() == 0));

/**
 * set constant value depending on get_magic_quotes_gpc status
 *
 * @var boolean
 */
define('CON_STRIPSLASHES', (get_magic_quotes_gpc() == 0));

// simulate get_magic_quotes_gpc on if turned off
if (CON_STRIPSLASHES) {

    /**
     * Adds slashes to passed variable
     *
     * @param mixed $value Either a string or a multi-dimensional array of
     *            values
     * @return array
     */
    function addslashes_deep($value) {
        $value = is_array($value)? array_map('addslashes_deep', $value) : addslashes($value);

        return $value;
    }

    /**
     * Removes slashes from passed variable.
     *
     * @param mixed $value Either a string or a multi-dimensional array of
     *            values
     * @return array
     */
    function stripslashes_deep($value) {
        $value = is_array($value)? array_map('stripslashes_deep', $value) : stripslashes($value);

        return $value;
    }

    $_POST = array_map('addslashes_deep', $_POST);
    $_GET = array_map('addslashes_deep', $_GET);
    $_COOKIE = array_map('addslashes_deep', $_COOKIE);

    $cfg['simulate_magic_quotes'] = true;
} else {
    $cfg['simulate_magic_quotes'] = false;
}

// this should be the default setting, but only for PHP older than 5.3.0
if (!CON_STRIPSLASHES && (version_compare(PHP_VERSION, '5.3.0', '<'))) {
    @set_magic_quotes_runtime(0);
}

// register globals
$types_to_register = array('GET', 'POST', 'COOKIE', 'SESSION', 'SERVER');
foreach ($types_to_register as $global_type) {
    $arr = @ ${'_' . $global_type};
    if (is_array($arr) && count($arr) > 0) {
        // second loop to prevent overwriting of globals by other globals' values
        foreach ($types_to_register as $global_type) {
            $key = '_' . $global_type;
            if (isset($arr[$key])) {
                unset($arr[$key]);
            }
        }
        // echo "<pre>\$_$global_type:"; print_r ($arr); echo "</pre>";
        extract($arr, EXTR_OVERWRITE);
    }
}
// save memory
unset($types_to_register, $global_type, $arr);
