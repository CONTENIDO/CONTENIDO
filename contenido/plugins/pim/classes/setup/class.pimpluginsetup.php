<?php
/**
 * Abstract class for Contenido Setup operations When creating Install,
 * Uninstall, Update you must extend this class
 *
 * @package plugin
 * @subpackage Plugin Manager
 * @version SVN Revision $Rev:$
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class PimPluginSetup {

    protected $valid = false;

    protected $tempXml;

    protected $isExtracted = false;

    protected $extractedPath;

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
        $dom = new DomDocument();
        $dom->loadXML($this->tempXml);

        if ($dom->schemaValidate('plugins/pim/xml/plugin_info.xsd')) {
            $this->valid = true;
            return true;
        } else {

            if ($this->isExtracted === false) {
                $this->_extractor->destroyTempFiles();
            }

            throw new cException('Invalid Xml document');
        }
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

        // add entries at *_area
        $this->_installAddArea($tempXml->contenido->areas, $pluginId);

        // add entries at *_actions
        $this->_installAddActions($tempXml->contenido->actions);

        // add entries at *_files and *_frame_files
        $this->_installAddFrames($tempXml->contenido->frames);

        // add entries at *_nav_main
        // TODO: $this->_installAddNavMain($tempXml->contenido->nav_main,
        // $pluginId);

        // add entries at *_nav_sub
        $this->_installAddNavSub($tempXml->contenido->nav_sub);

        // add specific sql queries
        $this->_installAddSpecificSql();
    }

    /**
     * Add entries at *_area
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @param $pluginId plugin identifier
     * @return void
     */
    protected function _installAddArea($tempXml, $pluginId) {
        $areaColl = new cApiAreaCollection();
        $pimPluginRelColl = new PimPluginRelationsCollection();

        $areaCount = count($tempXml->area);
        for ($i = 0; $i < $areaCount; $i++) {

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

            // create a new entry at *_files
            $file = $fileColl->create($attributes['area'], $attributes['name'], $attributes['filetype']);

            // create a new entry at *_frame_files
            if (!empty($attributes['frameId'])) {
                $frameFileColl->create($attributes['area'], $attributes['frameId'], $file->get('idfile'));
            }
        }
    }

    /**
     * TODO: Implement at XSD-File, add entries at *_nav_main
     *
     * @access protected
     * @param $tempXml temporary plugin definitions
     * @param $pluginId plugin identifier
     * @return void
     */
    protected function _installAddNavMain($tempXml, $pluginId) {
        $navMainColl = new cApiNavMainCollection();
        $pimPluginRelColl = new PimPluginRelationsCollection();

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

        $navCount = count($tempXml->nav);
        for ($i = 0; $i < $navCount; $i++) {

            $attributes = array();

            // build attributes
            foreach ($tempXml->nav[$i]->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // convert area to string
            $attributes['area'] = cSecurity::toString($attributes['area']);

            // create a new entry at *_nav_sub
            $navSubColl->create($attributes['navm'], $attributes['area'], $attributes['level'], $tempXml->nav[$i], 1);
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

        for ($i = 0; $i < $tempSqlLines; $i++) {

            if (strpos($tempSqlContent[$i], 'CREATE TABLE IF NOT EXISTS !PREFIX!') === 0 || strpos($tempSqlContent[$i], 'INSERT INTO !PREFIX!') === 0 || strpos($tempSqlContent[$i], 'UPDATE !PREFIX!') === 0 || strpos($tempSqlContent[$i], 'ALTER TABLE !PREFIX!') === 0) {
                $tempSqlContent[$i] = str_replace('!PREFIX!', $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query(cSecurity::escapeDB($tempSqlContent[$i], $db));
            }
        }
    }

    /**
     * Uninstall a plugin
     *
     * @access public
     * @param $pluginId id of uninstall plugid
     * @param $page page class for success message
     * @return void
     */
    public function uninstall($pluginId, $page = null) {
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
        $pimPluginColl = new PimPluginCollection();

        // get relations
        $pimPluginRelColl = new PimPluginRelationsCollection();
        $pimPluginRelColl->setWhere('idplugin', $pluginId);
        $pimPluginRelColl->query();

        $relations = array();

        while (($relation = $pimPluginRelColl->next()) !== false) {
            // relation to tables *_area and *_nav_main
            $index = $relation->get('type');

            // is equivalent to idarea oridnavm
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

        // get plugininformations
        $pimPluginColl->setWhere('idplugin', $pluginId);
        $pimPluginColl->query();
        $pimPluginSql = $pimPluginColl->next();

        $foldername = $pimPluginSql->get('folder');

        // delete specific sql entries or tables
        $this->_uninstallDeleteSpecificSql($foldername);

        // delete folders
        $folderpath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . cSecurity::escapeString($foldername);
        cFileHandler::recursiveRmdir($folderpath);

        // pluginname
        $pluginname = $pimPluginSql->get('name');

        // delete entries at *_plugins_rel and *_plugins
        $pimPluginRelColl->deleteByWhereClause('idplugin = ' . $pluginId);
        $pimPluginColl->deleteByWhereClause('idplugin = ' . $pluginId);

        // success message
        if ($page instanceof cGuiPage) {
            $page->displayInfo(i18n('The plugin <strong>', 'pim') . $pluginname . i18n('</strong> has been successfully uninstalled. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Delete specific sql entries or tables
     *
     * @access protected
     * @param $foldername foldername of installed plugin
     * @return void
     */
    protected function _uninstallDeleteSpecificSql($foldername) {
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

        for ($i = 0; $i < $tempSqlLines; $i++) {

            if (strpos($tempSqlContent[$i], 'DELETE FROM !PREFIX!') === 0 || strpos($tempSqlContent[$i], 'DROP TABLE !PREFIX!') === 0) {
                $tempSqlContent[$i] = str_replace('!PREFIX!', $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query(cSecurity::escapeDB($tempSqlContent[$i], $db));
            }
        }
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

        // path to temp-dir
        $tempFileNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

        move_uploaded_file($_FILES['package']['tmp_name'], $tempFileNewPath . $tempFileName);

        // initalizing plugin archive extractor
        try {
            $extractor = new PimPluginArchiveExtractor($tempFileNewPath, $tempFileName);
            $this->addArchiveObject($extractor);
        } catch (cException $e) {
            $extractor->destroyTempFiles();
        }

        $tempPluginXmlContent = $extractor->extractArchiveFileToVariable('plugin.xml');
        $this->setTempXml($tempPluginXmlContent);

        // load plugin.xml to an xml-string
        $tempXml = simplexml_load_string($this->getTempXml());

        // new uuId
        $newId = $tempXml->general->uuid;

        $pimPluginColl = new PimPluginCollection();

        if ($pluginId != '0') {
            $pimPluginColl->setWhere('idplugin', $pluginId);
        }

        $pimPluginColl->query();
        while ($result = $pimPluginColl->next()) {

            // old uuId
            $oldId = $result->get('uuid');

            if ($pluginId == 0 && $newId == $oldId) {
                $pageError = new cGuiPage('pim_error', 'pim');
                $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
                $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
                $pageError->displayError(i18n('This plugin is already installed', 'pim'));
                $pageError->render();
                exit();
            } elseif($pluginId != 0 && $newId != $oldId) {
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
     * Check file type
     *
     * @access public
     * @return void
     */
    public function checkZip() {
        $sess = cRegistry::getSession();

        if(substr($_FILES['package']['name'], -4) != ".zip") {
            $pageError = new cGuiPage('pim_error', 'pim');
            $pageError->set('s', 'BACKLINK', $sess->url('main.php?area=pim&frame=4'));
            $pageError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
            $pageError->displayError(i18n('Plugin Manager accepted only ZIP archives', 'pim'));
            $pageError->render();
            exit();
        }

    }

}
