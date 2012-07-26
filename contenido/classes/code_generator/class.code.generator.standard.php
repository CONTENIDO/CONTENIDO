<?php
/**
 * CONTENIDO standard code generator
 *
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 * @id SVN Id $Id$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * CONTENIDO standard code generator.
 * @package Core
 * @subpackage Content Type
 */
class cCodeGeneratorStandard extends cCodeGeneratorAbstract {

    /**
     * {@inheritdoc}
     */
    public function _generate($contype = true) {
        global $cfg, $code;

        $this->_cssData = '';
        $this->_jsData = '';
        $this->_tplName = '';

        $this->_debug("conGenerateCode($this->_idcat, $this->_idart, $this->_lang, $this->_client, $this->_layout);<br>");

        // Set category article id
        $idcatart = conGetCategoryArticleId($this->_idcat, $this->_idart);

        // Set configuration for article
        $this->_idtplcfg = $this->_getTemplateConfigurationId();
        if (null === $this->_idtplcfg) {
            $this->_processNoConfigurationError();
            return '0601';
        }

        $a_c = conGetContainerConfiguration($this->_idtplcfg);

        // Set idlay and idmod array
        $data = $this->_getTemplateData();
        $idlay = $data['idlay'];
        $idtpl = $data['idtpl'];
        $this->_tplName = $data['name'];

        // List of used modules
        $a_d = conGetUsedModules($idtpl);

        // Load layout code from file
        $layoutInFile = new LayoutInFile($idlay, '', $cfg, $this->_lang);
        $this->_layoutCode = $layoutInFile->getLayoutCode();
        $this->_layoutCode = cApiStrNormalizeLineEndings($this->_layoutCode, "\n");

        $moduleHandler = new cModuleHandler();

        // Create code for all containers
        if ($idlay) {
            cInclude('includes', 'functions.tpl.php');
            tplPreparseLayout($idlay);
            $tmp_returnstring = tplBrowseLayoutForContainers($idlay);
            $a_container = explode('&', $tmp_returnstring);

            foreach ($a_container as $key => $value) {
                if (!isset($a_d[$value]) || !is_numeric($a_d[$value])) {
                    continue;
                }

                $oModule = new cApiModule($a_d[$value]);
                $module = $oModule->toArray();
                if (false === $module) {
#                    continue;
                    $module = array();
                }

                $this->_resetModule();

                $this->_modulePrefix[] = '$cCurrentModule = ' . $a_d[$value] . ';';
                $this->_modulePrefix[] = '$cCurrentContainer = ' . $value . ';';

                $moduleHandler = new cModuleHandler($a_d[$value]);
                $input = '';

                // Get the contents of input and output from files and not from db-table
                if ($moduleHandler->modulePathExists() == true) {
                    $this->_moduleCode = $moduleHandler->readOutput();
                    // Load css and js content of the js/css files
                    if ($moduleHandler->getFilesContent('css', 'css') !== false) {
                        $this->_cssData .= $moduleHandler->getFilesContent('css', 'css');
                    }

                    if ($moduleHandler->getFilesContent('js', 'js') !== false) {
                        $this->_jsData .= $moduleHandler->getFilesContent('js', 'js');
                    }

                    $input = $moduleHandler->readInput();
                }

                $this->_moduleCode = $this->_moduleCode . "\n";

                // Process CMS value tags
                $containerCmsValues = $this->_processCmsValueTags($value, $a_c[$value]);

                // Add CMS value code to module prefix code
                if ($containerCmsValues) {
                    $this->_modulePrefix[] = $containerCmsValues;
                }

                // Process frontend debug
                $this->_processFrontendDebug($value, $module);

                // Replace new containers
                $this->_processCmsContainer($value);
            }
        }

        // Find out what kind of CMS_... Vars are in use
        $a_content = $this->_getUsedCmsTypesData();

        // Replace all CMS_TAGS[]
        if ($contype) {
            $this->_processCmsTags($a_content, true);
        }

        // Add/replace title tag
        $this->_processCodeTitleTag();

        // Add/replace meta tags
        $this->_processCodeMetaTags();

        // Save the collected css/js data and save it under the template name ([templatename].css , [templatename].js in cache dir
        $cssFile = '';
        if (strlen($this->_cssData) > 0) {
            if (($myFileCss = $moduleHandler->saveContentToFile($this->_tplName, 'css', $this->_cssData))) {
                $cssFile = '<link rel="stylesheet" type="text/css" href="' . $myFileCss . '"/>';
            }
        }

        $jsFile = '';
        if (strlen($this->_jsData) > 0) {
            if (($myFileJs = $moduleHandler->saveContentToFile($this->_tplName, 'js', $this->_jsData))) {
                $jsFile = '<script src="' . $myFileJs . '" type="text/javascript"></script>';
            }
        }

        // Add css files before closing head tag and js files before closing body tag
        $this->_layoutCode = str_ireplace_once('</head>', $cssFile . '</head>', $this->_layoutCode);
        $this->_layoutCode = str_ireplace_once('</body>', $jsFile . '</body>', $this->_layoutCode);

        // Save the generated code
        $this->_saveGeneratedCode($code, $idcatart);

        return $this->_layoutCode;
    }

