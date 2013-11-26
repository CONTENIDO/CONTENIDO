<?php
/**
 * This file contains the the system test class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Provides functions to test the system integrity
 *
 * @package Core
 * @subpackage Backend
 */
class cSystemtest {

    /**
     * The minimal PHP version
     *
     * @var string
     */
    const CON_SETUP_MIN_PHP_VERSION = '5.2.0';

    /**
     * Messages have no influence on the result of the system integrity
     *
     * @var int
     */
    const C_SEVERITY_NONE = 1;

    /**
     * Messages are only to inform the user about something.
     *
     * @var int
     */
    const C_SEVERITY_INFO = 2;

    /**
     * Messages about settings which aren't correct, but CONTENIDO might work
     * anyway
     *
     * @var int
     */
    const C_SEVERITY_WARNING = 3;

    /**
     * Messages about settings which aren't correct.
     * CONTENIDO won't work
     *
     * @var int
     */
    const C_SEVERITY_ERROR = 4;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The filepermissions are okay
     *
     * @var int
     */
    const CON_PREDICT_SUFFICIENT = 1;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The filepermissions are not predictable (we can't figure the server UID)
     *
     * @var int
     */
    const CON_PREDICT_NOTPREDICTABLE = 2;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The filepermissions for the owner have to be changed
     *
     * @var int
     */
    const CON_PREDICT_CHANGEPERM_SAMEOWNER = 3;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The filepermissions for the group have to be changed
     *
     * @var int
     */
    const CON_PREDICT_CHANGEPERM_SAMEGROUP = 4;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The filepermissions for others should be changed
     *
     * @var int
     */
    const CON_PREDICT_CHANGEPERM_OTHERS = 5;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The owner of the file should be changed
     *
     * @var int
     */
    const CON_PREDICT_CHANGEUSER = 6;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The group of the file should be changed
     *
     * @var int
     */
    const CON_PREDICT_CHANGEGROUP = 7;

    /**
     * Possible result of cSystemtest::predictCorrectFilePermissions()
     * The filepermissions are unpredictable because Windows
     *
     * @var int
     */
    const CON_PREDICT_WINDOWS = 8;

    /**
     * Possible result of cSystemtest::checkOpenBaseDir().
     * No restrictions
     *
     * @var int
     */
    const CON_BASEDIR_NORESTRICTION = 1;

    /**
     * Possible result of cSystemtest::checkOpenBaseDir().
     * The Basedir is set to ".". CONTENIDO won't work
     *
     * @var int
     */
    const CON_BASEDIR_DOTRESTRICTION = 2;

    /**
     * Possible result of cSystemtest::checkOpenBaseDir().
     * Open basedir is in effect but CONTENIDO works anyway
     *
     * @var int
     */
    const CON_BASEDIR_RESTRICTIONSUFFICIENT = 3;

    /**
     * Possible result of cSystemtest::checkOpenBaseDir().
     * Open basedir is in effect and CONTENIDO doesn't work with it
     *
     * @var int
     */
    const CON_BASEDIR_INCOMPATIBLE = 4;

    /**
     * Possible result of cSystemtest::isPHPExtensionLoaded()
     * The extension is loaded
     *
     * @var int
     */
    const CON_EXTENSION_AVAILABLE = 1;

    /**
     * Possible result of cSystemtest::isPHPExtensionLoaded()
     * The extension is not loaded
     *
     * @var int
     */
    const CON_EXTENSION_UNAVAILABLE = 2;

    /**
     * Possible result of cSystemtest::isPHPExtensionLoaded()
     * It was unable to check wether the extension is loaded or not
     *
     * @var int
     */
    const CON_EXTENSION_CANTCHECK = 3;

    /**
     * Possible result of cSystemtest::checkImageResizer()
     * GD is available for image resizing
     *
     * @var int
     */
    const CON_IMAGERESIZE_GD = 1;

    /**
     * Possible result of cSystemtest::checkImageResizer()
     * ImageMagick is available for image resizing
     *
     * @var int
     */
    const CON_IMAGERESIZE_IMAGEMAGICK = 2;

    /**
     * Possible result of cSystemtest::checkImageResizer()
     * It was unable to check which extension is available for image resizing
     *
     * @var int
     */
    const CON_IMAGERESIZE_CANTCHECK = 3;

    /**
     * Possible result of cSystemtest::checkImageResizer()
     * No fitting extension is available
     *
     * @var int
     */
    const CON_IMAGERESIZE_NOTHINGAVAILABLE = 4;

    /**
     * Possible result of cSystemtest::testMySQL()
     * Everything works fine with the given settings
     *
     * @var int
     */
    const CON_MYSQL_OK = 1;

    /**
     * Possible result of cSystemtest::testMySQL()
     * Strict mode is activated.
     * CONTENIDO won't work
     *
     * @var int
     */
    const CON_MYSQL_STRICT_MODE = 2;

    /**
     * Possible result of cSystemtest::testMySQL()
     * Strict mode is activated.
     * CONTENIDO won't work
     *
     * @var int
     */
    const CON_MYSQL_CANT_CONNECT = 3;

    /**
     * The test results which are stored for display.
     * Every array element is an assoicative array like this:
     * $_messages[$i] = array(
     * "result" => $result, //true or false, success or no success
     * "severity" => $severity, //one of the C_SEVERITY constants
     * "headline" => $headline, //the headline of the message
     * "message" => $message //the message
     * );
     *
     * @var array
     */
    protected $_messages;

    /**
     * The stored config array
     *
     * @var array
     */
    protected $_config;

    /**
     * Caches the given config array for later use.
     *
     * @param array $config A config array which should be similar to
     *        CONTENIDO's $cfg
     */
    public function __construct($config) {
        $this->_config = $config;
    }

    /**
     * Runs all available tests and stores the resuls in the messages array
     *
     * @param bool $testFileSystem If this is true the file system checks will
     *        be performed too with standard settings.
     */
    public function runTests($testFileSystem = true) {
        $this->storeResult($this->testPHPVersion(), self::C_SEVERITY_ERROR, sprintf(i18n("PHP Version lower than %s"), self::CON_SETUP_MIN_PHP_VERSION), sprintf(i18n("CONTENIDO requires PHP %s or higher as it uses functionality first introduced with this version. Please update your PHP version."), self::CON_SETUP_MIN_PHP_VERSION), i18n("The PHP version is higher than ") . self::CON_SETUP_MIN_PHP_VERSION);
        $this->storeResult($this->testFileUploadSetting(), self::C_SEVERITY_WARNING, i18n("File uploads disabled"), sprintf(i18n("Your PHP version is not configured for file uploads. You can't upload files using CONTENIDO's file manager unless you configure PHP for file uploads. See %s for more information"), '<a target="_blank" href="http://www.php.net/manual/en/ini.core.php#ini.file-uploads">http://www.php.net/manual/en/ini.core.php#ini.file-uploads</a>'), i18n("PHP file upload is enabled"));
        $this->storeResult($this->testMagicQuotesRuntimeSetting(), self::C_SEVERITY_ERROR, i18n("PHP setting 'magic_quotes_runtime' is turned on"), i18n("The PHP setting 'magic_quotes_runtime' is turned on. CONTENIDO has been developed to comply with magic_quotes_runtime=Off as this is the PHP default setting. You have to change this directive to make CONTENIDO work."), i18n("'magic_quotes_runtime' is turned off"));
        $this->storeResult($this->testMagicQuotesSybaseSetting(), self::C_SEVERITY_ERROR, i18n("PHP Setting 'magic_quotes_sybase' is turned on"), i18n("The PHP Setting 'magic_quotes_sybase' is turned on. CONTENIDO has been developed to comply with magic_quotes_sybase=Off as this is the PHP default setting. You have to change this directive to make CONTENIDO work."), i18n("'magic_quotes_sybase' is turned off"));
        $this->storeResult($this->testMaxExecutionTime(), self::C_SEVERITY_WARNING, i18n("PHP maximum execution time is less than 30 seconds"), i18n("PHP is configured for a maximum execution time of less than 30 seconds. This could cause problems with slow web servers and/or long operations in the backend. Our recommended execution time is 120 seconds on slow web servers, 60 seconds for medium ones and 30 seconds for fast web servers."), i18n("PHP allows execution times longer than 30 seconds"));
        $this->storeResult($this->testZIPArchive(), self::C_SEVERITY_WARNING, i18n("The class ZipArchive could not be found"), i18n("This could cause some problems, but CONTENIDO is able to run without it. You should check your PHP installation."), i18n("The ZipArchive class is enabled"));

        $test = $this->checkOpenBasedirCompatibility();
        switch ($test) {
            case self::CON_BASEDIR_NORESTRICTION:
                $this->storeResult(true, self::C_SEVERITY_ERROR, "", "", i18n("open_basedir directive doesn't enforce any restrictions"));
                break;
            case self::CON_BASEDIR_DOTRESTRICTION:
                $this->storeResult(false, self::C_SEVERITY_ERROR, i18n("open_basedir directive set to '.'"), i18n("The directive open_basedir is set to '.' (e.g. current directory). This means that CONTENIDO is unable to access files in a logical upper level in the filesystem. This will cause problems managing the CONTENIDO frontends. Either add the full path of this CONTENIDO installation to the open_basedir directive, or turn it off completely."));
                break;
            case self::CON_BASEDIR_RESTRICTIONSUFFICIENT:
                $this->storeResult(false, self::C_SEVERITY_INFO, i18n("open_basedir setting might be insufficient"), i18n("Setup believes that the PHP directive open_basedir is configured sufficient, however, if you encounter errors like 'open_basedir restriction in effect. File <filename> is not within the allowed path(s): <path>', you have to adjust the open_basedir directive"));
                break;
            case self::CON_BASEDIR_INCOMPATIBLE:
                $this->storeResult(false, self::C_SEVERITY_ERROR, i18n("open_basedir directive incompatible"), i18n("Setup has checked your PHP open_basedir directive and reckons that it is not sufficient. Please change the directive to include the CONTENIDO installation or turn it off completely."));
                break;
        }

        $this->storeResult($this->testMemoryLimit(), self::C_SEVERITY_WARNING, i18n("PHP memory_limit directive too small"), i18n("The memory_limit directive is set to 32 MB or lower. This might be not enough for CONTENIDO to operate correctly. We recommend to disable this setting completely, as this can cause problems with large CONTENIDO projects."), i18n("Memory limit is either high enough or deactivated"));
        $this->storeResult($this->testPHPSQLSafeMode(), self::C_SEVERITY_ERROR, i18n("PHP sql.safe_mode turned on"), i18n("The PHP directive sql.safe_mode is turned on. This causes problems with the SQL queries issued by CONTENIDO. Please turn that directive off."), i18n("sql.safe_mode is deactivated"));
        $this->storeResult($this->isPHPExtensionLoaded("gd") == self::CON_EXTENSION_AVAILABLE, self::C_SEVERITY_WARNING, i18n("PHP GD-Extension is not loaded"), i18n("The PHP GD-Extension is not loaded. Some third-party modules rely on the GD functionality. If you don't enable the GD extension, you will encounter problems with modules like galleries."), i18n("GD extension loaded"));
        if ($this->isPHPExtensionLoaded("gd") == self::CON_EXTENSION_AVAILABLE) {
            $this->storeResult($this->testGDGIFRead(), self::C_SEVERITY_INFO, i18n("GD-Library GIF read support missing"), i18n("Your GD version doesn't support reading GIF files. This might cause problems with some modules."), i18n("GD is able to read GIFs"));
            $this->storeResult($this->testGDGIFWrite(), self::C_SEVERITY_INFO, i18n("GD-Library GIF write support missing"), i18n("Your GD version doesn't support writing GIF files. This might cause problems with some modules."), i18n("GD is able to write GIFs"));
            $this->storeResult($this->testGDJPEGRead(), self::C_SEVERITY_INFO, i18n("GD-Library JPEG read support missing"), i18n("Your GD version doesn't support reading JPEG files. This might cause problems with some modules."), i18n("GD is able to read JPEGs"));
            $this->storeResult($this->testGDJPEGWrite(), self::C_SEVERITY_INFO, i18n("GD-Library JPEG write support missing"), i18n("Your GD version doesn't support writing JPEG files. This might cause problems with some modules."), i18n("GD is able to write JPEGs"));
            $this->storeResult($this->testGDPNGRead(), self::C_SEVERITY_INFO, i18n("GD-Library PNG read support missing"), i18n("Your GD version doesn't support reading PNG files. This might cause problems with some modules."), i18n("GD is able to read PNGs"));
            $this->storeResult($this->testGDPNGWrite(), self::C_SEVERITY_INFO, i18n("GD-Library PNG write support missing"), i18n("Your GD version doesn't support writing PNG files. This might cause problems with some modules."), i18n("GD is able to write PNGs"));
        }
        $this->storeResult($this->isPHPExtensionLoaded("pcre") == self::CON_EXTENSION_AVAILABLE, self::C_SEVERITY_ERROR, i18n("PHP PCRE Extension is not loaded"), i18n("The PHP PCRE Extension is not loaded. CONTENIDO uses PCRE-functions like preg_repace and preg_match and won't work without the PCRE Extension."), i18n("PCRE extension loaded"));
        $this->storeResult($this->isPHPExtensionLoaded("xml") == self::CON_EXTENSION_AVAILABLE, self::C_SEVERITY_ERROR, i18n("PHP XML Extension is not loaded"), i18n("The PHP XML Extension is not loaded. CONTENIDO won't work without the XML Extension."), i18n("XML extension loaded"));
        $this->storeResult($this->testDOMDocument(), self::C_SEVERITY_ERROR, i18n("Class 'DOMDocument' is not available"), i18n("The class DOMDocument could not be found. Please check your PHP installation and enable the XML extension if necessary. CONTENIDO won't work without it."), i18n("DOMDocument is available"));
        $this->storeResult($this->testXMLParserCreate(), self::C_SEVERITY_ERROR, i18n("Function 'xml_parser_create' is not available"), i18n("The function xml_parser_create could not be found. Please check your PHP installation and enable the XML extension if necessary. CONTENIDO won't work without it."), i18n("xml_parser_create is available"));

        $result = $this->checkImageResizer();
        switch ($result) {
            case self::CON_IMAGERESIZE_CANTCHECK:
                $this->storeResult(false, self::C_SEVERITY_WARNING, i18n("Unable to check for a suitable image resizer"), i18n("Setup has tried to check for a suitable image resizer (which is, for exampl, required for thumbnail creation), but was not able to clearly identify one. If thumbnails won't work, make sure you've got either the GD-extension or ImageMagick available."));
                break;
            case self::CON_IMAGERESIZE_NOTHINGAVAILABLE:
                $this->storeResult(false, self::C_SEVERITY_ERROR, i18n("No suitable image resizer available"), i18n("Setup checked your image resizing support, however, it was unable to find a suitable image resizer. Thumbnails won't work correctly or won't be looking good. Install the GD-Extension or ImageMagick"));
                break;
            case self::CON_IMAGERESIZE_GD:
                $this->storeResult(true, self::C_SEVERITY_WARNING, "", "", i18n("GD extension is available and usable to handle images"));
                break;
            case self::CON_IMAGERESIZE_IMAGEMAGICK:
                $this->storeResult(true, self::C_SEVERITY_WARNING, "", "", i18n("ImageMagick extension is available and usable to handle images"));
                break;
        }

        $this->storeResult($this->testIconv(), self::C_SEVERITY_ERROR, i18n("PHP iconv functions are not available."), i18n("PHP has been compiled with the --without-iconv directive. CONTENIDO won't work without the iconv functions."), i18n("iconv is available"));

        $result = $this->testMySQL($this->_config['db']['connection']['host'], $this->_config['db']['connection']['user'], $this->_config['db']['connection']['password']);
        switch ($result) {
            case self::CON_MYSQL_OK:
                $this->storeResult(true, self::C_SEVERITY_ERROR, "", "", i18n("Database connection works"));
                break;
            case self::CON_MYSQL_STRICT_MODE:
                $this->storeResult(false, self::C_SEVERITY_ERROR, i18n('MySQL is running in strict mode'), i18n('MySQL is running in strict mode, CONTENIDO will not work with this mode. Please change your sql_mode!'));
                break;
            default:
                $this->storeResult(false, self::C_SEVERITY_ERROR, i18n("MySQL database connect failed"), sprintf(i18n("Setup was unable to connect to the MySQL Server (Server %s, Username %s). Please correct the MySQL data and try again.<br><br>The error message given was: %s"), $this->_config['db']['connection']['host'], $this->_config['db']['connection']['user'], $result));
        }

        if ($testFileSystem) {
            $this->storeResult($this->testFilesystem(), self::C_SEVERITY_WARNING, i18n("Permission error"), i18n("CONTENIDO doesn't have the necessary permissions to write all the files it needs. Please check your filesystem permissions."), i18n("Filesystem checks"), i18n("CONTENIDO has all the necessary permissions to read and write files"));
        }
    }

    /**
     * Stores a result in the messages array for later display
     *
     * @param bool $result true for success, false otherwise
     * @param int $severity One one of the C_SEVERITY constants
     * @param string $errorHeadline The headline which will be stored in the
     *        case that $result is false
     * @param string $errorMessage The message which will be stored in the case
     *        that $result is false
     * @param string $successHeadline The headline which will be stored in the
     *        case that $result is true
     * @param string $successMessage The message which will be stored in the
     *        case that $result is true
     */
    public function storeResult($result, $severity, $errorHeadline = "", $errorMessage = "", $successHeadline = "", $successMessage = "") {
        if ($result) {
            $this->_messages[] = array(
                "result" => $result,
                "severity" => $severity,
                "headline" => $successHeadline,
                "message" => $successMessage
            );
        } else {
            $this->_messages[] = array(
                "result" => $result,
                "severity" => $severity,
                "headline" => $errorHeadline,
                "message" => $errorMessage
            );
        }
    }

    /**
     * Returns the message array
     *
     * @see cSystemtest::$_messages
     * @return array
     */
    public function getResults() {
        return $this->_messages;
    }

    /**
     * Returns an array with information about the file, especially the file
     * owner
     *
     * The return array looks like this:
     * array(
     * "info" => $info, //'s' for a socket, 'l' for a symbolic link, '-' for a
     * regular file, 'b' "block special", 'd' for a directory, 'c' "character
     * special", 'p' FIFO pipe, 'u' for unkown
     * "type" => $type, //A more descriptive version of $info
     * "owner" => array(
     * "id" => $id, //the owner id
     * "read" => $read, //true if the owner is allowed to read the file
     * "write" => $write //true if the owner is allowed to write the file
     * )
     * "group" => array(
     * "id" => $id, //the owner group
     * "read" => $read, //true if the owner group is allowed to read the file
     * "write" => $write //true if the owner group is allowed to write the file
     * )
     * "others" => array(
     * "read" => $read, //true if others are allowed to read the file
     * "write" => $write //true if others are allowed to write the file
     * )
     * )
     *
     * @param string $sFilename The path to the file
     * @return boolean array if the file can't be accessed
     */
    protected function getFileInfo($sFilename) {
        if (!cFileHandler::exists($sFilename)) {
            return false;
        }

        $oiFilePermissions = fileperms($sFilename);
        if ($oiFilePermissions === false) {
            return false;
        }

        switch (true) {
            case (($oiFilePermissions & 0xC000) == 0xC000):
                $info = 's';
                $type = "socket";
                break;
            case (($oiFilePermissions & 0xA000) == 0xA000):
                $info = 'l';
                $type = "symbolic link";
                break;
            case (($oiFilePermissions & 0x8000) == 0x8000):
                $info = '-';
                $type = "regular file";
                break;
            case (($oiFilePermissions & 0x6000) == 0x6000):
                $info = 'b';
                $type = "block special";
                break;
            case (($oiFilePermissions & 0x4000) == 0x4000):
                $info = 'd';
                $type = "directory";
                break;
            case (($oiFilePermissions & 0x2000) == 0x2000):
                $info = 'c';
                $type = "character special";
                break;
            case (($oiFilePermissions & 0x1000) == 0x1000):
                $info = 'p';
                $type = "FIFO pipe";
                break;
            default:
                $info = "u";
                $type = "Unknown";
                break;
        }

        $aFileinfo = array();
        $aFileinfo["info"] = $info;
        $aFileinfo["type"] = $type;
        $aFileinfo["owner"]["read"] = ($oiFilePermissions & 0x0100) ? true : false;
        $aFileinfo["owner"]["write"] = ($oiFilePermissions & 0x0080) ? true : false;
        $aFileinfo["group"]["read"] = ($oiFilePermissions & 0x0020) ? true : false;
        $aFileinfo["group"]["write"] = ($oiFilePermissions & 0x0010) ? true : false;
        $aFileinfo["others"]["read"] = ($oiFilePermissions & 0x0004) ? true : false;
        $aFileinfo["others"]["write"] = ($oiFilePermissions & 0x0002) ? true : false;
        $aFileinfo["owner"]["id"] = fileowner($sFilename);
        $aFileinfo["group"]["id"] = filegroup($sFilename);
        return ($aFileinfo);
    }

    /**
     * Returns true if the file is writeable
     *
     * @param string $filename The path to the file
     * @return boolean
     */
    protected function canWriteFile($filename) {
        clearstatcache();
        if (is_file($filename)) {
            return is_writable($filename);
        } else {
            return is_writable(dirname($filename));
        }
    }

    /**
     * Returns true if the given file is a directory and if it is writeable
     *
     * @param string $dirname The path to the directory
     * @return boolean
     */
    protected function canWriteDir($dirname) {
        clearstatcache();
        return is_dir($dirname) && is_writable($dirname);
    }

    /**
     * Returns the current user which runs the PHP interpreter
     *
     * @return number boolean ID or false if unable to determine the user
     */
    protected function getServerUID() {
        if (function_exists("posix_getuid")) {
            return posix_getuid();
        }

        $sFilename = md5(mt_rand()) . ".txt";

        if (is_writeable(".")) {
            cFileHandler::create($sFilename, "test");
            $iUserId = fileowner($sFilename);
            cFileHandler::remove($sFilename);

            return ($iUserId);
        } else {
            if (is_writeable("/tmp/")) {
                cFileHandler::create("/tmp/" . $sFilename, "w");
                $iUserId = fileowner("/tmp/" . $sFilename);
                cFileHandler::remove("/tmp/" . $sFilename);

                return ($iUserId);
            }
            return false;
        }
    }

    /**
     * Returns the current group which runs the PHP interpreter
     *
     * @return number boolean ID or false if unable to determine the group
     */
    protected function getServerGID() {
        if (function_exists("posix_getgid")) {
            return posix_getgid();
        }

        $sFilename = md5(mt_rand()) . ".txt";

        if (is_writeable(".")) {
            cFileHandler::create($sFilename, "test");
            $iUserId = filegroup($sFilename);
            cFileHandler::remove($sFilename);

            return ($iUserId);
        } else {
            return false;
        }
    }

    /**
     * Returns one of the CON_PREDICT suggestions depending on the permissions
     * of the given file
     *
     * @param string $file The path to the file
     * @return int CON_PREDICT_*
     */
    protected function predictCorrectFilepermissions($file) {
        // Check if the system is a windows system. If yes, we can't predict
        // anything.
        if ($this->isWindows()) {
            return self::CON_PREDICT_WINDOWS;
        }

        // Check if the file is read- and writeable. If yes, we don't need to do
        // any
        // further checks.
        if (is_writable($file) && is_readable($file)) {
            return self::CON_PREDICT_SUFFICIENT;
        }

        // If we can't find out the web server UID, we cannot predict the
        // correct
        // mask.
        $iServerUID = $this->getServerUID();
        if ($iServerUID === false) {
            return self::CON_PREDICT_NOTPREDICTABLE;
        }

        // If we can't find out the web server GID, we cannot predict the
        // correct
        // mask.
        $iServerGID = $this->getServerGID();
        if ($iServerGID === false) {
            return self::CON_PREDICT_NOTPREDICTABLE;
        }

        $aFilePermissions = $this->getFileInfo($file);

        if ($this->getSafeModeStatus()) {
            // SAFE-Mode related checks
            if ($iServerUID == $aFilePermissions["owner"]["id"]) {
                return self::CON_PREDICT_CHANGEPERM_SAMEOWNER;
            }

            if ($this->getSafeModeGidStatus()) {
                // SAFE-Mode GID related checks
                if ($iServerGID == $aFilePermissions["group"]["id"]) {
                    return self::CON_PREDICT_CHANGEPERM_SAMEGROUP;
                }

                return self::CON_PREDICT_CHANGEGROUP;
            }
        } else {
            // Regular checks
            if ($iServerUID == $aFilePermissions["owner"]["id"]) {
                return self::CON_PREDICT_CHANGEPERM_SAMEOWNER;
            }

            if ($iServerGID == $aFilePermissions["group"]["id"]) {
                return self::CON_PREDICT_CHANGEPERM_SAMEGROUP;
            }

            return self::CON_PREDICT_CHANGEPERM_OTHERS;
        }
    }

    /**
     * Gets a PHP setting with ini_get
     *
     * @param string $setting A PHP setting
     * @return mixed The value of the PHP setting or NULL if ini_get is disabled
     */
    protected function getPHPIniSetting($setting) {
        // Avoid errors if ini_get is in the disable_functions directive
        $value = @ini_get($setting);

        return $value;
    }

    /**
     * Converts a string like "12M" to the correct number of bytes
     *
     * @param string $val A string in the form of "12K", "12M" or "12G"
     * @return number
     */
    protected function getAsBytes($val) {
        if (strlen($val) == 0) {
            return 0;
        }
        $val = trim($val);
        $last = $val{strlen($val) - 1};
        switch ($last) {
            case 'k':
            case 'K':
                return (int) $val * 1024;
                break;
            case 'm':
            case 'M':
                return (int) $val * 1048576;
                break;
            case 'g':
            case 'G':
                return (int) $val * 1048576 * 1024;
                break;
            default:
                return $val;
        }
    }

    /**
     * Connects to the database with the given settings
     *
     * @param string $host The database host
     * @param string $username The database user
     * @param string $password The database user password
     * @return boolean cDb an array with the cDB object on the first place and a
     *         boolean on the second
     */
    protected function doMySQLConnect($host, $username, $password) {
        $aOptions = array(
            'connection' => array(
                'host' => $host,
                'user' => $username,
                'password' => $password
            )
        );
        try {
            $db = new cDb($aOptions);
        } catch (cDbException $e) {
            return array(
                $db,
                false
            );
        }

        if ($db->connect() == 0) {
            return array(
                $db,
                false
            );
        } else {
            return array(
                $db,
                true
            );
        }
    }

    /**
     * Checks if a given extension is loaded.
     *
     * @param string $extension A PHP extension
     * @return int Returns one of the CON_EXTENSION constants
     */
    public function isPHPExtensionLoaded($extension) {
        $value = extension_loaded($extension);

        if ($value === NULL) {
            return self::CON_EXTENSION_CANTCHECK;
        }

        if ($value === true) {
            return self::CON_EXTENSION_AVAILABLE;
        }

        if ($value === false) {
            return self::CON_EXTENSION_UNAVAILABLE;
        }
    }

    /**
     * Returns true if the interpreter is run on Windows
     *
     * @return boolean
     */
    public function isWindows() {
        if (strtolower(substr(PHP_OS, 0, 3)) == "win") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Test PHP function
     *
     * @return boolean true if the test passed and false if not
     */
    public function testPHPVersion() {
        if (version_compare(phpversion(), CON_SETUP_MIN_PHP_VERSION, '>=') == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function getSafeModeStatus() {
        if ($this->getPHPIniSetting("safe_mode") == "1") {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function getSafeModeGidStatus() {
        if ($this->getPHPIniSetting("safe_mode_gid") == "1") {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testXMLParserCreate() {
        return function_exists("xml_parser_create");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testFileUploadSetting() {
        return $this->getPHPIniSetting('file_uploads');
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testMagicQuotesRuntimeSetting() {
        return !$this->getPHPIniSetting('magic_quotes_runtime');
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testMagicQuotesSybaseSetting() {
        return !$this->getPHPIniSetting('magic_quotes_sybase');
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testMaxExecutionTime() {
        return intval($this->getPHPIniSetting('max_execution_time')) >= 30;
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testZIPArchive() {
        return class_exists("ZipArchive");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testMemoryLimit() {
        $memoryLimit = $this->getAsBytes($this->getPHPIniSetting("memory_limit"));
        return ($memoryLimit > 1024 * 1024 * 32) || ($memoryLimit == 0);
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testPHPSQLSafeMode() {
        return !$this->getPHPIniSetting('sql.safe_mode');
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testDOMDocument() {
        return class_exists("DOMDocument");
    }

    /**
     *
     * @param string $ext
     * @return bool true if the test passed and false if not
     */
    public function testPHPExtension($ext) {
        return $this->isPHPExtensionLoaded($ext) == CON_EXTENSION_AVAILABLE;
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testIconv() {
        return function_exists("iconv");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testGDGIFRead() {
        if (($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_AVAILABLE) && ($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_CANTCHECK)) {
            return false;
        }
        return function_exists("imagecreatefromgif");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testGDGIFWrite() {
        if (($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_AVAILABLE) && ($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_CANTCHECK)) {
            return false;
        }
        return function_exists("imagegif");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testGDJPEGRead() {
        if (($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_AVAILABLE) && ($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_CANTCHECK)) {
            return false;
        }
        return function_exists("imagecreatefromjpeg");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testGDJPEGWrite() {
        if (($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_AVAILABLE) && ($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_CANTCHECK)) {
            return false;
        }
        return function_exists("imagejpeg");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testGDPNGRead() {
        if (($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_AVAILABLE) && ($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_CANTCHECK)) {
            return false;
        }
        return function_exists("imagecreatefrompng");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testGDPNGWrite() {
        if (($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_AVAILABLE) && ($this->isPHPExtensionLoaded('gd') != self::CON_EXTENSION_CANTCHECK)) {
            return false;
        }
        return function_exists("imagepng");
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testMySQLExtension() {
        if ($this->isPHPExtensionLoaded("mysql") == self::CON_EXTENSION_AVAILABLE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testMySQLiExtension() {
        if ($this->isPHPExtensionLoaded("mysqli") == self::CON_EXTENSION_AVAILABLE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @return boolean true if the test passed and false if not
     */
    public function testMySQLModeStrict($host, $username, $password) {
        // host, user and password
        $dbCfg = array(
            'connection' => array(
                'host' => $host,
                'user' => $username,
                'password' => $password
            )
        );

        $db = new cDb($dbCfg);
        $db->query('SELECT LOWER(@@GLOBAL.sql_mode) AS sql_mode');
        if ($db->nextRecord()) {
            if (strpos($db->f('sql_mode'), 'strict_trans_tables') !== false || strpos($db->f('sql_mode'), 'strict_all_tables') !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @return int 1 if the test passed and > 1 if not
     */
    public function testMySQL($host, $username, $password) {
        list($handle, $status) = $this->doMySQLConnect($host, $username, $password);

        $errorMessage = "";
        if ($this->testMySQLiExtension() && !$this->testMySQLExtension()) {
            $errorMessage = mysqli_error($handle->getLinkId());
        } else {
            $errorMessage = mysql_error();
        }
        if ($errorMessage != "") {
            return $errorMessage;
        }

        if ($handle->getLinkId()->errno == 1045) {
            return self::CON_MYSQL_CANT_CONNECT;
        }

        if (!$this->testMySQLModeStrict($host, $username, $password)) {
            return self::CON_MYSQL_STRICT_MODE;
        }

        return self::CON_MYSQL_OK;
    }

    /**
     *
     * @param bool $testConfig
     * @param bool $testFrontend
     * @return bool true if the test passed and false if not
     */
    public function testFilesystem($testConfig = true, $testFrontend = true) {
        global $cfgClient;

        $status = true;

        $files = array(
            // check files
            array(
                'filename' => $this->_config['path']['contenido_logs'] . "errorlog.txt",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_logs'] . "setuplog.txt",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "pseudo-cron.log",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "session_cleanup.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "send_reminder.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "optimize_database.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "move_old_stats.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "move_articles.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "linkchecker.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "run_newsletter_job.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "setfrontenduserstate.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cronlog'] . "advance_workflow.php.job",
                'severity' => self::C_SEVERITY_WARNING
            ),
            array(
                'filename' => $this->_config['path']['contenido_cache'],
                'severity' => self::C_SEVERITY_WARNING,
                'dir' => true
            ),
            array(
                'filename' => $this->_config['path']['contenido_temp'],
                'severity' => self::C_SEVERITY_WARNING,
                'dir' => true
            ),
            array(
                'filename' => $this->_config['path']['contenido_config'] . "config.php",
                'severity' => self::C_SEVERITY_ERROR,
                'config' => $testConfig
            )
        );

        $frontendFiles = array(
            "cache",
            "cache/code",
            "css",
            "data",
            "data/layouts",
            "data/logs",
            "data/modules",
            "data/version",
            "data/version/css",
            "data/version/js",
            "data/version/layout",
            "data/version/module",
            "data/version/templates",
            "js",
            "templates",
            "upload"
        );

        $ret = true;
        foreach ($files as $key => $file) {

            $name = $file['filename'];
            $severity = $file['severity'];
            $dir = $file['dir'];
            $frontend = $file['frontend'];
            $config = $file['config'];

            if (array_key_exists('frontend', $file) && $frontend != false) {
                $ret = $this->testSingleFile($name, $severity, $frontend);
            } else if (array_key_exists('config', $file) && $config != false) {
                $ret = $this->testSingleFile($name, $severity);
            } else if (!array_key_exists('frontend', $file) && !array_key_exists('config', $file)) {
                $ret = $this->testSingleFile($name, $severity, $config);
            }
            if ($ret == false) {
                $status = false;
            }
        }

        if ($testFrontend) {
            foreach ($cfgClient as $oneClient) {
                if (!is_array($oneClient)) {
                    continue;
                }
                foreach ($frontendFiles as $file) {

                    // If data/layouts or data/modules not exist, do not display an error message
                    // Cause: At CONTENIDO 4.8 both folders do not exist
                    if (($file == "data/layouts" || $file == "data/modules") && !is_dir($oneClient["path"]["frontend"] . $file)) {
						continue;
                    } else {
                        $ret = $this->testSingleFile($oneClient["path"]["frontend"] . $file, self::C_SEVERITY_WARNING, true);
                    }

                    if ($ret == false) {
                        $status = false;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Checks a single file or directory wether it is writeable or not
     *
     * @param string $filename The file
     * @param int $severity The resulting C_SEVERITY constant should the test
     *        fail
     * @param bool $dir True if the $filename is a directory
     * @throws Exception Throws a generic Exception in the event that the
     *         permissions are wrong
     * @return boolean Returns true if everything is fine
     */
    protected function testSingleFile($filename, $severity, $dir = false) {
        if (strpos($filename, $this->_config["path"]["frontend"]) === 0) {
            $length = strlen($this->_config["path"]["frontend"]) + 1;
            $shortFilename = substr($filename, $length);
        } else { // for dirs
            $shortFilename = $filename;
        }

        if (!$dir) {
            $status = $this->canWriteFile($filename);
        } else {
            $status = $this->canWriteDir($filename);
        }

        $title = sprintf(i18n("Can't write %s"), $shortFilename);
        $message = sprintf(i18n("Setup or CONTENIDO can't write to the file %s. Please change the file permissions to correct this problem."), $shortFilename);

        if ($status == false) {
            if (cFileHandler::exists($filename)) {
                $perm = $this->predictCorrectFilepermissions($filename);

                switch ($perm) {
                    case self::CON_PREDICT_WINDOWS:
                        $predictMessage = i18n("Your Server runs Windows. Due to that, Setup can't recommend any file permissions.");
                        break;
                    case self::CON_PREDICT_NOTPREDICTABLE:
                        $predictMessage = sprintf(i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file %s, especially in environments where ACL (Access Control Lists) are used."), $shortFilename);
                        break;
                    case self::CON_PREDICT_CHANGEPERM_SAMEOWNER:
                        $mfileperms = substr(sprintf("%o", fileperms($filename)), -3);
                        $mfileperms{0} = intval($mfileperms{0}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server and the owner of your files are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the file mask to %s or set the owner to allow writing the file."), $shortFilename, $mfileperms);
                        break;
                    case self::CON_PREDICT_CHANGEPERM_SAMEGROUP:
                        $mfileperms = substr(sprintf("%o", fileperms($filename)), -3);
                        $mfileperms{1} = intval($mfileperms{1}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server's group and the group of your files are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the file mask to %s or set the group to allow writing the file."), $shortFilename, $mfileperms);
                        break;
                    case self::CON_PREDICT_CHANGEPERM_OTHERS:
                        $mfileperms = substr(sprintf("%o", fileperms($filename)), -3);
                        $mfileperms{2} = intval($mfileperms{2}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server is not equal to the file owner, and is not in the webserver's group. It would be highly insecure to allow world write acess to the files. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the file mask to %s or set the others to allow writing the file."), $shortFilename, $mfileperms);
                        break;
                }
            } else {
                $target = dirname($filename);

                $perm = $this->predictCorrectFilepermissions($target);

                switch ($perm) {
                    case self::CON_PREDICT_WINDOWS:
                        $predictMessage = i18n("Your Server runs Windows. Due to that, Setup can't recommend any directory permissions.");
                        break;
                    case self::CON_PREDICT_NOTPREDICTABLE:
                        $predictMessage = sprintf(i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file or directory %s, especially in environments where ACL (Access Control Lists) are used."), dirname($shortFilename));
                        break;
                    case self::CON_PREDICT_CHANGEPERM_SAMEOWNER:
                        $mfileperms = substr(sprintf("%o", @fileperms($target)), -3);
                        $mfileperms{0} = intval($mfileperms{0}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server and the owner of your directory are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the directory mask to %s or set the owner to allow writing the directory."), dirname($shortFilename), $mfileperms);
                        break;
                    case self::CON_PREDICT_CHANGEPERM_SAMEGROUP:
                        $mfileperms = substr(sprintf("%o", @fileperms($target)), -3);
                        $mfileperms{1} = intval($mfileperms{1}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server's group and the group of your directory are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the directory mask to %s or set the group to allow writing the directory."), dirname($shortFilename), $mfileperms);
                        break;
                    case self::CON_PREDICT_CHANGEPERM_OTHERS:
                        $mfileperms = substr(sprintf("%o", @fileperms($target)), -3);
                        $mfileperms{2} = intval($mfileperms{2}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server is not equal to the directory owner, and is not in the webserver's group. It would be highly insecure to allow world write acess to the directory. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the directory mask to %s or set the others to allow writing the directory."), dirname($shortFilename), $mfileperms);
                        break;
                }
            }

            $this->storeResult(false, $severity, $title, $message . "<br><br>" . $predictMessage);
            if ($title && $message) {
                $status = false;
            }
        }

        return $status;
    }

    /**
     *
     * @return boolean true if the test passed and false if not
     */
    public function testFrontendFolderCreation() {
        $directories = array(
            "cms/cache",
            "cms/cache/code",
            "cms/css",
            "cms/data",
            "cms/data/layouts",
            "cms/data/modules",
            "cms/data/version",
            "cms/data/version/css",
            "cms/data/version/js",
            "cms/data/version/layout",
            "cms/data/version/module",
            "cms/data/version/templates",
            "cms/js",
            "cms/templates",
            "cms/upload"
        );

        $ret = true;

        foreach ($directories as $dir) {
            if (!cFileHandler::exists("../" . $dir)) {
                if (!mkdir("../" . $dir)) {
                    $ret = false;
                    $this->storeResult(false, self::C_SEVERITY_WARNING, sprintf(i18n("Could not find or create directory %s"), $dir), i18n("The frontend expects certain directories to exist and it needs to be able to write to these directories."));
                } else {
                    if (!cFileHandler::chmod("../" . $dir, "777")) {
                        $ret = false;
                        $this->storeResult(false, self::C_SEVERITY_WARNING, sprintf(i18n("Could not find or create directory %s"), $dir), i18n("The frontend expects certain directories to exist and it needs to be able to write to these directories."));
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Checks for the open_basedir directive and returns one of the CON_BASEDIR
     * constants
     *
     * @return int
     */
    public function checkOpenBasedirCompatibility() {
        $value = $this->getPHPIniSetting("open_basedir");

        if ($this->isWindows()) {
            $aBasedirEntries = explode(";", $value);
        } else {
            $aBasedirEntries = explode(":", $value);
        }

        if (count($aBasedirEntries) == 1 && $aBasedirEntries[0] == $value) {
            return self::CON_BASEDIR_NORESTRICTION;
        }

        if (in_array(".", $aBasedirEntries) && count($aBasedirEntries) == 1) {
            return self::CON_BASEDIR_DOTRESTRICTION;
        }

        $sCurrentDirectory = getcwd();

        foreach ($aBasedirEntries as $entry) {
            if (stristr($sCurrentDirectory, $entry)) {
                return self::CON_BASEDIR_RESTRICTIONSUFFICIENT;
            }
        }

        return self::CON_BASEDIR_INCOMPATIBLE;
    }

    /**
     * Checks the available image resizer classes and functions
     *
     * @return int Returns one of the CON_IMAGERESIZE constants
     */
    public function checkImageResizer() {
        $iGDStatus = $this->isPHPExtensionLoaded('gd');

        if ($iGDStatus == self::CON_EXTENSION_AVAILABLE) {
            return self::CON_IMAGERESIZE_GD;
        }

        if (function_exists('imagecreate')) {
            return self::CON_IMAGERESIZE_GD;
        }

        checkAndInclude($this->_config['path']['contenido'] . 'includes/functions.api.images.php');
        if (capiIsImageMagickAvailable()) {
            return self::CON_IMAGERESIZE_IMAGEMAGICK;
        }

        if ($iGDStatus === self::CON_EXTENSION_CANTCHECK) {
            return self::CON_IMAGERESIZE_CANTCHECK;
        } else {
            return self::CON_IMAGERESIZE_NOTHINGAVAILABLE;
        }
    }

    /**
     *
     * @param string $setupType
     * @param string $databaseName
     * @param string $databasePrefix
     */
    public function checkSetupMysql($setupType, $databaseName, $databasePrefix) {
        switch ($setupType) {
            case "setup":

                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $databaseName);

                if ($status) {
                    // Yes, database exists
                    $db = getSetupMySQLDBConnection();
                    $db->connect();

                    // Check if data already exists
                    $db->query('SHOW TABLES LIKE "%s_actions"', $databasePrefix);

                    if ($db->nextRecord()) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("MySQL database already exists and seems to be filled", "setup"), sprintf(i18n("Setup checked the database %s and found the table %s. It seems that you already have a CONTENIDO installation in this database. If you want to install anyways, change the database prefix. If you want to upgrade from a previous version, choose 'upgrade' as setup type.", "setup"), $databaseName, sprintf("%s_actions", $databasePrefix)));
                        return;
                    }

                    // Check if data already exists
                    $db->query('SHOW TABLES LIKE "%s_test"', $databasePrefix);
                    if ($db->nextRecord()) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("MySQL test table already exists in the database", "setup"), sprintf(i18n("Setup checked the database %s and found the test table %s. Please remove it before continuing.", "setup"), $databaseName, sprintf("%s_test", $databasePrefix)));
                        return;
                    }

                    // Good, table doesn't exist. Check for database permisions
                    $status = checkMySQLTableCreation($db, $databaseName, sprintf("%s_test", $databasePrefix));
                    if (!$status) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database", "setup"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so.", "setup"), $databaseName));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $databaseName, sprintf("%s_test", $databasePrefix));
                    if (!$status) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to remove the test table", "setup"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually.", "setup"), sprintf("%s_test", $databasePrefix), $databaseName, sprintf("%s_test", $databasePrefix)));
                    }
                } else {
                    $db->connect();
                    // Check if database can be created
                    $status = checkMySQLDatabaseCreation($db, $databaseName);
                    if (!$status) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create the database in the MySQL server", "setup"), sprintf(i18n("Setup tried to create a test database and failed. Please assign database creation permissions to the database user you entered, ask an administrator to do so, or create the database manually.", "setup")));
                        return;
                    }

                    // Check for database permisions
                    $status = checkMySQLTableCreation($db, $databaseName, sprintf("%s_test", $databasePrefix));
                    if (!$status) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database", "setup"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so.", "setup"), $databaseName));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $databaseName, sprintf("%s_test", $databasePrefix));
                    if (!$status) {
                        $this->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to remove the test table", "setup"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually.", "setup"), sprintf("%s_test", $databasePrefix), $databaseName, sprintf("%s_test", $databasePrefix)));
                    }
                }
                break;
            case "upgrade":
                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $databaseName);
                if (!$status) {
                    $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("No data found for the upgrade", "setup"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s doesn't exist. You need to copy your database first before running setup.", "setup"), $databaseName));
                    return;
                }

                $db = getSetupMySQLDBConnection();

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';
                $db->query(sprintf($sql, $databasePrefix));
                if (!$db->nextRecord()) {
                    $this->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("No data found for the upgrade", "setup"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s contains no tables. You need to copy your database first before running setup.", "setup"), $databaseName));
                    return;
                }

                break;
        }
    }

}
