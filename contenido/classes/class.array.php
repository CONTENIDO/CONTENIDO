<?php

/**
 * This file contains the array utility class.
 *
 * @package Core
 * @subpackage Util
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
     * Strip whitespaces (or other characters) from the beginning and end of
     * each item in array.
     * Similar to trim() function.
     *
     * @param array $arr
     *         Array of strings that will be trimmed.
     * @param string $charlist [optional]
     *         Optionally the stripped characters can also be specified using
     *         the charlist parameter. Simply list all characters that you want
     *         to be stripped. With .. you can specify a range of characters.
     * @return array
     *         Array of trimmed strings.
     */
    public static function trim(array $arr, $charlist = NULL) {
        foreach ($arr as $key => $value) {
            $arr[$key] = isset($charlist) ? trim($value, $charlist) : trim($value);
        }

        return $arr;
    }

    /**
     * Search for given value in given array and return key of its first
     * occurance.
     *
     * If value wasn't found at all false will be returned. If given array
     * contains subarrays, these will be searched too. If value is found in
     * subarray the returned key is that of the subarray.
     *
     * Usually the values are tested for equality with the given $search. If the
     * flag $partial is not false values are tested to contain $search.
     * Otherwise, if $strict equals true values are tested for identity with
     * $search. Otherwise (which is the default) values are tested for equality.
     *
     * Be careful when searching by equality in arrays containing values that
     * are no strings! The same is true for searching by equality for values
     * that are no strings. PHPs behaviour is quite weird concerning comparision
     * of different data types. E.g. '0' equals '0.0', 'foo' equals 0, 'foo'
     * equals 0.0, NULL equals '' and false equals '0'! When dealing with
     * nonstrings consider to use the strict mode!
     *
     * Another caveat is when searching for an empty string when using the
     * partial mode. This would lead to an error and is considered a bug!
     *
     * @todo There should be only one flag for $partial and $strict in order to
     *       avoid ambiguities (imagine $partial=true & $strict=true).
     * @param array $arr
     *         array to search
     * @param mixed $search
     *         value to search for
     * @param bool $partial [optional]
     *         if values are tested to contain $search
     * @param bool $strict [optional]
     *         if values are tested for identity
     * @return mixed
     *         key of the array containing the searched value or false
     */
    public static function searchRecursive(array $arr, $search, $partial = false, $strict = false) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $ret = static::searchRecursive($value, $search, $partial, $strict);
                if ($ret !== false) {
                    return $ret;
                }
            } else {
                if ($partial !== false) {
                    // BUGFIX empty search
                    if (0 === cString::getStringLength($search)) {
                        return false;
                    }
                    // convert $search explicitly to string
                    // we do not want to use the ordinal value of $search
                    $found = false !== cString::findFirstPos($value, strval($search));
               } else if ($strict == true) {
                    // search by identity
                    $found = $value === $search;
                } else {
                    // search by equality
                    $found = $value == $search;
                }
                if ($found) {
                    return $key;
                }
            }
        }

        return false;
    }

    /**
     * Sorts an array by changing the locale temporary to passed value.
     *
     * @param array $arr
     *         The array to sort
     * @param string $locale
     *         The locale to change before sorting
     * @return array
     *         Sorted array
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
     *
     * Found at http://us2.php.net/manual/en/function.array-multisort.php
     *
     * Syntax:
     * <pre>
     * $new_array = cArray::csort($array [, 'col1' [, SORT_FLAG [,
     * SORT_FLAG]]]...);
     * </pre>
     *
     * Explanation:
     * - $array is the array you want to sort
     * - 'col1' is the name of the column you want to sort
     * - SORT_FLAGS are: SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC,
     * SORT_STRING
     *
     * You can repeat the 'col', FLAG, FLAG as often as you want. The highest
     * prioritiy is given to the first - so the array is sorted by the last
     * given column first, then the one before ...
     *
     * Example:
     * <pre>
     * $array = cArray::csort($array, 'town', 'age', SORT_DESC, 'name');
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
                    $a = cString::toUpperCase($row[$arg]);
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
     * Ensures that the passed array has the key, sets it by using the value.
     *
     * @param array $aArray
     * @param string $sKey
     * @param mixed $mDefault [optional]
     * @return bool
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
