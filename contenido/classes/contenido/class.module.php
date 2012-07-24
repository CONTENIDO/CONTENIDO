<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Area management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @todo  Switch to SimpleXML
 *
 * @package    CONTENIDO API
 * @version    1.2.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2003-02-26
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Module collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiModuleCollection extends ItemCollection {

    /**
     * Constructor Function
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['mod'], 'idmod');
        $this->_setItemClass('cApiModule');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiModuleCollection() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Creates a new module item
     */
    public function create($name) {
        global $auth, $client;

        $item = parent::createNewItem();

        $item->set('idclient', $client);
        $item->set('name', $name);
        $item->set('author', $auth->auth['uid']);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->store();

        return $item;
    }

}

/**
 * Module item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiModule extends Item {

    protected $_error;

    /**
     * Assoziative package structure array
     * @var array
     */
    protected $_packageStructure;

    /**
     * @var array
     */
    private $aUsedTemplates = array();

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
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
            $this->_packageStructure = array('jsfiles' => $cfgClient[$client]['js']['path'],
                'tplfiles' => $cfgClient[$client]['tpl']['path'],
                'cssfiles' => $cfgClient[$client]['css']['path']);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiModule($mId = false) {
        cDeprecated('Use __construct() instead');
        $this->__construct($mId);
    }

    /**
     * Returns the translated name of the module if a translation exists.
     *
     * @param none
     * @return string Translated module name or original
     */
    public function getTranslatedName() {
        global $lang;

        // If we're not loaded, return
        if ($this->virgin == true) {
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
     * @param $name string Translated name of the module
     * @return none
     */
    public function setTranslatedName($name) {
        global $lang;
        $this->setProperty('translated-name', $lang, $name);
    }

    /**
     * This method get the input and output for translating
     * from files and not from db-table.
     *
     */
    function parseModuleForStringsLoadFromFile($cfg, $client, $lang) {
        global $client;

        if ($this->virgin == true) {
            return false;
        }

        // Fetch the code, append input to output
        //$code  = $this->get('output');
        //$code .= $this->get('input');
        //Get the code(input,output) from files
        $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
        $code = $contenidoModuleHandler->readOutput() . ' ';
        $code.= $contenidoModuleHandler->readInput();

        // Initialize array
        $strings = array();

        // Split the code into mi18n chunks
        $varr = preg_split('/mi18n([\s]*)\(([\s]*)"/', $code, -1);

        if (count($varr) > 1) {
            foreach ($varr as $key => $value) {
                // Search first closing
                $closing = strpos($value, '")');

                if ($closing === false) {
                    $closing = strpos($value, '" )');
                }

                if ($closing !== false) {
                    $value = substr($value, 0, $closing) . '")';
                }

                // Append mi18n again
                $varr[$key] = 'mi18n("' . $value;

                // Parse for the mi18n stuff
                preg_match_all('/mi18n([\s]*)\("(.*)"\)/', $varr[$key], $results);

                // Append to strings array if there are any results
                if (is_array($results[1]) && count($results[2]) > 0) {
                    $strings = array_merge($strings, $results[2]);
                }

                // Unset the results for the next run
                unset($results);
            }
        }

        // adding dynamically new module translations by content types
        // this function was introduced with CONTENIDO 4.8.13
        // checking if array is set to prevent crashing the module translation page
        if (is_array($cfg['translatable_content_types']) && count($cfg['translatable_content_types']) > 0) {
            // iterate over all defines cms content types
            foreach ($cfg['translatable_content_types'] as $sContentType) {
                // check if the content type exists and include his class file
                if (file_exists($cfg['contenido']['path'] . 'classes/class.' . strtolower($sContentType) . '.php')) {
                    cInclude('classes', 'class.' . strtolower($sContentType) . '.php');
                    // if the class exists, has the method 'addModuleTranslations'
                    // and the current module contains this cms content type we
                    // add the additional translations for the module
                    if (class_exists($sContentType) &&
                            method_exists($sContentType, 'addModuleTranslations') &&
                            preg_match('/' . strtoupper($sContentType) . '\[\d+\]/', $code)) {

                        $strings = call_user_func(array($sContentType, 'addModuleTranslations'), $strings);
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
     * @return array Found strings for this module
     */
    public function parseModuleForStrings() {
        if ($this->virgin == true) {
            return false;
        }

        // Fetch the code, append input to output
        //$code  = $this->get('output');
        //$code .= $this->get('input');
        //Get the code(input,output) from files
        $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
        $code = $contenidoModuleHandler->readOutput() . ' ';
        $code.= $contenidoModuleHandler->readInput();

        // Initialize array
        $strings = array();

        // Split the code into mi18n chunks
        $varr = preg_split('/mi18n([\s]*)\(([\s]*)"/', $code, -1);

        if (count($varr) > 1) {
            foreach ($varr as $key => $value) {
                // Search first closing
                $closing = strpos($value, '")');

                if ($closing === false) {
                    $closing = strpos($value, '" )');
                }

                if ($closing !== false) {
                    $value = substr($value, 0, $closing) . '")';
                }

                // Append mi18n again
                $varr[$key] = 'mi18n("' . $value;

                // Parse for the mi18n stuff
                preg_match_all('/mi18n([\s]*)\("(.*)"\)/', $varr[$key], $results);

                // Append to strings array if there are any results
                if (is_array($results[1]) && count($results[2]) > 0) {
                    $strings = array_merge($strings, $results[2]);
                }

                // Unset the results for the next run
                unset($results);
            }
        }

        // adding dynamically new module translations by content types
        // this function was introduced with CONTENIDO 4.8.13
        // checking if array is set to prevent crashing the module translation page
        if (is_array($cfg['translatable_content_types']) && count($cfg['translatable_content_types']) > 0) {
            // iterate over all defines cms content types
            foreach ($cfg['translatable_content_types'] as $sContentType) {
                // check if the content type exists and include his class file
                if (cFileHandler::exists($cfg['contenido']['path'] . 'classes/class.' . strtolower($sContentType) . '.php')) {
                    cInclude('classes', 'class.' . strtolower($sContentType) . '.php');
                    // if the class exists, has the method 'addModuleTranslations'
                    // and the current module contains this cms content type we
                    // add the additional translations for the module
                    if (class_exists($sContentType) &&
                            method_exists($sContentType, 'addModuleTranslations') &&
                            preg_match('/' . strtoupper($sContentType) . '\[\d+\]/', $code)) {

                        $strings = call_user_func(array($sContentType, 'addModuleTranslations'), $strings);
                    }
                }
            }
        }

        // Make the strings unique
        return array_unique($strings);
    }

    /**
     * Checks if the module is in use
     * @return bool    Specifies if the module is in use
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

        if ($db->nf() == 0) {
            return false;
        } else {
            $i = 0;
            // save the datas of used templates in array
            if ($bSetData === true) {
                while ($db->next_record()) {
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
     * @return array template data
     */
    public function getUsedTemplates() {
        return $this->aUsedTemplates;
    }

    /**
     * Checks if the module is a pre-4.3 module
     * @return boolean true if this module is an old one
     */
    public function isOldModule() {
        // Keywords to scan
        $scanKeywords = array('$cfgTab', 'idside', 'idsidelang');

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

    public function getField($field) {
        $value = parent::getField($field);

        switch ($field) {
            case 'name':
                if ($value == '') {
                    $value = i18n('- Unnamed module -');
                }
        }
        return ($value);
    }

    public function store($bJustStore = false) {
        global $cfg;

        if ($bJustStore) {
            // Just store changes, e.g. if specifying the mod package
            $success = parent::store();
        } else {
            cInclude('includes', 'functions.con.php');

            $success = parent::store();

            conGenerateCodeForAllArtsUsingMod($this->get('idmod'));
        }
        return $success;
    }

    /**
     * Parse import xml file and returns its values.
     *
     * @param    string    $sFile    Filename including path of import xml file
     *
     * @return    array    Array with module data from XML file
     */
    private function _parseImportFile($sFile) {
        $oXmlReader = new cXmlReader();
        $oXmlReader->load($sFile);

        $aData = array();
        $aInformation = array('name', 'description', 'type', 'input', 'output', 'alias');

        foreach ($aInformation as $sInfoName) {
            $sPath = '/module/' . $sInfoName;

            $value = $oXmlReader->getXpathValue($sPath);
            $aData[$sInfoName] = $value;
        }

        return $aData;
    }

    /**
     *
     * Save the modul properties (description,type...)
     *
     * @param string $sFile weher is the modul info.xml file
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
     * import
     * Imports the a module from a XML file
     * Uses xmlparser and callbacks
     *
     * @param string    $file     Filename of data file (full path)
     */
    function import($sFile, $tempName) {
        global $cfgClient, $db, $client, $cfg, $encoding, $lang;
        $zip = new ZipArchive();
        $notification = new cGuiNotification();
        $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
        // file name Hello_World.zip => Hello_World
        // @TODO: fetch file extension correctly
        $modulName = substr($sFile, 0, -4);

        $sModulePath = $cfgClient[$client]['module_path'] . $modulName;

        // exist the modul in directory
        if (is_dir($sModulePath)) {
            $notification->displayNotification('error', i18n('Module exist!'));
            return false;
        }

        if ($zip->open($tempName)) {
            if ($zip->extractTo($sModulePath)) {
                $zip->close();

                // make new module
                $modules = new cApiModuleCollection;

                $module = $modules->create($modulName);
                $moduleProperties = $this->_getModuleProperties($sModulePath . '/info.xml');

                // set module properties and save it
                foreach ($moduleProperties as $key => $value) {
                    $module->set($key, $value);
                }

                $module->store();
            } else {
                $notification->displayNotification('error', i18n('Import failed, could not extract zip file!'));
                return false;
            }
        } else {
            $notification->displayNotification('error', i18n('Could not open the zip file!'));
            return false;
        }

        return true;
    }

    /**
     * importModuleFromXML
     * Imports the a module from a XML file
     * Uses xmlparser and callbacks
     *
     * @param string    $file     Filename of data file (full path)
     */
    function importModuleFromXML($sFile) {
        global $db, $cfgClient, $client, $cfg, $encoding, $lang;

        $inputOutput = array();
        $notification = new cGuiNotification();

        $aModuleData = $this->_parseImportFile($sFile);
        if (count($aModuleData) > 0) {
            foreach ($aModuleData as $key => $value) {
                if ($this->get($key) != $value) {
                    #the columns input/and outputs dont exist in table
                    if ($key == 'output' || $key == 'input')
                        $inputOutput[$key] = $value;
                    else
                        $this->set($key, addslashes($value));
                }
            }

            $moduleAlias = cApiStrCleanURLCharacters($this->get('name'));
            // is alias empty??
            if ($this->get('alias') == '') {
                $this->set('alias', $moduleAlias);
            }

            if (is_dir($cfgClient[$client]['module_path'] . $moduleAlias)) {
                $notification->displayNotification('error', i18n('Module exist!'));
                return false;
            } else {
                // save it in db table
                $this->store();
                $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));
                if (!$contenidoModuleHandler->createModule($inputOutput['input'], $inputOutput['output'])) {
                    $notification->displayNotification('error', i18n('Could not make a module!'));
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
     *
     * Add recrusive folder to zip archive
     * @param string $dir direcotry name
     * @param string $zipArchive name of the archive
     * @param string $zipdir
     */
    private function _addFolderToZip($dir, $zipArchive, $zipdir = '') {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                //Add the directory
                if (!empty($zipdir)) {
                    $zipArchive->addEmptyDir($zipdir);
                }

                // Loop through all the files
                while (($file = readdir($dh)) !== false) {
                    //If it's a folder, run the function again!
                    if (!is_file($dir . $file)) {
                        // Skip parent and root directories
                        if (($file !== '.') && ($file !== '..')) {
                            $this->_addFolderToZip($dir . $file . '/', $zipArchive, $zipdir . $file . '/');
                        }
                    } else {
                        // Add the files
                        if ($zipArchive->addFile($dir . $file, $zipdir . $file) === FALSE) {
                            $notification = new cGuiNotification();
                            $notification->displayNotification('error', sprintf(i18n('Could not add file %s to zip!'), $file));
                        }
                    }
                }
            }
        }
    }

    /**
     * export
     * Exports the specified module  to a zip file
     *
     */
    function export() {
        $notification = new cGuiNotification();
        $contenidoModuleHandler = new cModuleHandler($this->get('idmod'));

        // exist modul
        if ($contenidoModuleHandler->modulePathExists()) {
            $zip = new ZipArchive();
            $zipName = $this->get('alias') . '.zip';
            if ($zip->open($zipName, ZipArchive::CREATE)) {
                $retAddToFolder = $this->_addFolderToZip($contenidoModuleHandler->getModulePath(), $zip);

                $zip->close();
                //Stream the file to the client
                header('Content-Type: application/zip');
                header('Content-Length: ' . filesize($zipName));
                header("Content-Disposition: attachment; filename=\"$zipName\"");
                readfile($zipName);
                //erase the file
                $ret = unlink($zipName);
            } else {
                $notification->displayNotification('error', i18n('Could not open the zip file!'));
            }
        } else {
            $notification->displayNotification('error', i18n("Module don't exist on file system!"));
        }
    }

    /**
     * @deprecated 2012-02-29 This function is not longer supported.
     */
    public function getPackageOverview($sFile) {
        cDeprecated('This function is not longer supported.');
        return false;
    }

    /**
     * @deprecated 2012-02-29 This function is not longer supported.
     */
    public function importPackage($sFile, $aOptions = array()) {
        cDeprecated('This function is not longer supported.');
        return false;
    }

    /**
     * @deprecated 2012-02-29 This function is not longer supported.
     */
    public function exportPackage($sPackageFileName, $bReturn = false) {
        cDeprecated('This function is not longer supported.');
        return false;
    }

}

class cApiModuleTranslationCollection extends ItemCollection {

    protected $_error;

    /**
     * Constructor Function
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['mod_translations'], 'idmodtranslation');
        $this->_setItemClass('cApiModuleTranslation');
    }

    /**
     * Creates a new module translation item
     */
    public function create($idmod, $idlang, $original, $translation = false) {
        // Check if the original already exists. If it does,
        // update the translation if passed
        $mod = new cApiModuleTranslation();
        $sorg = $mod->_inFilter($original);

        $this->select("idmod = '$idmod' AND idlang = '$idlang' AND original = '$sorg'");

        if ($item = $this->next()) {
            if ($translation !== false) {
                $item->set('translation', $translation);
                $item->store();
            }
            return $item;
        } else {
            $item = parent::createNewItem();
            $item->set('idmod', $idmod);
            $item->set('idlang', $idlang);
            $item->set('original', $original);
            $item->set('translation', $translation);
            $item->store();
            return $item;
        }
    }

    /**
     * Fetches a translation
     *
     * @param $module int Module ID
     * @param $lang   int Language ID
     * @param $string string String to lookup
     */
    public function fetchTranslation($module, $lang, $string) {
        // If the f_obj does not exist, create one
        if (!is_object($this->f_obj)) {
            $this->f_obj = new cApiModuleTranslation();
        }

        // Create original string
        $sorg = $this->f_obj->_inFilter($string);

        // Look up
        $this->select("idmod = '$module' AND idlang='$lang' AND original = '$sorg'");

        if ($t = $this->next()) {
            $translation = $t->get('translation');

            if ($translation != '') {
                return $translation;
            } else {
                return $string;
            }
        } else {
            return $string;
        }
    }

    /**
     * @deprecated 2012-02-29 This function is not longer supported.
     */
    public function import($idmod, $idlang, $file) {
        cDeprecated('This function is not longer supported.');
        $this->_error = 'This function is not longer supported.';
        return false;
    }

    /**
     * @deprecated 2012-02-29 This function is not longer supported.
     */
    public function export($idmod, $idlang, $filename, $return = false) {
        cDeprecated('This function is not longer supported.');
        return false;
    }

}

/**
 * Module access class
 */
class cApiModuleTranslation extends Item {

    /**
     * Constructor Function
     * @param $loaditem Item to load
     */
    public function __construct($loaditem = false) {
        global $cfg;
        parent::__construct($cfg['tab']['mod_translations'], 'idmodtranslation');
        if ($loaditem !== false) {
            $this->loadByPrimaryKey($loaditem);
        }
    }

}

/** @deprecated 2012-03-03 Not supported any longer. */
function cHandler_ModuleData($sName, $aAttribs, $sContent) {
    cDeprecated('This function is not longer supported.');
    global $_mImport;
    $_mImport['module'][$sName] = $sContent;
}

/** @deprecated 2012-03-03 Not supported any longer. */
function cHandler_ItemArea($sName, $aAttribs, $sContent) {
    cDeprecated('This function is not longer supported.');
    global $_mImport;
    $_mImport['current_item_area'] = $sContent;
}

/** @deprecated 2012-03-03 Not supported any longer. */
function cHandler_ItemName($sName, $aAttribs, $sContent) {
    cDeprecated('This function is not longer supported.');
    global $_mImport;
    $_mImport['current_item_name'] = $sContent;
}

/** @deprecated 2012-03-03 Not supported any longer. */
function cHandler_ItemData($sName, $aAttribs, $sContent) {
    cDeprecated('This function is not longer supported.');
    global $_mImport;
    $_mImport['items'][$_mImport['current_item_area']][$_mImport['current_item_name']][$sName] = $sContent;
}

/** @deprecated 2012-03-03 Not supported any longer. */
function cHandler_Translation($sName, $aAttribs, $sContent) {
    cDeprecated('This function is not longer supported.');
    global $_mImport;
    $_mImport['translations'][$_mImport['current_item_area']][$_mImport['current_item_name']] = $sContent;
}

?>