<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class to check get and post variables
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.0
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2012-07-02
 *   $Id: class.requestvalidator.php 2395 2012-06-25 22:47:43Z xmurrix $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


#### FORMAT CONSTANTS ####
define('CON_CHECK_INTEGER', '/^[0-9]*$/'); // integer value
define('CON_CHECK_PRIMITIVESTRING', '/^[a-zA-Z0-9 -_]*$/'); // simple string
define('CON_CHECK_STRING', '/^[\w0-9 -_]*$/'); // more complex string
define('CON_CHECK_HASH32', '/^[a-zA-Z0-9]{32}$/'); // 32-character hash
define('CON_CHECK_BELANG', '/^de_DE|en_US|fr_FR|it_IT|nl_NL$/'); //valid values for belang
define('CON_CHECK_AREASTRING', '/^[a-zA-Z_]*$/'); //checks for string consisting of letters and "_" only
define('CON_CHECK_PATHSTRING', '!([*]*\/)|(dbfs:\/[*]*)|(dbfs:)|(^)$!'); //validates file paths for file uploading (matches "folder/", "", "dbfs:" and "dbfs:/*")

class cRequestValidator {

    /**
     * Path and filename of logfile
     *
     * @var string
     */
    protected $sLogPath;

    /**
     * Flag whether to write log or not.
     *
     * @var boolean
     */
    protected $bLog;

    /**
     * Path to config file.
     *
     * @var string
     */
    protected $sConfigPath;

    /**
     * Array with all possible parameters and parameter formats.
     * Structure has to be:
     *
     * <code>
     * $check['GET']['param1']  = VALIDATE_FORMAT;
     * $check['POST']['param2'] = VALIDATE_FORMAT;
     * </code>
     *
     * Possible formats are defined as constants in top of these class file.
     *
     * @var array
     */
    protected $aCheck;

    /**
     * Array with forbidden parameters. If any of these is set the request will be invalid
     *
     * @var array
     */
    protected $aBlacklist;

    /**
     * Contains first invalid parameter name.
     *
     * @var string
     */
    protected $sFailure;

    /**
     * Current mode
     *
     * @var string
     */
    protected $sMode;

    /**
     * The constructor will check if every parameter defined in the $sConfigPath."/config.http_check.php" is valid. If not it will stop the execution.
     *
     * @param string The path to config.http_check.php and config.http_check.local.php
     */
    public function __construct($sConfigPath) {

        $this->sLogPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../..')) . '/data/logs/security.txt';
        $this->bLog = true;
        $this->aCheck = array();
        $this->aBlacklist = array();
        $this->sFailure = "";
        $this->sMode = "";

        // check config and logging path
        if (!empty($sConfigPath) && cFileHandler::exists($sConfigPath . "/config.http_check.php")) {
            $this->sConfigPath = realpath($sConfigPath);
        } else {
            die('Could not load cRequestValidator configuration! (invalid path) ' . $sConfigPath);
        }

        // include configuration
        require($this->sConfigPath . "/config.http_check.php");

        // if custom config exists, include it also here
        if (cFileHandler::exists(dirname($this->sConfigPath) . '/config.http_check.local.php')) {
            require(dirname($this->sConfigPath) . '/config.http_check.local.php');
        }

        $this->bLog = $bLog;
        $this->sMode = $sMode;

        if ($this->bLog === true) {
            if (empty($this->sLogPath) || !is_writeable(dirname($this->sLogPath))) {
                $this->bLog = false;
            }
        }

        $this->aCheck = $aCheck;
        foreach ($aBlacklist as $elem) {
            $this->aBlacklist[] = strtolower($elem);
        }

        if ((!$this->checkGetParams()) || (!$this->checkPostParams())) {
            $this->logHackTrial();

            if ($this->sMode == 'stop') {
                ob_end_clean();
                die('Parameter check failed! (' . $this->sFailure . '=' . $_GET[$this->sFailure] . $_POST[$this->sFailure] . ')');
            }
        }
    }

    /**
     * Checks every given parameter. Parameters which aren't defined in config.http_check.php are considered to be fine
     *
     * @return bool True if every parameter is fine
     */
    public function checkParams() {
        return $this->checkGetParams() && $this->checkPostParams();
    }

    /**
     * Checks GET parameters only.
     *
     * @see cRequestValidator::checkParams()
     * @return bool True if every parameter is fine
     */
    public function checkGetParams() {
        return $this->checkArray($_GET, "GET");
    }

    /**
     * Checks POST parameters only.
     *
     * @see cRequestValidator::checkParams()
     * @return bool True if every parameter is fine
     */
    public function checkPostParams() {
        return $this->checkArray($_POST, "POST");
    }

    /**
     * Checks a single parameter.
     *
     * @see cRequestValidator::checkParams()
     *
     * @param string GET or POST
     * @param string the key of the parameter
     * @param mixed the value of the parameter
     * @return bool True if the parameter is fine
     */
    public function checkParameter($sType, $sKey, $mValue) {
        $bResult = false;

        if (in_array(strtolower($sKey), $this->aBlacklist)) {
            return false;
        }

        if (in_array(strtoupper($sType), array('GET', 'POST'))) {
            if (!isset($this->aCheck[$sType][$sKey]) && (is_null($mValue) || empty($mValue))) {
                // if unknown but empty the value is unaesthetic but ok
                $bResult = true;
            } elseif (isset($this->aCheck[$sType][$sKey])) {
                // parameter is known, check it...
                $bResult = preg_match($this->aCheck[$sType][$sKey], $mValue);
            } else {
                //unknown parameter. Will return tru
                $bResult = true;
            }
        }

        return $bResult;
    }

    /**
     * Returns the first bad parameter
     *
     * @return string the key of the bad parameter
     */
    public function getBadParameter() {
        return $this->sFailure;
    }

    /**
     * Writes a log entry containing information about the request which led to the halt of the execution
     *
     */
    protected function logHackTrial() {
        if ($this->bLog === true && !empty($this->sLogPath)) {
            $content = date('Y-m-d H:i:s') . '  ' .
                    $_SERVER['REMOTE_ADDR'] . str_repeat(' ', 17 - strlen($_SERVER['REMOTE_ADDR'])) .
                    $_SERVER['QUERY_STRING'] . "\n" .
                    print_r($_POST, true) . "\n";
            cFileHandler::write($this->sLogPath, $content);
        } elseif ($this->sMode == 'continue') {
            echo "\n<br />VIOLATION: URL contains invalid or undefined paramaters! URL: '" .
            htmlentities($_SERVER['QUERY_STRING']) . "' <br />\n";
        }
    }

    /**
     * Checks an array for validity.
     *
     * @param array the array which has to be checked
     * @param string GET or POST
     *
     * @return bool true if everything is fine.
     */
    protected function checkArray($arr, $type) {
        $bResult = true;

        foreach ($arr as $sKey => $mValue) {
            if (!$this->checkParameter(strtoupper($type), $sKey, $mValue)) {
                $this->sFailure = $sKey;
                $bResult = false;
                break;
            }
        }

        return $bResult;
    }

}