    /**
     * Will be invoked, if code generation wasn't able to find a configured article
     * or category.
     *
     * Creates a error message as and writes this into the code cache table.
     */
    protected function _processNoConfigurationError() {
        // fixme
        $this->_debug('Neither CAT or ART are configured!<br><br>');

        $code = '<html><body>No code was created for this art in this category.</body><html>';

        $oCodeColl = new cApiCodeCollection();
        $oCode = $oCodeColl->fetchByCatArtAndLang($this->_idcatart, $this->_lang);
        if (!is_object($oCode)) {
            $oCode = $oCodeColl->create($idcatart, $this->_lang, $this->_client, $code);
        } else {
            $oCode->updateCode($code);
        }
    }

    /**
     * Processes and adds or replaces title tag for an article.
     *
     * Calls also the CEC 'Contenido.Content.CreateTitletag' for user defined title
     * creation.
     */
    protected function _processCodeTitleTag() {
        if ($this->_pageTitle == '') {
            cApiCecHook::setDefaultReturnValue($this->_pageTitle);
            $this->_pageTitle = cApiCecHook::executeAndReturn('Contenido.Content.CreateTitletag');
        }

        // Add or replace title
        if ($this->_pageTitle != '') {
            $this->_layoutCode = preg_replace('/<title>.*?<\/title>/is', '{TITLE}', $this->_layoutCode, 1);
            if (strstr($this->_layoutCode, '{TITLE}')) {
                $this->_layoutCode = str_ireplace('{TITLE}', '<title>' . $this->_pageTitle . '</title>', $this->_layoutCode);
            } else {
                $this->_layoutCode = str_ireplace_once('</head>', '<title>' . $this->_pageTitle . "</title>\n</head>", $this->_layoutCode);
            }
        } else {
            $this->_layoutCode = str_replace('<title></title>', '', $this->_layoutCode);
        }
        return $this->_layoutCode;
    }

