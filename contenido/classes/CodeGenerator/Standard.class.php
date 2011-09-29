<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO standard code generator
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend classes
 * @version    0.0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created 2011-08-11
 *   $Id$:
 * }}
 *
 */

class Contenido_CodeGenerator_Standard extends Contenido_CodeGenerator_Abstract
{

    public function _generate()
    {
        global $cfgClient;
        global $db, $cfg, $code;

        $this->_cssData = '';
        $this->_jsData = '';
        $this->_tplName = '';

        $this->_debug("conGenerateCode($this->_idcat, $this->_idart, $this->_lang, $this->_client, $this->_layout);<br>");

        // Set CONTENIDO vars for module concepts
        Contenido_Vars::setVar('db', $db);
        Contenido_Vars::setVar('lang', $this->_lang);
        Contenido_Vars::setVar('cfg', $cfg);
        Contenido_Vars::setEncoding($db,$cfg,$this->_lang);
        Contenido_Vars::setVar('cfgClient', $cfgClient);
        Contenido_Vars::setVar('client', $this->_client);
        Contenido_Vars::setVar('fileEncoding', getEffectiveSetting('encoding', 'file_encoding','UTF-8'));

        // Get category article id
        $idcatart = conGetCategoryArticleId($this->_idcat, $this->_idart);

        // Get configuration for article
        $this->_idtplcfg = $this->_getTemplateConfigurationId();
        if (null === $this->_idtplcfg) {
            $this->_processNoConfigurationError();
            return '0601';
        }

        $a_c = conGetContainerConfiguration($this->_idtplcfg);

        // Get IDLAY and IDMOD array
        $data = $this->_getTemplateData();
        $idlay = $data['idlay'];
        $idtpl = $data['idtpl'];
        $this->_tplName = $data['name'];
        
        // List of used modules
        $a_d = conGetUsedModules($idtpl);

        //Load layout code from file
        $layoutInFile = new LayoutInFile($idlay, '', $cfg, $this->_lang);
        $this->_code = $layoutInFile->getLayoutCode();
        $this->_code = capiStrNormalizeLineEndings($this->_code, "\n");

        // Create code for all containers
        if ($idlay) {
            tplPreparseLayout($idlay);
            $tmp_returnstring = tplBrowseLayoutForContainers($idlay);
            $a_container = explode('&', $tmp_returnstring);

            foreach ($a_container as $key => $value) {
                if (!isset($a_d[$value]) || !is_numeric($a_d[$value])) {
                    continue;
                }

                $sql = "SELECT * FROM " . $cfg["tab"]["mod"] . " WHERE idmod=" . $a_d[$value];
                $db->query($sql);
                $db->next_record();
                $module = $db->toArray();

                $thisModule = '<?php $cCurrentModule = ' . $a_d[$value] . '; ?>';
                $thisContainer = '<?php $cCurrentContainer = ' . $value . '; ?>';

                $contenidoModuleHandler = new Contenido_Module_Handler($db->f("idmod"));
                $this->_output = $thisModule.$thisContainer;
                $input = $thisModule.$thisContainer;

                // get the contents of input and output from files and not from db-table
                if ($contenidoModuleHandler->existModul() == true) {
                    $this->_output = $thisModule . $thisContainer . $contenidoModuleHandler->readOutput();
                    //load css and js content of the js/css files
                    $this->_cssData .= $contenidoModuleHandler->getFilesContent("css", "css");
                    $this->_jsData .= $contenidoModuleHandler->getFilesContent("js", "js");

                    $input = $thisModule.$thisContainer.$contenidoModuleHandler->readInput();
                } else {

                }
                $this->_output = $this->_output . "\n";

                // Process CMS value tags
                $CiCMS_VALUE = $this->_processCmsValueTags($a_c[$value], $value);

                // Process frontend debug
                $this->_processFrontendDebug($module, $value);

                // Replace new containers
                $this->_processCmsContainer($value, $CiCMS_VALUE);
            }
        }

        // Find out what kind of CMS_... Vars are in use
        $a_content = $this->_getUsedCmsTypesData();

        // replace all CMS_TAGS[]
        $this->_processCmsTags($a_content, true);

        // add/replace title tag
        $this->_processCodeTitleTag();

        // add/replace meta tags
        $this->_processCodeMetaTags();

        //save the collected css/js data and save it undter the template name ([templatename].css , [templatename].js in cache dir
        $cssDatei = '';
        if (($myFileCss = Contenido_Module_Handler::saveContentToFile($cfgClient[$this->_client], $this->_tplName, "css", $this->_cssData)) == false) {
            $cssDatei = "error error culd not generate css file";
        } else {
            $cssDatei = '<link rel="stylesheet" type="text/css" href="'.$myFileCss.'"/>';
        }
        $jsDatei = '';
        if (($myFileJs = Contenido_Module_Handler::saveContentToFile($cfgClient[$this->_client], $this->_tplName, "js", $this->_jsData)) == false) {
            $jsDatei = "error error error culd not generate js file";
        } else {
            $jsDatei = '<script src="'.$myFileJs.'" type="text/javascript"></script>';
        }

        // Add meta tags
        $this->_code = str_ireplace_once("</head>", $cssDatei . $jsDatei . "</head>", $this->_code);

        // write code into the database
        if ($this->_layout == false) {
            $oCodeColl = new cApiCodeCollection();
            $oCode = $oCodeColl->selectByCatArtAndLang($idcatart, $this->_lang);
            if (!is_object($oCode)) {
                $oCode = $oCodeColl->create($idcatart, $this->_lang, $this->_client, $this->_code);
            } else {
                $oCode->updateCode($this->_code);
            }

            $db->update($cfg['tab']['cat_art'], array('createcode' => 0), array('idcatart' => (int) $idcatart));
        }

        // execute CEC hook
        $this->_code = CEC_Hook::executeAndReturn('Contenido.Content.conGenerateCode', $this->_code);
    }


