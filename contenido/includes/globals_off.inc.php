<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Makes available those super global arrays that are made available in versions of PHP after v4.1.0
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Martin Horwath
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created unkown
 *   modified 2008-06-25, Frederic Schneider, add stripslashes_deep and contenido_stripslashes constant
 *   modified 2008-06-26 Removed $_SERVER and $_ENV because this global vars are read only
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready) and removed code for PHP older than 4.1.0
 *   modified 2011-02-04, Murat Purc, fixed potential attac by manipulated request variables (see http://forum.contenido.org/viewtopic.php?f=11&t=30812)
 *   $Id$:
 * }}
 *
 */


// set constant value depending on get_magic_quotes_gpc status
define('CONTENIDO_STRIPSLASHES', (get_magic_quotes_gpc() == 0));


// PHP5 with register_long_arrays off?
if (!isset($HTTP_POST_VARS) && isset($_POST)) {
    $HTTP_POST_VARS = & $_POST;
    $HTTP_GET_VARS = & $_GET;
    $HTTP_COOKIE_VARS = & $_COOKIE;
    $HTTP_POST_FILES = & $_FILES;

    // _SESSION is the only superglobal which is conditionally set
    if (isset($_SESSION)) {
        $HTTP_SESSION_VARS = & $_SESSION;
    }
}

// simulate get_magic_quotes_gpc on if turned off
if (CONTENIDO_STRIPSLASHES) {

    /**
     * Adds slashes to passed variable
     *
     * @param   mixed  $value  Either a string or a multi-dimensional array of values
     * @return  array
     */
    function addslashes_deep($value)
    {
        $value = is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);

        return $value;
    }

    /**
     * Removes slashes from passed variable.
     *
     * @param   mixed  $value  Either a string or a multi-dimensional array of values
     * @return  array
     */
    function stripslashes_deep($value)
    {
        $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);

        return $value;
    }

    $_POST   = array_map('addslashes_deep', $_POST);
    $_GET    = array_map('addslashes_deep', $_GET);
    $_COOKIE = array_map('addslashes_deep', $_COOKIE);

    $cfg['simulate_magic_quotes'] = true;
} else {
    $cfg['simulate_magic_quotes'] = false;
}

if (!isset($_REQUEST) || $cfg['simulate_magic_quotes']) {
    /* Register post,get and cookie variables into $_REQUEST */
    $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
}

// this should be the default setting, but only for PHP older than 5.3.0
if (!CONTENIDO_STRIPSLASHES && (version_compare(PHP_VERSION, '5.3.0', '<'))) {
    @set_magic_quotes_runtime(0);
}

// register globals
$types_to_register = array ('GET', 'POST', 'COOKIE', 'SESSION', 'SERVER');
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
unset ($types_to_register, $global_type, $arr);

$FORM = $_REQUEST;

?>