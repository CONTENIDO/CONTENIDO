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

    public $valid = false;

    public $tempXml;

    protected $_pluginConfig;

    protected $_pluginSqlBuilder;

    protected $_extractor; // TODO
    public function addArchiveObject($extractor) {
        $this->_extractor = $extractor;
    }

    public function setConfig(Contenido_PluginConfig $config) {
        $this->_pluginConfig = $config;
    }

    // check $sTempXml file with getValidXml()
    public function checkXml() {
        $this->getValidXml($this->tempXml, 'plugins/pim/xml/plugin_info.xsd');
    }

    public function getValidXml($xml, $xsd) {
        $dom = new DomDocument();
        $dom->loadXML($xml);

        if ($dom->schemaValidate($xsd)) {
            $this->valid = true;
            return true;
        } else {
            $this->_extractor->destroyTempFiles();
            throw new cException('Invalid Xml document');
        }
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
        $pimPlugin = $pimPluginColl->create($tempXml->general->plugin_name, $tempXml->general->description, $tempXml->general->author, $tempXml->general->copyright, $tempXml->general->mail, $tempXml->general->website, $tempXml->general->version, $tempXml->general->plugin_foldername, $tempXml->general->guid, $tempXml->general->attributes()->active);
        $pluginId = $pimPlugin->get('idplugin');

        // add entries at *_area
        $this->_installAddArea($tempXml->contenido->areas, $pluginId);

        // add entries at *_actions
        $this->_installAddActions($tempXml->contenido->actions);

        // add entries at *_files and *_frame_files
        $this->_installAddFrames($tempXml->contenido->frames);

        // add entries at *_nav_main
        $this->_installAddNavMain($tempXml->contenido->nav_main, $pluginId);

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
            $attributes['parent'] = cSecurity::escapeString($attributes['parent']);
            $attributes['menuless'] = cSecurity::toInteger($attributes['menuless']);

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
     * TODO: Implement at XSD-File Add entries at *_nav_main
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
        $tempSqlFilename = $this->_extractor->extractArchiveFileToVariable('plugin.sql', 0);

        if (file_exists($tempSqlFilename)) {
            $f = fopen($tempSqlFilename, 'rb');

            while (($tempSqlContent = fgets($f)) !== false) {
                $tempSqlContent = str_replace('!PREFIX!', $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent);
                // TODO remove debug output
                echo $tempSqlContent . '<br />';
            }
        }
    }

    /**
     * Uninstall a plugin
     *
     * @access public
     * @param $pluginId id of uninstall plugid
     * @return void
     */
    public function uninstall($pluginId) {
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

        // delete folders
        $pimPluginColl->setWhere('idplugin', $pluginId);
        $pimPluginColl->query();

        while (($pimPlugin = $pimPluginColl->next()) !== false) {
            $foldername = $pimPlugin->get('folder');
            $folderpath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . cSecurity::escapeString($foldername);
            cFileHandler::recursiveRmdir($folderpath);
        }

        // delete entries at *_plugins_rel and *_plugins
        $pimPluginRelColl->deleteByWhereClause('idplugin = ' . $pluginId);
        $pimPluginColl->deleteByWhereClause('idplugin = ' . $pluginId);
    }

}
