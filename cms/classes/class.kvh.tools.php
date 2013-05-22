<?php

 /**
 *
 * @package Module
 * @subpackage ClinicSearch
 * @version SVN Revision $Rev:$
 * @author claus.schunk
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

 defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
 
/**
 * this class provides functions so merge arrays after reading from database.
 */
class Tools {

    /**
     * merge assoziative array with given key
     *
     */
    public static function mergeAssoziativ(array $ar, $str) {
        $con = array();
        for ($i = 0; $i < count($ar); $i++) {
            $con[] = $ar[$i][$str];
        }
        return $con;
    }

    /**
     * merge indexed arrays
     * 
     */
    public static function mergeArrayofArray($ar) {

        $new_array = array();


        for ($i = 0; $i < count($ar); $i++) {
            for ($j = 0; $j < count($ar[$i]); $j++) {
                $new_array[] = $ar[$i][$j];

            }
        }

        return $new_array;
    }
}
?>