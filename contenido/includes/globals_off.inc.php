<?php

/**
 * Makes available those super global arrays that are made available in versions
 * of PHP after v4.1.0.
 * This file is where all the "magic" begins. We ignore register_globals setting
 * and retrieve any variable from wherever and transform them to global
 * variables. This is highly insecure, so variables need to be checked
 * carefully.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Martin Horwarth
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// Simulate get_magic_quotes_gpc on if turned off
if (CON_STRIPSLASHES) {
    // classes cStringMultiByteWrapper and cString are not loaded here as autoloader wasn't called yet
    if (false === class_exists('cStringMultiByteWrapper')) {
        include_once dirname(__DIR__) . '/classes/class.string.multi.byte.wrapper.php';
    }
    if (false === class_exists('cString')) {
        include_once dirname(__DIR__) . '/classes/class.string.php';
    }

    $_POST   = array_map(['cString', 'addSlashes'], $_POST);
    $_GET    = array_map(['cString', 'addSlashes'], $_GET);
    $_COOKIE = array_map(['cString', 'addSlashes'], $_COOKIE);

    $cfg['simulate_magic_quotes'] = true;
} else {
    $cfg['simulate_magic_quotes'] = false;
}

// This should be the default setting, but only for PHP older than 5.3.0
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    @set_magic_quotes_runtime(0);
}

// Register globals
$types_to_register = [
    'GET',
    'POST',
    'COOKIE',
    'SESSION',
    'SERVER'
];
foreach ($types_to_register as $global_type) {
    $arr = ${'_' . $global_type} ?? [];
    if (is_array($arr) && count($arr) > 0) {
        // Second loop to prevent overwriting of globals by other globals'
        // values
        foreach ($types_to_register as $global_type) {
            $key = '_' . $global_type;
            if (isset($arr[$key])) {
                unset($arr[$key]);
            }
        }
        extract($arr, EXTR_OVERWRITE);
    }
}

// Save memory
unset($types_to_register, $global_type, $arr);
