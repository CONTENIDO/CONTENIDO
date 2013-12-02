<?php
/**
 * CONTENIDO code generator abstract class
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * CONTENIDO abstract code generator class.
 *
 * @package Core
 * @subpackage ContentType
 */
abstract class cCodeGeneratorAbstract {

    /**
     * CONTENIDO database instance
     *
     * @var cDb
     */
    protected $_db;

    /**
     * Frontend debug options, see $frontend_debug in
     * __FRONTEND_PATH__/data/config/config.php
     *
     * @var array
     */
    protected $_feDebugOptions = array();

    /**
     * Collected CSS data for current template
     *
     * @var string
     */
    protected $_cssData = '';

    /**
     * Collected JS data for current template
     *
     * @var string
     */
    protected $_jsData = '';

    /**
     * Template name
     *
     * @var string
     */
    protected $_tplName = '';

    /**
     * Category id
     *
     * @var int
     */
    protected $_idcat;

    /**
     * Article id
     *
     * @var int
     */
    protected $_idart;

    /**
     * Language id
     *
     * @var int
     */
    protected $_lang;

    /**
     * Client id
     *
     * @var int
     */
    protected $_client;

    /**
     * Flag to process layout
     *
     * @var bool
     */
    protected $_layout;

    /**
     * Flag to persist generated code
     *
     * @var bool
     */
    protected $_save;

    /**
     * Article language id
     *
     * @var int
     */
    protected $_idartlang;

    /**
     * Page title (generally from article language table)
     *
     * @var string
     */
    protected $_pageTitle;

    /**
     * Layout code.
     * Initially with container tags which will be replaced against module
     * outputs.
     *
     * @var string
     */
    protected $_layoutCode = '';

    /**
     * Module output code prefix
     *
     * @var array
     */
    protected $_modulePrefix = array();

    /**
     * Module output code
     *
     * @var string
     */
    protected $_moduleCode = '';

    /**
     * Module output code suffix
     *
     * @var array
     */
    protected $_moduleSuffix = array();

    /**
     * Module output code suffix
     *
     * @var cApiArticleLanguage
     */
    protected $_oArtLang;

    /**
     */
    public function __construct() {
        $this->_db = cRegistry::getDb();
    }

    /**
     * Setter for frontend debug options (see $frontend_debug in
     * __FRONTEND_PATH__/data/config/config.php
     * located in clients frontend directory)
     *
     * @param bool $debug
     */
    public function setFrontendDebugOptions(array $debugOptions) {
        $this->_feDebugOptions = $debugOptions;
    }

    /**
     * Generates the code for a specific article (article for a client in a
     * language).
     *
     * @param int $idcat
     * @param int $idart
     * @param int $lang
     * @param int $client
     * @param bool $layout
     * @param bool $save Flag to persist generated code
     * @param bool $contype Flag to enable/disable replacement of CMS_TAGS[]
     *
     * @throws cInvalidArgumentException if an article with the given idart and
     *         idlang can not be loaded
     * @return string Generated code or error code '0601' if no template
     *         configuration was found for category or article.
     */
    public function generate($idcat, $idart, $lang, $client, $layout = false, $save = true, $contype = true) {
        $this->_idcat = (int) $idcat;
        $this->_idart = (int) $idart;
        $this->_lang = (int) $lang;
        $this->_client = (int) $client;
        $this->_layout = (bool) $layout;
        $this->_save = (bool) $save;

        $this->_oArtLang = new cApiArticleLanguage();
        $this->_oArtLang->loadByArticleAndLanguageId($idart, $lang);
        if (!$this->_oArtLang->isLoaded()) {
            throw new cInvalidArgumentException('Couldn\'t load article language for idart=' . $idart . 'AND idlang=' . $lang);
        }

        $this->_idartlang = $this->_oArtLang->get('idartlang');
        $this->_pageTitle = stripslashes($this->_oArtLang->get('pagetitle'));

        return $this->_generate($contype);
    }

    /**
     * Generates the code for a specific article (article for a client in a
     * language).
     *
     * @param bool $contype Flag to enable/disable replacement of CMS_TAGS[]
     * @return string The generated code
     */
    abstract function _generate($contype = true);

