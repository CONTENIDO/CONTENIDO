<?php

/**
 * Makes available those super global arrays that are made available in versions
 * of PHP after v4.1.0.
 * This file is where all the "magic" begins. We ignore register_globals setting
 * and retrieve any variable from wherever and transform them to global
 * variables. This is highly insecure, so variables need to be checked
 * carefully.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Martin Horwarth
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Set constant value depending on get_magic_quotes_gpc status
 *
 * @var boolean
 */
if (function_exists('get_magic_quotes_gpc')) {
    define('CON_STRIPSLASHES', !get_magic_quotes_gpc());
} else {
    define('CON_STRIPSLASHES', true);
}

// Simulate get_magic_quotes_gpc on if turned off
if (CON_STRIPSLASHES) {

    // class cString is not loaded here as autoloader wasn't called yet
    if (false === class_exists('cString')) {
        include_once dirname(__DIR__) . '/classes/class.string.php';
    }

    $_POST = array_map(array('cString', 'addSlashes'), $_POST);
    $_GET = array_map(array('cString', 'addSlashes'), $_GET);
    $_COOKIE = array_map(array('cString', 'addSlashes'), $_COOKIE);

    $cfg['simulate_magic_quotes'] = true;
} else {
    $cfg['simulate_magic_quotes'] = false;
}

// This should be the default setting, but only for PHP older than 5.3.0
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    @set_magic_quotes_runtime(0);
}

// Register globals
$types_to_register = array(
    'GET',
    'POST',
    'COOKIE',
    'SESSION',
    'SERVER'
);
foreach ($types_to_register as $global_type) {
    $arr = @ ${'_' . $global_type};
    if (is_array($arr) && count($arr) > 0) {
        // Second loop to prevent overwriting of globals by other globals'
        // values
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
