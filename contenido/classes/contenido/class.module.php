<?php

/**
 * This file contains the module collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Module collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiModule>
 */
class cApiModuleCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('mod'), 'idmod');
        $this->_setItemClass('cApiModule');
    }

    /**
     * Creates a new module item
     *
     * @param string $name
     * @param int $clientId [optional]
     * @param string $alias [optional]
     * @param string $type [optional]
     * @param string $error [optional]
     * @param string $description [optional]
     * @param int $deletable [optional]
     * @param string $template [optional]
     * @param int $static [optional]
     * @param string $package_guid [optional]
     * @param string $package_data [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiModule
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $name,
        $clientId = NULL,
        $alias = '',
        $type = '',
        $error = 'none',
        $description = '',
        $deletable = 0,
        $template = '',
        $static = 0,
        $package_guid = '',
        $package_data = '',
        $author = '',
        $created = '',
        $lastmodified = ''
    )
    {
        if (NULL === $clientId) {
            $clientId = cRegistry::getClientId();
        }

        if (empty($author)) {
            $author = cRegistry::getAuth()->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();

        $item->set('idclient', $clientId);
        $item->set('name', $name);
        $item->set('alias', $alias);
        $item->set('type', $type);
        $item->set('error', $error);
        $item->set('description', $description);
        $item->set('deletable', $deletable);
        $item->set('template', $template);
        $item->set('static', $static);
        $item->set('package_guid', $package_guid);
        $item->set('package_data', $package_data);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

    /**
     * Returns list of all types by client id
     *
     * @param int $clientId
     * @return array
     * @throws cDbException
     */
    public function getAllTypesByIdclient($clientId): array
    {
        $types = [];

        $this->db->query($this->db->prepare(
            "SELECT `type` FROM `%s` WHERE `idclient` = %d GROUP BY `type`",
            $this->table,
            $clientId
        ));
        while ($this->db->nextRecord()) {
            $types[] = $this->db->f('type');
        }

        return $types;
    }

    /**
     * Returns a list of all modules used by the given client.
     * By default, the modules are ordered by name but can be ordered by any property.
     *
     * @param int $clientId
     * @param string $oderBy [optional]
     * @param bool $returnAsObjects [optional] Flag to return list of
     *      cApiModule instances instead of record data list.
     *      Since CONTENIDO 4.10.2.
     * @return array|cApiModule[]
     * @throws cDbException|cInvalidArgumentException
     */
    public function getAllByIdclient($clientId, $oderBy = 'name', bool $returnAsObjects = false): array
    {
        $records = [];

        if (!empty($oderBy)) {
            $oderBy = ' ORDER BY `' . $this->db->escape($oderBy) . '`';
        }
        $sql = "SELECT * FROM `%s` WHERE `idclient` = %d{$oderBy}";
        $sql = $this->db->prepare($sql, $this->table, $clientId);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $moduleId = cSecurity::toInteger($this->db->f('idmod'));
            if (!$returnAsObjects) {
                $records[$moduleId] = $this->db->toArray();
            } else {
                $obj = new $this->_itemClass();
                $obj->loadByRecordSet($this->db->toArray());
                $records[$moduleId] = $obj;
            }
        }

        return $records;
    }

    /**
     * Returns a list of all modules used by the given client.
     * By default, the modules are ordered by name but can be ordered by any
     * property.
     *
     * @param int $clientId
     * @param string $type
     * @param string $oderBy [optional]
     * @return array
     * @throws cDbException
     */
    public function getAllByIdclientAndType($clientId, $type, $oderBy = 'name'): array
    {
        $records = [];

        if (!empty($oderBy)) {
            $oderBy = ' ORDER BY `' . $this->db->escape($oderBy) . '`';
        }
        $sql = "SELECT * FROM `%s` WHERE `idclient` = %d AND `type` LIKE '%s' {$oderBy}";
        $sql = $this->db->prepare($sql, $this->table, $clientId, '%' . $type . '%');

        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $records[cSecurity::toInteger($this->db->f('idmod'))] = $this->db->toArray();
        }

        return $records;
    }

    /**
     * Checks if any modules are in use and returns the data
     *
     * @return array Returns all templates for all modules
     * @throws cDbException
     */
    public function getModulesInUse(): array
    {
        $db = cRegistry::getDb();

        $sql = 'SELECT
                    c.idmod, c.idtpl, t.name
                FROM
                    `:tab_container` AS c,
                    `:tab_tpl` AS t
                WHERE
                    t.idtpl = c.idtpl
                GROUP BY c.idmod, c.idtpl, t.name
                ORDER BY t.name';
        $db->query($sql, [
            'tab_container' => cDb::getTableName('container'),
            'tab_tpl' => cDb::getTableName('tpl'),
        ]);

        $aUsedTemplates = [];
        if ($db->numRows() != 0) {
            while ($db->nextRecord()) {
                $moduleId = cSecurity::toInteger($db->f('idmod'));
                $idTpl = cSecurity::toInteger($db->f('idtpl'));
                $aUsedTemplates[$moduleId][$idTpl]['tpl_name'] = $db->f('name');
                $aUsedTemplates[$moduleId][$idTpl]['tpl_id'] = $idTpl;
            }
        }

        return $aUsedTemplates;
    }
}

