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
 * @package    CONTENIDO Backend classes
 * @version    0.0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.9
 *
 * {@internal
 *   created 2011-08-11
 *   modified 2011-08-24, Dominik Ziegler, removed deprecated function SaveKeywordsforart
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

abstract class Contenido_CodeGenerator_Abstract
{
    protected $_db;

    protected $_feDebugOptions = array();
    protected $_debug = false;

    protected $_cssData = '';
    protected $_jsData = '';
    protected $_tplName = '';

    protected $_idcat;
    protected $_idart;
    protected $_lang;
    protected $_client;
    protected $_layout;

    protected $_code = '';
    protected $_output = '';


    public function __construct()
    {
        global $cfg, $cfgClient;
        $this->_db = new DB_Contenido();
    }


    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }


    public function setFrontendDebugOptions(array $debugOptions)
    {
        $this->_feDebugOptions = $debugOptions;
    }


    public function generate($idcat, $idart, $lang, $client, $layout = false)
    {
        $this->_idcat = (int) $idcat;
        $this->_idart = (int) $idart;
        $this->_lang = (int) $lang;
        $this->_client = (int) $client;
        $this->_layout = (bool) $layout;

        return $this->_generate();
    }


    abstract function _generate();


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
        global $db, $cfg;

        // Get IDLAY and IDMOD array
        $sql = "SELECT a.idlay AS idlay, a.idtpl AS idtpl, a.name AS name
                FROM " . $cfg["tab"]["tpl"] . " AS a, " . $cfg["tab"]["tpl_conf"] . " AS b
                WHERE b.idtplcfg = " . $this->_idtplcfg . " AND b.idtpl = a.idtpl";
        $db->query($sql);
        $db->next_record();
        $data = $db->toArray();

        if ($this->_layout !== false) {
            $data['idlay'] = $this->_layout;
        }
        $this->_debug("Usging Layout: $idlay and Template: $idtpl for generation of code.<br><br>");

        return $data;
    }


    /**
     * Processes replacements of all existing CMS_TAGS within passed code
     *
     * @param  array   $contentList  Assoziative list of CMS variables
     * @param  bool    $saveKeywords  Flag to save collected keywords during replacement process.
     */
    protected function _processCmsTags($contentList, $saveKeywords = true)
    {
        global $cfg, $db;

        $match = array();
        $keycode = array();

        // $a_content is used by code from database evaluated below
        $a_content = $contentList;

        // replace all CMS_TAGS[]
        $sql = 'SELECT type, code FROM ' . $cfg['tab']['type'];
        $db->query($sql);
        while ($db->next_record()) {
            $key = strtolower($db->f('type'));
            $type = $db->f('type');
            // try to find all CMS_{type}[{number}] values, e. g. CMS_HTML[1]
#            $tmp = preg_match_all('/(' . $type . ')\[+([a-z0-9_]+)+\]/i', $this->_code, $match);
            $tmp = preg_match_all('/(' . $type . ')\[+(\d)+\]/i', $this->_code, $match);

            $a_[$key] = $match[2];

            $success = array_walk($a_[$key], 'extractNumber');

            $search = array();
            $replacements = array();
            
            foreach ($a_[$key] as $val) {
                eval($db->f('code'));

                $search[$val] = sprintf('%s[%s]', $type, $val);
                $replacements[$val] = $tmp;
                $keycode[$type][$val] = $tmp;
            }

            $this->_code = str_ireplace($search, $replacements, $this->_code);
        }
    }


    abstract protected function _processCodeTitleTag($pageTitle);


    abstract protected function _processCodeMetaTags($idArtLang);


    protected function _processCmsValueTags($containerCfg, $cId)
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

        $CiCMS_Var = '$C' . $cId . 'CMS_VALUE';
        $CiCMS_VALUE = '';

        foreach ($containerCfgList as $key3 => $value3) {
            $tmp = urldecode($value3);
            $tmp = str_replace("\'", "'", $tmp);
            $CiCMS_VALUE .= $CiCMS_Var . '[' . $key3 . ']="' . $tmp . '"; ';
            $this->_output = str_replace("\$CMS_VALUE[$key3]", $tmp, $this->_output);
            $this->_output = str_replace("CMS_VALUE[$key3]", $tmp, $this->_output);
        }

        $this->_output = str_replace("CMS_VALUE", $CiCMS_Var, $this->_output);
        $this->_output = str_replace("\$" . $CiCMS_Var, $CiCMS_Var, $this->_output);
        $this->_output = preg_replace("/(CMS_VALUE\[)([0-9]*)(\])/i", '', $this->_output);

        return $CiCMS_VALUE;
    }


    protected function _processFrontendDebug(array $module, $cId)
    {
        global $cfg;

        if (empty($this->_feDebugOptions)) {
            return;
        }

        $sFeDebug = '';
        if ($this->_feDebugOptions['container_display'] == true) {
            $sFeDebug .= "Container: CMS_CONTAINER[$cId]\\n";
        }
        if ($this->_feDebugOptions['module_display'] == true) {
            $sFeDebug .= "Modul: " . $module['name'] . "\\n";
        }
        if ($this->_feDebugOptions['module_timing_summary'] == true || $this->_feDebugOptions['module_timing'] == true) {
            $sFeDebug .= 'Eval-Time: $modtime' . $cId . "\\n";
            $this->_output = '<?php $modstart' . $cId . '=getmicrotime();?>' . $this->_output . '<?php $modend' . $cId . '=getmicrotime()+0.001; $modtime' . $cId . '=$modend' . $cId . ' - $modstart' . $cId . ';?>';
        }

        if ($sFeDebug != '') {
            $this->_output = '<?php echo \'<img onclick="javascript:showmod' . $cId . '();" src="' . $cfg['path']['contenido_fullhtml'] . 'images/but_preview.gif">\'; ?>' . '<br>' . $this->_output;
            $this->_output = $this->_output . '<?php echo \'<script language="javascript">function showmod' . $cId . '(){window.alert(\\\'\'. "' . addslashes($sFeDebug) . '".\'\\\');} </script>\';?>';
        }

        if ($this->_feDebugOptions['module_timing_summary'] == true) {
            $this->_output .= '<?php $cModuleTimes["' . $cId . '"] = $modtime' . $cId . ';?>';
            $this->_output .= '<?php $cModuleNames["' . $cId . '"] = "' . addslashes($module['name']) . '";?>';
        }
    }


    protected function _processCmsContainer($cId, $ciCmsValue)
    {
        // Replace new containers
        $this->_code = preg_replace("/<container( +)id=\\\"$cId\\\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$cId]", $this->_code);
        $this->_code = preg_replace("/<container( +)id=\\\"$cId\\\"(.*)\/>/i", "CMS_CONTAINER[$cId]", $this->_code);
        $this->_code = str_ireplace("CMS_CONTAINER[$cId]", "<?php $ciCmsValue ?>\n" . $this->_output, $this->_code);
//        $this->_code = addslashes($this->_code);
    }


    protected function _getUsedCmsTypesData()
    {
        global $db, $cfg;

        $return = array();

        // Find out what kind of CMS_... Vars are in use
        $sql = "SELECT * FROM " . $cfg['tab']['content'] . " AS A, 
                    " . $cfg['tab']['art_lang'] . " AS B, " . $cfg['tab']['type'] . " AS C
                WHERE A.idtype = C.idtype AND A.idartlang = B.idartlang AND
                    B.idart = " . $this->_idart . " AND B.idlang = " . $this->_lang;
        $db->query($sql);
        while ($db->next_record()) {
            $return[$db->f('type')][$db->f('typeid')] = $db->f('value');
        }

        return $return;
    }


    protected function _debug($msg)
    {
        if ($this->_debug) {
            echo $msg;
        }
    }
}