    protected function _processNoConfigurationError()
    {
        // fixme
        $this->_debug("Neither CAT or ART are configured!<br><br>");

        $code = '<html><body>No code was created for this art in this category.</body><html>';

        $oCodeColl = new cApiCodeCollection();
        $oCode = $oCodeColl->selectByCatArtAndLang($this->_idcatart, $this->_lang);
        if (!is_object($oCode)) {
            $oCode = $oCodeColl->create($idcatart, $this->_lang, $this->_client, $code);
        } else {
            $oCode->updateCode($code);
        }
    }


    /**
     * Processes and adds or replaces title tag for an article
     */
    protected function _processCodeTitleTag()
    {
        if ($this->_pagetitle == '') {
            CEC_Hook::setDefaultReturnValue($this->_pagetitle);
            $this->_pagetitle = CEC_Hook::executeAndReturn('Contenido.Content.CreateTitletag');
        }

        // add or replace title
        if ($this->_pagetitle != '') {
            $this->_code = preg_replace('/<title>.*?<\/title>/is', '{TITLE}', $this->_code, 1);
            if (strstr($this->_code, '{TITLE}')) {
                $this->_code = str_ireplace('{TITLE}', '<title>' . $this->_pagetitle . '</title>', $this->_code);
            } else {
                $this->_code = str_ireplace_once('</head>', '<title>' . $this->_pagetitle . "</title>\n</head>", $this->_code);
            }
        } else {
            $this->_code = str_replace('<title></title>', '', $this->_code);
        }
        return $this->_code;
    }


