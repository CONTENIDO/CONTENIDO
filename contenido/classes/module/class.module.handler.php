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
     * The aliac name of the modul
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
     * getEffectiveSetting('encoding', 'file_encoding','UTF-8')
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
     * Construct for the class cModuleHandler.
     * With this class you can
     * make a new Modul, rename a Modul. You can save a Output from Modul and
     * Input in a
     * file. The save rules are [Modulname] (is uneque) the files input and
     * output will be named
     * [Modulname]_input.php , [Modulname]_output.php
     *
     * @param int $idmod the id of the modul
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
     * @param array $cfgClient
     */
    public function modulePathExistsInDirectory($name) {
        return is_dir($this->_cfgClient[$this->_client]['module']['path'] . $name . '/');
    }

    /**
     * Save a content in the file, use for css/js
     *
     * @param string $frontendPath
     * @param string $templateName
     * @param string $fileType
     * @param string $fileContent
     * @return bool string
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
     * @param string $name mod name
     * @param string $defaultChar default character
     */
    public static function getCleanName($name, $defaultChar = '_') {
        // the first character of modul/Layut name should be [a-zA-Z0-9]|_|-
        $name = cApiStrCleanURLCharacters($name);
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
     * @param array $modulData
     *            [idmod],[name],[input],[output],[forntedpath],[client]
     */
    public function initWithDatabaseRow($db) {
        if (is_object($db)) {
            $this->_initByModule($db->f('idmod'));
        }
    }

    /**
     * Init the vars of the class, make a query to the Db
     *
     * @param int $idmod the id of the modul
     */
    protected function _initByModule($idmod = NULL) {
        if ((int) $idmod == 0) {
            return;
        }

        $cApiModule = new cApiModule($idmod);

        if ($cApiModule->virgin == false) {
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
     * return the complete paht + file
     *
     * @param string $file
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
     * @param integer $count amount of characters
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
     * @param string $type js | template | css the directory of the file
     * @param string $fileName file name
     */
    public function existFile($type, $fileName) {
        return cFileHandler::exists($this->_modulePath . $this->_directories[$type] . $fileName);
    }

    /**
     * Delete file
     *
     * @param string $type js |template | css directory of the file
     * @param string $fileName file name
     */
    public function deleteFile($type, $fileName) {
        if ($this->existFile($type, $fileName)) {
            return unlink($this->_modulePath . $this->_directories[$type] . $fileName);
        } else {
            return false;
        }
    }

    /**
     * Make and save new file
     *
     * @param string $type css | js | template directory of the file
     * @param string $fileName file name
     * @param string $content content of the file
     */
    public function createModuleFile($type, $fileName = NULL, $content = '') {
        // make directory if not exist
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

        // make and save file contents
        if ($type == 'css' || $type == 'js' || $type == 'template') {
            if (!$this->existFile($type, $fileName)) {
                $content = iconv($this->_encoding, $this->_fileEncoding, $content);
                if (!$this->isWritable($this->_modulePath . $this->_directories[$type] . $fileName, $this->_modulePath . $this->_directories[$type])) {
                    return false;
                }

                if (cFileHandler::write($this->_modulePath . $this->_directories[$type] . $fileName, $content) === false) {
                    $notification = new cGuiNotification();
                    $notification->displayNotification('error', i18n("Can't make file: ") . $fileName);
                    return false;
                }
            } elseif ($content != '') {
                $content = iconv($this->_encoding, $this->_fileEncoding, $content);
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
     * @param string $type css | js | template directory of the file
     * @param string $oldFileName old name of the file
     * @param string $newFileName the new name of the file
     * @return boolean by success return true
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
     * @return string the name of the js file
     */
    public function getJsFileName() {
        return $this->_moduleAlias . '.js';
    }

    /**
     * Get the content of file, modul js or css or template or php
     *
     * @param string $directory where in module should we look
     * @param string $fileTyp css or js
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
     * @return boolean
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
                cFileHandler::setDefaultDirPerms($sMainModuleDirectory);
            }
        }

        return true;
    }

    /**
     * Get all files from a module directory
     *
     * @param string $moduleDirectory template css or js...
     * @return array
     */
    public function getAllFilesFromDirectory($moduleDirectory) {
        $retArray = array();
        $dir = $this->_modulePath . $this->_directories[$moduleDirectory];

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    // is file a dir or not
                    if ($file != '..' && $file != '.' && !is_dir($dir . $file . '/')) {
                        $retArray[] = $file;
                    }
                }
            }
        }
        return $retArray;
    }

    /**
     * Set the new modul name.
     *
     * @var $name string
     */
    public function changeModuleName($name) {
        $this->_moduleAlias = $name;
        $this->_modulePath = $this->_path . $this->_moduleAlias . '/';
    }

    /**
     * Removes this module from the filesystem.
     * Also deletes the version files.
     *
     * @return bool true on success or false on failure
     */
    public function eraseModule() {
        // Delete modules only if we find info.xml at module path
        if (cFileHandler::exists($this->_modulePath . 'info.xml')) {
            return cFileHandler::recursiveRmdir($this->_modulePath);
        } else {
            return false;
        }
    }

    /**
     * Read the input of the file _input.php
     *
     * @return string Contents of the Module file (_input.php)
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
     * @return string Contents of the Module file( _output.php)
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
     */
    protected function createModuleDirectory($type) {
        if (array_key_exists($type, $this->_directories)) {
            if (!is_dir($this->_modulePath . $this->_directories[$type])) {
                if (@mkdir($this->_modulePath . $this->_directories[$type]) == false) {
                    return false;
                } else
                    cFileHandler::setDefaultDirPerms($this->_modulePath . $this->_directories[$type]);
            } else {
                return true;
            }
        }

        return true;
    }

    /**
     * Can write/create a file
     *
     * @param string $fileName file name
     * @param string $directory directory where is the file
     * @return bool, success true else false
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
     * @param string $type php oder template
     * @return boolean true or false
     */
    public function moduleWriteable($type) {
        if (!cFileHandler::writeable($this->_modulePath . $this->_directories[$type])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Save a string into the file (_output.php).
     *
     * @param string
     * @return bool if the action (save contents into the file _output.php is
     *         success) return true else false
     */
    public function saveOutput($output = NULL) {
        $fileName = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_output.php';

        if (!$this->createModuleDirectory('php') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['php'])) {
            return false;
        }

        if ($output == NULL) {
            $output = $this->_output;
        }

        $output = iconv($this->_encoding, $this->_fileEncoding, $output);

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
     * @param string
     * @return bool if the action (save contents into the file _input.php is
     *         success) return true else false
     */
    public function saveInput($input = NULL) {
        $fileName = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_input.php';

        if (!$this->createModuleDirectory('php') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['php'])) {
            return false;
        }

        if ($input == NULL) {
            $input = $this->_input;
        }

        $input = iconv($this->_encoding, $this->_fileEncoding, $input);

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
     * @param string $moduleName name of the modul
     * @param string $description description of the modul
     * @param string $type type of the modul
     * @return true if success else false
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
        $oRootElement = $oWriter->addElement('module', '', null);

        $oWriter->addElement('name', conHtmlSpecialChars($moduleName), $oRootElement);
        $oWriter->addElement('description', conHtmlSpecialChars($description), $oRootElement);
        $oWriter->addElement('type', conHtmlSpecialChars($type), $oRootElement);
        $oWriter->addElement('alias', conHtmlSpecialChars($alias), $oRootElement);

        return $oWriter->saveToFile($this->_modulePath, 'info.xml');
    }

    /**
     * Make a new module into the modul dir.
     * The modul name will be [ModulName] example
     * Contact_Form or GoogleMaps2.
     *
     * @return bool if modul exist or mkdir and saveInput and saveOutput success
     *         return true.
     *         Else if the mkdir or saveInput or saveOutput not success return
     *         false.
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
            cFileHandler::setDefaultDirPerms($this->_modulePath);
        }

        // make others directorys
        foreach ($this->_directories as $directory) {
            if (!is_dir($this->_modulePath . $directory)) {
                if (mkdir($this->_modulePath . $directory) == false) {
                    return false;
                } else {
                    cFileHandler::setDefaultDirPerms($this->_modulePath . $directory);
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
     * @param string $old old name of the modul
     * @param string $new new name of the modul
     *
     * @return bool true if success
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
     * return bool if the modul exist return true, else false
     */
    public function modulePathExists() {
        return is_dir($this->_modulePath);
    }

}
