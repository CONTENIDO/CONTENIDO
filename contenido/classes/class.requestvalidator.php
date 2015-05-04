<?php
/**
 * This file contains the the request validator class.
 *
 * @package    Core
 * @subpackage Security
 * @version    SVN Revision $Rev:$
 *
 * @author     Mischa Holz, Andreas Kummer
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to check get and post variables
 *
 * @package    Core
 * @subpackage Security
 */
class cRequestValidator {

    /**
     * Instance of this class
     *
     * @var cRequestValidator
     */
    private static $_instance = NULL;

    /**
     * Path and filename of logfile
     *
     * @var string
     */
    protected $_logPath;

    /**
     * Flag whether to write log or not.
     *
     * @var bool
     */
    protected $_log = true;

    /**
     * Path to config file.
     *
     * @var string
     */
    protected $_configPath;

    /**
     * Array with all possible parameters and parameter formats.
     * Structure has to be:
     *
     * <code>
     * $check['GET']['param1'] = VALIDATE_FORMAT;
     * $check['POST']['param2'] = VALIDATE_FORMAT;
     * </code>
     *
     * Possible formats are defined as constants in top of these class file.
     *
     * @var array
     */
    protected $_check = array();

    /**
     * Array with forbidden parameters.
     * If any of these is set the request will be invalid
     *
     * @var array
     */
    protected $_blacklist = array();

    /**
     * Contains first invalid parameter name.
     *
     * @var string
     */
    protected $_failure = '';

    /**
     * Current mode
     *
     * @var string
     */
    protected $_mode = '';

    /**
     * Regexp for integers.
     *
     * @var string
     */
    const CHECK_INTEGER = '/^[0-9]*$/';

    /**
     * Regexp for primitive strings.
     *
     * @var string
     */
    const CHECK_PRIMITIVESTRING = '/^[a-zA-Z0-9 -_]*$/';

    /**
     * Regexp for strings.
     *
     * @var string
     */
    const CHECK_STRING = '/^[\w0-9 -_]*$/';

    /**
     * Regexp for 32 character hash.
     *
     * @var string
     */
    const CHECK_HASH32 = '/^[a-zA-Z0-9]{32}$/';

    /**
     * Regexp for valid belang values.
     *
     * @var string
     */
    const CHECK_BELANG = '/^[a-z]{2}_[A-Z]{2}$/';

    /**
     * Regexp for valid area values.
     *
     * @var string
     */
    const CHECK_AREASTRING = '/^[a-zA-Z_]*$/';

    /**
     * Regexp for validating file upload paths.
     *
     * @var string
     */
    const CHECK_PATHSTRING = '!([*]*\/)|(dbfs:\/[*]*)|(dbfs:)|(^)$!';

    /**
     * The constructor sets up the singleton object and reads the config from
     *     'data/config/' . CON_ENVIRONMENT . '/config.http_check.php'
     * It also reads existing local config from
     *     'data/config/' . CON_ENVIRONMENT . '/config.http_check.local.php'
     *
     * @throws cFileNotFoundException if the configuration can not be loaded
     */
    private function __construct() {
        // globals from config.http_check.php file which is included below
        global $bLog, $sMode, $aCheck, $aBlacklist;

        // some paths...
        $installationPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../..'));
        $configPath = $installationPath . '/data/config/' . CON_ENVIRONMENT;

        $this->_logPath = $installationPath . '/data/logs/security.txt';

        // check config and logging path
        if (cFileHandler::exists($configPath . '/config.http_check.php')) {
            $this->_configPath = $configPath;
        } else {
            throw new cFileNotFoundException('Could not load cRequestValidator configuration! (invalid path) ' . $configPath . '/config.http_check.php');
        }

        // include configuration
        require($this->_configPath . '/config.http_check.php');

        // if custom config exists, include it also here
        if (cFileHandler::exists($this->_configPath . '/config.http_check.local.php')) {
            require($this->_configPath . '/config.http_check.local.php');
        }

        $this->_log = $bLog;
        $this->_mode = $sMode;

        if ($this->_log === true) {
            if (empty($this->_logPath) || !is_writeable(dirname($this->_logPath))) {
                $this->_log = false;
            }
        }

        $this->_check = $aCheck;
        foreach ($aBlacklist as $elem) {
            $this->_blacklist[] = strtolower($elem);
        }
    }

