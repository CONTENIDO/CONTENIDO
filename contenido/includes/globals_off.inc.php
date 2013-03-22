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
if (phpversion() <= "5.3.0") {
    define('CONTENIDO_STRIPSLASHES', get_magic_quotes_gpc());
}

/**
 * Set constant value depending on get_magic_quotes_gpc status
 * Only with phpversion before 5.3.0
 * @var boolean
 */
if (phpversion() <= "5.3.0") {
    define('CON_STRIPSLASHES', get_magic_quotes_gpc());
}

// Simulate get_magic_quotes_gpc on if turned off
if (CON_STRIPSLASHES) {

    /**
     * Adds slashes to passed variable
     * @param mixed $value Either a string or a multi-dimensional array of values
     * @return array
     * @deprecated [2013-03-12]  This function is for internal usage, use cString::addSlashes() for own purposes
     */
    function addslashes_deep($value) {
        $value = is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
        return $value;
    }

    /**
     * Removes slashes from passed variable.
     * @param mixed $value Either a string or a multi-dimensional array of values
     * @return array
     * @deprecated [2013-03-12]  This function is for internal usage, use cString::stripSlashes() for own purposes
     */
    function stripslashes_deep($value) {
        $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
        return $value;
    }

    $_POST = array_map('addslashes_deep', $_POST);
    $_GET = array_map('addslashes_deep', $_GET);
    $_COOKIE = array_map('addslashes_deep', $_COOKIE);

    $cfg['simulate_magic_quotes'] = true;
} else {
    $cfg['simulate_magic_quotes'] = false;
}

// This should be the default setting, but only for PHP older than 5.3.0
if (!CON_STRIPSLASHES && (version_compare(PHP_VERSION, '5.3.0', '<'))) {
    @set_magic_quotes_runtime(0);
}

// Register globals
$types_to_register = array('GET', 'POST', 'COOKIE', 'SESSION', 'SERVER');
foreach ($types_to_register as $global_type) {
    $arr = @ ${'_' . $global_type};
    if (is_array($arr) && count($arr) > 0) {
        // Second loop to prevent overwriting of globals by other globals' values
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

// Save memory
unset($types_to_register, $global_type, $arr);
