<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO code generator abstract class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-08-11
 *   modified 2011-08-24, Dominik Ziegler, removed deprecated function SaveKeywordsforart
 *
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * CONTENIDO abstract code generator class.
 * @package    CONTENIDO Backend Classes
 */
abstract class Contenido_CodeGenerator_Abstract
{
    /**
     * CONTENIDO database instance
     * @var DB_Contenido
     */
    protected $_db;

    /**
     * Frontend debug options, see $frontend_debug in cms/config.php
     * @var array
     */
    protected $_feDebugOptions = array();

    /**
     * Debug flag, prints some status messages if enabled.
     * @var bool
     * @deprecated No longer needed. The backend chooses the debug mode.
     */
    protected $_debug = false;

    /**
     * Collected CSS data for current template
     * @var string
     */
    protected $_cssData = '';

    /**
     * Collected JS data for current template
     * @var string
     */
    protected $_jsData = '';

    /**
     * Template name
     * @var string
     */
    protected $_tplName = '';

    /**
     * Category id
     * @var int
     */
    protected $_idcat;

    /**
     * Article id
     * @var int
     */
    protected $_idart;

    /**
     * Language id
     * @var int
     */
    protected $_lang;

    /**
     * Client id
     * @var int
     */
    protected $_client;

    /**
     * Flag to process layout
     * @var bool
     */
    protected $_layout;

    /**
     * Flag to persist generated code
     * @var bool
     */
    protected $_save;

    /**
     * Article language id
     * @var int
     */
    protected $_idartlang;

    /**
     * Page title (generally from article language table)
     * @var string
     */
    protected $_pageTitle;

    /**
     * Layout code. Initially with container tags which will be replaced against module outputs.
     * @var string
     */
    protected $_layoutCode = '';

    /**
     * Module output code prefix
     * @var array
     */
    protected $_modulePrefix = array();

    /**
     * Module output code
     * @var string
     */
    protected $_moduleCode = '';

    /**
     * Module output code suffix
     * @var array
     */
    protected $_moduleSuffix = array();


    public function __construct()
    {
        global $cfg, $cfgClient;
        $this->_db = new DB_Contenido();
    }


    /**
     * Setter for debug property
     *
     * @deprecated No longer needed. The backend chooses the debug mode.
     * @param  bool  $debug
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }


    /**
     * Setter for frontend debug options (see $frontend_debug in config.php
     * located in clients frontend directory)
     *
     * @param  bool  $debug
     */
    public function setFrontendDebugOptions(array $debugOptions)
    {
        $this->_feDebugOptions = $debugOptions;
    }


    /**
     * Generates the code for a specific article (article for a client in a language).
     *
     * @param  int   $idcat
     * @param  int   $idart
     * @param  int   $lang
     * @param  int   $client
     * @param  bool  $layout
     * @param  bool  $save  Flag to persist generated code
     * @return  string  Generated code or error code '0601' if no template
     *                  configuration was found for category or article.
     */
    public function generate($idcat, $idart, $lang, $client, $layout = false, $save = true)
    {
        global $cfg;

        $this->_idcat = (int) $idcat;
        $this->_idart = (int) $idart;
        $this->_lang = (int) $lang;
        $this->_client = (int) $client;
        $this->_layout = (bool) $layout;
        $this->_save = (bool) $save;

        $sql = "SELECT idartlang, pagetitle FROM " . $cfg['tab']['art_lang']
             . " WHERE idart=" . (int) $this->_idart . " AND idlang=" . (int) $this->_lang;
        $this->_db->query($sql);
        $this->_db->next_record();

        $this->_idartlang = $this->_db->f('idartlang');
        $this->_pageTitle = stripslashes($this->_db->f('pagetitle'));

        return $this->_generate();
    }


    /**
     * Generates the code for a specific article (article for a client in a language).
     * @return  string  The generated code
     */
    abstract function _generate();


    /**
     * Returns the template configuration id, either by configured article or by
     * configured category.
     *
     * @return int|null
     */
    protected function _getTemplateConfigurationId()
    {
        // Get configuration for article
        $idtplcfg = conGetTemplateConfigurationIdForArticle($this->_idart, $this->_idcat, $this->_lang, $this->_client);
        if (is_numeric($idtplcfg)) {
            // Article is configured
            $this->_debug("configuration for article found: $idtplcfg<br><br>");
        } else {
            // Check whether category is configured
            $idtplcfg = conGetTemplateConfigurationIdForCategory($this->_idcat, $this->_lang, $this->_client);
            if (null !== $idtplcfg) {
                // Category is configured
                $this->_debug("configuration for category found: $idtplcfg<br><br>");
            }
        }

        return (is_numeric($idtplcfg)) ? $idtplcfg : null;
    }


