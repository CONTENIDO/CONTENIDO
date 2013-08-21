<?php
/**
 * This file contains abstract class for contenido setup.
 *
 * @package CONTENIDO Plugins
 * @subpackage PluginManager
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract class for contenido setup operations When creating install,
 * uninstall, update you must extend this class.
 *
 * @package Plugin
 * @subpackage PluginManager
 *
 */
class PimPluginSetupOld {

    protected $sqlPrefix = "!PREFIX!";

    protected $valid = false;

    protected $tempXml;

    protected $isExtracted = false;

    protected $extractedPath;

    protected $pluginId = 0;

    protected $allAreas = array();

    protected $isUpdate = 0; // 0 = No (Standard),
                             // 1 = Yes
    protected $_extractor; // TODO
    public function addArchiveObject($extractor) {
        $this->_extractor = $extractor;
    }

    /**
     * Checks xml file to valid
     *
     * @throws cException
     * @return boolean
     */
    public function checkValidXml() {
        $sess = cRegistry::getSession();

        $dom = new DomDocument();
        $dom->loadXML($this->tempXml);

        if ($dom->schemaValidate('plugins/pim/xml/plugin_info.xsd')) {
            $this->valid = true;
            return true;
        } else {

            if ($this->isExtracted === false) {
                $this->_extractor->destroyTempFiles();
            }

            $pageError = new cGuiPage('pim_error', 'pim');
            $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
            $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
            $pageError->displayError(i18n('Invalid Xml document. Please contact the plugin author.', 'pim'));
            $pageError->render();
            exit();
        }
    }

    /**
     * Checks plugin specific requirements
     *
     * @access public
     * @return void
     */
    public function checkRequirements() {

        // get config
        $cfg = cRegistry::getConfig();

        // get requirements xml
        $xml = simplexml_load_string($this->getTempXml());

        // check CONTENIDO min version
        if (version_compare($cfg['version'], $xml->requirements->contenido->attributes()->minversion, '<')) {
            $this->getRequirementsError(i18n('You have to install CONTENIDO <strong>', 'pim') . $xml->requirements->contenido->attributes()->minversion . i18n('</strong> or higher to install this plugin!', 'pim'));
        }

        // check CONTENIDO max version
        if ($xml->requirements->contenido->attributes()->maxversion) {

            if (version_compare($cfg['version'], $xml->requirements->contenido->attributes()->maxversion, '>')) {
                $this->getRequirementsError(i18n('This plugin is only valid for CONTENIDO <strong>', 'pim') . $xml->requirements->contenido->attributes()->maxversion . i18n('</strong> or lower!', 'pim'));
            }
        }

        // check PHP version
        if (version_compare(phpversion(), $xml->requirements->attributes()->php, '<')) {
            $this->getRequirementsError(i18n('You have to install PHP <strong>', 'pim') . $xml->requirements->attributes()->php . i18n('</strong> or higher to install this plugin!', 'pim'));
        }

        // check extensions
        if (count($xml->requirements->extension) != 0) {

            for ($i = 0; $i < count($xml->requirements->extension); $i++) {

                if (!extension_loaded($xml->requirements->extension[$i]->attributes()->name)) {
                    $this->getRequirementsError(i18n('The plugin could not find the PHP extension <strong>', 'pim') . $xml->requirements->extension[$i]->attributes()->name . i18n('</strong>. Because this is required by the plugin, it can not be installed.', 'pim'));
                }
            }
        }

        // check classes
        if (count($xml->requirements->class) != 0) {

            for ($i = 0; $i < count($xml->requirements->class); $i++) {

                if (!class_exists($xml->requirements->class[$i]->attributes()->name)) {
                    $this->getRequirementsError(i18n('The plugin could not find the class <strong>', 'pim') . $xml->requirements->class[$i]->attributes()->name . i18n('</strong>. Because this is required by the plugin, it can not be installed.', 'pim'));
                }
            }
        }

        // check functions
        if (count($xml->requirements->function) != 0) {

            for ($i = 0; $i < count($xml->requirements->function); $i++) {

                if (!function_exists($xml->requirements->function[$i]->attributes()->name)) {
                    $this->getRequirementsError(i18n('The plugin could not find the function <strong>', 'pim') . $xml->requirements->function[$i]->attributes()->name . i18n('</strong>. Because this is required by the plugin, it can not be installed.', 'pim'));
                }
            }
        }
    }

