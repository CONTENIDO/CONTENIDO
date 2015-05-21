<?php

/**
 * This file contains the module handler class.
 * TODO: Rework comments of this class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Rusmir Jusufovic
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for new modul structere.
 * Saves the Modul-Input in a file (input.php)
 * and
 * Modul-Output in a file(output.php).
 * All moduls of a clients are in [frontend]/modules/.
 *
 * @package Core
 * @subpackage Backend
 */
class cModuleHandler {

    /**
     * Path to a modul dir
     *
     * @var string
     */
    private $_modulePath;

    /**
     * Path to the modul dir where are all the moduls of a client (frontendpath)
     *
     * @var string
     */
    private $_path;

    /**
     * Id of the Modul
     *
     * @var int
     */
    protected $_idmod = NULL;

    /**
     * The name of the modul
     *
     * @var string
     */
    private $_moduleName = NULL;

    /**
     * Description of the modul.
     *
     * @var string
     */
    protected $_description;

    /**
     * The type of the modul.
     *
     * @var string
     */
    protected $_type;

    /**
     * The alias name of the modul
     *
     * @var string
     */
    protected $_moduleAlias;

    /**
     * The names of the modul directories.
     *
     * @var array
     */
    protected $_directories = array(
        'css' => 'css/',
        'js' => 'js/',
        'template' => 'template/',
        'image' => 'image/',
        'lang' => 'lang/',
        'php' => 'php/'
    );

    /**
     * CONTENIDO cfg
     *
     * @var array
     */
    protected $_cfg = NULL;

    /**
     * Contenido cfgClient
     *
     * @var array
     */
    protected $_cfgClient = NULL;

    /**
     * id of the Client
     *
     * @var int
     */
    protected $_client = '0';

    /**
     * The code of the modul input
     *
     * @var string
     */
    protected $_input = '';

    /**
     * The code of the modul output
     *
     * @var string
     */
    protected $_output = '';

    /**
     * Encoding oft the site
     *
     * @var string
     */
    protected $_encoding = '';

    /**
     * Which format of encoding should be files (input/output/translation...)
     * getEffectiveSetting('encoding', 'file_encoding', 'UTF-8')
     *
     * @var string
     */
    protected $_fileEncoding = '';

    /**
     * The id of the lang
     *
     * @var int
     */
    protected $_idlang = -1;

    /**
     *
     * @var cDb
     */
    private $_db = NULL;

    /**
     *
     * @var array
     */
    protected static $_encodingStore = array();

    /**
     * Constructor for the class cModuleHandler.
     * With this class you can create a new module, rename a module.
     * You can save a Output from modules and Input in a file.
     * The save rules are [Modulname] (is unique) the files input and output
     * will be named [Modulname]_input.php , [Modulname]_output.php
     *
     * @param int $idmod [optional]
     *         the id of the module
     * @throws cException if the module directory can not be created
     */
    public function __construct($idmod = NULL) {
        global $cfg, $cfgClient, $lang, $client;
        $this->_cfg = $cfg;
        $this->_client = $client;
        $this->_cfgClient = $cfgClient;
        $this->_idlang = $lang;
        $this->_encoding = self::getEncoding();
        $this->_fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

        $this->_db = cRegistry::getDb();

        $this->_idmod = $idmod;

        $this->_initByModule($idmod);

        if ($this->_makeModuleDirectory() == false) {
            throw new cException('Can not create main module directory.');
        }
    }

    /**
     *
     * @param int $overrideLanguageId [optional]
     * @return mixed
     */
    public static function getEncoding($overrideLanguageId = 0) {
        $lang = cRegistry::getLanguageId();

        if ((int) $overrideLanguageId != 0) {
            $lang = $overrideLanguageId;
        }

        if ((int) $lang == 0) {
            $clientId = cRegistry::getClientId();

            $clientsLangColl = new cApiClientLanguageCollection();
            $clientLanguages = $clientsLangColl->getLanguagesByClient($clientId);
            sort($clientLanguages);

            if (isset($clientLanguages[0]) && (int) $clientLanguages[0] != 0) {
                $lang = $clientLanguages[0];
            }
        }

        if (!isset(self::$_encodingStore[$lang])) {
            $cApiLanguage = new cApiLanguage($lang);
            self::$_encodingStore[$lang] = $cApiLanguage->get('encoding');
        }

        return self::$_encodingStore[$lang];
    }