    abstract protected function _processNoConfigurationError();


    protected function _getTemplateData()
    {
        global $cfg;

        // Get IDLAY and IDMOD array
        $sql = "SELECT a.idlay AS idlay, a.idtpl AS idtpl, a.name AS name
                FROM " . $cfg['tab']['tpl'] . " AS a, " . $cfg['tab']['tpl_conf'] . " AS b
                WHERE b.idtplcfg = " . $this->_idtplcfg . " AND b.idtpl = a.idtpl";
        $this->_db->query($sql);
        $this->_db->next_record();
        $data = $this->_db->toArray();

        if ($this->_layout !== false) {
            $data['idlay'] = $this->_layout;
        }
        $this->_debug("Usging Layout: $idlay and Template: $idtpl for generation of code.<br><br>");

        return $data;
    }


    /**
     * Processes replacements of all existing CMS_... tags within passed code
     *
     * @param  array   $contentList  Assoziative list of CMS variables
     * @param  bool    $saveKeywords  Flag to save collected keywords during replacement process.
     */
    protected function _processCmsTags($contentList, $saveKeywords = true)
    {
        // #####################################################################
        // NOTE: Variables below are required in included/evaluated content type codes!
        global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

        // NOTE: Variables below are additionally required in included/evaluated
        //       content type codes within backend edit mode!
        global $edit, $editLink, $belang;

        $idcat = $this->_idcat;
        $idart = $this->_idart;
        $lang = $this->_lang;
        $client = $this->_client;
        $idartlang = $this->_idartlang;

        if (!is_object($db2)) {
            $db2 = new DB_Contenido();
        }
        // End: Variables required in content type codes
        // #####################################################################

        $match = array();
        $keycode = array();

        // $a_content is used by included/evaluated content type codes below
        $a_content = $contentList;

        // Select all cms_type entries
        $sql = 'SELECT idtype, type, code FROM ' . $cfg['tab']['type'];
        $db->query($sql);
        $_typeList = array();
        while ($db->next_record()) {
            $_typeList[] = $db->toObject();
        }

        // Replace all CMS_TAGS[]
        foreach($_typeList as $_typeItem) {
            $key = strtolower($_typeItem->type);
            $type = $_typeItem->type;
            // Try to find all CMS_{type}[{number}] values, e. g. CMS_HTML[1]
#            $tmp = preg_match_all('/(' . $type . ')\[+([a-z0-9_]+)+\]/i', $this->_layoutCode, $match);
            $tmp = preg_match_all('/(' . $type . '\[+(\d)+\])/i', $this->_layoutCode, $match);

            $a_[$key] = $match[0];

            $success = array_walk($a_[$key], 'extractNumber');

            $search = array();
            $replacements = array();

            $typeCodeFile = $cfg['path']['contenido'] . 'includes/type/code/include.' . $type . '.code.php';

            foreach ($a_[$key] as $val) {
                if (file_exists($typeCodeFile)) {
                    // include CMS type code
                    include($typeCodeFile);
                } elseif (!empty($_typeItem->code)) {
                    // old version, evaluate CMS type code
                    cDeprecated("Move code for $type from table into file system (contenido/includes/cms/code/)");
                    eval($_typeItem->code);
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
     * @param int  $containerId  Container id
     * @param string $containerCfg  A string being formatted like concatenated query
     *                              parameter, e. g. param1=value1&param2=value2...
     * @return  string  Concatenated PHP code containing CMS_VALUE variables and their values
     */
    protected function _processCmsValueTags($containerId, $containerCfg)
    {
        $containerCfgList = array();

        $containerCfg = preg_replace('/(&\$)/', '', $containerCfg);
        parse_str($containerCfg, $containerCfgList);
/*        $tmp1 = preg_split('/&/', $containerCfg);
        foreach ($tmp1 as $key1 => $value1) {
            $tmp2 = explode('=', $value1);
            foreach ($tmp2 as $key2 => $value2) {
                $containerCfgList["$tmp2[0]"] = $tmp2[1];
            }
        }*/

        $CiCMS_Var = '$C' . $containerId . 'CMS_VALUE';
        $CiCMS_Values = array();

        foreach ($containerCfgList as $key3 => $value3) {
            $tmp = urldecode($value3);
            $tmp = str_replace("\'", "'", $tmp);
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
     * @param int  $containerId  Container id
     * @param array $module Recordset as assoziative array of related module (container code)
     */
    protected function _processFrontendDebug($containerId, array $module)
    {
        global $cfg;

        if (empty($this->_feDebugOptions)) {
            return;
        }

        $sFeDebug = '';
        if ($this->_feDebugOptions['container_display'] == true) {
            $sFeDebug .= "Container: CMS_CONTAINER[$containerId]\\n";
        }
        if ($this->_feDebugOptions['module_display'] == true) {
            $sFeDebug .= "Module: " . $module['name'] . "\\n";
        }

        if ($this->_feDebugOptions['module_timing_summary'] == true || $this->_feDebugOptions['module_timing'] == true) {
            $sFeDebug .= 'Eval-Time: $modTime' . $containerId . "\\n";
            $this->_modulePrefix[] = '$modStart' . $containerId . ' = getmicrotime(true);';
            $this->_moduleSuffix[] = '$modEnd' . $containerId . ' = getmicrotime(true);';
            $this->_moduleSuffix[] = '$modTime' . $containerId . ' = $modEnd' . $containerId . ' - $modStart' . $containerId . ';';
        }

        if ($sFeDebug != '') {
            $this->_modulePrefix[] = 'echo \'<img onclick="javascript:showmod' . $containerId . '();" src="' . $cfg['path']['contenido_fullhtml'] . 'images/but_preview.gif"><br>\';';
        }

        if ($this->_feDebugOptions['module_timing_summary'] == true) {
            $this->_moduleSuffix[] = 'echo \'<script type="text/javascript">function showmod' . $containerId . '(){window.alert(\\\'\'. "' . addslashes($sFeDebug) . '".\'\\\');} </script>\';';
            $this->_moduleSuffix[] = '$cModuleTimes["' . $containerId . '"] = $modTime' . $containerId . ';';
            $this->_moduleSuffix[] = '$cModuleNames["' . $containerId . '"] = "' . addslashes($module['name']) . '";';
        }
    }


    /**
     * Replaces container tag in layout against the parsed container code (module code).
     *
     * @param  int  $containerId  Container id
     */
    protected function _processCmsContainer($containerId)
    {
        $cmsContainer = "CMS_CONTAINER[$containerId]";

        // Replace new container (<container id="n"..>) against old one (CMS_CONTAINER[n])
        $this->_layoutCode = preg_replace("/<container( +)id=\\\"$containerId\\\"(.*)>(.*)<\/container>/Uis", $cmsContainer, $this->_layoutCode);
        $this->_layoutCode = preg_replace("/<container( +)id=\\\"$containerId\\\"(.*)\/>/i", $cmsContainer, $this->_layoutCode);

        // Concatenate final container/module output code
        $modulePrefix = "<?php\n" . implode("\n", $this->_modulePrefix) . "\n?>";
        $moduleSuffix = "<?php\n" . implode("\n", $this->_moduleSuffix) . "\n?>";
        $moduleOutput = $modulePrefix . $this->_moduleCode . $moduleSuffix;

        // Replace container (CMS_CONTAINER[n]) against the container code
        $this->_layoutCode = str_ireplace($cmsContainer, $moduleOutput, $this->_layoutCode);
//        $this->_layoutCode = addslashes($this->_layoutCode);
    }


    /**
     * Returns array of all CMS_... vars being used by current article and language
     *
     * @return array like $arr[type][typeid] = value;
     */
    protected function _getUsedCmsTypesData()
    {
        global $cfg;

        $return = array();

        // Find out what kind of CMS_... Vars are in use
        $sql = "SELECT * FROM " . $cfg['tab']['content'] . " AS A,
                    " . $cfg['tab']['art_lang'] . " AS B, " . $cfg['tab']['type'] . " AS C
                WHERE A.idtype = C.idtype AND A.idartlang = B.idartlang AND
                    B.idart = " . $this->_idart . " AND B.idlang = " . $this->_lang;
        $this->_db->query($sql);
        while ($this->_db->next_record()) {
            $return[$this->_db->f('type')][$this->_db->f('typeid')] = $this->_db->f('value');
        }

        return $return;
    }


    /**
     * Resets module related variables
     */
    protected function _resetModule()
    {
        $this->_modulePrefix = array();
        $this->_moduleCode = '';
        $this->_moduleSuffix = array();
    }

    /**
     * Outputs passed message, if debug is enabled
     *
     * @param  string  $msg
     */
    protected function _debug($msg)
    {
    	cDebug($msg);
    }
}