    /**
     * Get error template for requirements (checkRequirements())
     *
     * @access private
     * @param errorMessage Specific error message string
     * @return void
     */
    private function getRequirementsError($errorMessage) {
        $sess = cRegistry::getSession();

        $pageError = new cGuiPage('pim_error', 'pim');
        $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
        $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
        $pageError->displayError($errorMessage);
        $pageError->render();
        exit();
    }

    /**
     * Get method for $tempXml
     *
     * @access public
     * @return content of $tempXml
     */
    public function getTempXml() {
        return $this->tempXml;
    }

    /**
     * Get method for $valid
     *
     * @access public
     * @return true or fales value of $valid
     */
    public function getValid() {
        return $this->valid;
    }

    /**
     * Get method for $pluginId
     *
     * @access public
     * @return id of selected plugin
     */
    public function getPluginId() {
        return $this->pluginId;
    }

    /**
     * Get method for "is update?" ($isUpdate)
     *
     * 0 = No (Standard)
     * 1 = Yes
     *
     * TODO: Optimize this function (CON-1358)
     *
     * @access public
     */
    public function getIsUpdate() {
        return $this->isUpdate;
    }

    /**
     * Set method for "is update?" ($isUpdate)
     *
     * 0 = No (Standard)
     * 1 = Yes
     *
     * TODO: Optimize this function (CON-1358)
     *
     * @access public
     * @param integer $value
     */
    public function setIsUpdate($value) {
        $this->isUpdate = $value;
    }

    /**
     * Set method for $tempXml
     *
     * @access public
     * @param $value xml content
     * @return void
     */
    public function setTempXml($value) {
        $this->tempXml = $value;
    }

    /**
     * Set method for $isExtracted
     *
     * @access public
     * @param $value true or false value
     * @return void
     */
    public function setIsExtracted($value) {
        $this->isExtracted = $value;
    }

    /**
     * Set method for $extractedPath
     *
     * @access public
     * @param $value path to extracted files
     * @return void
     */
    public function setExtractedPath($value) {
        $this->extractedPath = $value;
    }

    /**
     * Set method for $pluginId
     *
     * @access public
     * @param $value id of selected plugin
     * @return void
     */
    public function setPluginId($value) {
        $this->pluginId = $value;
    }

