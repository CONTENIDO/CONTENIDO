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
 * @version    1.0.1
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 * 
 * {@internal 
 *   created 2008-06-25
 *   modified 2008-07-02, Frederic Schneider, added boolean functions and checkRequests() 
 *   modified 2008-07-04, Frederic Schneider, added test to valid contenido-session-var
 *   modified 2008-07-23, Frederic Schneider, fixed stripslashes_deep functionality
 *   modified 2008-07-31, Frederic Schneider, added escapeString() with fallback at escapeDB()
 *   modified 2008-11-13, Timo Trautmann also strip slashes, if they were added autmatically by php
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
     * Escapes string using contenido urlencoding method and escapes string for inserting
     * @access public
     * @param string $sVar
     * @return string
     */
    public static function filter($sString, $oDb) {
      $sString = (string) $sString;
	  
	  if(defined('CONTENIDO_STRIPSLASHES')) {
			if(function_exists("stripslashes_deep")) {
				$sString = stripslashes_deep($sString);
			} else {
				$sString = stripslashes($sString);
			}
	  }
	  
      $sString = Contenido_Security::escapeDB( htmlspecialchars( urlencode($sString)), $oDb, false);
      return $sString;
    }
    
   /**
     * Reverses effect of method filter()
     * @access public
     * @param string $sVar
     * @return string
     */
    public static function unFilter($sString) {
      $sString = (string) $sString;
      $sString = urldecode( htmldecode( Contenido_Security::unEscapeDB($sString)));
      return $sString;
    }

    /**
     * Check: Has the variable an boolean value?
     * @access public
     * @param string $sVar
     * @return boolean
     */
    public static function isBoolean($sVar) {

        $sTempVar = $sVar;
        $sVar = (bool) $sVar;

        if($sTempVar === $sVar) {
            return true;
        } else {
            return false;
        }

    }
    
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
     * Convert an string to an boolean
     * @access public
     * @param string $sInteger
     * @return $sInteger converted boolean
     */
    public static function toBoolean($sInteger) {    
        return (bool) $sInteger;    
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
     * Checks some request-vars (XSS)
     * @access public
     * @return die() or true
     */
    public static function checkRequests() {

        if (isset($_REQUEST['belang'])) {
    
            $aValid = array('de_DE', 'en_US', 'fr_FR', 'it_IT', 'nl_NL');

            if (!in_array(strval($_REQUEST['belang']), $aValid)) {
                die('Please use a valid language!');
            }

        }

        if(isset($_REQUEST['cfg']) || isset($_REQUEST['cfgClient']) || isset($_REQUEST['contenido_path'])) {
            die('Invalid call!');
        } else {
            return true;
        }

        self::checkSession();

    }

    /**
     * Checks contenido-var (session) to ascii
     * @access public
     * @return die() or true
     */
    public static function checkSession() {
    
        if(isset($_REQUEST['contenido']) && !preg_match('/^[0-9a-f]{32}$/', $_REQUEST['contenido'])) {
            die('Invalid call');
        } else {
            return true;
        }
    
    }

	/**
	 * Escaped an query-string with mysql_real_escape_string
	 * @access public
	 * @param string $sString
	 * @param object $oDB contenido database object
	 * @param boolean $bUndoAddSlashes (optional, true)
	 * @return converted string
	 */
    public static function escapeDB($sString, $oDB, $bUndoAddSlashes = true) {

        if(!is_object($oDB)) {
            return self::escapeString($sString);
        } else {

			if(defined('CONTENIDO_STRIPSLASHES') && $bUndoAddSlashes == true) {
	            if(function_exists("stripslashes_deep")) {
	                $sString = stripslashes_deep($sString);
	            } else {
					$sString = stripslashes($sString);
				}
			}

            return $oDB->Escape($sString);
        }

    }

    /**
     * Escaped an query-string with addslashes
     * @access public
     * @param string $sString
     * @return converted string
     */
    public static function escapeString($sString) {

        if(defined('CONTENIDO_STRIPSLASHES')) {
			if(function_exists("stripslashes_deep")) {
				$sString = stripslashes_deep($sString);
			} else {
				$sString = stripslashes($sString);
			}
		}

        return addslashes($sString);

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