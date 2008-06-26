<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * This object makes contenido more secure
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 * 
 * {@internal 
 *   created 2008-06-25
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Contenido Security class
 */
class Contenido_Security {

    /**
     * Check: Is the variable an integer?
     * @access public
     * @param string $sVar
     * @return true or false
     */
    public static function isInteger($iVar) {

        $iTempVar = $iVar;
        $iVar = (int) $iVar;

        if($iTempVar === $iVar) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Check: Is the variable an string?
     * @access public
     * @param string $sVar
     * @return true or false
     */
    public static function isString($sVar) {

        if(is_string($sVar)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Convert an string to an integer
     * @access public
     * @param string $sInteger
     * @return $sInteger converted string
     */
    public static function toInteger($sInteger) {    
        return (int) $sInteger;    
    }

    /**
     * Convert an string
     * @access public
     * @param string $sString
     * @param bool $bHTML if true check with strip_tags and stripslashes
     * @param string $sAllowableTags allowable tags if $bHTML is true
     * @return $sString converted string
     */
    public static function toString($sString, $bHTML = false, $sAllowableTags = '') {

        $sString = (string) $sString;

        if($bHTML == true) {
            $sString = strip_tags(stripslashes($sString), $sAllowableTags);
        }

        return $sString;

    }

    /**
     * Convert an query-string to mysql_real_escape_string
     * @access public
     * @param string $sString
     * @param object $oDB contenido database object
     * @return converted string
     */
    public static function escapeDB($sString, $oDB) {

        if(CONTENIDO_STRIPSLASHES == true && !get_magic_quotes_gpc()) {
            $sString = stripslashes_deep($sString);
        }

        if(!is_object($oDB)) {
            return mysql_escape_string($sString);
        } else {
            return $oDB->Escape($sString);
        }

    }

    /**
     * Un-quote string quoted with escapeDB()
     * @access public
     * @param string $sString
     * @return converted string
     */
    public static function unescapeDB($sString) {
        return stripslashes($sString);
    }

}
?>