    /**
     * Returns the template configuration id, either by configured article or by
     * configured category.
     *
     * @return int|NULL
     */
    protected function _getTemplateConfigurationId() {
        // Get configuration for article
        $idtplcfg = conGetTemplateConfigurationIdForArticle($this->_idart, $this->_idcat, $this->_lang, $this->_client);
        if (is_numeric($idtplcfg) && $idtplcfg != 0) {
            // Article is configured
            cDebug::out("configuration for article found: $idtplcfg<br><br>");
        } else {
            // Check whether category is configured
            $idtplcfg = conGetTemplateConfigurationIdForCategory($this->_idcat, $this->_lang, $this->_client);
            if (NULL !== $idtplcfg) {
                // Category is configured
                cDebug::out("configuration for category found: $idtplcfg<br><br>");
            }
        }

        return (is_numeric($idtplcfg)) ? $idtplcfg : NULL;
    }

    /**
     *
     * @param int $idcatart
     */
    abstract protected function _processNoConfigurationError($idcatart);

    /**
     * Returns array containing used layout, template and template name
     *
     * @global array $cfg
     * @return array Asooziative array like array('idlay' => (int), 'idtpl' =>
     *         (int), 'name' => (string))
     */
    protected function _getTemplateData() {
        global $cfg;

        // Get IDLAY and IDMOD array
        $sql = "SELECT
                    a.idlay AS idlay
                    , a.idtpl AS idtpl
                    , a.name AS name
                FROM
                    `%s` AS a
                    , `%s` AS b
                WHERE
                    b.idtplcfg = %d
                    AND b.idtpl = a.idtpl
                ;";

        $sql = $this->_db->prepare($sql, $cfg['tab']['tpl'], $cfg['tab']['tpl_conf'], $this->_idtplcfg);
        $this->_db->query($sql);
        $this->_db->nextRecord();
        $data = $this->_db->toArray();

        if ($this->_layout !== false) {
            $data['idlay'] = $this->_layout;
        }

        cDebug::out("Using Layout: $data[idlay] and Template: $data[idtpl] for generation of code.<br><br>");

        return $data;
    }

    /**
     * Processes replacements of all existing CMS_...
     * tags within passed code
     *
     * @param array $contentList Assoziative list of CMS variables
     * @param bool $saveKeywords Flag to save collected keywords during
     *        replacement process.
     */
    protected function _processCmsTags($contentList, $saveKeywords = true) {
        // #####################################################################
        // NOTE: Variables below are required in included/evaluated content type
        // codes!
        global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

        // NOTE: Variables below are additionally required in included/evaluated
        // content type codes within backend edit mode!
        global $edit, $editLink, $belang;

        $idcat = $this->_idcat;
        $idart = $this->_idart;
        $lang = $this->_lang;
        $client = $this->_client;
        $idartlang = $this->_idartlang;

        if (!is_object($db2)) {
            $db2 = cRegistry::getDb();
        }
        // End: Variables required in content type codes
        // #####################################################################

        $match = array();
        $keycode = array();

        // NOTE: $a_content is used by included/evaluated content type codes
        // below
        $a_content = $contentList;

        // Select all cms_type entries
        $_typeList = array();
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();
        while ($oType = $oTypeColl->next()) {
            $_typeList[] = $oType->toObject();
        }

        // Replace all CMS_TAGS[]
        foreach ($_typeList as $_typeItem) {
            $key = strtolower($_typeItem->type);

            $type = $_typeItem->type;
            // Try to find all CMS_{type}[{number}] values, e. g. CMS_HTML[1]
            // $tmp = preg_match_all('/(' . $type . ')\[+([a-z0-9_]+)+\]/i',
            // $this->_layoutCode, $match);
            $tmp = preg_match_all('/(' . $type . '\[+(\d)+\])/i', $this->_layoutCode, $match);

            $a_[$key] = $match[0];

            $success = array_walk($a_[$key], 'extractNumber');

            $search = array();
            $replacements = array();

            $typeClassName = $this->_getContentTypeClassName($type);
            $typeCodeFile = $this->_getContentTypeCodeFilePathName($type);

            foreach ($a_[$key] as $val) {
                if (class_exists($typeClassName)) {
                    // We have a class for the content type, use it
                    $tmp = $a_content[$_typeItem->type][$val];
                    $cTypeObject = new $typeClassName($tmp, $val, $a_content);
                    if (cRegistry::isBackendEditMode()) {
                        $tmp = $cTypeObject->generateEditCode();
                    } else {
                        $tmp = $cTypeObject->generateViewCode();
                    }
                } else if (cFileHandler::exists($typeCodeFile)) {
                    // Include CMS type code file
                    include($typeCodeFile);
                }

                $search[$val] = sprintf('%s[%s]', $type, $val);
                $replacements[$val] = $tmp;
                $keycode[$type][$val] = $tmp;
            }

            $this->_layoutCode = str_ireplace($search, $replacements, $this->_layoutCode);
        }
    }

