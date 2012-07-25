<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This object makes CONTENIDO more secure
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.2
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 *
 * @TODO: Some features are the same as in HttpInputValidator (see contenido/classes/class.httpinputvalidator.php),
 *        merge them...
 *
 * {@internal
 *   created  2008-06-25
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * CONTENIDO Security exception class
 *
 * @deprecated [2012-07-03] This class is no longer needed
 */
class Contenido_Security_Exception extends Exception
{
    /**
     * Logging flag. Set to true for logging invalid calls.
     * @var      boolean
     */
    protected static $_logging = false;

    /**
     * @see Exception::__construct()
     * @deprecated [2012-07-03] This class is no longer needed
     */
    public function __construct($sMessage, $sParamName)
    {
        global $cfg;

        cDeprecated("Contenido_Security_Exception is no longer needed");

        parent::__construct($sMessage);

        // check if logging is enabled
        if (self::$_logging == true) {
            $sLogFile = $cfg['path']['contenido_config'] . 'security.txt';

            $sFileContent = '---------' . PHP_EOL;
            $sFileContent .= "Invalid call caused by parameter '" . $sParamName . "' at " . date("c") . PHP_EOL;
            $sFileContent .= "Original value was '" . $_REQUEST[$sParamName] . "'" . PHP_EOL;
            $sFileContent .= "URL: " . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . " (Protocol: " . $_SERVER['SERVER_PROTOCOL'] . ")" . PHP_EOL;

            cFileHandler::write($sLogFile, $sFileContent, true);
        }

        // strictly die here
        die($sMessage);
        exit;
    }
}

/**
 * CONTENIDO Security class
 */
class cSecurity
{
    /**
     * Escapes string using CONTENIDO urlencoding method and escapes string for inserting
     *
     * @param   string        $sString  Input string
     * @param   DB_Contenido  $oDb      CONTENIDO database object
     * @return  string   Filtered string
     */
    public static function filter($sString, $oDb)
    {
        $sString = self::toString($sString);
        if (defined('CONTENIDO_STRIPSLASHES')) {
            $sString = stripslashes($sString);
        }
        return self::escapeDB(htmlspecialchars($sString), $oDb, false);
    }

    /**
     * Reverts effect of method filter()
     *
     * @param   string  $sString  Input string
     * @return  string  Unfiltered string
     */
    public static function unFilter($sString)
    {
        $sString = self::toString($sString);
        return htmldecode(self::unEscapeDB($sString));
    }

    /**
     * Check: Has the variable an boolean value?
     *
     * @param   string   $sVar  Input string
     * @return  boolean  Check state
     */
    public static function isBoolean($sVar)
    {
        $sTempVar  = $sVar;
        $sTemp2Var = self::toBoolean($sVar);
        return ($sTempVar === $sTemp2Var);
    }

    /**
     * Check: Is the variable an integer?
     *
     * @param   string   $sVar  Input string
     * @return  boolean  Check state
     */
    public static function isInteger($sVar)
    {
        return (preg_match('/^[0-9]+$/', $sVar));
    }

    /**
     * Check: Is the variable an string?
     *
     * @param   string   $sVar  Input string
     * @return  boolean  Check state
     */
    public static function isString($sVar)
    {
        return (is_string($sVar));
    }

    /**
     * Convert an string to an boolean
     *
     * @param   string   $sString   Input string
     * @return  boolean  Type casted input string
     */
    public static function toBoolean($sString)
    {
        return (bool) $sString;
    }

    /**
     * Convert an string to an integer
     *
     * @param   string   $sString   Input string
     * @return  integer  Type casted input string
     */
    public static function toInteger($sString)
    {
        return (int) $sString;
    }

    /**
     * Convert an string
     *
     * @param   string   $sString         Input string
     * @param   boolean  $bHTML           If true check with strip_tags and stripslashes
     * @param   string   $sAllowableTags  Allowable tags if $bHTML is true
     * @return  string  Converted string
     */
    public static function toString($sString, $bHTML = false, $sAllowableTags = '')
    {
        $sString = (string) $sString;
        if ($bHTML == true) {
            $sString = strip_tags(stripslashes($sString), $sAllowableTags);
        }
        return $sString;
    }

    /**
     * Escaped an query-string with mysql_real_escape_string
     *
     * @param   string        $sString          Input string
     * @param   DB_Contenido  $oDB              CONTENIDO database object
     * @param   boolean       $bUndoAddSlashes  Flag for undo addslashes (optional, default: true)
     * @return  string  Converted string
     */
    public static function escapeDB($sString, $oDB, $bUndoAddSlashes = true)
    {
        if (!is_object($oDB)) {
            return self::escapeString($sString);
        } else {
            if (defined('CONTENIDO_STRIPSLASHES') && $bUndoAddSlashes == true) {
                $sString = stripslashes($sString);
            }
            return $oDB->Escape($sString);
        }
    }

    /**
     * Escaped an query-string with addslashes
     *
     * @param   string  $sString  Input string
     * @return  string  Converted string
     */
    public static function escapeString($sString)
    {
        $sString = (string) $sString;
        if (defined('CONTENIDO_STRIPSLASHES')) {
            $sString = stripslashes($sString);
        }
        return addslashes($sString);
    }

    /**
     * Un-quote string quoted with escapeDB()
     *
     * @param   string  $sString  Input string
     * @return  string  Converted string
     */
    public static function unescapeDB($sString)
    {
        return stripslashes($sString);
    }

}

class Contenido_Security extends cSecurity {

    /**
     * Returns accepted backend language values
     * @deprecated [2012-07-02] This class was replaced by cRequestValidator
     * @return  array
     */
    public static function getAcceptedBelangValues()
    {
        cDeprecated("Please use cSecurity instead");
        return array();
    }

    /**
     * Returns must be numeric request parameters
     * @deprecated [2012-07-02] This class was replaced by cRequestValidator
     * @return  array
     */
    public static function getMustbeNumericParameters()
    {
        cDeprecated("Please use cSecurity instead");
        return array();
    }

    /**
     * Returns forbidden request parameters
     * @deprecated [2012-07-02] This class was replaced by cRequestValidator
     * @return  array
     */
    public static function getForbiddenParameters()
    {
        cDeprecated("Please use cSecurity instead");
        return array();
    }

    /**
     * Checks some CONTENIDO core related request parameters against XSS
     *
     * @deprecated [2012-07-02] This function is now executed by cRequestValidator
     * @return  bool|void  True on success otherwhise nothing.
     */
    public static function checkRequests()
    {
        global $oRequestValidator;

        cDeprecated("Please use cSecurity instead");
        return $oRequestValidator->checkParams();
    }

    /**
     * Checks backend language parameter in request.
     *
     * @deprecated [2012-07-02] This function is now executed by cRequestValidator
     * @return  bool|void  True on success otherwhise nothing.
     */
    public static function checkRequestBelang()
    {
        global $oRequestValidator;

        cDeprecated("Please use cSecurity instead");
        return $oRequestValidator->checkParams();
    }

    /**
     * Checks for forbidden parameters in request.
     *
     * @deprecated [2012-07-02] This function is now executed by cRequestValidator
     * @return  bool|void  True on success otherwhise nothing.
     */
    public static function checkRequestForbiddenParameter()
    {
        global $oRequestValidator;

        cDeprecated("Please use cSecurity instead");
        return $oRequestValidator->checkParams();
    }

    /**
     * Checks for parameters in request who must be numeric.
     *
     * Contrary to other request checks, this method don't throws a exception. It just insures that
     * incomming values are really numeric, by type casting them to an integer.
     *
     * @deprecated [2012-07-02] This function is now executed by cRequestValidator
     * @return  bool  Just true
     */
    public static function checkRequestMustbeNumericParameter()
    {
        global $oRequestValidator;

        cDeprecated("Please use cSecurity instead");
        return $oRequestValidator->checkParams();
    }

    /**
     * Checks/Validates existing CONTENIDO session request parameter.
     *
     * @deprecated [2012-07-02] This function is now executed by cRequestValidator
     * @return  bool|void  True on success otherwhise nothing.
     */
    public static function checkRequestSession()
    {
        global $oRequestValidator;

        cDeprecated("Please use cSecurity instead");
        return $oRequestValidator->checkParams();
    }

    /**
     * Checks also contenido-var (session) to ascii, but works as a wrapper to checkRequestSession().
     *
     * @return  true
     * @deprecated  Use checkRequestSession() instead due to better naming conventions
     * @TODO:  Should be removed, but later in few years...
     */
    public static function checkSession()
    {
        cDeprecated("Use checkRequestSession() instead");

        cDeprecated("Please use cSecurity instead");
        return self::checkRequestSession();
    }

    /**
     * Escapes string using CONTENIDO urlencoding method and escapes string for inserting
     *
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     * @param   string        $sString  Input string
     * @param   DB_Contenido  $oDb      CONTENIDO database object
     * @return  string   Filtered string
     */
    public static function filter($sString, $oDb)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::filter($sString, $oDb);
    }

    /**
     * Reverts effect of method filter()
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string  $sString  Input string
     * @return  string  Unfiltered string
     */
    public static function unFilter($sString)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::unFilter($sString);
    }

    /**
     * Check: Has the variable an boolean value?
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string   $sVar  Input string
     * @return  boolean  Check state
     */
    public static function isBoolean($sVar)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::isBoolean($sVar);
    }

    /**
     * Check: Is the variable an integer?
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string   $sVar  Input string
     * @return  boolean  Check state
     */
    public static function isInteger($sVar)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::isInteger($sVar);
    }

    /**
     * Check: Is the variable an string?
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string   $sVar  Input string
     * @return  boolean  Check state
     */
    public static function isString($sVar)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::isString($sVar);
    }

    /**
     * Convert an string to an boolean
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string   $sString   Input string
     * @return  boolean  Type casted input string
     */
    public static function toBoolean($sString)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::toBoolean($sString);
    }

    /**
     * Convert an string to an integer
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string   $sString   Input string
     * @return  integer  Type casted input string
     */
    public static function toInteger($sString)
    {
        cDeprecated("Please use cSecurity instead");

        return parent::toInteger($sString);
    }

    /**
     * Convert an string
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @param   string   $sString         Input string
     * @param   boolean  $bHTML           If true check with strip_tags and stripslashes
     * @param   string   $sAllowableTags  Allowable tags if $bHTML is true
     * @return  string  Converted string
     */
    public static function toString($sString, $bHTML = false, $sAllowableTags = '')
    {
        cDeprecated("Please use cSecurity instead");

        return parent::toString($sString, $bHTML, $sAllowableTags);
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
     * @deprecated [2012-07-02] This function is now executed by cSecurity
     *
     * @return  void
     */
    public static function checkFrontendGlobals()
    {
        cDeprecated("This function was removed from cSecurity. The checks are performred by cRequestValidator now");
    }
}
?>