    /**
     * Processes and adds or replaces all meta tags for an article.
     *
     * Calls also the CEC 'Contenido.Content.CreateMetatags' for user defined meta
     * tags creation.
     */
    protected function _processCodeMetaTags() {
        global $encoding, $_cecRegistry;

        // Get all basic meta tags
        $aMetaTags = $this->_getBasicMetaTags();

        // Process chain to update meta tags
        $_cecIterator = $_cecRegistry->getIterator('Contenido.Content.CreateMetatags');

        if ($_cecIterator->count() > 0) {
            $aTmpMetaTags = $aMetaTags;

            while ($chainEntry = $_cecIterator->next()) {
                $aTmpMetaTags = $chainEntry->execute($aTmpMetaTags);
            }

            // Added 2008-06-25 Timo Trautmann -- system metatags were merged to user meta
            // tags and user meta tags were not longer replaced by system meta tags
            if (is_array($aTmpMetaTags)) {
                // Check for all system meta tags if there is already a user meta tag
                foreach ($aTmpMetaTags as $aAutValue) {
                    $bExists = false;

                    // Get name of meta tag for search
                    $sSearch = '';
                    if (array_key_exists('name', $aAutValue)) {
                        $sSearch = $aAutValue['name'];
                    } else if (array_key_exists('http-equiv', $aAutValue)) {
                        $sSearch = $aAutValue['http-equiv'];
                    }

                    // Check if meta tag is already in list of user meta tags
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

                    // Add system meta tag if there is no user meta tag
                    if ($bExists == false && strlen($aAutValue['content']) > 0) {
                        array_push($aMetaTags, $aAutValue);
                    }
                }
            }
        }

        $sMetatags = '';

        foreach ($aMetaTags as $value) {
            // Decode entities and htmlspecialchars, content will be converted later
            // using htmlspecialchars() by render() function
            if (isset($value['content'])) {
                $value['content'] = html_entity_decode($value['content'], ENT_QUOTES, strtoupper($encoding[$this->_lang]));
                $value['content'] = htmlspecialchars_decode($value['content'], ENT_QUOTES);
            }

            // Build up metatag string
            $oMetaTagGen = new cHTML();
            $oMetaTagGen->setTag('meta');
            $oMetaTagGen->updateAttributes($value);

            // HTML does not allow ID for meta tags
            $oMetaTagGen->removeAttribute('id');

            // Check if metatag already exists
            $sPattern = '/(<meta(?:\s+)name(?:\s*)=(?:\s*)(?:\\"|\\\')(?:\s*)' . $value['name'] . '(?:\s*)(?:\\"|\\\')(?:[^>]+)>\n?)/i';
            if (preg_match($sPattern, $this->_layoutCode, $aMatch)) {
                $this->_layoutCode = str_replace($aMatch[1], $oMetaTagGen->render() . "\n", $this->_layoutCode);
            } else {
                $sMetatags .= $oMetaTagGen->render() . "\n";
            }
        }

        // Add meta tags
        $this->_layoutCode = str_ireplace_once('</head>', $sMetatags . '</head>', $this->_layoutCode);
    }

    /**
     * Saves the generated code (if layout flag is false and save flag is true)
     *
     * @global  array  $cfgClient
     * @param  string  $code  The generated code
     * @param  int   $idcatart  Category article id
     */
    protected function _saveGeneratedCode($code, $idcatart) {
        global $cfgClient;

        // Write code in the cache of the client. If the folder does not exist create one.
        if ($this->_layout == false && $this->_save == true) {
            if (!is_dir($cfgClient[$this->_client]['code_path'])) {
                mkdir($cfgClient[$this->_client]['code_path']);
                chmod($cfgClient[$this->_client]['code_path'], 0777);
                cFileHandler::write($cfgClient[$this->_client]['code_path'] . '.htaccess', "Order Deny,Allow\nDeny from all\n");
            }
            $code = "<?php\ndefined('CON_FRAMEWORK') or die('Illegal call');\n\n?>\n" . $this->_layoutCode;
            cFileHandler::write($cfgClient[$this->_client]['code_path'] . $this->_client . '.' . $this->_lang . '.' . $idcatart . '.php', $code, false);

            // Update create code flag
            $oCatArtColl = new cApiCategoryArticleCollection();
            $oCatArtColl->setCreateCodeFlag($idcatart, 0);
        }
    }

    /**
     * Collects basic meta tags an returns them.
     * @global  array  $encoding
     * @return array  List of assozative meta tag values
     */
    protected function _getBasicMetaTags() {
        global $cfg, $encoding;

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

        // Add content type or charseet meta tag
        if (getEffectiveSetting('generator', 'html5', 'false') == 'true') {
            $aMetaTags[] = array('charset' => $encoding[$this->_lang]);
        } elseif (getEffectiveSetting('generator', 'xhtml', 'false') == 'true') {
            $aMetaTags[] = array('http-equiv' => 'Content-Type', 'content' => 'application/xhtml+xml; charset=' . $encoding[$this->_lang]);
        } else {
            $aMetaTags[] = array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=' . $encoding[$this->_lang]);
        }

        return $aMetaTags;
    }
}
