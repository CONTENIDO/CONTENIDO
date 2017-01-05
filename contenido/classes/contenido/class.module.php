<?php

/**
 * This file contains the module collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Module collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiModuleCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['mod'], 'idmod');
        $this->_setItemClass('cApiModule');
    }

    /**
     * Creates a new module item
     *
     * @global int $client
     * @global object $auth
     *
     * @param string $name
     * @param int $idclient [optional]
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
     */
    public function create($name, $idclient = NULL, $alias = '', $type = '',
            $error = 'none', $description = '', $deletable = 0, $template = '',
            $static = 0, $package_guid = '', $package_data = '', $author = '',
            $created = '', $lastmodified = '') {
        global $client, $auth;

        if (NULL === $idclient) {
            $idclient = $client;
        }

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();

        $item->set('idclient', $idclient);
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
     * @param int $idclient
     *
     * @return array
     */
    public function getAllTypesByIdclient($idclient) {
        $types = array();

        $sql = "SELECT type FROM `%s` WHERE idclient = %d GROUP BY type";
        $sql = $this->db->prepare($sql, $this->table, $idclient);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $types[] = $this->db->f('type');
        }

        return $types;
    }

    /**
     * Returns a list of all modules used by the given client.
     * By default the modules are ordered by name but can be ordered by any
     * property.
     *
     * @param int $idclient
     * @param string $oderBy [optional]
     * @return array
     */
    public function getAllByIdclient($idclient, $oderBy = 'name') {
        $records = array();

        if (!empty($oderBy)) {
            $oderBy = ' ORDER BY ' . $this->db->escape($oderBy);
        }
        $sql = "SELECT * FROM `%s` WHERE idclient = %d{$oderBy}";
        $sql = $this->db->prepare($sql, $this->table, $idclient);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $records[$this->db->f('idmod')] = $this->db->toArray();
        }

        return $records;
    }

    /**
     * Checks if any modules are in use and returns the data
     *
     * @return array
     *         Returns all templates for all modules
     */
    public function getModulesInUse() {
        global $cfg;

        $db = cRegistry::getDb();

        $sql = 'SELECT
                    c.idmod, c.idtpl, t.name
                FROM
                ' . $cfg['tab']['container'] . ' as c,
                ' . $cfg['tab']['tpl'] . " as t
                WHERE
                    t.idtpl = c.idtpl
                GROUP BY c.idmod
                ORDER BY t.name";
        $db->query($sql);

        $aUsedTemplates = array();
        if ($db->numRows() != 0) {
            while ($db->nextRecord()) {
                $aUsedTemplates[$db->f('idmod')][$db->f('idtpl')]['tpl_name'] = $db->f('name');
                $aUsedTemplates[$db->f('idmod')][$db->f('idtpl')]['tpl_id'] = (int) $db->f('idtpl');
            }
        }

        return $aUsedTemplates;
    }
}