/**
 * Module item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiModule extends Item
{

    /**
     * for finding module translations in source code of module
     *
     * @var string
     */
    private $_translationPatternText = '/mi18n([\s]*)\("((\\\\"|[^"])*)"(([\s]*),([\s]*)[^\),]+)*\)/';

    /**
     * for finding basic module translations in source code of module
     *
     * @var string
     */
    private $_translationPatternBase = '/mi18n([\s]*)\(([\s]*)"/';

    /**
     * for replacing base module translations in source code of module
     *
     * @var string
     */
    private $_translationReplacement = 'mi18n("';

    /**
     * for finding module translations in source code of templates
     *
     * @var string
     */
    private $_translationPatternTemplate = '/\{\s*"([^"]+)"\s*\|\s*mi18n\s*\}/';

    /**
     * @todo check if this property is still required
     * @var string
     */
    protected $_error;

    /**
     * Associative package structure array
     *
     * @var array
     */
    protected $_packageStructure;

    /**
     *
     * @var array
     */
    private $usedTemplates = [];

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        $table = cDb::getTableName('mod');
        parent::__construct($table, 'idmod');

        // Using no filters is just for compatibility reasons.
        // That's why you don't have to stripslashes values if you store them
        // using ->set. You have to add slashes, if you store data directly
        // (data not from a form field)
        $this->setFilters();

        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }

        $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());
        if (count($clientConfig)) {
            $this->_packageStructure = [
                'jsfiles' => $clientConfig['js']['path'] ?? '',
                'tplfiles' => $clientConfig['tpl']['path'] ?? '',
                'cssfiles' => $clientConfig['css']['path'] ?? '',
            ];
        }
    }

    /**
     * Returns the translated name of the module if a translation exists.
     *
     * @return string|false Translated module name or original
     * @throws cDbException|cException
     */
    public function getTranslatedName()
    {
        // If we're not loaded, return
        if (!$this->isLoaded()) {
            return false;
        }

        $modName = $this->getProperty('translated-name', cRegistry::getLanguageId());

        return $modName === false ? $this->get('name') : $modName;
    }

    /**
     * Sets the translated name of the module
     *
     * @param string $name Translated name of the module
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setTranslatedName($name)
    {
        $this->setProperty('translated-name', cRegistry::getLanguageId(), $name);
    }

    /**
     * This method get the input and output for translating from files and not
     * from db-table.
     *
     * @param array $cfg The CONTENIDO configuration array
     * @param int $client Deprecated, is no longer used.
     * @param int $lang Deprecated, is no longer used.
     * @return array|false
     * @throws cException
     */
    function parseModuleForStringsLoadFromFile(array $cfg, $client, $lang)
    {
        // If we're not loaded, return
        if (!$this->isLoaded()) {
            return false;
        }

        // Fetch the code, append input to output
        $contenidoModuleHandler = new cModuleHandler($this);
        $code = $contenidoModuleHandler->readOutput() . ' ';
        $code .= $contenidoModuleHandler->readInput();

        // Initialize array
        $strings = [];

        // Split the code into mi18n chunks
        $chunks = preg_split($this->_translationPatternBase, $code, -1);
        array_shift($chunks);

        if (count($chunks) > 0) {
            foreach ($chunks as $key => $value) {
                // Search first closing
                $closing = cString::findFirstPos($value, '")');

                if ($closing === false) {
                    $closing = cString::findFirstPos($value, '" )');
                }

                if ($closing !== false) {
                    $value = cString::getPartOfString($value, 0, $closing) . '")';
                }

                // Append mi18n again
                $chunks[$key] = $this->_translationReplacement . $value;

                // Parse for the mi18n stuff
                preg_match_all($this->_translationPatternText, $chunks[$key], $results);

                // Append to strings array if there are any results
                if (is_array($results[1]) && count($results[2]) > 0) {
                    $strings = array_merge($strings, $results[2]);
                }

                // Unset the results for the next run
                unset($results);
            }
        }

        // Parse all templates too
        $moduleTemplateHandler = new cModuleTemplateHandler($this, null);
        $filesArray = $moduleTemplateHandler->getAllFilesFromDirectory('template');

        if (is_array($filesArray)) {
            $code = '';
            foreach ($filesArray as $file) {
                $code .= $moduleTemplateHandler->getFilesContent('template', '', $file);
            }

            // Parse for the mi18n stuff
            preg_match_all($this->_translationPatternTemplate, $code, $results);

            if (is_array($results) && is_array($results[1]) && count($results[1]) > 0) {
                $strings = array_merge($strings, $results[1]);
            }
        }

        // Adding dynamically new module translations by content types this
        // function was introduced with CONTENIDO 4.8.13 checking if array
        // is set to prevent crashing the module translation page
        $translatableContentTypes = $cfg['translatable_content_types'] ?? null;
        if (is_array($translatableContentTypes) && count($translatableContentTypes) > 0) {
            // iterate over all defines cms content types
            foreach ($translatableContentTypes as $sContentType) {
                // check if the content type exists and include his class file
                $className = 'class.' . cString::toLowerCase($sContentType) . '.php';
                if (cFileHandler::exists(cRegistry::getBackendPath() . 'classes/' . $className)) {
                    cInclude('classes', $className);
                    // if the class exists, has the method 'addModuleTranslations'
                    // and the current module contains this cms content type we
                    // add the additional translations for the module
                    if (
                        class_exists($sContentType)
                        && method_exists($sContentType, 'addModuleTranslations')
                        && preg_match('/' . cString::toUpperCase($sContentType) . '\[\d+\]/', $code)
                    ) {
                        $strings = call_user_func([
                            $sContentType,
                            'addModuleTranslations'
                        ], $strings);
                    }
                }
            }
        }

        // Make the strings unique
        return array_unique($strings);
    }

    /**
     * Parses the module for mi18n strings and returns them in an array
     *
     * TODO Function had almost same code as {@see cApiModule::parseModuleForStringsLoadFromFile()},
     *      therefore its body has been replaced against the call of parseModuleForStringsLoadFromFile().
     *      But parseModuleForStrings() is not used anywhere, can we remove it?
     *
     * @return array|false Found strings for this module
     * @throws cInvalidArgumentException|cException
     */
    public function parseModuleForStrings()
    {
        if (!$this->isLoaded()) {
            return false;
        }

        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        return $this->parseModuleForStringsLoadFromFile($cfg, $client, $lang);
    }

    /**
     * Checks if the module is in use
     *
     * @param int $module
     * @param bool $setData [optional]
     * @return bool true if the module is in use
     * @throws cDbException
     */
    public function moduleInUse($module, bool $setData = false): bool
    {
        $db = cRegistry::getDb();

        $sql = 'SELECT
                    c.idmod, c.idtpl, t.name
                FROM
                    `:tab_container` AS c,
                    `:tab_tpl` AS t
                WHERE
                    c.idmod = :idmod AND
                    t.idtpl = c.idtpl
                GROUP BY c.idtpl, c.idmod, t.name
                ORDER BY t.name';
        $db->query($sql, [
            'tab_container' => cDb::getTableName('container'),
            'tab_tpl' => cDb::getTableName('tpl'),
            'idmod' => cSecurity::toInteger($module),
        ]);

        if ($db->numRows() == 0) {
            return false;
        } else {
            $i = 0;
            if ($setData === true) {
                while ($db->nextRecord()) {
                    $this->usedTemplates[$i]['tpl_name'] = $db->f('name');
                    $this->usedTemplates[$i]['tpl_id'] = cSecurity::toInteger($db->f('idmod'));
                    $i++;
                }
            }

            return true;
        }
    }

    /**
     * Get the information of used templates
     *
     * @return array template data
     */
    public function getUsedTemplates(): array
    {
        return $this->usedTemplates;
    }

    /**
     * Checks if the module is a pre-4.3 module
     *
     * @return bool true if this module is an old one
     */
    public function isOldModule(): bool
    {
        // Keywords to scan
        $scanKeywords = [
            '$cfgTab',
            'idside',
            'idsidelang'
        ];

        $input = $this->get('input');
        $output = $this->get('output');

        foreach ($scanKeywords as $keyword) {
            if (strstr($input, $keyword)) {
                return true;
            }
            if (strstr($output, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     * @throws cException
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'name':
                if ($value == '') {
                    $value = i18n('- Unnamed module -');
                }
        }

        return $value;
    }

    /**
     * Stores the loaded and modified item to the database.
     * Also generates the code for all articles using this module
     * (if not suppressed by giving a true value for $justStore).
     *
     * @param bool $justStore [optional] don't generate code for all articles using this module (default false)
     * @return bool
     * @throws cDbException|cInvalidArgumentException|cException
     * @inheritDoc
     */
    public function store(bool $justStore = false)
    {
        if ($justStore) {
            // Just store changes, e.g. if specifying the mod package
            $success = parent::store();
        } else {
            cInclude('includes', 'functions.con.php');

            $success = parent::store();

            conGenerateCodeForAllartsUsingMod($this->get('idmod'));
        }

        return $success;
    }

    /**
     * Parse import xml file and returns its values.
     *
     * @param string $filename Filename including path of import xml file
     * @return array Array with module data from XML file
     * @throws cException
     */
    private function _parseImportFile(string $filename): array
    {
        $oXmlReader = new cXmlReader();
        $oXmlReader->load($filename);

        $aData = [];
        $aInformation = [
            'name',
            'description',
            'type',
            'input',
            'output',
            'alias'
        ];

        foreach ($aInformation as $sInfoName) {
            $sPath = '/module/' . $sInfoName;

            $value = $oXmlReader->getXpathValue($sPath);
            $aData[$sInfoName] = $value;
        }

        return $aData;
    }

    /**
     * Save the module properties (description,type...)
     *
     * @param string $filename Where is the module info.xml file
     * @throws cException
     */
    private function _getModuleProperties(string $filename): array
    {
        $ret = [];

        $aModuleData = $this->_parseImportFile($filename);
        if (count($aModuleData) > 0) {
            foreach ($aModuleData as $key => $value) {
                // the columns input/and outputs don't exist in table
                if ($key != 'output' && $key != 'input') {
                    $ret[$key] = addslashes($value);
                }
            }
        }

        return $ret;
    }

    /**
     * Imports the module from a zip file, uses xml-parser and callbacks
     *
     * @param string $filename Filename of data file (full path)
     * @param string $tempName of archive
     * @param bool $showNotification [optional] standard: true, mode to turn notifications off
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function import(string $filename, string $tempName, bool $showNotification = true): bool
    {
        $zip = new ZipArchive();
        $notification = new cGuiNotification();

        // File name Hello_World.zip => Hello_World
        $moduleName = cFileHandler::getFilename($filename);

        $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());
        $sModulePath = $clientConfig['module']['path'] . $moduleName;

        $cfg = cRegistry::getConfig();
        $sTempPath = $cfg['path']['contenido_temp'] . 'module_import_' . $moduleName;

        // does module already exist in directory?
        if (cDirHandler::exists($sModulePath)) {
            if ($showNotification) {
                $notification->displayNotification('error', i18n('Module already exists!'));
            }
            return false;
        }

        if ($zip->open($tempName)) {
            if ($zip->extractTo($sTempPath)) {
                $zip->close();

                // Check module xml information
                if (cFileHandler::exists($sTempPath . '/info.xml')) {
                    // make new module
                    $modules = new cApiModuleCollection();
                    $module = $modules->create($moduleName);
                    $moduleProperties = $this->_getModuleProperties($sTempPath . '/info.xml');
                    // set module properties and save it
                    foreach ($moduleProperties as $key => $value) {
                        $module->set($key, $value);
                    }
                    $module->store();
                } else {
                    if ($showNotification) {
                        $notification->displayNotification(
                            'error',
                            i18n('Import failed, could load module information!')
                        );
                    }
                    return false;
                }
            } else {
                if ($showNotification) {
                    $notification->displayNotification(
                        'error',
                        i18n('Import failed, could not extract zip file!')
                    );
                }

                return false;
            }
        } else {
            if ($showNotification) {
                $notification->displayNotification('error', i18n('Could not open the zip file!'));
            }

            return false;
        }

        // Move into module dir
        cDirHandler::rename($sTempPath, $sModulePath);

        return true;
    }

    /**
     * Imports the module from an XML file, uses xml-parser and callbacks
     *
     * @param string $filename Filename of data file (full path)
     * @throws cException|cInvalidArgumentException|DOMException
     */
    public function importModuleFromXML(string $filename): bool
    {
        $inputOutput = [];
        $notification = new cGuiNotification();

        $aModuleData = $this->_parseImportFile($filename);
        if (count($aModuleData) > 0) {
            foreach ($aModuleData as $key => $value) {
                if ($this->get($key) != $value) {
                    // The columns input/and outputs don't exist in table
                    if ($key == 'output' || $key == 'input') {
                        $inputOutput[$key] = $value;
                    } else {
                        $this->set($key, addslashes($value));
                    }
                }
            }

            $moduleName = cString::cleanURLCharacters($this->get('name'));
            $moduleAlias = cString::cleanURLCharacters($this->get('alias'));

            // Is alias empty? Use module name as alias
            if ($this->get('alias') == '') {
                $this->set('alias', $moduleName);
            }

            $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());
            if (cDirHandler::exists($clientConfig['module']['path'] . $moduleAlias)) {
                $notification->displayNotification('error', i18n('Module exist!'));
                return false;
            } else {
                // save it in db table
                $this->store();
                $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
                if (!$contenidoModuleHandler->createModule($inputOutput['input'], $inputOutput['output'])) {
                    $notification->displayNotification('error', i18n('Could not create a module!'));
                    return false;
                } else {
                    // save the module data to module info file
                    $contenidoModuleHandler->saveInfoXML();
                }
            }
        } else {
            $notification->displayNotification('error', i18n('Could not parse xml file!'));

            return false;
        }

        return true;
    }

    /**
     * Add recursive folder to zip archive
     *
     * @param string $directory directory name
     * @param ZipArchive $zipArchive Zip archive instance
     * @param string $zipDirectory [optional]
     * @throws cException
     */
    private function _addFolderToZip(string $directory, ZipArchive $zipArchive, string $zipDirectory = '')
    {
        if (!cDirHandler::exists($directory)) {
            return;
        }

        if (false !== $handle = cDirHandler::read($directory)) {
            if (!empty($zipDirectory)) {
                $zipArchive->addEmptyDir($zipDirectory);
            }

            foreach ($handle as $file) {
                // If it is a folder, run the function again!
                if (!cFileHandler::exists($directory . $file)) {
                    // Skip parent and root directories
                    if (!cFileHandler::fileNameIsDot($file)) {
                        $this->_addFolderToZip(
                            $directory . $file . '/',
                            $zipArchive,
                            $zipDirectory . $file . '/'
                        );
                    }
                } else {
                    // Add the files
                    if ($zipArchive->addFile($directory . $file, $zipDirectory . $file) === false) {
                        $notification = new cGuiNotification();
                        $notification->displayNotification(
                            'error',
                            sprintf(i18n('Could not add file %s to zip!'), $file)
                        );
                    }
                }
            }
        }
    }

    /**
     * Exports the specified module to a zip file and outputs its content.
     */
    public function export()
    {
        $notification = new cGuiNotification();
        $moduleHandler = new cModuleHandler($this->get('idmod'));

        // exist module
        if ($moduleHandler->modulePathExists()) {
            $zip = new ZipArchive();
            $zipName = $this->get('alias') . '.zip';
            $cfg = cRegistry::getConfig();
            $path = $cfg['path']['contenido_temp'];
            if ($zip->open($path . $zipName, ZipArchive::CREATE)) {
                $this->_addFolderToZip($moduleHandler->getModulePath(), $zip);

                $zip->close();
                // Stream the file to the client
                header('Content-Type: application/zip');
                header('Content-Length: ' . filesize($path . $zipName));
                header("Content-Disposition: attachment; filename=\"$zipName\"");
                readfile($path . $zipName);

                // erase the file
                unlink($path . $zipName);
            } else {
                $notification->displayNotification('error', i18n('Could not open the zip file!'));
            }
        } else {
            $notification->displayNotification('error', i18n("Module don't exist on file system!"));
        }
    }

    /**
     * User-defined setter for module fields.
     *
     * @inheritdoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'deletable':
            case 'static':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Processes container placeholder (e.g. CMS_VAR[123], CMS_VALUE[123]) in given module input code.
     * Tries to find the proper container tag and replaces its value against container configuration.
     * @param int $containerNr The container number to process
     * @param string $containerCfg Container configuration string containing key/values pairs for all containers
     * @param string $moduleInputCode
     * @return string Concatenated PHP code containing CMS_VALUE variables and the module input code
     */
    public static function processContainerInputCode(
        int $containerNr,
        string $containerCfg,
        string &$moduleInputCode
    ): string {
        $CiCMS_Values = self::_processContainerCode(
            $containerNr, $containerCfg, $moduleInputCode, true
        );
        return $CiCMS_Values . "\n" . $moduleInputCode;
    }

    /**
     * Processes container placeholder (e.g. CMS_VALUE[123]) in given module output code.
     * Tries to find the proper container tag and replaces its value against container configuration.
     * @param int $containerNr The container number to process
     * @param string $containerCfg Container configuration string containing key/values pairs for all containers
     * @param string $moduleIOutputCode
     * @return string Concatenated PHP code containing CMS_VALUE variables
     * @since CONTENIDO 4.10.2
     */
    public static function processContainerOutputCode(
        int $containerNr,
        string $containerCfg,
        string &$moduleIOutputCode
    ): string {
        return self::_processContainerCode(
            $containerNr, $containerCfg, $moduleIOutputCode, false
        );
    }

    /**
     * Processes container placeholder (e.g. CMS_VAR[123], CMS_VALUE[123]) in given module input/output code.
     *
     * @param int $containerNr
     * @param string $containerCfg
     * @param string $moduleCode
     * @param bool $isModuleInput
     * @return string
     * @since CONTENIDO 4.10.2
     */
    protected static function _processContainerCode(
        int $containerNr,
        string $containerCfg,
        string &$moduleCode,
        bool $isModuleInput = true
    ): string {
        $containerConfigurations = [];
        if (!empty($containerCfg)) {
            $containerConfigurations = cApiContainerConfiguration::parseContainerValue($containerCfg);
        }

        $CiCMS_Var = '$C' . $containerNr . 'CMS_VALUE';
        $CiCMS_Values = [];

        foreach ($containerConfigurations as $key3 => $value3) {
            // Convert special characters and escape backslashes!
            $tmp = conHtmlSpecialChars($value3);
            $tmp = str_replace('\\', '\\\\', $tmp);

            $CiCMS_Values[] = $CiCMS_Var . '[' . $key3 . '] = "' . $tmp . '";';
            $moduleCode = str_replace("\$CMS_VALUE[$key3]", $tmp, $moduleCode);
            $moduleCode = str_replace("CMS_VALUE[$key3]", $tmp, $moduleCode);
        }

        $moduleCode = str_replace('CMS_VALUE', $CiCMS_Var, $moduleCode);
        $moduleCode = str_replace("\$" . $CiCMS_Var, $CiCMS_Var, $moduleCode);
        if ($isModuleInput) {
            // Additional replacement in module input code
            $moduleCode = str_replace('CMS_VAR', 'C' . $containerNr . 'CMS_VAR', $moduleCode);
        }
        // Remove any leftover container variables and placeholder, e.g. $C123CMS_VALUE[123] or CMS_VALUE[123]
        $moduleCode = preg_replace('/\$C([0-9]*)CMS_VALUE\[([0-9]*)\]/i', '', $moduleCode);
        $moduleCode = preg_replace("/(CMS_VALUE\[)([0-9]*)(\])/i", '', $moduleCode);

        return implode("\n", $CiCMS_Values);
    }

}