    /**
     * Returns the instance of this class.
     *
     * @return cRequestValidator
     */
    public static function getInstance() {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Checks every given parameter.
     * Parameters which aren't defined in config.http_check.php are considered
     * to be fine
     *
     * @return bool
     *         True if every parameter is fine
     */
    public function checkParams() {
        if ((!$this->checkGetParams()) || (!$this->checkPostParams())) {
            $this->logHackTrial();

            if ($this->_mode == 'stop') {
                ob_end_clean();
                $msg = 'Parameter check failed! (%s = %s %s)';
                // prevent XSS!
                $msg = sprintf($msg, htmlentities($this->_failure), htmlentities($_GET[$this->_failure]), htmlentities($_POST[$this->_failure]));
                die($msg);
            }
        }

        return true;
    }

    /**
     * Checks GET parameters only.
     *
     * @see cRequestValidator::checkParams()
     * @return bool
     *         True if every parameter is fine
     */
    public function checkGetParams() {
        return $this->checkArray($_GET, 'GET');
    }

    /**
     * Checks POST parameters only.
     *
     * @see cRequestValidator::checkParams()
     * @return bool
     *         True if every parameter is fine
     */
    public function checkPostParams() {
        return $this->checkArray($_POST, 'POST');
    }

    /**
     * Checks a single parameter.
     *
     * @see cRequestValidator::checkParams()
     *
     * @param string $type
     *         GET or POST
     * @param string $key
     *         the key of the parameter
     * @param mixed $value
     *         the value of the parameter
     * @return bool
     *         True if the parameter is fine
     */
    public function checkParameter($type, $key, $value) {
        $result = false;

        if (in_array(strtolower($key), $this->_blacklist)) {
            return false;
        }

        if (in_array(strtoupper($type), array(
            'GET',
            'POST'
        ))) {
            if (!isset($this->_check[$type][$key]) && (is_null($value) || empty($value))) {
                // if unknown but empty the value is unaesthetic but ok
                $result = true;
            } elseif (isset($this->_check[$type][$key])) {
                // parameter is known, check it...
                $result = preg_match($this->_check[$type][$key], $value);
            } else {
                // unknown parameter. Will return true
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Returns the first bad parameter
     *
     * @return string
     *         the key of the bad parameter
     */
    public function getBadParameter() {
        return $this->_failure;
    }

    /**
     * Writes a log entry containing information about the request which led to
     * the halt of the execution
     */
    protected function logHackTrial() {
        if ($this->_log === true && !empty($this->_logPath)) {
            $content = date('Y-m-d H:i:s') . '    ';
            $content .= $_SERVER['REMOTE_ADDR'] . str_repeat(' ', 17 - strlen($_SERVER['REMOTE_ADDR'])) . "\n";
            $content .= '    Query String: ' . $_SERVER['QUERY_STRING'] . "\n";
            $content .= '    Bad parameter: ' . $this->getBadParameter() . "\n";
            $content .= '    POST array: ' . print_r($_POST, true) . "\n";
            cFileHandler::write($this->_logPath, $content, true);
        } elseif ($this->_mode == 'continue') {
            echo "\n<br>VIOLATION: URL contains invalid or undefined paramaters! URL: '" . conHtmlentities($_SERVER['QUERY_STRING']) . "' <br>\n";
        }
    }

    /**
     * Checks an array for validity.
     *
     * @param array $arr
     *         the array which has to be checked
     * @param string $type
     *         GET or POST
     * @return bool
     *         true if everything is fine.
     */
    protected function checkArray($arr, $type) {
        $result = true;

        foreach ($arr as $key => $value) {
            if (!$this->checkParameter(strtoupper($type), $key, $value)) {
                $this->_failure = $key;
                $result = false;
                break;
            }
        }

        return $result;
    }

}
