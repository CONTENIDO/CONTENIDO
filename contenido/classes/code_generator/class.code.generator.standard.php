<?php

/**
 * CONTENIDO standard code generator
 *
 * @package Core
 * @subpackage ContentType
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
     * @var int
     */
    protected $_idtplcfg;

    /**
     * Generates the code for a specific article (article for a client in a
     * language).
     *
     * @see cCodeGeneratorAbstract::_generate()
     *
     * @param bool     $contype  [optional]
     *                           Flag to enable/disable replacement of CMS_TAGS[]
     * @param bool     $editable [optional]
     * @param int|NULL $version  [optional]
     *
     * @return string
     *         The generated code
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function _generate($contype = true, $editable = true, $version = NULL) {
        global $cfg, $code;

        $this->_cssData = '';
        $this->_jsData = '';
        $this->_tplName = '';

        cDebug::out("conGenerateCode($this->_idcat, $this->_idart, $this->_lang, $this->_client, $this->_layout);<br>");

        // set category article id
        $idcatart = conGetCategoryArticleId($this->_idcat, $this->_idart);

        // set configuration for article
        $this->_idtplcfg = $this->_getTemplateConfigurationId();
        if (NULL === $this->_idtplcfg) {
            $this->_processNoConfigurationError($idcatart);
            return '0601';
        }

        // list of configured container
        $containerConfigurations = conGetContainerConfiguration($this->_idtplcfg);

        // set idlay and idmod array
        $data = $this->_getTemplateData();
        $idlay = $data['idlay'];
        $idtpl = $data['idtpl'];
        $this->_tplName = cString::cleanURLCharacters($data['name']);

        // list of used modules
        $containerModules = conGetUsedModules($idtpl);

        // load layout code from file
        $layoutInFile = new cLayoutHandler($idlay, '', $cfg, $this->_lang);
        $this->_layoutCode = $layoutInFile->getLayoutCode();
        $this->_layoutCode = cString::normalizeLineEndings($this->_layoutCode, "\n");

        $moduleHandler = new cModuleHandler();

        // create code for all containers
        if ($idlay) {
            cInclude('includes', 'functions.tpl.php');
            $containerNumbers = tplGetContainerNumbersInLayout($idlay);

            // Initializing check arrays (CON-2706)
            $loadedCSS = array();
            $loadedJS = array();

            foreach ($containerNumbers as $containerNr) {
                // if there's no configured module in this container
                if (!isset($containerModules[$containerNr]) || !is_numeric($containerModules[$containerNr])) {
                    // reset current module state and process empty container
                    $this->_resetModule();
                    $this->_processCmsContainer($containerNr);
                    continue;
                }

                $containerModuleId = $containerModules[$containerNr];
                $oModule = new cApiModule($containerModuleId);
                $module = $oModule->toArray();
                if (false === $module) {
                    $module = array();
                }

                $this->_resetModule();

                $this->_modulePrefix[] = '$cCurrentModule = ' . $containerModuleId . ';';
                $this->_modulePrefix[] = '$cCurrentContainer = ' . $containerNr . ';';

                $moduleHandler = new cModuleHandler($containerModuleId);
                $input = '';
                $this->_moduleCode = '';

                // get contents of input and output from files and not from db
                if ($moduleHandler->modulePathExists() == true) {
                    // do not execute faulty modules
                    // caution: if no module is bound to a container then idmod of $oModule is false
                    // caution: and as result error field is also empty
                    if ($oModule->get('error') === 'none' || $oModule->get('idmod') === false) {
                        $this->_moduleCode = $moduleHandler->readOutput();
                    } else {
                        continue;
                    }

                    // load css and js content of the js/css files
                    if ($moduleHandler->getFilesContent('css', 'css') !== false && !in_array($oModule->get('idmod'), $loadedCSS)) {
                        $this->_cssData .= $moduleHandler->getFilesContent('css', 'css') . PHP_EOL;
                        $loadedCSS[] = $oModule->get('idmod');
                    }

                    if ($moduleHandler->getFilesContent('js', 'js') !== false && !in_array($oModule->get('idmod'), $loadedJS)) {
                        $this->_jsData .= $moduleHandler->getFilesContent('js', 'js') . PHP_EOL;
                        $loadedJS[] = $oModule->get('idmod');
                    }

                    $input = $moduleHandler->readInput();
                }

                // CON-1536 strip comments from module code
                // regex is not enough to correctly remove comments
                // use php_strip_whitespace instead of writing own parser
                // downside: php_strip_whitespace requires a file as parameter
                $tmpFile = dirname(cRegistry::getBackendPath()) . '/' . $cfg['path']['temp'] . uniqid('code_gen_') . '.php';
                if (cFileHandler::exists(dirname($tmpFile))
                    && cFileHandler::readable(dirname($tmpFile))
                    && cFileHandler::writeable(dirname($tmpFile))) {
                    if (false !== cFileHandler::write($tmpFile, $this->_moduleCode)) {
                        $this->_moduleCode = php_strip_whitespace($tmpFile);

                        // delete file
                        cFileHandler::remove($tmpFile);
                    }
                }

                // process CMS value tags
                $containerCmsValues = $this->_processCmsValueTags($containerNr, $containerConfigurations[$containerNr]);

                // add CMS value code to module prefix code
                if ($containerCmsValues) {
                    $this->_modulePrefix[] = $containerCmsValues;
                }

                // process frontend debug
                $this->_processFrontendDebug($containerNr, $module);

                // replace new containers
                $this->_processCmsContainer($containerNr);
            }
        }

        // find out what kind of CMS_... Vars are in use
        $a_content = $this->_getUsedCmsTypesData($editable, $version);

        // replace all CMS_TAGS[]
        if ($contype) {
            $this->_processCmsTags($a_content, true, $editable);
        }

        // add/replace title tag
        $this->_processCodeTitleTag();

        // add/replace meta tags
        $this->_processCodeMetaTags();

        // save the collected css/js data and save it under the template name
        // ([templatename].css , [templatename].js in cache dir
        $cssFile = '';
        if (cString::getStringLength($this->_cssData) > 0) {
            if (($myFileCss = $moduleHandler->saveContentToFile($this->_tplName, 'css', $this->_cssData)) !== false) {
                $oHTML = new cHTML(array(
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => $myFileCss
                ));
                $oHTML->setTag('link');
                $cssFile = $oHTML->render();
            }
        }

        $jsFile = '';
        if (cString::getStringLength($this->_jsData) > 0) {
            if (($myFileJs = $moduleHandler->saveContentToFile($this->_tplName, 'js', $this->_jsData)) !== false) {
                $jsFile = '<script src="' . $myFileJs . '" type="text/javascript"></script>';
            }
        }

        // add module CSS at {CSS} position, after title
        // or after opening head tag
        if (cString::findFirstPos($this->_layoutCode, '{CSS}') !== false) {
            $this->_layoutCode = cString::iReplaceOnce('{CSS}', $cssFile, $this->_layoutCode);
        } else if (!empty($cssFile)) {
            if (cString::findFirstPos($this->_layoutCode, '</title>') !== false) {
                $matches = array();
                preg_match_all("#(<head>.*?</title>)(.*?</head>)#si", $this->_layoutCode, $matches);
                $this->_layoutCode = cString::iReplaceOnce($matches[1][0], $matches[1][0] . $cssFile . $matches[1][1], $this->_layoutCode);
            } else {
                $this->_layoutCode = cString::iReplaceOnce('<head>', '<head>' . $cssFile, $this->_layoutCode);
            }
        }

        if (cString::findFirstPos($this->_layoutCode, '{REV}') !== false) {
            $this->_layoutCode = cString::iReplaceOnce('{REV}', ((int) getEffectiveSetting("ressource", "revision", 0)), $this->_layoutCode);
        }

        // add module JS at {JS} position
        // or before closing body tag if there is no {JS}
        if (cString::findFirstPos($this->_layoutCode, '{JS}') !== false) {
            $this->_layoutCode = cString::iReplaceOnce('{JS}', $jsFile, $this->_layoutCode);
        } else if (!empty($jsFile)) {
            $this->_layoutCode = cString::iReplaceOnce('</body>', $jsFile . '</body>', $this->_layoutCode);
        }

        if (cString::findFirstPos($this->_layoutCode, '{META}') !== false) {
            $this->_layoutCode = cString::iReplaceOnce('{META}', $this->_processCodeMetaTags(), $this->_layoutCode);
        } else {
            $this->_layoutCode = cString::iReplaceOnce('</head>', $this->_processCodeMetaTags() . '</head>', $this->_layoutCode);
        }

        if ($this->_feDebugOptions['general_information']) {
            $debugPrefix = '';

            $debugPrefix .= "<?php\nif (\$frontend_debug['general_information']) {\n";
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

        // save the generated code even if there are faulty modules
        // if one does not do so, a non existing cache file
        // will be tried to be loaded in frontend
        $this->_saveGeneratedCode($idcatart);

        return $this->_layoutCode;
    }

    /**
     * Will be invoked, if code generation wasn't able to find a
     * configured article or category.
     *
     * Creates a error message and writes this into the code cache.
     *
     * @param int $idcatart
     *         category article id
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    protected function _processNoConfigurationError($idcatart) {
        cDebug::out('Neither CAT or ART are configured!<br><br>');

        $code = '<html><body>No code was created for this article in this category.</body><html>';
        $this->_saveGeneratedCode($idcatart, $code, false);
    }

    /**
     * Processes and adds or replaces title tag for an article.
     * Also calls the CEC 'Contenido.Content.CreateTitletag' for user defined title creation if none is given.
     *
     * @see cCodeGeneratorAbstract::_processCodeTitleTag()
     * @return string
     */
    protected function _processCodeTitleTag()
    {
        if ($this->_pageTitle == '') {
            cApiCecHook::setDefaultReturnValue($this->_pageTitle);
            $this->_pageTitle = cApiCecHook::executeAndReturn('Contenido.Content.CreateTitletag');
        }

        // define regular expressions
        $reHead  = '/<head>.*?<\/head>/is';
        $reTitle = '/<title>.*?<\/title>/is';

        // find head tags in layout code (case insensitive, search across linebreaks)
        $matches = [];
        $succ    = preg_match_all($reHead, $this->_layoutCode, $matches);

        // check if head tag has been found
        if (false !== $succ && count($matches) && isset($matches[0], $matches[0][0])) {
            // use first head tag found
            // by definition there must be no more than one head tag but user supplied markup might be invalid
            $headTag = $matches[0][0];

            // add or replace title
            if ($this->_pageTitle != '') {
                $replaceTag = '{__TITLE__' . md5(rand() . time()) . '}';
                $headCode   = preg_replace($reTitle, $replaceTag, $headTag, 1);
                $pageTitle  = conHtmlentities($this->_pageTitle);
                if (false !== cString::findFirstPos($headCode, $replaceTag)) {
                    $headCode = str_ireplace($replaceTag, "<title>$pageTitle</title>", $headCode);
                } else {
                    $headCode = cString::iReplaceOnce('</head>', "<title>$pageTitle</title>\n</head>", $headCode);
                }
            } else {
                // remove empty title tags from head tag
                $headCode = str_replace('<title></title>', '', $headTag);
            }

            // overwrite first head tag in original layout code
            $this->_layoutCode = preg_replace($reHead, $headCode, $this->_layoutCode, 1);
        }

        return $this->_layoutCode;
    }

    /**
     * Processes and adds or replaces all meta tags for an article.
     * Also calls the CEC 'Contenido.Content.CreateMetatags' for user defined
     * meta tags creation.
     *
     * @global array $encoding
     * @return string
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
                $value['content'] = str_replace('"', '\"', (conHtmlEntityDecode(stripslashes($value['content']))));
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
                // the meta tag is already specified in the layout
                // replace it only if its attributes are not empty
                $replace = true;
                foreach ($value as $test) {
                    if ($test == '') {
                        $replace = false;
                        break;
                    }
                }
                if ($replace) {
                    $this->_layoutCode = str_replace($aMatch[1], $oMetaTagGen->render() . "\n", $this->_layoutCode);
                }
            } else {
                $sMetatags .= $oMetaTagGen->render() . "\n";
            }
        }

        return $sMetatags;
    }

    /**
     * Saves the generated code if layout flag is false and save flag is true.
     *
     * @param int    $idcatart
     *                               Category article id
     * @param string $code           [optional]
     *                               parameter for setting code manually instead of using the generated layout code
     * @param bool   $flagCreateCode [optional]
     *                               whether the create code flag in cat_art should be set or not (optional)
     * @throws cDbException
     * @throws cInvalidArgumentException
     * @global array $cfgClient
     */
    protected function _saveGeneratedCode($idcatart, $code = '', $flagCreateCode = true) {
        global $cfgClient;

        // Write code in the cache of the client. If the folder does not exist
        // create one.
        // do not write code cache into root directory of client
        if (cRegistry::getFrontendPath() === $cfgClient[$this->_client]['code']['path']) {
            return;
        }

        // parent directory must be named cache
        $directoryName = basename(dirname($cfgClient[$this->_client]['code']['path']));
        if ('cache' !== $directoryName) {
            // directory name is not cache -> abort
            return;
        }

        // CON-2113
        // Do not overwrite an existing .htaccess file to prevent misconfiguring permissions
        if ($this->_layout == false && $this->_save == true && isset($cfgClient[$this->_client]['code']['path'])) {
            if (false === is_dir($cfgClient[$this->_client]['code']['path'])) {
                mkdir($cfgClient[$this->_client]['code']['path']);
                @chmod($cfgClient[$this->_client]['code']['path'], cDirHandler::getDefaultPermissions());
            }

            if (true !== cFileHandler::exists($cfgClient[$this->_client]['code']['path'] . '.htaccess')) {
                cFileHandler::write($cfgClient[$this->_client]['code']['path'] . '.htaccess', "Order Deny,Allow\nDeny from all\n");
            }

            if (true === is_dir($cfgClient[$this->_client]['code']['path'])) {
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
    }

    /**
     * Collects and return basic meta tags/elements.
     *
     * @global array $encoding
     * @return array
     *         List of assozative meta tag values
     */
    protected function _getBasicMetaTags() {

        // collect all available meta tag entries with non empty values
        $metaTags = array();
        foreach (conGetAvailableMetaTagTypes() as $key => $value) {
            $metaValue = conGetMetaValue($this->_idartlang, $key);
            if (0 < cString::getStringLength($metaValue)) {
                $metaTags[] = array(
                    $value['fieldname'] => $value['metatype'],
                    'content' => $metaValue
                );
            }
        }

        // add generator meta tag
        // if the version is appended should be configurable
        // due to security reasons
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
        // the following value will not be changed
        // $index = (bool) $this->getArtLangObject()->get('searchable');
        // $metaTags = $this->_updateMetaRobots($metaTags, $index, NULL);

        return $metaTags;
    }

    /**
     * This method allows to set new values for the robots meta element.
     * If NULL is given for $index or $follow, existing settings are *not*
     * overwritten. If article should be indexed and followed, 'all' will be
     * set.
     *
     * @param array $metaTags
     *         array of meta elements to amend
     * @param bool|NULL $index
     *         if article should be indexed
     * @param bool|NULL $follow
     *         if links in article should be followed
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
     * Both, the reduced array of meta elements and the meta element to be
     * extracted are returned as an array. If the meta element to be extracted
     * could not be found, NULL will be returned in its place.
     *
     * @param array $metaTags
     * @param string $type
     *         either 'name' or 'http-equiv'
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