    /**
     * Processes title tag in page code (layout)
     */
    abstract protected function _processCodeTitleTag();

    /**
     * Processes all meta tags in page code (layout)
     */
    abstract protected function _processCodeMetaTags();

    /**
     * Replaces all container/module configuration tags (CMS_VALUE[n] values)
     * against their settings.
     *
     * @param int $containerNumber Container number
     * @param string $containerCfg A string being formatted like concatenated
     *        query
     *        parameter, e. g. param1=value1&param2=value2...
     *
     * @return string Concatenated PHP code containing CMS_VALUE variables and
     *         their values
     */
    protected function _processCmsValueTags($containerNumber, $containerCfg) {
        $containerCfgList = array();

        $containerCfg = preg_replace('/(&\$)/', '', $containerCfg);
        parse_str($containerCfg, $containerCfgList);
        /*
         * $tmp1 = preg_split('/&/', $containerCfg); foreach ($tmp1 as $key1 =>
         * $value1) { $tmp2 = explode('=', $value1); foreach ($tmp2 as $key2 =>
         * $value2) { $containerCfgList["$tmp2[0]"] = $tmp2[1]; } }
         */

        $CiCMS_Var = '$C' . $containerNumber . 'CMS_VALUE';
        $CiCMS_Values = array();

        foreach ($containerCfgList as $key3 => $value3) {
            // Convert special characters and escape backslashes!
            $tmp = conHtmlSpecialChars($value3);
            $tmp = str_replace('\\', '\\\\', $tmp);
            $CiCMS_Values[] = $CiCMS_Var . '[' . $key3 . '] = "' . $tmp . '"; ';
            $this->_moduleCode = str_replace("\$CMS_VALUE[$key3]", $tmp, $this->_moduleCode);
            $this->_moduleCode = str_replace("CMS_VALUE[$key3]", $tmp, $this->_moduleCode);
        }

        $this->_moduleCode = str_replace("CMS_VALUE", $CiCMS_Var, $this->_moduleCode);
        $this->_moduleCode = str_replace("\$" . $CiCMS_Var, $CiCMS_Var, $this->_moduleCode);
        $this->_moduleCode = preg_replace("/(CMS_VALUE\[)([0-9]*)(\])/i", '', $this->_moduleCode);

        return implode("\n", $CiCMS_Values);
    }

    /**
     * Extends container code by adding several debug features, if enabled and
     * configured.
     *
     * @param int $containerNumber Container number (The id attribute in container tag)
     * @param array $module Recordset as assoziative array of related module
     *        (container code)
     */
    protected function _processFrontendDebug($containerNumber, array $module) {
        global $containerinf;

        $data = $this->_getTemplateData();

        if (empty($this->_feDebugOptions)) {
            return;
        }

        $sFeDebug = '';
        if ($this->_feDebugOptions['container_display'] == true) {
            $this->_modulePrefix[] = 'if ($frontend_debug[\'container_display\']) echo "<!-- START CONTAINER ' . $containerinf[$data['idlay']][$containerNumber]['name'] . ' (' . $containerNumber . ') -->";';
        }

        if ($this->_feDebugOptions['module_display'] == true) {
            $this->_modulePrefix[] = 'if ($frontend_debug[\'module_display\']) echo "<!-- START MODULE ' . $module['name'] . ' (' . $module['idmod'] . ') -->";';
        }

        if ($this->_feDebugOptions['module_timing'] == true) {
            $this->_modulePrefix[] = '$modTime' . $containerNumber . ' = -getmicrotime(true);';
            $this->_moduleSuffix[] = '$modTime' . $containerNumber . ' += getmicrotime(true);';
        }

        if ($this->_feDebugOptions['module_display'] == true) {
            $this->_moduleSuffix[] = 'if ($frontend_debug[\'module_display\']) echo "<!-- END MODULE ' . $module['name'] . ' (' . $module['idmod'] . ')";';
            if ($this->_feDebugOptions['module_timing'] == true) {
                $this->_moduleSuffix[] = 'if ($frontend_debug[\'module_timing\']) echo(" AFTER " . $modTime' . $containerNumber . ');';
            }
            $this->_moduleSuffix[] = 'if ($frontend_debug[\'module_display\']) echo " -->";';
        }
        if ($this->_feDebugOptions['container_display'] == true) {
            $this->_moduleSuffix[] = 'if ($frontend_debug[\'container_display\']) echo "<!-- END CONTAINER ' . $containerinf[$data['idlay']][$containerNumber]['name'] . ' (' . $containerNumber . ') -->";';
        }
    }

