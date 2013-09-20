<?php

/**
 * CONTENIDO standard code generator
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
 * CONTENIDO standard code generator.
 *
 * @package Core
 * @subpackage ContentType
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

        cDebug::out("conGenerateCode($this->_idcat, $this->_idart, $this->_lang, $this->_client, $this->_layout);<br>");

        // Set category article id
        $idcatart = conGetCategoryArticleId($this->_idcat, $this->_idart);

        // Set configuration for article
        $this->_idtplcfg = $this->_getTemplateConfigurationId();
        if (NULL === $this->_idtplcfg) {
            $this->_processNoConfigurationError($idcatart);
            return '0601';
        }

        $a_c = conGetContainerConfiguration($this->_idtplcfg);

        // Set idlay and idmod array
        $data = $this->_getTemplateData();
        $idlay = $data['idlay'];
        $idtpl = $data['idtpl'];
        $this->_tplName = cApiStrCleanURLCharacters($data['name']);

        // List of used modules
        $a_d = conGetUsedModules($idtpl);

        // Load layout code from file
        $layoutInFile = new cLayoutHandler($idlay, '', $cfg, $this->_lang);
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
                    // No configured module in this container
                    // reset current module state and process empty container
                    $this->_resetModule();
                    $this->_processCmsContainer($value);
                    continue;
                }

                $oModule = new cApiModule($a_d[$value]);
                $module = $oModule->toArray();
                if (false === $module) {
                    $module = array();
                }

                $this->_resetModule();

                $this->_modulePrefix[] = '$cCurrentModule = ' . $a_d[$value] . ';';
                $this->_modulePrefix[] = '$cCurrentContainer = ' . $value . ';';

                $moduleHandler = new cModuleHandler($a_d[$value]);
                $input = '';

                // Get the contents of input and output from files and not from
                // db-table
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

        // Save the collected css/js data and save it under the template name
        // ([templatename].css , [templatename].js in cache dir
        $cssFile = '';
        if (strlen($this->_cssData) > 0) {
            if (($myFileCss = $moduleHandler->saveContentToFile($this->_tplName, 'css', $this->_cssData)) !== false) {
                $cssFile = '<link rel="stylesheet" type="text/css" href="' . $myFileCss . '" />';
            }
        }

        $jsFile = '';
        if (strlen($this->_jsData) > 0) {
            if (($myFileJs = $moduleHandler->saveContentToFile($this->_tplName, 'js', $this->_jsData)) !== false) {
                $jsFile = '<script src="' . $myFileJs . '" type="text/javascript"></script>';
            }
        }

        // // show toolbar with revisions of article This toolbar is only shown
        // in
        // // backend in edit mode if revision control is enabled
        // $revionsToolBar = $this->_revisionControl->getToolbar();
        // if (NULL !== $revionsToolBar) {
        // if (false !== strpos($this->_layoutCode, '<body>')) {
        // $this->_layoutCode = str_ireplace_once('<body>', '<body>' .
        // $revionsToolBar, $this->_layoutCode);
        // } else {
        // // TODO wo füg ich die dann ein?
        // // $this->_layoutCode .= $revionsToolBar;
        // }
        // }

        // add module CSS at {CSS} position, after title or after opening head
        // tag
        if (strpos($this->_layoutCode, '{CSS}') !== false) {
            $this->_layoutCode = cString::iReplaceOnce('{CSS}', $cssFile, $this->_layoutCode);
        } else if (!empty($cssFile)) {
            if (strpos($this->_layoutCode, '</title>') !== false) {
                $this->_layoutCode = cString::iReplaceOnce('</title>', '</title>' . $cssFile, $this->_layoutCode);
            } else {
                $this->_layoutCode = cString::iReplaceOnce('<head>', '<head>' . $cssFile, $this->_layoutCode);
            }
        }

        // add module JS at {JS} position or before closing body tag if there is
        // no {JS}
        if (strpos($this->_layoutCode, '{JS}') !== false) {
            $this->_layoutCode = cString::iReplaceOnce('{JS}', $jsFile, $this->_layoutCode);
        } else if (!empty($jsFile)) {
            $this->_layoutCode = cString::iReplaceOnce('</body>', $jsFile . '</body>', $this->_layoutCode);
        }

        if($this->_feDebugOptions['general_information']) {
            $debugPrefix = '';

            $debugPrefix .= "<?php\nif(\$frontend_debug['general_information']) {\n";
            $debugPrefix .= "\techo(\"<!-- \\n\\n\");\n";

            $layout = new cApiLayout($idlay);
            $layouName = $layout->get('name');
            $debugPrefix .= "\techo(\"Layout: " . $layouName . " (" . $idlay . ")\\n\");\n";

            $debugPrefix .= "\techo(\"Template: " . $this->_tplName . " (" . $idtpl . ")\\n\");\n";

            $article = new cApiArticleLanguage($this->_idartlang);
            $catart = new cApiCategoryArticle();
            $cat = new cApiCategoryLanguage();
            $cat->loadByCategoryIdAndLanguageId($this->_idcat, $article->get('idlang'));
            $catart->loadByMany(array(
            	'idcat' => $cat->get('idcat'),
                'idart' => $article->get('idart')
            ));
            $lang = new cApiLanguage($article->get('idlang'));
            $debugPrefix .= "\techo(\"Language: " . $lang->get('idlang') . " (" . $lang->get('name') . ")\\n\");\n";

            $debugPrefix .= "\techo(\"Category: " . $cat->get('idcat') . " (" . $cat->get('name') . ")\\n\");\n";

            $articleName = $article->get('title');
            $debugPrefix .= "\techo(\"Article: " . $articleName . " (catart = " . $catart->get('idcatart') . ", artlang = " . $this->_idartlang . ", art = " . $article->get('idart') . ")\\n\");\n";


            $debugPrefix .= "\techo(\"\\n--> \\n\");\n";
            $debugPrefix .= "}\n?>";

            $this->_layoutCode = $debugPrefix . $this->_layoutCode;
        }

        // Save the generated code
        $this->_saveGeneratedCode($idcatart);

        return $this->_layoutCode;
    }

    /**
     * Will be invoked, if code generation wasn't able to find a configured
     * article
     * or category.
     *
     * Creates a error message and writes this into the code cache.
     *
     * @param int $idcatart Category article id
     */
    protected function _processNoConfigurationError($idcatart) {
        cDebug::out('Neither CAT or ART are configured!<br><br>');

        $code = '<html><body>No code was created for this article in this category.</body><html>';
        $this->_saveGeneratedCode($idcatart, $code, false);
    }

    /**
     * Processes and adds or replaces title tag for an article.
     * Calls also the CEC 'Contenido.Content.CreateTitletag' for user defined
     * title creation.
     *
     * @see cCodeGeneratorAbstract::_processCodeTitleTag()
     * @return string
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
                $this->_layoutCode = cString::iReplaceOnce('</head>', '<title>' . $this->_pageTitle . "</title>\n</head>", $this->_layoutCode);
            }
        } else {
            $this->_layoutCode = str_replace('<title></title>', '', $this->_layoutCode);
        }

        return $this->_layoutCode;
    }

    /**
     * Processes and adds or replaces all meta tags for an article.
     *
     * Calls also the CEC 'Contenido.Content.CreateMetatags' for user defined
     * meta tags creation.
     *
     * @global array $encoding
     */
    protected function _processCodeMetaTags() {
        global $encoding;

        // get basic meta tags (from article & system)
        $metaTags = $this->_getBasicMetaTags();

        // process chain Contenido.Content.CreateMetatags to update meta tags
        $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Content.CreateMetatags');
        if ($_cecIterator->count() > 0) {
            while (false !== $chainEntry = $_cecIterator->next()) {
                $metaTags = $chainEntry->execute($metaTags);
            }
        }

        $sMetatags = '';
        foreach ($metaTags as $value) {

            // get meta tag keys
            $valueKeys = array_keys($value);
            $nameKey = 'name';
            foreach ($valueKeys as $key) {

                if ($key != 'content')
                    $nameKey = $key;
            }

            // decode entities and htmlspecialchars, content will be converted
            // later using conHtmlSpecialChars() by render() function
            if (isset($value['content'])) {
                $value['content'] = conHtmlEntityDecode($value['content'], ENT_QUOTES, strtoupper($encoding[$this->_lang]));
                $value['content'] = htmlspecialchars_decode($value['content'], ENT_QUOTES);
            }

            // build up metatag string
            $oMetaTagGen = new cHTML();
            $oMetaTagGen->setTag('meta');
            $oMetaTagGen->updateAttributes($value);

            // HTML does not allow ID for meta tags
            $oMetaTagGen->removeAttribute('id');

            // check if metatag already exists
            $sPattern = '/(<meta(?:\s+)' . $nameKey . '(?:\s*)=(?:\s*)(?:\\"|\\\')(?:\s*)' . $value[$nameKey] . '(?:\s*)(?:\\"|\\\')(?:[^>]+)>\n?)/i';
            if (preg_match($sPattern, $this->_layoutCode, $aMatch)) {
                $this->_layoutCode = str_replace($aMatch[1], $oMetaTagGen->render() . "\n", $this->_layoutCode);
            } else {
                $sMetatags .= $oMetaTagGen->render() . "\n";
            }
        }

        // add meta tags
        $this->_layoutCode = cString::iReplaceOnce('</head>', $sMetatags . '</head>', $this->_layoutCode);
    }

    /**
     * Saves the generated code (if layout flag is false and save flag is true)
     *
     * @global array $cfgClient
     * @param int $idcatart Category article id
     * @param string $code parameter for setting code manually instead of using
     *        the generated layout code
     * @param bool $flagCreateCode whether the create code flag in cat_art
     *        should be set or not (optional)
     */
    protected function _saveGeneratedCode($idcatart, $code = '', $flagCreateCode = true) {
        global $cfgClient;

        // Write code in the cache of the client. If the folder does not exist
        // create one.
        if ($this->_layout == false && $this->_save == true) {
            if (!is_dir($cfgClient[$this->_client]['code']['path'])) {
                mkdir($cfgClient[$this->_client]['code']['path']);
                @chmod($cfgClient[$this->_client]['code']['path'], 0777);
                cFileHandler::write($cfgClient[$this->_client]['code']['path'] . '.htaccess', "Order Deny,Allow\nDeny from all\n");
            }

            $fileCode = ($code == '')? $this->_layoutCode : $code;

            $code = "<?php\ndefined('CON_FRAMEWORK') or die('Illegal call');\n\n?>\n" . $fileCode;
            cFileHandler::write($cfgClient[$this->_client]['code']['path'] . $this->_client . '.' . $this->_lang . '.' . $idcatart . '.php', $code, false);

            // Update create code flag
            if ($flagCreateCode == true) {
                $oCatArtColl = new cApiCategoryArticleCollection();
                $oCatArtColl->setCreateCodeFlag($idcatart, 0);
            }
        }
    }

    /**
     * Collects and return basic meta tags/elements.
     *
     * @global array $encoding
     * @return array List of assozative meta tag values
     */
    protected function _getBasicMetaTags() {

        // collect all available meta tag entries with non empty values
        $metaTags = array();
        foreach (conGetAvailableMetaTagTypes() as $key => $value) {
            $metaValue = conGetMetaValue($this->_idartlang, $key);
            if (0 < strlen($metaValue)) {
                $metaTags[] = array(
                    $value['fieldname'] => $value['metatype'],
                    'content' => $metaValue
                );
            }
        }

        // add generator meta tag
        // if the version is appended should be configurable due to security
        // reasons
        $generator = 'CMS CONTENIDO';
        $addVersion = true;
        if ($addVersion) {
            $cfg = cRegistry::getConfig();
            $aVersion = explode('.', CON_VERSION);
            $generator .= ' ' . $aVersion[0] . '.' . $aVersion[1];
        }
        $metaTags[] = array(
            'name' => 'generator',
            'content' => $generator
        );

        // add charset or content type meta tag
        global $encoding;
        if (getEffectiveSetting('generator', 'html5', 'false') == 'true') {
            $metaTags[] = array(
                'charset' => $encoding[$this->_lang]
            );
        } elseif (getEffectiveSetting('generator', 'xhtml', 'false') == 'true') {
            $metaTags[] = array(
                'http-equiv' => 'Content-Type',
                'content' => 'application/xhtml+xml; charset=' . $encoding[$this->_lang]
            );
        } else {
            $metaTags[] = array(
                'http-equiv' => 'Content-Type',
                'content' => 'text/html; charset=' . $encoding[$this->_lang]
            );
        }

        // update (!) index setting of robots meta tag
        // the follow value will not be changed
        // $index = (bool) $this->getArtLangObject()->get('searchable');
        // $metaTags = $this->_updateMetaRobots($metaTags, $index, NULL);

        return $metaTags;
    }

    /**
     * This method allows to set new values for the robots meta element.
     *
     * If NULL is given for $index or $follow, existing settings are *not*
     * overwritten. If article should be indexed and followed, 'all' will be
     * set.
     *
     * @param array $metaTags array of meta elements to amend
     * @param bool|NULL $index if article should be indexed
     * @param bool|NULL $follow if links in article should be followed
     * @return array
     */
    protected function _updateMetaRobots(array $metaTags, $index, $follow) {

        // extract robots setting from current meta elements
        list($metaTags, $metaRobots) = $this->_extractMetaElement($metaTags, 'name', 'robots');

        if (is_null($metaRobots)) {
            // build new meta element if none could be found
            $metaRobots = array(
                'name' => 'robots',
                'content' => ''
            );
        } else {
            $content = array_map('trim', explode(',', $metaRobots['content']));
            // determine index from extracted element if given value is NULL
            if (is_null($index)) {
                $index = (bool) (in_array('all', $content) || in_array('index', $content));
                if (in_array('index', $content) || in_array('all', $content)) {
                    $index = true;
                } else if (in_array('noindex', $content)) {
                    $index = true;
                } else {
                    $index = NULL;
                }
            }
            // determine follow from extracted element if given value is NULL
            if (is_null($follow)) {
                if (in_array('follow', $content) || in_array('all', $content)) {
                    $follow = true;
                } else if (in_array('nofollow', $content)) {
                    $follow = true;
                } else {
                    $follow = NULL;
                }
            }
        }

        // build and set new content for robots element
        $content = array();
        if (true === $index && true === $follow) {
            $content[] = 'all';
        } else {
            if (!is_null($index)) {
                $content[] = $index? 'index' : 'noindex';
            }
            if (!is_null($follow)) {
                $content[] = $follow? 'follow' : 'nofollow';
            }
        }
        $metaRobots['content'] = implode(',', $content);

        // add robots meta element
        $metaTags[] = $metaRobots;

        // what do you expect?
        return $metaTags;
    }

    /**
     * Extracts a meta element of type $type (either 'name' or 'http-equiv') and
     * name or HTTP header equivalent $nameOrEquiv from the given array of meta
     * elements.
     *
     * Both, the reduced array of meta elements and the meta element to be
     * extracted are returned as an array. If the meta element to be extracted
     * could not be found, NULL will be returned in its place.
     *
     * @param array $metaTags
     * @param string $type either 'name' or 'http-equiv'
     * @param string $nameOrEquiv
     * @return array
     */
    protected function _extractMetaElement(array $metaTags, $type, $nameOrEquiv) {

        // prepare result structure
        $result = array(
            array(),
            NULL
        );

        // loop all given meta elements
        foreach ($metaTags as $metaTag) {
            if (!is_array($metaTag)) {
                // skip $metaTag if it's no array
                continue;
            } else if (!array_key_exists($type, $metaTag)) {
                // add element to reduced array if it's of different type
                array_push($result[0], $metaTag);
            } else if ($metaTag[$type] !== $nameOrEquiv) {
                // add element to reduced array if it has different name
                array_push($result[0], $metaTag);
            } else {
                // set element as extracted element
                $result[1] = $metaTag;
            }
        }

        // what do you expect?
        return $result;
    }
}