/**
 * Module item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiModule extends Item {

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
     * Assoziative package structure array
     *
     * @var array
     */
    protected $_packageStructure;

    /**
     *
     * @var array
     */
    private $aUsedTemplates = array();

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg, $cfgClient, $client;
        parent::__construct($cfg['tab']['mod'], 'idmod');

        // Using no filters is just for compatibility reasons.
        // That's why you don't have to stripslashes values if you store them
        // using ->set. You have to add slashes, if you store data directly
        // (data not from a form field)
        $this->setFilters(array(), array());

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }

        if (isset($client) && $client != 0) {
            $this->_packageStructure = array(
                'jsfiles' => $cfgClient[$client]['js']['path'],
                'tplfiles' => $cfgClient[$client]['tpl']['path'],
                'cssfiles' => $cfgClient[$client]['css']['path']
            );
        }
    }

    /**
     * Returns the translated name of the module if a translation exists.
     *
     * @return string
     *         Translated module name or original
     */
    public function getTranslatedName() {
        global $lang;

        // If we're not loaded, return
        if (!$this->isLoaded()) {
            return false;
        }

        $modname = $this->getProperty('translated-name', $lang);

        if ($modname === false) {
            return $this->get('name');
        } else {
            return $modname;
        }
    }

    /**
     * Sets the translated name of the module
     *
     * @param string $name
     *         Translated name of the module
     */
    public function setTranslatedName($name) {
        global $lang;
        $this->setProperty('translated-name', $lang, $name);
    }

    /**
     * This method get the input and output for translating from files and not
     * from db-table.
     *
     * @param array $cfg
     * @param int $client
     * @param int $lang
     * @return bool|array
     */
    function parseModuleForStringsLoadFromFile($cfg, $client, $lang) {
        global $client;

        // If we're not loaded, return
        if (!$this->isLoaded()) {
            return false;
        }

        // Fetch the code, append input to output
        $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
        $code = $contenidoModuleHandler->readOutput() . ' ';
        $code .= $contenidoModuleHandler->readInput();

        // Initialize array
        $strings = array();

        // Split the code into mi18n chunks
        $varr = preg_split($this->_translationPatternBase, $code, -1);
        array_shift($varr);

        if (count($varr) > 0) {
            foreach ($varr as $key => $value) {
                // Search first closing
                $closing = cString::findFirstPos($value, '")');

                if ($closing === false) {
                    $closing = cString::findFirstPos($value, '" )');
                }

                if ($closing !== false) {
                    $value = cString::getPartOfString($value, 0, $closing) . '")';
                }

                // Append mi18n again
                $varr[$key] = $this->_translationReplacement . $value;

                // Parse for the mi18n stuff
                preg_match_all($this->_translationPatternText, $varr[$key], $results);

                // Append to strings array if there are any results
                if (is_array($results[1]) && count($results[2]) > 0) {
                    $strings = array_merge($strings, $results[2]);
                }

                // Unset the results for the next run
                unset($results);
            }
        }
		
		//Parse all templates too
		$contenidoModulTemplateHandler = new cModuleTemplateHandler($this->get('idmod'), null);
		$filesArray = $contenidoModulTemplateHandler->getAllFilesFromDirectory('template');
		
		if (is_array($filesArray)) {
			$code = '';
			foreach ($filesArray as $file) {
				$code .= $contenidoModulTemplateHandler->getFilesContent('template', '', $file);
			}
			
			// Parse for the mi18n stuff
            preg_match_all($this->_translationPatternTemplate, $code, $results);
			
			if (is_array($results) && is_array($results[1]) && count($results[1]) > 0) {
				$strings = array_merge($strings, $results[1]);
			}
		}
		
        // adding dynamically new module translations by content types
        // this function was introduced with CONTENIDO 4.8.13
        // checking if array is set to prevent crashing the module translation
        // page
        if (is_array($cfg['translatable_content_types']) && count($cfg['translatable_content_types']) > 0) {
            // iterate over all defines cms content types
            foreach ($cfg['translatable_content_types'] as $sContentType) {
                // check if the content type exists and include his class file
                if (file_exists($cfg['contenido']['path'] . 'classes/class.' . cString::toLowerCase($sContentType) . '.php')) {
                    cInclude('classes', 'class.' . cString::toLowerCase($sContentType) . '.php');
                    // if the class exists, has the method
                    // 'addModuleTranslations'
                    // and the current module contains this cms content type we
                    // add the additional translations for the module
                    if (class_exists($sContentType) && method_exists($sContentType, 'addModuleTranslations') && preg_match('/' . cString::toUpperCase($sContentType) . '\[\d+\]/', $code)) {

                        $strings = call_user_func(array(
                            $sContentType,
                            'addModuleTranslations'
                        ), $strings);
                    }
                }
            }
        }

        /* Make the strings unique */
        return array_unique($strings);
    }

    /**
     * Parses the module for mi18n strings and returns them in an array
     *
     * @return array
     *         Found strings for this module
     */
    public function parseModuleForStrings() {
        if (!$this->isLoaded()) {
            return false;
        }

        // Fetch the code, append input to output
        // $code = $this->get('output');
        // $code .= $this->get('input');
        // Get the code(input,output) from files
        $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
        $code = $contenidoModuleHandler->readOutput() . ' ';
        $code .= $contenidoModuleHandler->readInput();

        // Initialize array
        $strings = array();

        // Split the code into mi18n chunks
        $varr = preg_split($this->_translationPatternBase, $code, -1);

        if (count($varr) > 1) {
            foreach ($varr as $key => $value) {
                // Search first closing
                $closing = cString::findFirstPos($value, '")');

                if ($closing === false) {
                    $closing = cString::findFirstPos($value, '" )');
                }

                if ($closing !== false) {
                    $value = cString::getPartOfString($value, 0, $closing) . '")';
                }

                // Append mi18n again
                $varr[$key] = $this->_translationReplacement . $value;

                // Parse for the mi18n stuff
                preg_match_all($this->_translationPatternText, $varr[$key], $results);

                // Append to strings array if there are any results
                if (is_array($results[1]) && count($results[2]) > 0) {
                    $strings = array_merge($strings, $results[2]);
                }

                // Unset the results for the next run
                unset($results);
            }
        }

        global $cfg;

        // adding dynamically new module translations by content types
        // this function was introduced with CONTENIDO 4.8.13
        // checking if array is set to prevent crashing the module translation
        // page
        if (is_array($cfg['translatable_content_types']) && count($cfg['translatable_content_types']) > 0) {
            // iterate over all defines cms content types
            foreach ($cfg['translatable_content_types'] as $sContentType) {
                // check if the content type exists and include his class file
                if (cFileHandler::exists($cfg['contenido']['path'] . 'classes/class.' . cString::toLowerCase($sContentType) . '.php')) {
                    cInclude('classes', 'class.' . cString::toLowerCase($sContentType) . '.php');
                    // if the class exists, has the method
                    // 'addModuleTranslations'
                    // and the current module contains this cms content type we
                    // add the additional translations for the module
                    if (class_exists($sContentType) && method_exists($sContentType, 'addModuleTranslations') && preg_match('/' . cString::toUpperCase($sContentType) . '\[\d+\]/', $code)) {

                        $strings = call_user_func(array(
                            $sContentType,
                            'addModuleTranslations'
                        ), $strings);
                    }
                }
            }
        }

        // Make the strings unique
        return array_unique($strings);
    }

    /**
     * Checks if the module is in use
     *
     * @param int $module
     * @param bool $bSetData [optional]
     * @return bool
     *         true if the module is in use
     */
    public function moduleInUse($module, $bSetData = false) {
        global $cfg;

        $db = cRegistry::getDb();

        $sql = 'SELECT
                    c.idmod, c.idtpl, t.name
                FROM
                ' . $cfg['tab']['container'] . ' as c,
                ' . $cfg['tab']['tpl'] . " as t
                WHERE
                    c.idmod = '" . cSecurity::toInteger($module) . "' AND
                    t.idtpl=c.idtpl
                GROUP BY c.idtpl
                ORDER BY t.name";
        $db->query($sql);

        if ($db->numRows() == 0) {
            return false;
        } else {
            $i = 0;
            // save the datas of used templates in array
            if ($bSetData === true) {
                while ($db->nextRecord()) {
                    $this->aUsedTemplates[$i]['tpl_name'] = $db->f('name');
                    $this->aUsedTemplates[$i]['tpl_id'] = (int) $db->f('idmod');
                    $i++;
                }
            }

            return true;
        }
    }

    /**
     * Get the informations of used templates
     *
     * @return array
     *         template data
     */
    public function getUsedTemplates() {
        return $this->aUsedTemplates;
    }

    /**
     * Checks if the module is a pre-4.3 module
     *
     * @return bool
     *         true if this module is an old one
     */
    public function isOldModule() {
        // Keywords to scan
        $scanKeywords = array(
            '$cfgTab',
            'idside',
            'idsidelang'
        );

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
    }

    /**
     * Gets the value of a specific field.
     *
     * @see Item::getField()
     * @param string $sField
     *         Specifies the field to retrieve
     * @param bool $bSafe [optional]
     *         Flag to run defined outFilter on passed value
     * @return mixed
     *         Value of the field
     */
    public function getField($field, $bSafe = true) {
        $value = parent::getField($field, $bSafe);

        switch ($field) {
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
     * (if not suppressed by giving a true value for $bJustStore).
     *
     * @see Item::store()
     * @param bool $bJustStore [optional]
     *     don't generate code for all articles using this module (default false)
     * @return bool
     */
    public function store($bJustStore = false) {
        if ($bJustStore) {
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
     * @param string $sFile
     *         Filename including path of import xml file
     * @return array
     *         Array with module data from XML file
     */
    private function _parseImportFile($sFile) {
        $oXmlReader = new cXmlReader();
        $oXmlReader->load($sFile);

        $aData = array();
        $aInformation = array(
            'name',
            'description',
            'type',
            'input',
            'output',
            'alias'
        );

        foreach ($aInformation as $sInfoName) {
            $sPath = '/module/' . $sInfoName;

            $value = $oXmlReader->getXpathValue($sPath);
            $aData[$sInfoName] = $value;
        }

        return $aData;
    }

    /**
     * Save the modul properties (description,type...)
     *
     * @param string $sFile
     *         Where is the modul info.xml file
     * @return array
     */
    private function _getModuleProperties($sFile) {
        $ret = array();

        $aModuleData = $this->_parseImportFile($sFile);
        if (count($aModuleData) > 0) {
            foreach ($aModuleData as $key => $value) {
                // the columns input/and outputs dont exist in table
                if ($key != 'output' && $key != 'input') {
                    $ret[$key] = addslashes($value);
                }
            }
        }

        return $ret;
    }

    /**
     * Imports the a module from a zip file, uses xmlparser and callbacks
     *
     * @param string $sFile
     *         Filename of data file (full path)
     * @param string $tempName
     *         of archive
     * @param bool $show_notification [optional]
     *         standard: true, mode to turn notifications off
     * @return bool
     */
    function import($sFile, $tempName, $show_notification = true) {
        global $cfgClient, $db, $client, $cfg, $encoding, $lang;
        $zip = new ZipArchive();
        $notification = new cGuiNotification();

        // file name Hello_World.zip => Hello_World
        // @TODO: fetch file extension correctly
        $modulName = cString::getPartOfString($sFile, 0, -4);

        $sModulePath = $cfgClient[$client]['module']['path'] . $modulName;
        $sTempPath = $cfg['path']['contenido_temp'] . 'module_import_' . $modulName;

        // does module already exist in directory
        if (is_dir($sModulePath)) {
            if ($show_notification == true) {
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

                    $module = $modules->create($modulName);
                    $moduleProperties = $this->_getModuleProperties($sTempPath . '/info.xml');

                    // set module properties and save it
                    foreach ($moduleProperties as $key => $value) {
                        $module->set($key, $value);
                    }

                    $module->store();
                } else {
                    if ($show_notification == true) {
                        $notification->displayNotification('error', i18n('Import failed, could load module information!'));
                    }
                    return false;
                }
            } else {
                if ($show_notification == true) {
                    $notification->displayNotification('error', i18n('Import failed, could not extract zip file!'));
                }

                return false;
            }
        } else {
            if ($show_notification == true) {
                $notification->displayNotification('error', i18n('Could not open the zip file!'));
            }

            return false;
        }

        // Move into module dir
        cDirHandler::rename($sTempPath, $sModulePath);

        return true;
    }

    /**
     * Imports the a module from a XML file, uses xmlparser and callbacks
     *
     * @param string $sFile
     *         Filename of data file (full path)
     * @return bool
     */
    function importModuleFromXML($sFile) {
        global $db, $cfgClient, $client, $cfg, $encoding, $lang;

        $inputOutput = array();
        $notification = new cGuiNotification();

        $aModuleData = $this->_parseImportFile($sFile);
        if (count($aModuleData) > 0) {
            foreach ($aModuleData as $key => $value) {
                if ($this->get($key) != $value) {
                    // he columns input/and outputs dont exist in table
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

            if (is_dir($cfgClient[$client]['module']['path'] . $moduleAlias)) {
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
                    // save the modul data to modul info file
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
     * @param string $dir
     *         directory name
     * @param string $zipArchive
     *         name of the archive
     * @param string $zipdir [optional]
     */
    private function _addFolderToZip($dir, $zipArchive, $zipdir = '') {
        if (is_dir($dir)) {
            if (false !== $handle = cDirHandler::read($dir)) {
                if (!empty($zipdir)) {
                    $zipArchive->addEmptyDir($zipdir);
                }

                foreach ($handle as $file) {
                    // If it's a folder, run the function again!
                    if (!is_file($dir . $file)) {
                        // Skip parent and root directories
                        if (false === cFileHandler::fileNameIsDot($file)) {
                            $this->_addFolderToZip($dir . $file . '/', $zipArchive, $zipdir . $file . '/');
                        }
                    } else {
                        // Add the files
                        if ($zipArchive->addFile($dir . $file, $zipdir . $file) === false) {
                            $notification = new cGuiNotification();
                            $notification->displayNotification('error', sprintf(i18n('Could not add file %s to zip!'), $file));
                        }
                    }
                }
            }
        }
    }

    /**
     * Exports the specified module to a zip file
     */
    public function export() {
        $notification = new cGuiNotification();
        $moduleHandler = new cModuleHandler($this->get('idmod'));

        // exist modul
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
     * Userdefined setter for module fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'deletable':
            case 'static':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

    /**
     * Processes container placeholder (e.g. CMS_VAR[123], CMS_VALUE[123]) in given module input code.
     * Tries to find the proper container tag and replaces its value against
     * container configuration.
     * @param int $containerNr
     *         The container number to process
     * @param string $containerCfg
     *         Container configuration string containing key/values pairs for all containers
     * @param string $moduleInputCode
     * @return string
     */
    public static function processContainerInputCode($containerNr, $containerCfg, $moduleInputCode) {
        $containerConfigurations = array();
        if (!empty($containerCfg)) {
            $containerConfigurations = cApiContainerConfiguration::parseContainerValue($containerCfg);
        }

        $CiCMS_Var = '$C' . $containerNr . 'CMS_VALUE';
        $CiCMS_Values = array();

        foreach ($containerConfigurations as $key3 => $value3) {
            // Convert special characters and escape backslashes!
            $tmp = conHtmlSpecialChars($value3);
            $tmp = str_replace('\\', '\\\\', $tmp);

            $CiCMS_Values[] = $CiCMS_Var . '[' . $key3 . '] = "' . $tmp . '";';
            $moduleInputCode = str_replace("\$CMS_VALUE[$key3]", $tmp, $moduleInputCode);
            $moduleInputCode = str_replace("CMS_VALUE[$key3]", $tmp, $moduleInputCode);
        }

        $moduleInputCode = str_replace('CMS_VALUE', $CiCMS_Var, $moduleInputCode);
        $moduleInputCode = str_replace("\$" . $CiCMS_Var, $CiCMS_Var, $moduleInputCode);
        $moduleInputCode = str_replace('CMS_VAR', 'C' . $containerNr . 'CMS_VAR', $moduleInputCode);

        $CiCMS_Values = implode("\n", $CiCMS_Values);

        return $CiCMS_Values . "\n" . $moduleInputCode;
    }
}
