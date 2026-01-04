<?php

/**
 * This file contains the module handler class.
 *
 * @todo refactor documentation
 *
 * @package    Core
 * @subpackage Backend
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for new module structure.
 *
 * Saves the module-input in file "[module_alias]_input.php"
 * and the module-output in file "[module_alias]_output.php".
 * All modules of a client are located in [frontend]/modules/.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleHandler
{

    /**
     * @var string Path to a module dir.
     */
    private $_modulePath;

    /**
     * @var string Path to the module dir where are all the modules of a client (frontendpath).
     */
    private $_path;

    /**
     * @var int Id of the module.
     */
    protected $_idmod = null;

    /**
     * @var string The name of the module.
     */
    private $_moduleName = null;

    /**
     * @var string Description of the module.
     */
    protected $_description;

    /**
     * @var string The type of the module.
     */
    protected $_type;

    /**
     * @var string The alias name of the module.
     */
    protected $_moduleAlias;

    /**
     * @var array Name/Path mapping of the module directories.
     */
    protected $_directories = [
        'css' => 'css/',
        'js' => 'js/',
        'template' => 'template/',
        'image' => 'image/',
        'lang' => 'lang/',
        'php' => 'php/',
    ];

    /**
     * @var array CONTENIDO cfg.
     */
    protected $_cfg = null;

    /**
     * @var array CONTENIDO cfgClient.
     */
    protected $_cfgClient = null;

    /**
     * @var int Id of the client.
     */
    protected $_client = 0;

    /**
     * @var string The code of the module input.
     */
    protected $_input = '';

    /**
     * @var string The code of the module output.
     */
    protected $_output = '';

    /**
     * @var string Encoding oft the site.
     */
    protected $_encoding = '';

    /**
     * Which format of encoding should be files (input/output/translation...)
     *
     * getEffectiveSetting('encoding', 'file_encoding', 'UTF-8')
     *
     * @var string
     */
    protected $_fileEncoding = '';

    /**
     * @var int The id of the lang.
     */
    protected $_idlang = -1;

    /**
     * @var cDb Database instance
     */
    private $_db;

    /**
     * @var array Language encoding list
     */
    protected static $_encodingStore = [];

    /**
     * Constructor to create an instance of this class.
     *
     * With this class you can create a new module, rename a module.
     * You can save an output from modules and Input in a file.
     * The save rules are [module_alias] (is unique) the files input
     * and output will be named [module_alias]_input.php,
     * and [module_alias]_output.php respectively.
     *
     * @param cApiModule|array|int $module [optional] The module instance or the module
     *      recordset array from the database or the id of the module
     * @throws cException If the module directory can not be created
     */
    public function __construct($module = null)
    {
        $this->_cfg = cRegistry::getConfig();
        $this->_client = cRegistry::getClientId();
        $this->_cfgClient = cRegistry::getClientConfig();
        $this->_idlang = cRegistry::getLanguageId();
        $this->_encoding = self::getEncoding();
        $this->_fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

        $this->_db = cRegistry::getDb();

        $this->_idmod = is_numeric($module) ? $module : null;

        if (!is_null($module)) {
            $this->_initByModule($module);
        }

        if (!$this->_makeModuleDirectory()) {
            throw new cException('Can not create main module directory.');
        }
    }

    /**
     * Gets the encoding for the current language.
     *
     * @param int $overrideLanguageId [optional]
     * @throws cDbException|cException
     */
    public static function getEncoding(int $overrideLanguageId = 0): string
    {
        $languageId = cRegistry::getLanguageId();
        if ($overrideLanguageId > 0) {
            $languageId = $overrideLanguageId;
        }

        if ($languageId == 0) {
            // Get clients first language as a fallback
            $clientsLangColl = new cApiClientLanguageCollection();
            $clientLanguage = $clientsLangColl->getFirstLanguageIdByClient(cRegistry::getClientId());
            if ($clientLanguage) {
                $languageId = $clientLanguage;
            }
        }

        if (!isset(self::$_encodingStore[$languageId])) {
            $cApiLanguage = new cApiLanguage($languageId);
            self::$_encodingStore[$languageId] = (string) $cApiLanguage->get('encoding');
        }

        return self::$_encodingStore[$languageId];
    }

    /**
     * Exist the modulname in directory.
     */
    public function modulePathExistsInDirectory(string $name): bool
    {
        return is_dir($this->_cfgClient[$this->_client]['module']['path'] . $name . '/');
    }

    /**
     * Save a content in the file, use for css/js.
     *
     * @param string $templateName
     * @param string $fileType
     * @param mixed $content The data to write. Can be either a string, an array or a stream resource.
     * @param string $saveDirectory [optional]
     * @return string|bool URL on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function saveContentToFile(string $templateName, string $fileType, $content, string $saveDirectory = 'cache')
    {
        $sSaveDirectory = $this->_cfgClient[$this->_client]['path']['frontend'] . $saveDirectory . '/';
        if (!is_dir($sSaveDirectory)) {
            return false;
        }

        $templateName = str_replace(' ', '_', $templateName);
        $templateName = cString::toLowerCase($templateName);
        $fileOperation = cFileHandler::write($sSaveDirectory . $templateName . '.' . $fileType, $content);
        if ($fileOperation === false) {
            return false;
        }
        $path = cAsset::frontend($saveDirectory . '/' . $templateName . '.' . $fileType, $this->_client);
        $url = $this->_cfgClient[$this->_client]['path']['htmlpath'] . $path;

        // Remove protocol so CSS & JS can be displayed for HTTPS too!
        $url = str_replace('http://', '//', $url);

        return $url;
    }

    /**
     * Get the cleaned name.
     *
     * @param string $name Module  name
     * @param string $defaultChar Default character
     */
    public static function getCleanName(string $name, string $defaultChar = '_'): string
    {
        // the first character of module/layout name should be [a-zA-Z0-9]|_|-
        $name = cString::cleanURLCharacters($name);
        // get the first character
        $firstChar = cString::getPartOfString($name, 0, 1);
        if (!preg_match('/^[a-zA-Z0-9]|_|-$/', $firstChar)) {
            // replace the first character
            $name = $defaultChar . cString::getPartOfString($name, 1);
        }

        return $name;
    }

    /**
     * Initialize the variables of the class.
     */
    public function initWithDatabaseRow(?cDb $db = null)
    {
        if (is_object($db)) {
            $this->_initByModule($db->toArray());
        }
    }

    /**
     * Initialize the variables of the class.
     *
     * @param cApiModule|array|int $module [optional] The module instance or the module recordset
     *      array from the database or the id of the module
     * @throws cDbException|cException
     */
    protected function _initByModule($module = null)
    {
        if (is_numeric($module) && cSecurity::toInteger($module) == 0) {
            return;
        } elseif (is_array($module) && empty($module)) {
            return;
        } elseif (is_object($module) && !$module instanceof cApiModule) {
            return;
        }

        if (is_numeric($module)) {
            $cApiModule = new cApiModule($module);
        } elseif (is_array($module)) {
            $cApiModule = new cApiModule();
            $cApiModule->loadByRecordSet($module);
        } else {
            $cApiModule = $module;
        }

        if ($cApiModule->isLoaded()) {
            $this->_idmod = cSecurity::toInteger($cApiModule->get('idmod'));
            $this->_client = cSecurity::toInteger($cApiModule->get('idclient'));
            $this->_description = $cApiModule->get('description') ?? '';
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
     * Get the name of module.
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * Get the module Path also cms path + module + module name.
     *
     * @return string
     */
    public function getModulePath()
    {
        return $this->_modulePath;
    }

    /**
     * Get the template path.
     *
     * If file is set it will return the complete path + file.
     *
     * @param string $file [optional]
     * @return string
     */
    public function getTemplatePath(string $file = '')
    {
        return $this->_modulePath . $this->_directories['template'] . $file;
    }

    /**
     * Get the css path of the module.
     *
     * @return string
     */
    public function getCssPath()
    {
        return $this->_modulePath . $this->_directories['css'];
    }

    /**
     * Get the php path of the module.
     *
     * @return string
     */
    public function getPhpPath()
    {
        return $this->_modulePath . $this->_directories['php'];
    }

    /**
     * Get the js path of the module.
     *
     * @return string
     */
    public function getJsPath()
    {
        return $this->_modulePath . $this->_directories['js'];
    }

    /**
     * Get the main css file [module_alias].css.
     */
    public function getCssFileName(): string
    {
        return $this->_moduleAlias . '.css';
    }

    /**
     * Returns random characters.
     *
     * @param int $count Amount of characters
     */
    protected function getRandomCharacters(int $count): string
    {
        $micro1 = microtime();
        $rand1 = rand(0, time());
        $rand2 = rand(0, time());
        return cString::getPartOfString(md5($micro1 . $rand1 . $rand2), 0, $count);
    }

    /**
     * Check if exist a file.
     *
     * @param string $type js | template | css the directory of the file
     * @param string $fileName file name
     */
    public function existFile(string $type, string $fileName): bool
    {
        return cFileHandler::exists($this->_modulePath . $this->_directories[$type] . $fileName);
    }

    /**
     * Delete file.
     *
     * @param string $type js |template | css directory of the file
     * @param string $fileName file name
     */
    public function deleteFile(string $type, string $fileName): bool
    {
        if ($this->existFile($type, $fileName)) {
            return unlink($this->_modulePath . $this->_directories[$type] . $fileName);
        } else {
            return false;
        }
    }

    /**
     * Create and save new file.
     *
     * @param string $type css | js | template directory of the file
     * @param string $fileName [optional] File name
     * @param string $content [optional] Content of the file
     * @return bool true on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function createModuleFile(string $type, $fileName = null, string $content = ''): bool
    {
        // create directory if not exist
        if (!$this->createModuleDirectory($type)) {
            return false;
        }

        // if not set use default filename
        if ($fileName == null || $fileName == '') {
            $fileName = $this->_moduleAlias;
            $fileName = $type === 'template' ? $fileName . '.html' : $fileName . '.' . $type;
        } else {
            $fileName = cString::replaceDiacritics($fileName);
        }

        // create and save file contents
        if ($type == 'css' || $type == 'js' || $type == 'template') {
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
            return false;
        }

        return true;
    }

    /**
     * Rename a file.
     *
     * @param string $type
     *         css | js | template directory of the file
     * @param string $oldFileName
     *         old name of the file
     * @param string $newFileName
     *         the new name of the file
     * @return bool
     *         true on success or false on failure
     */
    public function renameModuleFile($type, $oldFileName, $newFileName)
    {
        $newFileName = cString::replaceDiacritics($newFileName);

        if ($this->existFile($type, $newFileName)) {
            return false;
        }

        if (!$this->existFile($type, $oldFileName)) {
            return false;
        }

        return rename($this->_modulePath . $this->_directories[$type] . $oldFileName, $this->_modulePath . $this->_directories[$type] . $newFileName);
    }

    /**
     * Get the name of the main js file (modulname.js).
     *
     * @return string The name of the js file
     */
    public function getJsFileName(): string
    {
        return $this->_moduleAlias . '.js';
    }

    /**
     * Get the content of file, module js or css or template or php.
     *
     * @param string $directory Where in module should we look
     * @param string $fileTyp css or js
     * @param string $fileName [optional]
     * @return string|bool
     * @throws cInvalidArgumentException
     */
    public function getFilesContent($directory, $fileTyp, $fileName = null)
    {
        if ($fileName == null) {
            $fileName = $this->_moduleAlias . '.' . $fileTyp;
        }

        if ($this->existFile($directory, $fileName)) {
            $content = cFileHandler::read($this->_modulePath . $this->_directories[$directory] . $fileName);
            return iconv($this->_fileEncoding, $this->_encoding . '//IGNORE', $content);
        }

        return false;
    }

    /**
     * Make main module directory.
     *
     * @throws cInvalidArgumentException
     */
    protected function _makeModuleDirectory(): bool
    {
        // Do not display error on login page
        if (cSecurity::toInteger($this->_client) == 0) {
            return true;
        }

        $sMainModuleDirectory = $this->_cfgClient[$this->_client]['module']['path'];

        // make
        if (!is_dir($sMainModuleDirectory) && $sMainModuleDirectory != null) {
            if (!mkdir($sMainModuleDirectory, cDirHandler::getDefaultPermissions(), true)) {
                return false;
            } else {
                cDirHandler::setDefaultPermissions($sMainModuleDirectory);
            }
        }

        return true;
    }

    /**
     * Get all files from a module directory.
     *
     * @param string $moduleDirectory Template css or js...
     */
    public function getAllFilesFromDirectory(string $moduleDirectory): array
    {
        $dir = $this->_modulePath . $this->_directories[$moduleDirectory];

        return (array) cDirHandler::read($dir);
    }

    /**
     * Set the new module name.
     *
     * @param string $name
     */
    public function changeModuleName(string $name)
    {
        $this->_moduleAlias = $name;
        $this->_modulePath = $this->_path . $this->_moduleAlias . '/';
    }

    /**
     * Removes this module from the filesystem.
     * Also deletes the version files.
     *
     * @return bool true on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function eraseModule(): bool
    {
        // Delete modules only if we find info.xml at module path
        if (cFileHandler::exists($this->_modulePath . 'info.xml')) {
            return cDirHandler::recursiveRmdir($this->_modulePath);
        } else {
            return false;
        }
    }

    /**
     * Read the input of the file _input.php.
     *
     * @param bool $isSource [optional]
     * @return string|bool Content of module input file or false on failure
     * @throws cInvalidArgumentException
     */
    public function readInput(bool $isSource = false)
    {
        $inputFilePath = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_input.php';
        if (!cFileHandler::exists($inputFilePath)) {
            return false;
        }

        $content = cFileHandler::read($inputFilePath);
        if ($isSource) {
            $content = conHtmlentities($content);
        }

        return iconv($this->_fileEncoding, $this->_encoding . '//IGNORE', $content);
    }

    /**
     * Read the output of the file _output.php.
     *
     * @param bool $isSource [optional]
     * @return bool|string Content of module output file or false on failure
     * @throws cInvalidArgumentException
     */
    public function readOutput(bool $isSource = false)
    {
        $inputFilePath = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_output.php';
        if (!cFileHandler::exists($inputFilePath)) {
            return false;
        }

        $content = cFileHandler::read($inputFilePath);
        if ($isSource) {
            $content = conHtmlentities($content);
        }

        return iconv($this->_fileEncoding, $this->_encoding . '//IGNORE', $content);
    }

    /**
     * Make a directory template/css/image/js/php if not exist.
     *
     * @param string $type
     * @return bool true on success or false on failure
     * @throws cInvalidArgumentException
     */
    protected function createModuleDirectory(string $type): bool
    {
        if (array_key_exists($type, $this->_directories)) {
            if (!is_dir($this->_modulePath . $this->_directories[$type])) {
                if (!cDirHandler::create($this->_modulePath . $this->_directories[$type])) {
                    return false;
                } else
                    cDirHandler::setDefaultPermissions($this->_modulePath . $this->_directories[$type]);
            } else {
                return true;
            }
        }

        return true;
    }

    /**
     * Can write/create a file.
     *
     * @param string $fileName File name
     * @param string $directory Directory where is the file
     */
    public function isWritable(string $fileName, string $directory): bool
    {
        if (cFileHandler::exists($fileName)) {
            if (!cFileHandler::writeable($fileName)) {
                return false;
            }
        } else {
            if (!cFileHandler::writeable($directory)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check write permissions for this module.
     *
     * @param string $type php oder template
     * @return bool
     */
    public function moduleWriteable(string $type): bool
    {
        // check if type directory inside module folder exists and has write permissions
        if (cFileHandler::exists($this->_modulePath . $this->_directories[$type])) {
            return cFileHandler::writeable($this->_modulePath . $this->_directories[$type]);
        }

        // check if module folder exists and has write permissions
        if (cFileHandler::exists($this->_modulePath)) {
            return cFileHandler::writeable($this->_modulePath);
        }

        return false;
    }

    /**
     * Save a string into the file (_output.php).
     *
     * @param string $output [optional]
     * @return bool true on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function saveOutput($output = null): bool
    {
        $fileName = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_output.php';

        if (!$this->createModuleDirectory('php')
            || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['php'])) {
            return false;
        }

        if ($output == null) {
            $output = $this->_output;
        }

        $output = cString::recodeString($output, $this->_encoding, $this->_fileEncoding);

        $fileOperation = cFileHandler::write($fileName, $output);

        if ($fileOperation === false) {
            return false; // return false if file_put_contents dont work
        } else {
            cFileHandler::setDefaultPermissions($fileName);
            return true; // return true if file_put_contents working
        }
    }

    /**
     * Save a string into the file (_input.php).
     *
     * @param string $input [optional]
     * @return bool true on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function saveInput($input = null): bool
    {
        $fileName = $this->_modulePath . $this->_directories['php'] . $this->_moduleAlias . '_input.php';

        if (!$this->createModuleDirectory('php')
            || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['php'])) {
            return false;
        }

        if ($input == null) {
            $input = $this->_input;
        }

        $input = cString::recodeString($input, $this->_encoding, $this->_fileEncoding);

        $fileOperation = cFileHandler::write($fileName, $input);

        if ($fileOperation === false) {
            return false; // return false if file_put_contents dont work
        } else {
            cFileHandler::setDefaultPermissions($fileName);
            return true; // return true if file_put_contents working
        }
    }

    /**
     * This method save a xml file with module information.
     * If the params not set, get the value from this.
     *
     * @param string $moduleName [optional] Name of the module
     * @param string $description [optional] Description of the module
     * @param string $type [optional] Type of the module
     * @param string $alias [optional]
     * @return bool true if success else false
     * @throws cException|DOMException
     */
    public function saveInfoXML($moduleName = null, $description = null, $type = null, $alias = null): bool
    {
        if ($moduleName === null) {
            $moduleName = $this->_moduleName;
        }

        if ($description === null) {
            $description = $this->_description;
        }

        if ($type === null) {
            $type = $this->_type;
        }

        if ($alias === null) {
            $alias = $this->_moduleAlias;
        }

        $oWriter = new cXmlWriter();
        $oWriter->getDomDocument()->formatOutput = true;
        $oRootElement = $oWriter->addElement('module', '', null);

        $oWriter->addElement('name', conHtmlSpecialChars($moduleName), $oRootElement);
        $oWriter->addElement('description', conHtmlSpecialChars($description), $oRootElement);
        $oWriter->addElement('type', conHtmlSpecialChars($type), $oRootElement);
        $oWriter->addElement('alias', conHtmlSpecialChars($alias), $oRootElement);

        return $oWriter->saveToFile($this->_modulePath, 'info.xml');
    }

    /**
     * Create a new module in the module dir.
     *
     * The module name will be [ModuleName] example Contact_Form or GoogleMaps2.
     *
     * @param string $input [optional]
     * @param string $output [optional]
     * @return bool If module exist or mkdir and saveInput and saveOutput success return true.
     *      Else if the mkdir or saveInput or saveOutput not success return false.
     * @throws cException|cInvalidArgumentException
     */
    public function createModule($input = '', $output = ''): bool
    {
        if ($input != '') {
            $this->_input = $input;
        }

        if ($output != '') {
            $this->_output = $output;
        }

        if ($this->modulePathExists()) {
            return true;
        }

        if (!mkdir($this->_modulePath)) {
            return false;
        } else {
            cDirHandler::setDefaultPermissions($this->_modulePath);
        }

        // create other directories
        foreach ($this->_directories as $directory) {
            if (!is_dir($this->_modulePath . $directory)) {
                if (!mkdir($this->_modulePath . $directory)) {
                    return false;
                } else {
                    cDirHandler::setDefaultPermissions($this->_modulePath . $directory);
                }
            }
        }

        // could not save the info xml
        if (!$this->saveInfoXML()) {
            return false;
        }

        // Save empty strings into the module files, if someone trying to
        // read contents before save into the files
        $retInput = $this->saveInput();
        $retOutput = $this->saveOutput();

        if (!$retInput || !$retOutput) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated Since [2023-02-08] 4.10.2, use {@see cModuleHandler::renameModule} instead
     */
    public function renameModul($old, $new)
    {
        return $this->renameModule($old, $new);
    }

    /**
     * Rename a module and the input and output files.
     *
     * @param string $old old name of the module
     * @param string $new new name of the module
     * @return bool true on success or false on failure
     */
    public function renameModule($old, $new): bool
    {
        // try to rename the dir
        if (!rename($this->_path . $old, $this->_path . $new)) {
            return false;
        }

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

        if ($retInput && $retOutput) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Show if the module with the module name exist in module dir.
     *
     * @return bool If the module exist return true, else false
     */
    public function modulePathExists(): bool
    {
        return is_string($this->_modulePath) && is_dir($this->_modulePath);
    }

    /**
     * Test input code.
     *
     * @return array{state: bool, errorMessage: string}} bool state, string errorMessage
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function testInput(): array
    {
        return $this->_testCode('input');
    }

    /**
     * Test output code.
     *
     * @return array{state: bool, errorMessage: string}} bool state, string errorMessage
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function testOutput(): array
    {
        return $this->_testCode('output');
    }

    /**
     * Test module code.
     *
     * @param string $inputType code field type, 'input' or 'output'
     * @return array{state: bool, errorMessage: string}} bool state, string errorMessage
     * @throws cDbException|cInvalidArgumentException|cException
     */
    protected function _testCode(string $inputType): array
    {
        $result = [
            'state' => false,
            'errorMessage' => 'Module path not exist',
        ];

        if (!$this->modulePathExists()) {
            return $result;
        }

        $module = new cApiModule($this->_idmod);
        $isError = 'none';
        $toCheck = '';

        // Set code as error before checking, if fatal exist
        switch ($module->get('error')) {
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
        if ($toCheck !== $module->get('error')) {
            $module->set('error', $toCheck);

            // Do not rewrite cache on validation
            // it is rewritten when saving module
            $module->store(true);
        }

        // Check code
        switch ($inputType) {
            case 'input':
                $code = $this->readInput();
                $result = $this->_verifyCode($code, $this->_idmod . 'i');
                if ($result['state'] !== true) $isError = 'input';
                break;
            case 'output':
                $code = $this->readOutput();
                $result = $this->_verifyCode($code, $this->_idmod . 'o', true);
                if ($result['state'] !== true) $isError = 'output';
                break;
        }

        // Update error value for input and output
        switch ($module->get('error')) {
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

        // Store error information in the database (to avoid re-eval for module
        // overview/menu)
        if ($isError !== $module->get('error')) {
            $module->set('error', $isError);

            // Do not rewrite cache on validation
            // it is rewritten when saving module
            $module->store(true);
        }

        return $result;
    }

    /**
     * Check module php code.
     *
     * @param string $code Code to evaluate
     * @param string $id Unique ID for the test function
     * @param bool $output [optional] true if start in php mode, otherwise false
     * @return array{state: bool, errorMessage: string}} bool state, string errorMessage
     * @throws cDbException|cException
     */
    protected function _verifyCode($code, $id, $output = false): array
    {
        $isError = false;
        $result = [
            'state' => false,
            'errorMessage' => null,
        ];

        // Put a $ in front of all CMS variables to prevent PHP error messages
        $typeCollection = new cApiTypeCollection();
        $typeCollection->addResultField('type');
        $typeCollection->query();
        $types = $typeCollection->fetchTable(['type' => 'type']);
        foreach ($types as $entry) {
            $code = str_replace($entry['type'] . '[', '$' . $entry['type'] . '[', $code);
        }

        $code = preg_replace(',\[(\d+)?CMS_VALUE\[(\d+)\](\d+)?\],i', '[\1\2\3]', $code);
        $code = str_replace('CMS_VALUE', '$CMS_VALUE', $code);
        $code = str_replace('CMS_VAR', '$CMS_VAR', $code);

        // If the module is an output module, escape PHP since all output modules
        // enter php mode
        if ($output === true) {
            $code = "?>\n" . $code . "\n<?php";
        }

        // Looks ugly: Paste a function declarator in front of the code
        $code = 'function foo' . $id . ' () {' . $code;
        $code .= "\n}\n";

        $html_errors = ini_get('html_errors');

        // To parse the error message, we prepend and append a phperror tag in front
        // of the output
        $sErs = ini_get('error_prepend_string'); // Save current setting (see below)
        $sEas = ini_get('error_append_string'); // Save current setting (see below)
        @ini_set('error_prepend_string', '<phperror>');
        @ini_set('error_append_string', '</phperror>');

        // Turn off output buffering and error reporting, eval the code
        ob_start();
        $display_errors = ini_get('display_errors');
        @ini_set('html_errors', false);
        @ini_set('display_errors', true);
        $output = eval($code);
        @ini_set('display_errors', $display_errors);

        // Get the buffer contents and turn it on again
        $output = ob_get_contents();
        ob_end_clean();

        // Restore html_errors
        @ini_set('html_errors', $html_errors);

        // Remove the prepend and append settings
        @ini_set('error_prepend_string', $sErs); // Restoring settings (see above)
        @ini_set('error_append_string', $sEas); // Restoring settings (see above)

        // Strip out the error message
        if ($isError === false) {
            $isError = cString::findFirstPos($output, '<phperror>');
        }

        // More stripping: Users shouldn't see where the file is located,
        // but they should see the error line
        if ($isError !== false) {
            if (!isset($modErrorMessage)) {
                $pattern = '/(<phperror>|<\/phperror>|<b>|<\/b>|<br>|<br \/>)/im';
                $modErrorMessage = trim(preg_replace($pattern, '', $output));
                $errorPart1 = cString::getPartOfString($modErrorMessage, 0, cString::findFirstPos($modErrorMessage, ' in '));
                $errorPart2 = cString::getPartOfString($modErrorMessage, cString::findFirstPos($modErrorMessage, ' on line '));
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

}
