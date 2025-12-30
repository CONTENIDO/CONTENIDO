<?php

/**
 * This file contains the request validator class.
 *
 * @package    Core
 * @subpackage Security
 * @author     Mischa Holz
 * @author     Andreas Kummer
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to check get and post variables
 *
 * @package    Core
 * @subpackage Security
 */
class cRequestValidator
{

    /**
     * @var cRequestValidator Instance of this class.
     */
    private static $_instance = null;

    /**
     * @var string Path and filename of logfile.
     */
    protected $_logPath;

    /**
     * @var bool Flag whether to write log or not.
     */
    protected $_log = true;

    /**
     * @var string Path to config file.
     */
    protected $_configPath;

    /**
     * Array with all possible parameters and parameter formats.
     * Structure has to be:
     * <code>
     * $check['GET']['param1'] = VALIDATE_FORMAT;
     * $check['POST']['param2'] = VALIDATE_FORMAT;
     * </code>
     * Possible formats are defined as constants in top of these class file.
     *
     * @var array
     */
    protected $_check = [];

    /**
     * @var array Array with forbidden parameters.
     *      If any of these is set the request will be invalid.
     */
    protected $_blacklist = [];

    /**
     * @var string Contains first invalid parameter name.
     */
    protected $_failure = '';

    /**
     * @var string Current mode.
     */
    protected $_mode = '';

    /**
     * @var string Regexp for integers.
     */
    public const CHECK_INTEGER = '/^[0-9]*$/';

    /**
     * @var string Regexp for primitive strings.
     */
    public const CHECK_PRIMITIVESTRING = '/^[a-zA-Z0-9 -_]*$/';

    /**
     * @var string Regexp for strings.
     */
    public const CHECK_STRING = '/^[\w0-9 -_]*$/';

    /**
     * @var string Regexp for 32 character hash.
     */
    public const CHECK_HASH32 = '/^[a-zA-Z0-9]{32}$/';

    /**
     * @var string Regexp for valid belang values.
     */
    public const CHECK_BELANG = '/^[a-z]{2}_[A-Z]{2}$/';

    /**
     * @var string Regexp for valid area values.
     */
    public const CHECK_AREASTRING = '/^[a-zA-Z_]*$/';

    /**
     * @var string Regexp for validating file upload paths.
     */
    public const CHECK_PATHSTRING = '!([*]*\/)|(dbfs:\/[*]*)|(dbfs:)|(^)$!';

    /**
     * Constructor to create an instance of this class.
     * The constructor sets up the singleton object and reads the config from
     *     'data/config/' . CON_ENVIRONMENT . '/config.http_check.php'
     * It also reads existing local config from
     *     'data/config/' . CON_ENVIRONMENT . '/config.http_check.local.php'
     *
     * @throws cFileNotFoundException if the configuration can not be loaded
     */
    private function __construct()
    {
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
            $this->_blacklist[] = cString::toLowerCase($elem);
        }
    }

    /**
     * Returns the instance of this class.
     * @throws cFileNotFoundException
     */
    public static function getInstance(): self
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Checks every given parameter.
     * Parameters which aren't defined in config.http_check.php
     * are considered to be fine.
     *
     * @return bool True if every parameter is fine
     * @throws cInvalidArgumentException
     */
    public function checkParams(): bool
    {
        if ((!$this->checkGetParams()) || (!$this->checkPostParams() || (!$this->checkCookieParams()))) {
            $this->logHackTrial();

            if ($this->_mode === 'stop') {
                die();
            }

            return false;
        }

        return true;
    }

    /**
     * Checks GET parameters only.
     *
     * @return bool True if every parameter is fine
     * @see cRequestValidator::checkParams()
     */
    public function checkGetParams(): bool
    {
        return $this->checkArray($_GET, 'GET');
    }

    /**
     * Checks POST parameters only.
     *
     * @return bool True if every parameter is fine
     * @see cRequestValidator::checkParams()
     */
    public function checkPostParams(): bool
    {
        return $this->checkArray($_POST, 'POST');
    }

    /**
     * Checks COOKIE parameters only.
     *
     * @return bool True if every parameter is fine
     * @see cRequestValidator::checkParams()
     */
    public function checkCookieParams(): bool
    {
        return $this->checkArray($_COOKIE, 'COOKIE');
    }

    /**
     * Returns the first bad parameter.
     *
     * @return string The key of the bad parameter
     */
    public function getBadParameter(): string
    {
        return $this->_failure;
    }

    /**
     * Checks a single parameter.
     *
     * @param string $type GET, POST, or COOKIE
     * @param string $key The key of the parameter
     * @param mixed $value The value of the parameter
     * @return bool True if the parameter is fine
     * @see cRequestValidator::checkParams()
     */
    public function checkParameter(string $type, string $key, $value): bool
    {
        $result = false;

        if (in_array(cString::toLowerCase($key), $this->_blacklist)) {
            return false;
        }

        if (in_array(cString::toUpperCase($type), [
            'GET',
            'POST',
            'COOKIE'
        ])) {
            if (!isset($this->_check[$type][$key]) && empty($value)) {
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
     * Writes a log entry containing information about the request which
     * led to the halt of the execution.
     * @throws cInvalidArgumentException
     */
    protected function logHackTrial()
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        if ($this->_log === true && !empty($this->_logPath)) {
            $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
            $content = date('Y-m-d H:i:s') . '    ';
            $content .= $remoteAddr . str_repeat(' ', 17 - cString::getStringLength($remoteAddr)) . "\n";
            $content .= '    Query String: ' . $queryString . "\n";
            $content .= '    Bad parameter: ' . $this->getBadParameter() . "\n";
            $content .= '    POST array: ' . print_r($_POST, true) . "\n";
            $content .= '    GET array: ' . print_r($_GET, true) . "\n";
            $content .= '    COOKIE array: ' . print_r($_COOKIE, true) . "\n";
            cFileHandler::write($this->_logPath, $content, true);
        } elseif ($this->_mode == 'continue') {
            echo "\n<br>VIOLATION: URL contains invalid or undefined paramaters! URL: '" . htmlentities($queryString) . "' <br>\n";
        }
    }

    /**
     * This function removes unwanted chars from given string.
     */
    public static function cleanParameter(string $param): string
    {
        $charsToReplace = [
            '<', '>', '?', '&', '$', '{', '}', '(', ')'
        ];

        foreach ($charsToReplace as $char) {
            $param = str_replace($char, '', $param);
        }

        return $param;
    }

    /**
     * Checks an array for validity.
     *
     * @param array $arr The array which has to be checked
     * @param string $type GET, POST, or COOKIE
     * @return bool True if everything is fine.
     */
    protected function checkArray(array $arr, string $type): bool
    {
        $result = true;

        foreach ($arr as $key => $value) {
            if (!$this->checkParameter(cString::toUpperCase($type), $key, $value)) {
                $this->_failure = $key;
                $result = false;
                break;
            }
        }

        return $result;
    }

}