    /**
     * Replaces container tag in layout against the parsed container code
     * (module code).
     *
     * @param int $containerNumber Container number (The id attribute in container tag)
     */
    protected function _processCmsContainer($containerNumber) {
        $cmsContainer = "CMS_CONTAINER[$containerNumber]";

        // Replace new container (<container id="n"..>) against old one
        // (CMS_CONTAINER[n])
        $this->_layoutCode = preg_replace("/<container( +)id=\\\"$containerNumber\\\"(.*)>(.*)<\/container>/Uis", $cmsContainer, $this->_layoutCode);
        $this->_layoutCode = preg_replace("/<container( +)id=\\\"$containerNumber\\\"(.*)\/>/i", $cmsContainer, $this->_layoutCode);

        // Concatenate final container/module output code, but generate PHP code
        // only
        // if there is something to generate
        $modulePrefix = trim(implode("\n", $this->_modulePrefix));
        if (!empty($modulePrefix)) {
            $modulePrefix = "<?php\n" . $modulePrefix . "\n?>";
        }
        $moduleSuffix = trim(implode("\n", $this->_moduleSuffix));
        if (!empty($moduleSuffix)) {
            $moduleSuffix = "<?php\n" . $moduleSuffix . "\n?>";
        }
        $moduleOutput = $modulePrefix . $this->_moduleCode . $moduleSuffix;

        // Replace container (CMS_CONTAINER[n]) against the container code
        $this->_layoutCode = str_ireplace($cmsContainer, $moduleOutput, $this->_layoutCode);
        // $this->_layoutCode = addslashes($this->_layoutCode);
    }

    /**
     * Returns array of all CMS_...
     * vars being used by current article and language
     *
     * @return array like $arr[type][typeid] = value;
     */
    protected function _getUsedCmsTypesData() {
        global $cfg;

        $return = array();

        // Find out what kind of CMS_... Vars are in use
        $sql = "SELECT * FROM `%s` AS A, `%s` AS B, `%s` AS C
                WHERE A.idtype = C.idtype AND A.idartlang = B.idartlang AND B.idart = %d AND B.idlang = %d";
        $sql = $this->_db->prepare($sql, $cfg['tab']['content'], $cfg['tab']['art_lang'], $cfg['tab']['type'], $this->_idart, $this->_lang);
        $this->_db->query($sql);
        while ($this->_db->nextRecord()) {
            $return[$this->_db->f('type')][$this->_db->f('typeid')] = $this->_db->f('value');
        }

        return $return;
    }

    /**
     * Resets module related variables
     */
    protected function _resetModule() {
        $this->_modulePrefix = array();
        $this->_moduleCode = '';
        $this->_moduleSuffix = array();
    }

    /**
     * Returns the classname for a content type.
     *
     * @param string $type Content type, e. g. CMS_HTMLHEAD
     *
     * @return string The classname e. g. cContentTypeHtmlhead for content type
     *         CMS_HTMLHEAD
     */
    protected function _getContentTypeClassName($type) {
        $typeClassName = 'cContentType' . ucfirst(strtolower(str_replace('CMS_', '', $type)));

        return $typeClassName;
    }

    /**
     * Returns the full path to the include file name of a content type.
     *
     * @param string $type Content type, e. g. CMS_HTMLHEAD
     *
     * @return string The full path e. g.
     *         {path_to_contenido_includes}/type/code/include.CMS_HTMLHEAD.code.php
     *         for content type CMS_HTMLHEAD
     */
    protected function _getContentTypeCodeFilePathName($type) {
        global $cfg;
        $typeCodeFile = cRegistry::getBackendPath() . $cfg['path']['includes'] . 'type/code/include.' . $type . '.code.php';

        return $typeCodeFile;
    }

    /**
     *
     * @return cApiArticleLanguage the artlang object
     */
    protected function getArtLangObject() {
        return $this->_oArtLang;
    }
}