    /**
     * Exist the modulname in directory
     *
     * @param string $name
     * @return bool
     */
    public function modulePathExistsInDirectory($name) {
        return is_dir($this->_cfgClient[$this->_client]['module']['path'] . $name . '/');
    }

    /**
     * Save a content in the file, use for css/js
     *
     * @param string $templateName
     * @param string $fileType
     * @param string $fileContent
     * @param string $saveDirectory [optional]
     * @return bool|string
     */
    public function saveContentToFile($templateName, $fileType, $fileContent, $saveDirectory = 'cache') {
        $sSaveDirectory = $this->_cfgClient[$this->_client]['path']['frontend'] . $saveDirectory . '/';
        if (!is_dir($sSaveDirectory)) {
            return false;
        }

        $templateName = str_replace(' ', '_', $templateName);
        $templateName = strtolower($templateName);
        $fileOperation = cFileHandler::write($sSaveDirectory . $templateName . '.' . $fileType, $fileContent);
        if ($fileOperation === false) {
            return false;
        }
        $url = $this->_cfgClient[$this->_client]['path']['htmlpath'] . $saveDirectory . '/' . $templateName . '.' . $fileType;

        // Remove protocol so CSS & JS can be displayed for HTTPS too!
        $url = str_replace('http://', '//', $url);

        return $url;
    }

    /**
     * Get the cleaned name
     *
     * @param string $name
     *         mod name
     * @param string $defaultChar [optional]
     *         default character
     * @return string
     */
    public static function getCleanName($name, $defaultChar = '_') {
        // the first character of modul/Layut name should be [a-zA-Z0-9]|_|-
        $name = cString::cleanURLCharacters($name);
        // get the first charcte
        $firstChar = substr($name, 0, 1);
        if (!preg_match('/^[a-zA-Z0-9]|_|-$/', $firstChar)) {
            // replace the first character
            $name = $defaultChar . substr($name, 1);
        }

        return $name;
    }

    /**
     * Init the vars of the class.
     *
     * @param mixed $db
     */
    public function initWithDatabaseRow($db) {
        if (is_object($db)) {
            $this->_initByModule($db->f('idmod'));
        }
    }

    /**
     * Init the vars of the class, make a query to the Db
     *
     * @param int $idmod [optional]
     *         the id of the modul
     */
    protected function _initByModule($idmod = NULL) {
        if ((int) $idmod == 0) {
            return;
        }

        $cApiModule = new cApiModule($idmod);

        if (true === $cApiModule->isLoaded()) {
            $this->_idmod = $idmod;
            $this->_client = $cApiModule->get('idclient');
            $this->_description = $cApiModule->get('description');
            $this->_type = $cApiModule->get('type');
            $this->_input = '';
            $this->_output = '';

            $this->_moduleAlias = $cApiModule->get('alias');
            $this->_moduleName = $cApiModule->get('name');
            $this->_path = $this->_cfgClient[$this->_client]['module']['path'];
            $this->_modulePath = $this->_path . $this->_moduleAlias . '/';
        }
    }

    /**
     * Get the Modul Path also cms path + module + module name.
     *
     * @return string
     */
    public function getModulePath() {
        return $this->_modulePath;
    }

    /**
     * Get the template path.
     * If file is set it will
     * return the complete path + file
     *
     * @param string $file [optional]
     * @return string
     */
    public function getTemplatePath($file = '') {
        return $this->_modulePath . $this->_directories['template'] . $file;
    }

    /**
     * Get the css path of the modul
     *
     * @return string
     */
    public function getCssPath() {
        return $this->_modulePath . $this->_directories['css'];
    }

    /**
     * Get the php path of the modul
     *
     * @return string
     */
    public function getPhpPath() {
        return $this->_modulePath . $this->_directories['php'];
    }

    /**
     * Get the js path of the modul
     *
     * @return string
     */
    public function getJsPath() {
        return $this->_modulePath . $this->_directories['js'];
    }

    /**
     * Get the main css file modulenam.css
     *
     * @return string
     */
    public function getCssFileName() {
        return $this->_moduleAlias . '.css';
    }

