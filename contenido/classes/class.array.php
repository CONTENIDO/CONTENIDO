<?php
/**
 * This file contains the array utility class.
 *
 * @package Core
 * @subpackage Util
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Array helper class.
 *
 * @package Core
 * @subpackage Util
 */
class cArray {

    /**
     * Strip whitespace (or other characters) from the beginning and end of each
     * item in array.
     * Similar to trim() function.
     *
     * @param array $arr
     * @param string $charlist
     * @return array The trimmer array
     */
    public static function trim(array $arr, $charlist = null) {
        foreach ($arr as $key => $value) {
            $arr[$key] = trim($value, $charlist);
        }

        return $arr;
    }

    /**
     *
     * @todo : Ask timo to document this.
     *
     *       Note: If subarrays exists, this function currently returns the key
     *       of the array
     *       given by $arr, and not from the subarrays (todo: add flag to allow
     *       this)
     *
     * @param array $arr The array to search
     * @param mixed $search The value to search in the array
     * @param bool $partial
     * @param bool $strict
     * @return mixed bool key/index of the array containing the searched value
     *         or false.
     */
    public static function searchRecursive(array $arr, $search, $partial = false, $strict = false) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $val = self::searchRecursive($value, $search, $partial, $strict);
                if ($val !== false) {
                    return $key;
                }
            } else {
                if ($partial == false) {
                    if ($strict == true) {
                        if ($value === $search) {
                            return $key;
                        }
                    } else {
                        if ($value == $search) {
                            return $key;
                        }
                    }
                } else {
                    if (strpos($value, $search) !== false) {
                        return $key;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Sorts an array by changing the locale temporary to passed value.
     *
     * @param array $arr The array to sort
     * @param string $locale The locale to change before sorting
     * @return array Sorted array
     */
    public static function sortWithLocale(array $arr, $locale) {
        $oldlocale = setlocale(LC_COLLATE, 0);
        setlocale(LC_COLLATE, $locale);

        uasort($arr, 'strcoll');

        setlocale(LC_COLLATE, $oldlocale);

        return $arr;
    }

    /**
     * Very cool algorithm for sorting multi-dimensional arrays.
     * Found at http://us2.php.net/manual/en/function.array-multisort.php
     * Syntax:
     * <pre>
     * $new_array = cArray::csort($array [, 'col1' [, SORT_FLAG [,
     * SORT_FLAG]]]...);
     * </pre>
     * Explanation: $array is the array you want to sort, 'col1' is the name of
     * the column
     * you want to sort, SORT_FLAGS are : SORT_ASC, SORT_DESC, SORT_REGULAR,
     * SORT_NUMERIC, SORT_STRING
     * you can repeat the 'col',FLAG,FLAG, as often you want, the highest
     * prioritiy is given to
     * the first - so the array is sorted by the last given column first, then
     * the one before ...
     * Example:
     * <pre>
     * $array = cArray::csort($array,'town','age', SORT_DESC, 'name');
     * </pre>
     *
     * @return array
     */
    public static function csort() {
        $args = func_get_args();
        $marray = array_shift($args);
        $msortline = "return(array_multisort(";
        $i = 0;
        foreach ($args as $arg) {
            $i++;
            if (is_string($arg)) {
                foreach ($marray as $row) {
                    $a = strtoupper($row[$arg]);
                    $sortarr[$i][] = $a;
                }
            } else {
                $sortarr[$i] = $arg;
            }
            $msortline .= "\$sortarr[" . $i . "],";
        }
        $msortline .= "\$marray));";
        @eval($msortline);
        return $marray;
    }

    /**
     * Ensures that the passed array has the key, sets it by using te value
     *
     * @param array $aArray
     * @param string $sKey
     * @param mixed $mDefault
     * @return boolean
     */
    public static function initializeKey(&$aArray, $sKey, $mDefault = '') {
        if (!is_array($aArray)) {
            if (isset($aArray)) {
                return false;
            }
            $aArray = array();
        }

        if (!array_key_exists($sKey, $aArray)) {
            $aArray[$sKey] = $mDefault;
        }
    }
}
