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
 * @version    1.0.5
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 *
 * @TODO: Some features are the same as in HttpInputValidator (see contenido/classes/class.httpinputvalidator.php),
 *        merge them...
 *
 * {@internal
 *   created 2008-06-25
 *   modified 2008-07-02, Frederic Schneider, added boolean functions and checkRequests()
 *   modified 2008-07-04, Frederic Schneider, added test to valid contenido-session-var
 *   modified 2008-07-23, Frederic Schneider, fixed stripslashes_deep functionality
 *   modified 2008-07-31, Frederic Schneider, added escapeString() with fallback at escapeDB()
 *   modified 2008-11-13, Timo Trautmann also strip slashes, if they were added autmatically by php
 *   modified 2010-05-20, Murat Purc, extended/added request parameter checks which are usable
 *                        by Contenido startup process. Changed script terminations by die() to Exceptions.
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Contenido Security exception class
 */
class Contenido_Security_Exception extends Exception {}


/**
 * Contenido Security class
 */
class Contenido_Security {

    // @TODO: Following settings should be configurable but the securiy check runs before loading
    //        configuration...

    /**
     * Accepted backend languages
     * @var  array
     */
    protected static $_acceptedBelangValues = array('de_DE', 'en_US', 'fr_FR', 'it_IT', 'nl_NL');

    /**
     * Request paramaters, which must be numeric
     * @var  array
     */
    protected static $_mustbeNumericParameters = array(
        'client', 'changeclient', 'lang', 'changelang', 'idcat', 'idcatlang', 'idart', 'idartlang',
        'idcatart'
    );

    /**
     * Request paramaters, which are strictly forbitten
     * @var  array
     */
    protected static $_forbittenParameters = array('cfg', 'cfgClient', 'contenido_path', '_PHPLIB');


    /**
     * Returns accepted backend language values
     *
     * @return  array
     */
    public static function getAcceptedBelangValues()
    {
        return self::$_acceptedBelangValues;
    }


    /**
     * Returns must be numeric request parameters
     *
     * @return  array
     */
    public static function getMustbeNumericParameters()
    {
        return self::$_mustbeNumericParameters;
    }


    /**
     * Returns forbitten request parameters
     *
     * @return  array
     */
    public static function getForbittenParameters()
    {
        return self::$_forbittenParameters;
    }


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
     * Checks some Contenido core related request parameters against XSS
     *
     * @access  public
     * @return  bool|void  True on success otherwhise nothing.
     * @throws  Contenido_Security_Exception if one of the checks fails
     */
    public static function checkRequests() {

        // Check backend language
        self::checkRequestBelang();

        // Check for forbitten parameters
        self::checkRequestForbittenParameter();

        // Check for parameters who must be numeric
        self::checkRequestMustbeNumericParameter();

        // Check session id
        self::checkRequestSession();

        return true;
    }


    /**
     * Checks backend language parameter in request.
     *
     * @return  bool|void  True on success otherwhise nothing.
     * @throws  Contenido_Security_Exception if existing backend language parameter is not valid
     */
    public static function checkRequestBelang() {
        if (isset($_REQUEST['belang'])) {
            $_REQUEST['belang'] = strval($_REQUEST['belang']);
            if (!in_array($_REQUEST['belang'], self::$_acceptedBelangValues)) {
                throw new Contenido_Security_Exception('Please use a valid language!');
            }
        }
        return true;
    }


    /**
     * Checks for forbitten parameters in request.
     *
     * @return  bool|void  True on success otherwhise nothing.
     * @throws  Contenido_Security_Exception if the request contains one of forbitten parameters.
     */
    public static function checkRequestForbittenParameter() {
        foreach (self::$_forbittenParameters as $param) {
            if (isset($_REQUEST[$param])) {
                throw new Contenido_Security_Exception('Invalid call!');
            }
        }
        return true;
    }


    /**
     * Checks for parameters in request who must be numeric.
     *
     * Contrary to other request checks, this method don't throws a exception. It just insures that
     * incomming values are really numeric, by type casting them to an integer.
     *
     * @return  bool  Just true
     */
    public static function checkRequestMustbeNumericParameter() {
        foreach (self::$_mustbeNumericParameters as $param) {
            if (isset($_REQUEST[$param])) {
                $_REQUEST[$param] = (int) $_REQUEST[$param];
            }
        }
        return true;
    }


    /**
     * Checks/Validates existing contenido session request parameter.
     *
     * @return  bool|void  True on success otherwhise nothing.
     * @throws  Contenido_Security_Exception if contenido parameter in request don't matches the required format
     */
    public static function checkRequestSession() {
        if (isset($_REQUEST['contenido']) && !preg_match('/^[0-9a-f]{32}$/', $_REQUEST['contenido'])) {
            if ($_REQUEST['contenido'] != '') {
                throw new Contenido_Security_Exception('Invalid call');
            }
        }
        return true;
    }


    /**
     * Checks also contenido-var (session) to ascii, but works as a wrapper to checkRequestSession().
     *
     * @access public
     * @return  true
     * @throws  Contenido_Security_Exception if contenido parameter in request don't matches the required format
     * @deprecated  Use checkRequestSession() instead due to better naming conventions
     * @TODO:  Should be removed, but later in few years...
     */
    public static function checkSession() {
        return self::checkRequestSession();
    }


    /**
     * Checks some global variables at frontend like $lang, $client, $changelang, $changeclient,
     * $tmpchangelang.
     *
     * Validates client and language related variables and takes care that their content is
     * really a numeric value.
     *
     * Logic in this function is taken over from front_content.php (v 4.8.12, line 164 - 192).
     *
     * @TODO:  Need a solution for used globals
     *
     * @return  void
     */
    public static function checkFrontendGlobals() {
        global $tmpchangelang, $savedlang, $lang, $changelang, $load_lang, $changeclient, $client, $load_client;

        if (isset($tmpchangelang) && is_numeric($tmpchangelang) && $tmpchangelang > 0) {
            // savelang is needed to set language before closing the page, see
            // {frontend_clientdir}/front_content.php before page_close()
            $savedlang = $lang;
            $lang      = $tmpchangelang;
        }

        // Check basic incomming data
        if (isset($changeclient) && !is_numeric($changeclient)) {
            unset($changeclient);
        }
        if (isset($client) && !is_numeric($client)) {
            unset($client);
        }
        if (isset($changelang) && !is_numeric($changelang)) {
            unset($changelang);
        }
        if (isset($lang) && !is_numeric($lang)) {
            unset($lang);
        }

        // Change client
        if (isset($changeclient)){
            $client = $changeclient;
            unset($lang);
            unset($load_lang);
        }

        // Change language
        if (isset($changelang)) {
            $lang = $changelang;
        }

        // Initialize client
        if (!isset($client)) {
            // load_client defined in {frontend_clientdir}/config.php
            $client = $load_client;
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