    /**
     * Returns random characters
     *
     * @param int $count
     *         amount of characters
     * @return string
     */
    protected function getRandomCharacters($count) {
        $micro1 = microtime();
        $rand1 = rand(0, time());
        $rand2 = rand(0, time());
        return substr(md5($micro1 . $rand1 . $rand2), 0, $count);
    }

    /**
     * Check if exist a file
     *
     * @param string $type
     *         js | template | css the directory of the file
     * @param string $fileName
     *         file name
     * @return bool
     */
    public function existFile($type, $fileName) {
        return cFileHandler::exists($this->_modulePath . $this->_directories[$type] . $fileName);
    }

    /**
     * Delete file
     *
     * @param string $type
     *         js |template | css directory of the file
     * @param string $fileName
     *         file name
     * @return bool
     */
    public function deleteFile($type, $fileName) {
        if ($this->existFile($type, $fileName)) {
            return unlink($this->_modulePath . $this->_directories[$type] . $fileName);
        } else {
            return false;
        }
    }

    /**
     * Create and save new file
     *
     * @param string $type
     *         css | js | template directory of the file
     * @param string $fileName [optional]
     *         file name
     * @param string $content [optional]
     *         content of the file
     * @return bool
     *         true if file can be created, and false otherwise
     */
    public function createModuleFile($type, $fileName = NULL, $content = '') {
        // create directory if not exist
        if (!$this->createModuleDirectory($type)) {
            return false;
        }

        // if not set use default filename
        if ($fileName == NULL || $fileName == '') {
            $fileName = $this->_moduleAlias;

            if ($type == 'template') {
                $fileName = $fileName . '.html';
            } else {
                $fileName = $fileName . '.' . $type;
            }
        }

        // create and save file contents
        if ($type == 'css' || $type == 'js' || $type == 'template') {
            if (!$this->existFile($type, $fileName)) {
                $content = cString::recodeString($content, $this->_encoding, $this->_fileEncoding);
                if (!$this->isWritable($this->_modulePath . $this->_directories[$type] . $fileName, $this->_modulePath . $this->_directories[$type])) {
                    return false;
                }

                if (cFileHandler::write($this->_modulePath . $this->_directories[$type] . $fileName, $content) === false) {
                    $notification = new cGuiNotification();
                    $notification->displayNotification('error', i18n("Can't make file: ") . $fileName);
                    return false;
                }
            } else {
                $content = cString::recodeString($content, $this->_encoding, $this->_fileEncoding);
                if (!$this->isWritable($this->_modulePath . $this->_directories[$type] . $fileName, $this->_modulePath . $this->_directories[$type])) {
                    return false;
                }
                if (cFileHandler::write($this->_modulePath . $this->_directories[$type] . $fileName, $content) === false) {
                    $notification = new cGuiNotification();
                    $notification->displayNotification('error', i18n("Can't make file: ") . $fileName);
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Rename a file
     *
     * @param string $type
     *         css | js | template directory of the file
     * @param string $oldFileName
     *         old name of the file
     * @param string $newFileName
     *         the new name of the file
     * @return bool
     *         by success return true
     */
    public function renameModuleFile($type, $oldFileName, $newFileName) {
        if ($this->existFile($type, $newFileName)) {
            return false;
        }

        if (!$this->existFile($type, $oldFileName)) {
            return false;
        }

        return rename($this->_modulePath . $this->_directories[$type] . $oldFileName, $this->_modulePath . $this->_directories[$type] . $newFileName);
    }

    /**
     * Get the name of the main js file (modulname.js)
     *
     * @return string
     *         the name of the js file
     */
    public function getJsFileName() {
        return $this->_moduleAlias . '.js';
    }

    /**
     * Get the content of file, modul js or css or template or php
     *
     * @param string $directory
     *         where in module should we look
     * @param string $fileTyp
     *         css or js
     * @param string $fileName [optional]
     * @return string|bool
     */
    public function getFilesContent($directory, $fileTyp, $fileName = NULL) {
        if ($fileName == NULL) {
            $fileName = $this->_moduleAlias . '.' . $fileTyp;
        }

        if ($this->existFile($directory, $fileName)) {
            $content = cFileHandler::read($this->_modulePath . $this->_directories[$directory] . $fileName);
            $content = iconv($this->_fileEncoding, $this->_encoding . '//IGNORE', $content);
            return $content;
        }

        return false;
    }

    /**
     * Make main module directory.
     *
     * @return bool
     */
    protected function _makeModuleDirectory() {
        // Do not display error on login page
        if ((int) $this->_client == 0) {
            return true;
        }

        $sMainModuleDirectory = $this->_cfgClient[$this->_client]['module']['path'];

        // make
        if (!is_dir($sMainModuleDirectory) && $sMainModuleDirectory != NULL) {
            if (mkdir($sMainModuleDirectory, 0777, true) == false) {
                return false;
            } else {
                cDirHandler::setDefaultDirPerms($sMainModuleDirectory);
            }
        }

        return true;
    }

    /**
     * Get all files from a module directory
     *
     * @param string $moduleDirectory
     *         template css or js...
     * @return array
     */
    public function getAllFilesFromDirectory($moduleDirectory) {
        $dir = $this->_modulePath . $this->_directories[$moduleDirectory];
        return cDirHandler::read($dir);
    }

    /**
     * Set the new modul name.
     *
     * @param string $name
     */
    public function changeModuleName($name) {
        $this->_moduleAlias = $name;
        $this->_modulePath = $this->_path . $this->_moduleAlias . '/';
    }

    /**
     * Removes this module from the filesystem.
     * Also deletes the version files.
     *
     * @return bool
     *         true on success or false on failure
     */
    public function eraseModule() {
        // Delete modules only if we find info.xml at module path
        if (cFileHandler::exists($this->_modulePath . 'info.xml')) {
            return cDirHandler::recursiveRmdir($this->_modulePath);
        } else {
            return false;
        }
    }

    /**
     * Read the input of the file _input.php
     *
     * @param bool $issource [optional]
     * @return bool|string
     *         Contents of the Module file (_input.php)
     */
    public function readInput($issource = false) {
        if (cFileHandler::exists($this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_input.php') == false) {
            return false;
        }

        $content = cFileHandler::read($this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_input.php');

        if ($issource == true) {
            $content = conHtmlentities($content);
        }

        return iconv($this->_fileEncoding, $this->_encoding . '//IGNORE', $content);
    }

    /**
     * Read the output of the file _output.php
     *
     * @param bool $issource [optional]
     * @return bool|string
     *         Contents of the Module file( _output.php)
     */
    public function readOutput($issource = false) {
        if (cFileHandler::exists($this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_output.php') == false) {
            return false;
        }

        $content = cFileHandler::read($this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_output.php');

        if ($issource == true) {
            $content = conHtmlentities($content);
        }

        return iconv($this->_fileEncoding, $this->_encoding . '//IGNORE', $content);
    }

    /**
     * Make a directory template/css/image/js/php if not exist
     *
     * @param string $type
     * @return bool
     */
    protected function createModuleDirectory($type) {
        if (array_key_exists($type, $this->_directories)) {
            if (!is_dir($this->_modulePath . $this->_directories[$type])) {
                if (cDirHandler::create($this->_modulePath . $this->_directories[$type]) == false) {
                    return false;
                } else
                    cDirHandler::setDefaultDirPerms($this->_modulePath . $this->_directories[$type]);
            } else {
                return true;
            }
        }

        return true;
    }

    /**
     * Can write/create a file
     *
     * @param string $fileName
     *         file name
     * @param string $directory
     *         directory where is the file
     * @return bool
     *         success true else false
     */
    public function isWritable($fileName, $directory) {
        if (cFileHandler::exists($fileName)) {
            if (!is_writable($fileName)) {
                return false;
            }
        } else {
            if (!is_writable($directory)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check write permissions for this module
     *
     * @param string $type
     *         php oder template
     * @return bool
     *         true or false
     */
    public function moduleWriteable($type) {
        // check if type directory inside module folder exists and has write permissions
        if (true === cFileHandler::exists($this->_modulePath . $this->_directories[$type])) {
            return cFileHandler::writeable($this->_modulePath . $this->_directories[$type]);
        }

        // check if module folder exists and has write permissions
        if (true === cFileHandler::exists($this->_modulePath)) {
            return cFileHandler::writeable($this->_modulePath);
        }

        return false;
    }

    /**
     * Save a string into the file (_output.php).
     *
     * @param string $output [optional]
     * @return bool
     *         if the action (save contents into the file _output.php) is
     *         successful return true else false
     */
    public function saveOutput($output = NULL) {
        $fileName = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_output.php';

        if (!$this->createModuleDirectory('php') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['php'])) {
            return false;
        }

        if ($output == NULL) {
            $output = $this->_output;
        }

        $output = cString::recodeString($output, $this->_encoding, $this->_fileEncoding);

        $fileOperation = cFileHandler::write($fileName, $output);

        if ($fileOperation === false) {
            return false; // return false if file_put_contents dont work
        } else {
            cFileHandler::setDefaultFilePerms($fileName);
            return true; // return true if file_put_contents working
        }
    }

    /**
     * Save a string into the file (_input.php)
     *
     * @param string $input [optional]
     * @return bool
     *         if the action (save contents into the file _input.php) is
     *         successful return true else false
     */
    public function saveInput($input = NULL) {
        $fileName = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_input.php';

        if (!$this->createModuleDirectory('php') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['php'])) {
            return false;
        }

        if ($input == NULL) {
            $input = $this->_input;
        }

        $input = cString::recodeString($input, $this->_encoding, $this->_fileEncoding);

        $fileOperation = cFileHandler::write($fileName, $input);

        if ($fileOperation === false) {
            return false; // return false if file_put_contents dont work
        } else {
            cFileHandler::setDefaultFilePerms($fileName);
            return true; // return true if file_put_contents working
        }
    }

    /**
     * This method save a xml file with modul information.
     * If the params not set, get the value from this
     *
     * @param string $moduleName [optional]
     *         name of the modul
     * @param string $description [optional]
     *         description of the modul
     * @param string $type [optional]
     *         type of the modul
     * @param string $alias [optional]
     * @return true
     *         if success else false
     */
    public function saveInfoXML($moduleName = NULL, $description = NULL, $type = NULL, $alias = NULL) {
        if ($moduleName == NULL) {
            $moduleName = $this->_moduleName;
        }

        if ($description == NULL) {
            $description = $this->_description;
        }

        if ($type == NULL) {
            $type = $this->_type;
        }

        if ($alias == NULL) {
            $alias = $this->_moduleAlias;
        }

        $oWriter = new cXmlWriter();
        $oRootElement = $oWriter->addElement('module', '', NULL);

        $oWriter->addElement('name', conHtmlSpecialChars($moduleName), $oRootElement);
        $oWriter->addElement('description', conHtmlSpecialChars($description), $oRootElement);
        $oWriter->addElement('type', conHtmlSpecialChars($type), $oRootElement);
        $oWriter->addElement('alias', conHtmlSpecialChars($alias), $oRootElement);

        return $oWriter->saveToFile($this->_modulePath, 'info.xml');
    }

    /**
     * Create a new module in the module dir.
     * The module name will be [ModuleName] example Contact_Form or GoogleMaps2.
     *
     * @param string $input [optional]
     * @param string $output [optional]
     * @return bool
     *         if module exist or mkdir and saveInput and saveOutput success
     *         return true. Else if the mkdir or saveInput or saveOutput not
     *         success return false.
     */
    public function createModule($input = '', $output = '') {
        if ($input != '') {
            $this->_input = $input;
        }

        if ($output != '') {
            $this->_output = $output;
        }

        if ($this->modulePathExists()) {
            return true;
        }

        if (mkdir($this->_modulePath) == false) {
            return false;
        } else {
            cDirHandler::setDefaultDirPerms($this->_modulePath);
        }

        // create other directories
        foreach ($this->_directories as $directory) {
            if (!is_dir($this->_modulePath . $directory)) {
                if (mkdir($this->_modulePath . $directory) == false) {
                    return false;
                } else {
                    cDirHandler::setDefaultDirPerms($this->_modulePath . $directory);
                }
            }
        }

        // could not save the info xml
        if ($this->saveInfoXML() == false) {
            return false;
        }

        // Save empty strings into the modul files, if someone trying to read
        // contents bevore save into the files
        $retInput = $this->saveInput();
        $retOutput = $this->saveOutput();

        if ($retInput == false || $retOutput == false) {
            return false;
        }

        return true;
    }

    /**
     * Rename a modul and the input and output files.
     *
     * @param string $old
     *         old name of the modul
     * @param string $new
     *         new name of the modul
     *
     * @return bool
     *         true if success
     */
    public function renameModul($old, $new) {
        // try to rename the dir
        if (rename($this->_path . $old, $this->_path . $new) == false) {
            return false;
        } else {
            $retInput = true;
            $retOutput = true;

            // if file input exist rename it
            if (cFileHandler::exists($this->_path . $new . '/' . $this->_directories['php'] . $old . '_input.php'))
                $retInput = rename($this->_path . $new . '/' . $this->_directories['php'] . $old . '_input.php', $this->_path . $new . '/' . $this->_directories['php'] . $new . '_input.php');

                // if file output exist rename it
            if (cFileHandler::exists($this->_path . $new . '/' . $this->_directories['php'] . $old . '_output.php'))
                $retOutput = rename($this->_path . $new . '/' . $this->_directories['php'] . $old . '_output.php', $this->_path . $new . '/' . $this->_directories['php'] . $new . '_output.php');

                // rename the css file
            if (cFileHandler::exists($this->_path . $new . '/' . $this->_directories['css'] . $old . '.css'))
                rename($this->_path . $new . '/' . $this->_directories['css'] . $old . '.css', $this->_path . $new . '/' . $this->_directories['css'] . $new . '.css');

                // rename the javascript file
            if (cFileHandler::exists($this->_path . $new . '/' . $this->_directories['js'] . $old . '.js'))
                rename($this->_path . $new . '/' . $this->_directories['js'] . $old . '.js', $this->_path . $new . '/' . $this->_directories['js'] . $new . '.js');

                // rename the template file
            if (cFileHandler::exists($this->_path . $new . '/' . $this->_directories['template'] . $old . '.html'))
                rename($this->_path . $new . '/' . $this->_directories['template'] . $old . '.html', $this->_path . $new . '/' . $this->_directories['template'] . $new . '.html');

            if ($retInput == true && $retOutput == true) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Show if the Modul with the modul name exist in modul dir.
     *
     * @return bool
     *         if the modul exist return true, else false
     */
    public function modulePathExists() {
        return is_dir($this->_modulePath);
    }

    /**
     * Test input code
     *
     * @return array
     *         bool state, string errorMessage
     */
    public function testInput() {

        return $this->_testCode('input');

    }

    /**
     * Test output code
     *
     * @return array
     *         bool state, string errorMessage
     */
    public function testOutput() {

        return $this->_testCode('output');

    }

    /**
     * Test module code
     *
     * @param $inputType
     *         string code field type
     * @return array
     *         bool state, string errorMessage
     */
    protected function _testCode($inputType) {

        $result = array(
            'state' => false,
            'errorMessage' => 'Module path not exist'
        );

        if (!$this->modulePathExists()) return $result;

        $module  = new cApiModule($this->_idmod);
        $isError = 'none';

        //Set code as error before checking, if fatal exist
        switch ($module->get("error")) {
            case 'none';
            $toCheck = $inputType;
            break;
            case 'input';
            if ($inputType == 'output') $toCheck = 'both';
            break;
            case 'output';
            if ($inputType == 'input') $toCheck = 'both';
            break;
            case 'both';

            break;
        }
        if ($toCheck !== $module->get("error")) {
            $module->set("error", $toCheck);

            // do not rewrite cache on validation
            // it is rewritten when saving module
            $module->store(true);
        }

        //check code
        switch($inputType) {
            case 'input':

                $code       = $this->readInput();
                $result = $this->_verifyCode($code, $this->_idmod . "i");
                if ($result['state'] !== true) $isError = 'input';

                break;
            case 'output':

                $code       = $this->readOutput();
                $result = $this->_verifyCode($code, $this->_idmod . "o", true);
                if ($result['state'] !== true) $isError = 'output';

                break;
        }

        //update error value for input and output
        switch ($module->get("error")) {
            case 'none';

            break;
            case 'input';
            if ($isError == 'none' && $inputType == 'output') $isError = 'input';
            if ($isError == 'output') $isError = 'both';
            break;
            case 'output';
            if ($isError == 'none' && $inputType == 'input') $isError = 'output';
            if ($isError == 'input') $isError = 'both';
            break;
            case 'both';
            if ($isError == 'none' && $inputType == 'input') $isError = 'output';
            if ($isError == 'none' && $inputType == 'output') $isError = 'input';
            break;
        }

        //Store error information in the database (to avoid re-eval for module
        //overview/menu)
        if ($isError !== $module->get("error")) {
            $module->set("error", $isError);

            // do not rewrite cache on validation
            // it is rewritten when saving module
            $module->store(true);
        }

        return $result;

    }

    /**
     * Check module php code
     *
     * @param string $code
     *         Code to evaluate
     * @param string $id
     *         Unique ID for the test function
     * @param string $output [optional]
     *         true if start in php mode, otherwise false
     * @return array
     *         bool state, string errorMessage
     */
    protected function _verifyCode($code, $id, $output = false) {
        $isError = false;
        $result = array(
            'state' => false,
            'errorMessage' => NULL
        );

        // Put a $ in front of all CMS variables to prevent PHP error messages
        $sql = 'SELECT type FROM ' . $this->_cfg['tab']['type'];
        $this->_db->query($sql);
        while ($this->_db->nextRecord()) {
            $code = str_replace($this->_db->f('type') . '[', '$' . $this->_db->f('type') . '[', $code);
        }

        $code = preg_replace(',\[(\d+)?CMS_VALUE\[(\d+)\](\d+)?\],i', '[\1\2\3]', $code);
        $code = str_replace('CMS_VALUE', '$CMS_VALUE', $code);
        $code = str_replace('CMS_VAR', '$CMS_VAR', $code);

        // If the module is an output module, escape PHP since all output modules
        // enter php mode
        if ($output == true) {
            $code = "?>\n" . $code . "\n<?php";
        }

        // Looks ugly: Paste a function declarator in front of the code
        $code = 'function foo' . $id . ' () {' . $code;
        $code .= "\n}\n";

        // To parse the error message, we prepend and append a phperror tag in front
        // of the output
        $sErs = ini_get('error_prepend_string'); // Save current setting (see below)
        $sEas = ini_get('error_append_string'); // Save current setting (see below)
        @ini_set('error_prepend_string', '<phperror>');
        @ini_set('error_append_string', '</phperror>');

        // Turn off output buffering and error reporting, eval the code
        ob_start();
        $display_errors = ini_get('display_errors');
        @ini_set('display_errors', true);
        $output = eval($code);
        @ini_set('display_errors', $display_errors);

        // Get the buffer contents and turn it on again
        $output = ob_get_contents();
        ob_end_clean();

        // Remove the prepend and append settings
        /*
         * 19.09.2006: Following lines have been disabled, as ini_restore has been
         * disabled by some hosters as there is a security leak in PHP (PHP <= 5.1.6
         * & <= 4.4.4)
         */
        // ini_restore('error_prepend_string');
        // ini_restore('error_append_string');
        @ini_set('error_prepend_string', $sErs); // Restoring settings (see above)
        @ini_set('error_append_string', $sEas); // Restoring settings (see above)

        // Strip out the error message
        if ($isError === false) {
            $isError = strpos($output, '<phperror>');
        }

        // More stripping: Users shouldnt see where the file is located, but they
        // should see the error line
        if ($isError !== false) {
            if (isset($modErrorMessage) === false) {
                $pattern         = '/(<phperror>|<\/phperror>|<b>|<\/b>|<br>|<br \/>)/im';
                $modErrorMessage = trim(preg_replace($pattern, '', $output));
                $errorPart1      = substr($modErrorMessage, 0, strpos($modErrorMessage, ' in '));
                $errorPart2      = substr($modErrorMessage, strpos($modErrorMessage, ' on line '));
                $modErrorMessage = $errorPart1 . $errorPart2;
            }
            $result['errorMessage'] = sprintf(i18n("Error in module. Error location: %s"), $modErrorMessage);

        }

        // Check if there are any php short tags in code, and display error
        $bHasShortTags = false;
        if (preg_match('/<\?\s+/', $code)) {
            $bHasShortTags = true;
            $result['errorMessage'] = i18n('Please do not use short open tags. (Use <?php instead of <?).');
        }

        // Now, check if the magic value is 941. If not, the function didn't compile
        if ($bHasShortTags || $isError !== false) {
            $result['state'] = false;
        } else {
            $result['state'] = true;
        }

        return $result;

    }

    /**
     * Get method for module path value
     * Path to a module dir
     *
     * @return $modulePath
     *         string
     */
    protected static function _getModulePath() {
         return $this->_modulePath;
    }

}
