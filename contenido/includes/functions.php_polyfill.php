<?php
/**
 * This file contains polyfill functions for PHP.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purç <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');


if (!function_exists('is_iterable')) {

    /**
     * Verify that the contents of a variable is an iterable value.
     * is_iterable polyfill for PHP < 7.1.
     *
     * @link https://www.php.net/manual/en/function.is-iterable.php
     * @param mixed $var The value to check
     * @return bool Returns TRUE if var is iterable, FALSE otherwise
     */
    function is_iterable($var) {
        return is_array($var) || (is_object($var) && ($var instanceof \Traversable));
    }

}

if (!function_exists('is_countable')) {

    /**
     * Verify that the content of a variable is an array or an object
     * implementing the Countable interface.
     * is_countable polyfill for PHP < 7.3.
     *
     * @link https://www.php.net/manual/de/function.is-countable.php
     * @param mixed $var The value to check
     * @return bool Returns TRUE if var is countable, FALSE otherwise
     */
    function is_countable($var) {
        return (is_array($var)
            || $var instanceof \Countable
            || $var instanceof \SimpleXMLElement
            || $var instanceof \ResourceBundle
        );
    }

}