    /**
     * Processes and adds or replaces all meta tags for an article
     */
    protected function _processCodeMetaTags()
    {
        global $cfg, $encoding, $_cecRegistry;

        // Collect all available meta tag entries with non empty values
        $aMetaTags = array();
        $aAvailableTags = conGetAvailableMetaTagTypes();
        foreach ($aAvailableTags as $key => $value) {
            $sMetaValue = conGetMetaValue($this->_idartlang, $key);
            if (strlen($sMetaValue) > 0) {
                //$aMetaTags[$value['name']] = array(array('attribute' => $value['fieldname'], 'value' => $sMetaValue), ...);
                $aMetaTags[] = array($value['fieldname'] => $value['name'], 'content' => $sMetaValue);
            }
        }

        // Add CONTENIDO meta tag
        $aVersion = explode('.', $cfg['version']);
        $sContenidoVersion = $aVersion[0] . '.' . $aVersion[1];
        $aMetaTags[] = array('name' => 'generator', 'content' => 'CMS CONTENIDO ' . $sContenidoVersion);

        // Add content type meta tag
        // @todo html5 requires something like <meta charset="{encoding}">
        if (getEffectiveSetting('generator', 'xhtml', 'false') == 'true') {
            $aMetaTags[] = array('http-equiv' => 'Content-Type', 'content' => 'application/xhtml+xml; charset='.$encoding[$this->_lang]);
        } else {
            $aMetaTags[] = array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset='.$encoding[$this->_lang]);
        }

        // Process chain to update meta tags
        $_cecIterator = $_cecRegistry->getIterator('Contenido.Content.CreateMetatags');

        if ($_cecIterator->count() > 0) {
            $aTmpMetaTags = $aMetaTags;

            while ($chainEntry = $_cecIterator->next()) {
                $aTmpMetaTags = $chainEntry->execute($aTmpMetaTags);
            }

            // added 2008-06-25 Timo Trautmann -- system metatags were merged to user meta 
            // tags and user meta tags were not longer replaced by system meta tags
            if (is_array($aTmpMetaTags)) {
                //check for all system meta tags if there is already a user meta tag
                foreach ($aTmpMetaTags as $aAutValue) {
                    $bExists = false;

                    //get name of meta tag for search
                    $sSearch = '';
                    if (array_key_exists('name', $aAutValue)) {
                        $sSearch = $aAutValue['name'];
                    } else if (array_key_exists('http-equiv', $aAutValue)) {
                        $sSearch = $aAutValue['http-equiv'];
                    }

                    //check if meta tag is already in list of user meta tags
                    if (strlen($sSearch) > 0) {
                        foreach ($aMetaTags as $aValue) {
                            if (array_key_exists('name', $aValue)) {
                                if ($sSearch == $aValue['name']) {
                                    $bExists = true;
                                    break;
                                }
                            } else if (array_key_exists('http-equiv', $aAutValue)) {
                                if ($sSearch == $aValue['http-equiv']) {
                                    $bExists = true;
                                    break;
                                }
                            }
                        }
                    }

                    //add system meta tag if there is no user meta tag
                    if ($bExists == false && strlen($aAutValue['content']) > 0) {
                        array_push($aMetaTags, $aAutValue);
                    }
                }
            }
        }

        $sMetatags = '';

        foreach ($aMetaTags as $value) {
            // decode entities and htmlspecialchars, content will be converted later using htmlspecialchars()
            // by render() function
            if (isset($value['content'])) {
                $value['content'] = html_entity_decode($value['content'], ENT_QUOTES, strtoupper($encoding[$this->_lang]));
                $value['content'] = htmlspecialchars_decode($value['content'], ENT_QUOTES);
            }

            // build up metatag string
            $oMetaTagGen = new cHTML();
            $oMetaTagGen->_tag = 'meta';
            $oMetaTagGen->updateAttributes($value);

            // HTML does not allow ID for meta tags
            $oMetaTagGen->removeAttribute('id');

            // Check if metatag already exists
            $sPattern = '/(<meta(?:\s+)name(?:\s*)=(?:\s*)(?:\\"|\\\')(?:\s*)' . $value['name'] . '(?:\s*)(?:\\"|\\\')(?:[^>]+)>\n?)/i';
            if (preg_match($sPattern, $this->_code, $aMatch)) {
                $this->_code = str_replace($aMatch[1], $oMetaTagGen->render() . "\n", $this->_code);
            } else {
                $sMetatags .= $oMetaTagGen->render() . "\n";
            }
        }

        // Add meta tags
        $this->_code = str_ireplace_once('</head>', $sMetatags . '</head>', $this->_code);
    }

}
