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
 * @version    1.1.1
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
 *   modified 2010-09-30, Dominik Ziegler, added optional logging
 *   modified 2010-11-22, Dominik Ziegler, fixed behaviour of isInteger [CON-365]
 *
 *   $Id: class.security.php  1238 2010-11-22 11:26:33Z dominik.ziegler $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Contenido Security exception class
 */
class Contenido_Security_Exception extends Exception {
	/**
	 * Logging flag. Set to true for logging invalid calls.
	 * @access	protected
	 * @static
	 * @var		boolean
	 */
	protected static $_logging = false;
	
	/**
	 * @see Exception::__construct()
	 */
	public function __construct($sMessage, $sParamName) {
		parent::__construct($sMessage);
		
		// check if logging is enabled
		if ( self::$_logging == true ) {
			$sLogFile = realpath( dirname(__FILE__) . '/../logs/') . '/security.txt';

			$sFileContent = '---------' . PHP_EOL;
			$sFileContent .= "Invalid call caused by parameter '" . $sParamName . "' at " . date("c") . PHP_EOL;
			$sFileContent .= "Original value was '" . $_REQUEST[$sParamName] . "'" . PHP_EOL;
			$sFileContent .= "URL: " . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . " (Protocol: " . $_SERVER['SERVER_PROTOCOL'] . ")" . PHP_EOL;
			
			file_put_contents($sLogFile, $sFileContent, FILE_APPEND);
		}
		
		// strictly die here
		die( $sMessage );
		exit;
	}
}


/**
 * Contenido Security class
 */
class Contenido_Security {
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
     * Request paramaters, which are strictly forbidden
     * @var  array
     */
    protected static $_forbiddenParameters = array('cfg', 'cfgClient', 'contenido_path', '_PHPLIB', 'db', 'sess');


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
     * Returns forbidden request parameters
     *
     * @return  array
     */
    public static function getForbiddenParameters()
    {
        return self::$_forbiddenParameters;
    }


    /**
     * Escapes string using contenido urlencoding method and escapes string for inserting
	 * @static
	 *
     * @param	string			$sString	input string
	 * @param	DB_Contenido	$oDb		contenido database object
	 *
     * @return 	string	filtered string
     */
    public static function filter($sString, $oDb) {
		$sString = self::toString( $sString );
	  
		if ( defined('CONTENIDO_STRIPSLASHES') ) {
			if ( function_exists("stripslashes_deep") ) {
				$sString = stripslashes_deep($sString);
			} else {
				$sString = stripslashes($sString);
			}
		}
	  
		return self::escapeDB( conHtmlSpecialChars( urlencode($sString)), $oDb, false);
    }

    /**
     * Reverts effect of method filter()
     * @static
	 *
     * @param 	string 	$sString	input string
	 *
     * @return 	string	unfiltered string
     */
    public static function unFilter($sString) {
		$sString = self::toString( $sString );
		return urldecode( htmldecode( self::unEscapeDB($sString)));
    }

    /**
     * Check: Has the variable an boolean value?
     * @static
	 *
     * @param 	string 	$sVar	input string
	 *
     * @return 	boolean	check state
     */
    public static function isBoolean($sVar) {
        $sTempVar 	= $sVar;
        $sTemp2Var 	= self::toBoolean( $sVar );
		
		return ( $sTempVar === $sTemp2Var );
    }
    
    /**
     * Check: Is the variable an integer?
     * @static
	 *
     * @param 	string 	$sVar	input string
	 *
     * @return 	boolean	check state
     */
    public static function isInteger($sVar) { 
	
        return ( preg_match('/^[0-9]+$/', $sVar) );
    }

    /**
     * Check: Is the variable an string?
     * @static
	 *
     * @param 	string 	$sVar	input string
	 *
     * @return 	boolean	check state
     */
    public static function isString($sVar) {
		return ( is_string($sVar) );
    }

    /**
     * Convert an string to an boolean
     * @static
	 *
     * @param 	string 	$sString	input string
	 *
     * @return 	boolean	type casted input string
     */
    public static function toBoolean($sString) {    
        return (bool) $sString;    
    }

    /**
     * Convert an string to an integer
     * @static
	 *
     * @param 	string 	$sString	input string
	 *
     * @return 	integer	type casted input string
     */
    public static function toInteger($sString) {    
        return (int) $sString;    
    }

    /**
     * Convert an string
     * @static
	 *
     * @param 	string 	$sString			input string
     * @param 	boolean	$bHTML 				if true check with strip_tags and stripslashes
     * @param 	string 	$sAllowableTags 	allowable tags if $bHTML is true
	 *
     * @return 	string	converted string
     */
    public static function toString($sString, $bHTML = false, $sAllowableTags = '') {
        $sString = (string) $sString;

        if ( $bHTML == true ) {
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

        // Check for forbidden parameters
        self::checkRequestForbiddenParameter();

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
                throw new Contenido_Security_Exception('Please use a valid language!', 'belang');
            }
        }
        return true;
    }


    /**
     * Checks for forbidden parameters in request.
     *
     * @return  bool|void  True on success otherwhise nothing.
     * @throws  Contenido_Security_Exception if the request contains one of forbidden parameters.
     */
    public static function checkRequestForbiddenParameter() {
        foreach (self::$_forbiddenParameters as $param) {
            if (isset($_REQUEST[$param])) {
                throw new Contenido_Security_Exception('Invalid call!', $param);
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
        foreach (self::$_mustbeNumericParameters as $sParamName) {
			if ( isset($_REQUEST[$sParamName]) ) {
				$sValue = $_REQUEST[$sParamName];
				if ( strlen($sValue) > 0 && self::isInteger($sValue) == false ) {
					throw new Contenido_Security_Exception('Invalid call', $sParamName);
				}
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
                throw new Contenido_Security_Exception('Invalid call', 'contenido');
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
	 * @static
	 *
	 * @param	string			$sString			input string
	 * @param 	DB_Contenido 	$oDB 				contenido database object
	 * @param 	boolean 		$bUndoAddSlashes 	flag for undo addslashes (optional, default: true)
	 *
	 * @return 	string	converted string
	 */
    public static function escapeDB($sString, $oDB, $bUndoAddSlashes = true) {
        if ( !is_object($oDB) ) {
            return self::escapeString($sString);
        } else {
			if ( defined('CONTENIDO_STRIPSLASHES') && $bUndoAddSlashes == true ) {
	            if ( function_exists("stripslashes_deep") ) {
	                $sString = stripslashes_deep($sString);
	            } else {
					$sString = stripslashes($sString);
				}
			}

            return $oDB->escape($sString);
        }
    }

    /**
     * Escaped an query-string with addslashes
     * @static
	 *
     * @param 	string	$sString	input string
	 *
     * @return 	string	converted string
     */
    public static function escapeString($sString) {
        if ( defined('CONTENIDO_STRIPSLASHES') ) {
			if ( function_exists("stripslashes_deep") ) {
				$sString = stripslashes_deep($sString);
			} else {
				$sString = stripslashes($sString);
			}
		}

        return addslashes($sString);
    }

    /**
     * Un-quote string quoted with escapeDB()
     * @static
	 *
     * @param 	string 	$sString	input string
	 *
     * @return 	string	converted string
     */
    public static function unescapeDB($sString) {
        return stripslashes($sString);
    }

}
?>