    /**
     * Install a new plugin
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    public function install($tempXml) {
        $pimPluginColl = new PimPluginCollection();

        // add entry at *_plugins
        $pimPlugin = $pimPluginColl->create($tempXml->general->plugin_name, $tempXml->general->description, $tempXml->general->author, $tempXml->general->copyright, $tempXml->general->mail, $tempXml->general->website, $tempXml->general->version, $tempXml->general->plugin_foldername, $tempXml->general->uuid, $tempXml->general->attributes()->active);
        $pluginId = $pimPlugin->get('idplugin');

        // set pluginId
        $this->setPluginId($pluginId);

        // add entries at *_area
        $this->_installAddArea($tempXml->contenido->areas);

        // add entries at *_actions
        $this->_installAddActions($tempXml->contenido->actions);

        // add entries at *_files and *_frame_files
        $this->_installAddFrames($tempXml->contenido->frames);

        // add entries at *_nav_main
        $this->_installAddNavMain($tempXml->contenido->nav_main);

        // add entries at *_nav_sub
        $this->_installAddNavSub($tempXml->contenido->nav_sub);

        // add specific sql queries
        if ($this->getIsUpdate() == 0) {
            $this->_installAddSpecificSql();
        }

        // add content types
        $this->_installAddContentTypes($tempXml->content_types);

        // add modules
        $this->_installAddModules($tempXml->general);
    }

    /**
     * Add entries at *_area
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    protected function _installAddArea($tempXml) {
        $areaColl = new cApiAreaCollection();
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pluginId = $this->getPluginId();

        // get all area names from database
        $oItem = new cApiAreaCollection();
        $oItem->select(null, null, 'name');
        while (($areas = $oItem->next()) !== false) {
            $this->allAreas[] = $areas->get('name');
        }

        $areaCount = count($tempXml->area);
        for ($i = 0; $i < $areaCount; $i++) {
            $attributes = array();

            // build attributes
            foreach ($tempXml->area[$i]->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // security check
            $area = cSecurity::escapeString($tempXml->area[$i]);
            $attributes = array(
                'parent' => cSecurity::escapeString($attributes['parent']),
                'menuless' => cSecurity::toInteger($attributes['menuless'])
            );

            // parent fix
            if (empty($attributes['parent'])) {
                $attributes['parent'] = 0;
            }

            // create a new entry
            $item = $areaColl->create($area, $attributes['parent'], 1, 1, $attributes['menuless']);

            // set a relation
            $pimPluginRelColl->create($item->get('idarea'), $pluginId, 'area');

            // add new area to all area array
            $this->allAreas[] = $area;
        }
    }

    /**
     * Add entries at *_actions
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    protected function _installAddActions($tempXml) {
        $actionColl = new cApiActionCollection();

        $actionCount = count($tempXml->action);
        for ($i = 0; $i < $actionCount; $i++) {
            // build attribut
            $area = $tempXml->action[$i]->attributes();

            // security check
            $area = cSecurity::escapeString($area);
            $action = cSecurity::escapeString($tempXml->action[$i]);

            // check for valid area
            if (!in_array($area, $this->allAreas)) {
                $this->errorArea($area);
            }

            // create a new entry
            $actionColl->create($area, $action, '', '', '', 1);
        }
    }

    /**
     * Add entries at *_files and *_frame_files
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    protected function _installAddFrames($tempXml) {
        $fileColl = new cApiFileCollection();
        $frameFileColl = new cApiFrameFileCollection();

        $frameCount = count($tempXml->frame);
        for ($i = 0; $i < $frameCount; $i++) {

            $attributes = array();

            // build attributes with security checks
            foreach ($tempXml->frame[$i]->attributes() as $sKey => $sValue) {
                $attributes[$sKey] = cSecurity::escapeString($sValue);
            }

            // check for valid area
            if (!in_array($attributes['area'], $this->allAreas)) {
                $this->errorArea($attributes['area']);
            }

            // create a new entry at *_files
            $file = $fileColl->create($attributes['area'], $attributes['name'], $attributes['filetype']);

            // create a new entry at *_frame_files
            if (!empty($attributes['frameId'])) {
                $frameFileColl->create($attributes['area'], $attributes['frameId'], $file->get('idfile'));
            }
        }
    }

    /**
     * Add entries at *_nav_main
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    protected function _installAddNavMain($tempXml) {
        $navMainColl = new cApiNavMainCollection();
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pluginId = $this->getPluginId();

        $navCount = count($tempXml->nav);
        for ($i = 0; $i < $navCount; $i++) {
            // security check
            $location = cSecurity::escapeString($tempXml->nav[$i]);

            // create a new entry at *_nav_main
            $navMain = $navMainColl->create($location);

            // set a relation
            $pimPluginRelColl->create($navMain->get('idnavm'), $pluginId, 'navm');
        }
    }

    /**
     * Add entries at *_nav_sub
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    protected function _installAddNavSub($tempXml) {
        $navSubColl = new cApiNavSubCollection();
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pluginId = $this->getPluginId();

        $navCount = count($tempXml->nav);
        for ($i = 0; $i < $navCount; $i++) {

            $attributes = array();

            // build attributes
            foreach ($tempXml->nav[$i]->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // convert area to string
            $attributes['area'] = cSecurity::toString($attributes['area']);

            // check for valid area
            if (!in_array($attributes['area'], $this->allAreas)) {
                $this->errorArea($attributes['area']);
            }

            // create a new entry at *_nav_sub
            $item = $navSubColl->create($attributes['navm'], $attributes['area'], $attributes['level'], $tempXml->nav[$i], 1);

            // set a relation
            $pimPluginRelColl->create($item->get('idnavs'), $pluginId, 'navs');
        }
    }

    /**
     * Add specific sql queries
     *
     * @access protected
     * @return void
     */
    protected function _installAddSpecificSql() {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        if ($this->isExtracted === false) {
            $tempSqlFilename = $this->_extractor->extractArchiveFileToVariable('plugin_install.sql', 0);
        } else {
            $tempSqlFilename = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->extractedPath . '/plugin_install.sql';
        }

        if (!cFileHandler::exists($tempSqlFilename)) {
            return;
        }

        $tempSqlContent = cFileHandler::read($tempSqlFilename);
        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        $pattern = '/(CREATE TABLE IF NOT EXISTS|INSERT INTO|UPDATE|ALTER TABLE) ' . $this->sqlPrefix . '\b/';

        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $tempSqlContent[$i] = str_replace($this->sqlPrefix, $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query($tempSqlContent[$i]);
            }
        }
    }

    /**
     * Add content types (*_type)
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @return void
     */
    protected function _installAddContentTypes($tempXml) {
        $typeColl = new cApiTypeCollection();
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pluginId = $this->getPluginId();

        $pattern = '/^CMS_.+/';

        $typeCount = count($tempXml->type);
        for ($i = 0; $i < $typeCount; $i++) {

            $type = cSecurity::toString($tempXml->type[$i]);

            if (preg_match($pattern, $type)) {

                // create new content type
                $item = $typeColl->create($type, '');

                // set a relation
                $pimPluginRelColl->create($item->get('idtype'), $pluginId, 'ctype');
            }
        }
    }

    /**
     * Add modules
     *
     * @access protected
     * @return void
     */
    protected function _installAddModules($tempXml) {
        $cfg = cRegistry::getConfig();
        $module = new cApiModule();

        $modulesPath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $tempXml->plugin_foldername . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR;

        if (!is_dir($modulesPath)) {
            return false;
        }

        foreach (new DirectoryIterator($modulesPath) as $modulesFiles) {

            if (substr($modulesFiles->getBasename(), -4) == ".zip") {
                $module->import($modulesFiles->getBasename(), $modulesFiles->getBasename(), false);
            }
        }

        $this->uninstallDir($tempXml->plugin_foldername . DIRECTORY_SEPARATOR . "modules");
    }

    /**
     * Uninstall a plugin
     *
     * TODO: Optimize sql method for update plugins (CON-1358)
     *
     * @access public
     * @param $pluginId id of uninstall plugid
     * @param $page page class for success message
     * @param $sql true or false
     * @return void
     */
    public function uninstall($pluginId, $page = null, $sql = true) {
        $cfg = cRegistry::getConfig();

        // security check
        $pluginId = cSecurity::toInteger($pluginId);

        // initializing collection classes
        $areaColl = new cApiAreaCollection();
        $actionColl = new cApiActionCollection();
        $fileColl = new cApiFileCollection();
        $frameFileColl = new cApiFrameFileCollection();
        $navMainColl = new cApiNavMainCollection();
        $navSubColl = new cApiNavSubCollection();
        $typeColl = new cApiTypeCollection();
        $pimPluginColl = new PimPluginCollection();

        // get relations
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pimPluginRelColl->setWhere('idplugin', $pluginId);
        $pimPluginRelColl->query();

        $relations = array();

        while (($relation = $pimPluginRelColl->next()) !== false) {
            // relation to tables *_area, *_nav_main and *_type
            $index = $relation->get('type');

            // is equivalent to idarea, idnavm or idtype column
            $value = $relation->get('iditem');
            $relations[$index][] = $value;
        }

        // delete entries with relations to *_area
        if (!empty($relations['area'])) {
            $actionColl->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $fileColl->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $frameFileColl->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $navSubColl->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $areaColl->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
        }

        // delete entries from *_nav_main
        if (!empty($relations['navm'])) {
            $navMainColl->deleteByWhereClause("idnavm IN('" . join("', '", $relations['navm']) . "')");
        }

        // delete content types
        if (!empty($relations['ctype'])) {
            $typeColl->deleteByWhereClause("idtype IN('" . join("', '", $relations['ctype']) . "')");
        }

        // get plugininformations
        $pimPluginColl->setWhere('idplugin', $pluginId);
        $pimPluginColl->query();
        $pimPluginSql = $pimPluginColl->next();

        $foldername = $pimPluginSql->get('folder');

        // delete specific sql entries or tables
        if ($sql == true) {

            if ($this->getIsUpdate() == 0) {
                $this->_uninstallFullDeleteSpecificSql($foldername);
            } else {
                $this->_uninstallUpdateDeleteSpecificSql($foldername);
            }
        }

        // pluginname
        $pluginname = $pimPluginSql->get('name');

        // delete entries at *_plugins_rel and *_plugins
        $pimPluginRelColl->deleteByWhereClause('idplugin = ' . $pluginId);
        $pimPluginColl->deleteByWhereClause('idplugin = ' . $pluginId);

        // success message
        if ($page instanceof cGuiPage) {
            $page->displayInfo(i18n('The plugin', 'pim') . ' <strong>' . $pluginname . '</strong> ' . i18n('has been successfully uninstalled. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Delete specific sql entries or tables, full uninstall mode
     *
     * @access protected
     * @param $foldername foldername of installed plugin
     * @return void
     */
    protected function _uninstallFullDeleteSpecificSql($foldername) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        $tempSqlFilename = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $foldername . '/plugin_uninstall.sql';

        if (!cFileHandler::exists($tempSqlFilename)) {
            return;
        }

        $tempSqlContent = cFileHandler::read($tempSqlFilename);
        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        $pattern = '/(DELETE FROM|DROP TABLE) ' . $this->sqlPrefix . '\b/';

        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $tempSqlContent[$i] = str_replace($this->sqlPrefix, $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query($tempSqlContent[$i]);
            }
        }
    }

    /**
     * Delete specific sql entries or tables, update uninstall mode
     *
     * TODO: Optimize this function (CON-1358)
     *
     * @access protected
     * @param $foldername foldername of installed plugin
     * @return void
     */
    protected function _uninstallUpdateDeleteSpecificSql($foldername) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        // name of uploaded file
        $tempFileName = cSecurity::escapeString($_FILES['package']['name']);

        // path to temporary dir
        $tempFileNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

        $extractor = new PimPluginArchiveExtractor($tempFileNewPath, $tempFileName);
        $tempSqlContent = $extractor->extractArchiveFileToVariable('plugin_update.sql');

        if (empty($tempSqlContent)) {
            return;
        }

        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        $pattern = '/(UPDATE|ALTER TABLE|DELETE FROM|DROP TABLE) ' . $this->sqlPrefix . '\b/';

        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $tempSqlContent[$i] = str_replace($this->sqlPrefix, $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query($tempSqlContent[$i]);
            }
        }
    }

    /**
     * Delete a installed plugin directory
     *
     * @access public
     * @param $foldername name of extracted plugin
     * @param $page page class for success or error message
     * @return void
     */
    public function uninstallDir($foldername, $page = null) {
        $cfg = cRegistry::getConfig();

        // delete folders
        $folderpath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . cSecurity::escapeString($foldername);
        cFileHandler::recursiveRmdir($folderpath);

        if ($page instanceof cGuiPage) {

            // success message
            if (!cFileHandler::exists($folderpath)) {
                $page->displayInfo(i18n('The pluginfolder', 'pim') . ' <strong>' . $foldername . '</strong> ' . i18n('has been successfully uninstalled.', 'pim'));
            } else if (cFileHandler::exists($folderpath)) {
                $page->displayError(i18n('The pluginfolder', 'pim') . ' <strong>' . $foldername . '</strong> ' . i18n('could not be uninstalled.', 'pim'));
            }
        }
    }

    /**
     * Enable / disable plugins (active status)
     *
     * @param integer $pluginId
     * @param $page page class for success or error message
     * @return void
     */
    public function changeActiveStatus($pluginId, $page = null) {
        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $pimPluginColl->query();
        $plugin = $pimPluginColl->next();
        $pluginname = $plugin->get('name');
        $activeStatus = $plugin->get('active');

        // get relations
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pimPluginRelColl->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $pimPluginRelColl->setWhere('type', 'navs');
        $pimPluginRelColl->query();

        if ($activeStatus == 1) { // set offline
            $plugin->set('active', 0);
            $plugin->store();

            while (($relation = $pimPluginRelColl->next()) !== false) {
                // is equivalent to idnavs column at *_nav_sub
                $idnavs = $relation->get('iditem');
                $this->_setNavSubOnlineStatus($idnavs, 0);
            }

            $page->displayInfo(i18n('The plugin', 'pim') . ' <strong>' . $pluginname . '</strong> ' . i18n('has been sucessfully disabled. To apply the changes please login into backend again.', 'pim'));
        } else { // set online
            $plugin->set('active', 1);
            $plugin->store();

            while (($relation = $pimPluginRelColl->next()) !== false) {
                // is equivalent to idnavs column at *_nav_sub
                $idnavs = $relation->get('iditem');
                $this->_setNavSubOnlineStatus($idnavs, 1);
            }

            $page->displayInfo(i18n('The plugin', 'pim') . ' <strong>' . $pluginname . '</strong> ' . i18n('has been sucessfully enabled. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Set the online status of nav_sub
     *
     * @param integer $idnavs Id of nav_sub menu (is equivalent to idnavs
     * @param integer $online 0 = offline, 1 = online
     * @return true
     */
    protected function _setNavSubOnlineStatus($idnavs, $online) {
        $navSubColl = new cApiNavSubCollection();
        $navSubColl->setWhere('idnavs', cSecurity::toInteger($idnavs));
        $navSubColl->query();

        $navSub = $navSubColl->next();
        $navSub->set('online', cSecurity::toInteger($online));
        $navSub->store();

        return true;
    }

    /**
     * Check uuId for update routine
     *
     * @access protected
     * @param integer $pluginId id of installed plugid
     * @return void
     */
    public function checkSamePlugin($pluginId = '0') {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $sess = cRegistry::getSession();

        // name of uploaded file
        $tempFileName = cSecurity::escapeString($_FILES['package']['name']);

        // path to temporary dir
        $tempFileNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

        // move temporary files into CONTENIDO temp dir
        move_uploaded_file($_FILES['package']['tmp_name'], $tempFileNewPath . $tempFileName);

        // initalizing plugin archive extractor
        try {
            $extractor = new PimPluginArchiveExtractor($tempFileNewPath, $tempFileName);
            $this->addArchiveObject($extractor);
        } catch (cException $e) {
            $extractor->destroyTempFiles();
        }

        // extract plugin.xml content to variable
        $tempPluginXmlContent = $extractor->extractArchiveFileToVariable('plugin.xml');
        $this->setTempXml($tempPluginXmlContent);

        // load plugin.xml to an xml-string
        $tempXml = simplexml_load_string($this->getTempXml());

        // save new uuId
        $newId = $tempXml->general->uuid;

        // initializing PimPluginCollection class
        $pimPluginColl = new PimPluginCollection();

        // if pluginId is not null, add idplugin for sql statement (case:
        // update)
        if ($pluginId != '0') {
            $pimPluginColl->setWhere('idplugin', $pluginId);
        }

        $pimPluginColl->query();
        while ($result = $pimPluginColl->next()) {

            // save old uuId
            $oldId = $result->get('uuid');

            if ($pluginId == 0 && $newId == $oldId) { // case: new installation
                                                      // failed
                $pageError = new cGuiPage('pim_error', 'pim');
                $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
                $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
                $pageError->displayError(i18n('This plugin is already installed', 'pim'));
                $pageError->render();
                exit();
            } elseif ($pluginId != 0 && $newId != $oldId) { // case: update
                                                            // failed
                $pageError = new cGuiPage('pim_error', 'pim');
                $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
                $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
                $pageError->displayError(i18n('You have to update the same plugin', 'pim'));
                $pageError->render();
                exit();
            }
        }
    }

    /**
     * Check file type, Plugin Manager accepts only Zip archives
     *
     * @access public
     * @return void
     */
    public function checkZip() {
        $sess = cRegistry::getSession();

        if (substr($_FILES['package']['name'], -4) != ".zip") {
            $pageError = new cGuiPage('pim_error', 'pim');
            $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
            $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
            $pageError->displayError(i18n('Plugin Manager accepts only Zip archives', 'pim'));
            $pageError->render();
            exit();
        }
    }

    /**
     * Error message function for not existing areas
     *
     * @access protected
     * @param string $area name of not existing area
     */
    protected function errorArea($area) {
        $sess = cRegistry::getSession();

        // uninstall plugin from database
        $this->uninstall($this->pluginId, null, false);

        // error template
        $pageError = new cGuiPage('pim_error', 'pim');
        $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
        $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
        $pageError->displayError(i18n('Defined area', 'pim') . ' <strong>' . $area . '</strong> ' . i18n('are not found on your CONTENIDO installation. Please contact your plugin author.', 'pim'));
        $pageError->render();
        exit();
    }

}
