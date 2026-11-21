<?php

/**
 * CONTENIDO standard code generator
 *
 * @package    Core
 * @subpackage ContentType
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * CONTENIDO standard code generator.
 *
 * @package    Core
 * @subpackage ContentType
 */
class cCodeGeneratorStandard extends cCodeGeneratorAbstract
{

    /**
     * @inheritdoc
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function _generate(bool $contype = true, bool $editable = true, ?int $version = null): string
    {
        $cfg = cRegistry::getConfig();

        $this->cssData = '';
        $this->jsData = '';
        $this->templateName = '';

        cDebug::out("conGenerateCode($this->categoryId, $this->articleId, $this->languageId, $this->clientId, $this->layoutId);");

        // set category article id
        $categoryArticleId = conGetCategoryArticleId($this->categoryId, $this->articleId);

        // set configuration for article
        $this->templateConfigurationId = $this->getTemplateConfigurationId();
        if (NULL === $this->templateConfigurationId) {
            $this->processNoConfigurationError($categoryArticleId ?? 0);
            return '0601';
        }

        // list of configured container
        $containerConfigurations = conGetContainerConfiguration($this->templateConfigurationId);

        // set idlay and idmod array
        $data = $this->getTemplateData();
        $layoutId = $data['idlay'];
        $templateId = $data['idtpl'];
        $this->templateName = cString::cleanURLCharacters($data['name']);

        // list of used modules
        $containerModules = $templateId ? conGetUsedModules($templateId) : [];

        // load layout code from file
        $layoutInFile = new cLayoutHandler($layoutId, '', $cfg, $this->languageId);
        $this->layoutCode = $layoutInFile->getLayoutCode();
        $this->layoutCode = cString::normalizeLineEndings($this->layoutCode, "\n");

        $moduleHandler = new cModuleHandler();

        // create code for all containers
        if ($layoutId) {
            cInclude('includes', 'functions.tpl.php');
            $containerNumbers = tplGetContainerNumbersInLayout($layoutId);

            // Initializing check arrays (CON-2706)
            $loadedCSS = [];
            $loadedJS = [];

            // Load all modules at once
            $moduleCollection = new cApiModuleCollection();
            $modules = $moduleCollection->fetchByIds(
                array_map(fn ($containerNumber) => $containerModules[$containerNumber] ?? null, $containerNumbers)
            );

            foreach ($containerNumbers as $containerNumber) {
                // if there's no configured module in this container
                $containerModuleId = $containerModules[$containerNumber] ?? null;
                $module = $containerModuleId && is_numeric($containerModuleId)
                    ? array_find($modules, fn(cApiModule $module) => $module->getId() === cSecurity::toInteger($containerModuleId))
                    : null;

                if (!$containerModuleId || !$module) {
                    // reset current module state and process empty container
                    $this->resetModule();
                    $this->processCmsContainer($containerNumber);
                    continue;
                }

                $this->resetModule();

                $this->modulePrefixes[] = '$cCurrentModule = ' . $containerModuleId . ';';
                $this->modulePrefixes[] = '$cCurrentContainer = ' . $containerNumber . ';';

                $moduleHandler = new cModuleHandler($module);
                $input = '';
                $this->moduleCode = '';

                // get contents of input and output from files and not from db
                if ($moduleHandler->modulePathExists()) {
                    // do not execute faulty modules
                    // caution: if no module is bound to a container then idmod of $module is false
                    // caution: and as result error field is also empty
                    if ($module->get('error') === 'none' || $module->getId() === false) {
                        $this->moduleCode = cSecurity::toString($moduleHandler->readOutput());
                    } else {
                        continue;
                    }

                    // load css and js content of the js/css files
                    if ($moduleHandler->getFilesContent('css', 'css') !== false && !in_array($module->getId(), $loadedCSS)) {
                        $this->cssData .= $moduleHandler->getFilesContent('css', 'css') . PHP_EOL;
                        $loadedCSS[] = $module->getId();
                    }

                    if ($moduleHandler->getFilesContent('js', 'js') !== false && !in_array($module->getId(), $loadedJS)) {
                        $this->jsData .= $moduleHandler->getFilesContent('js', 'js') . PHP_EOL;
                        $loadedJS[] = $module->getId();
                    }

                    $input = cSecurity::toString($moduleHandler->readInput());
                }

                // Strip all comments and whitespace from module code
                $this->moduleCode = $this->stripWhitespace($this->moduleCode);

                // process CMS value tags
                $containerCmsValues = $this->processCmsValueTags(
                    $containerNumber,
                    $containerConfigurations[$containerNumber] ?? ''
                );

                // add CMS value code to module prefix code
                if ($containerCmsValues) {
                    $this->modulePrefixes[] = $containerCmsValues;
                }

                // process frontend debug
                $this->processFrontendDebug($containerNumber, $module->isLoaded() ? $module : null);

                $this->moduleSuffixes[] = '$cCurrentModule = null;';
                $this->moduleSuffixes[] = '$cCurrentContainer = null;';


                // replace new containers
                $this->processCmsContainer($containerNumber);
            }
        }

        // find out what kind of CMS_... Vars are in use
        $a_content = $this->getUsedCmsTypesData($editable, $version);

        // replace all CMS_TAGS[]
        if ($contype) {
            $this->processCmsTags($a_content, true, $editable);
        }

        // add/replace title tag
        $this->processCodeTitleTag();

        // add/replace meta-tags
        $this->processCodeMetaTags();

        // add CSS link and JS script tags
        $this->processModuleCssLinkTag($moduleHandler);
        $this->processModuleJsScriptTag($moduleHandler);

        $this->provessRevisionPlaceholder();
        $this->processMetaTagsPlaaceholder();

        $this->processGeneralFrontendDebug($layoutId, $templateId);

        // save the generated code even if there are faulty modules if one does not do so,
        // a not existing cache file  will be tried to be loaded in frontend
        $this->_saveGeneratedCode($categoryArticleId);

        return $this->layoutCode;
    }

    /**
     * Will be invoked, if code generation wasn't able to find a configured article or category.
     *
     * Creates an error message and writes this into the code cache.
     *
     * @param int $categoryArticleId Category article id
     * @throws cDbException|cInvalidArgumentException
     */
    protected function processNoConfigurationError(int $categoryArticleId): void
    {
        cDebug::out('Neither CAT or ART are configured!');

        $code = '<html><body>No code was created for this article in this category.</body></html>';
        $this->_saveGeneratedCode($categoryArticleId, $code, false);
    }

    /**
     * Processes and adds or replaces title tag for an article.
     * Also calls the CEC 'Contenido.Content.CreateTitletag' for user defined title creation if none is given.
     *
     * @see cCodeGeneratorAbstract::processCodeTitleTag()
     */
    protected function processCodeTitleTag(): string
    {
        if ($this->pageTitle == '') {
            cApiCecHook::setDefaultReturnValue($this->pageTitle);
            $this->pageTitle = cApiCecHook::executeAndReturn('Contenido.Content.CreateTitletag');
        }

        // define regular expressions
        $reHead = '/<head>.*?<\/head>/is';
        $reTitle = '/<title>.*?<\/title>/is';

        // find head tags in layout code (case insensitive, search across linebreaks)
        $matches = [];
        $succ = preg_match_all($reHead, $this->layoutCode, $matches);

        // check if head tag has been found
        if (false !== $succ && count($matches) && isset($matches[0], $matches[0][0])) {
            // use first head tag found
            // by definition there must be no more than one head tag but user supplied markup might be invalid
            $headTag = $matches[0][0];

            // add or replace title
            if ($this->pageTitle != '') {
                $replaceTag = '{__TITLE__' . md5(rand() . time()) . '}';
                $headCode = preg_replace($reTitle, $replaceTag, $headTag, 1);
                $pageTitle = conHtmlentities($this->pageTitle);
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
            $this->layoutCode = preg_replace($reHead, $headCode, $this->layoutCode, 1);
        }

        return $this->layoutCode;
    }

    /**
     * Save the collected CSS data in cache dir as [templateName].css and add the CSS link tag to the layout.
     *
     * @throws cInvalidArgumentException
     * @since CONTENIDO 4.10.2
     */
    protected function processModuleCssLinkTag(cModuleHandler $moduleHandler): void
    {
        $cssFile = '';
        if (
            cString::getStringLength($this->cssData) > 0
            && ($myFileCss = $moduleHandler->saveContentToFile($this->templateName, 'css', $this->cssData)) !== false
        ) {
            $cssFile = cHTMLLinkTag::stylesheet($myFileCss);
        }

        // add module CSS at {CSS} position, after title or after opening head tag
        if (cString::findFirstPos($this->layoutCode, '{CSS}') !== false) {
            $this->layoutCode = cString::iReplaceOnce('{CSS}', $cssFile, $this->layoutCode);
        } elseif (!empty($cssFile)) {
            if (cString::findFirstPos($this->layoutCode, '</title>') !== false) {
                $matches = [];
                if (preg_match_all("#(<head>.*?</title>)(.*?</head>)#si", $this->layoutCode, $matches)) {
                    $this->layoutCode = cString::iReplaceOnce(
                        $matches[1][0],
                        $matches[1][0] . $cssFile,
                        $this->layoutCode
                    );
                }
            } else {
                $this->layoutCode = cString::iReplaceOnce('<head>',
                    '<head>' . $cssFile,
                    $this->layoutCode
                );
            }
        }
    }

    /**
     * Save the collected JS data in cache dir as [templateName].js and add the JS script tag to the layout.
     *
     * @throws cInvalidArgumentException
     * @since CONTENIDO 4.10.2
     */
    protected function processModuleJsScriptTag(cModuleHandler $moduleHandler): void
    {
        $jsFile = '';
        if (
            cString::getStringLength($this->jsData) > 0
            && ($myFileJs = $moduleHandler->saveContentToFile($this->templateName, 'js', $this->jsData)) !== false
        ) {
            $jsFile = cHTMLScript::external($myFileJs);
        }

        // add module JS at {JS} position or before closing body tag if there is no {JS}
        if (cString::findFirstPos($this->layoutCode, '{JS}') !== false) {
            $this->layoutCode = cString::iReplaceOnce('{JS}', $jsFile, $this->layoutCode);
        } elseif (!empty($jsFile)) {
            $this->layoutCode = cString::iReplaceOnce(
                '</body>',
                $jsFile . '</body>',
                $this->layoutCode
            );
        }
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    protected function processMetaTagsPlaaceholder(): void
    {
        if (cString::findFirstPos($this->layoutCode, '{META}') !== false) {
            $this->layoutCode = cString::iReplaceOnce(
                '{META}',
                $this->processCodeMetaTags(),
                $this->layoutCode
            );
        } else {
            $this->layoutCode = cString::iReplaceOnce(
                '</head>',
                $this->processCodeMetaTags() . '</head>',
                $this->layoutCode
            );
        }
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    protected function provessRevisionPlaceholder(): void
    {
        if (cString::findFirstPos($this->layoutCode, '{REV}') !== false) {
            $this->layoutCode = cString::iReplaceOnce(
                '{REV}',
                cSecurity::toInteger(getEffectiveSetting('ressource', 'revision', 0)),
                $this->layoutCode
            );
        }
    }

    /**
     * Processes and adds or replaces all meta-tags for an article.
     * Also calls the CEC 'Contenido.Content.CreateMetatags' for user defined meta-tags creation.
     */
    protected function processCodeMetaTags(): string
    {
        // get basic meta-tags (from article & system)
        $metaTags = $this->_getBasicMetaTags();

        // process chain Contenido.Content.CreateMetatags to update meta-tags
        $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.CreateMetatags');
        while ($chainEntry = $cecIterator->next()) {
            $metaTags = $chainEntry->execute($metaTags);
        }

        $sMetaTags = '';

        foreach ($metaTags as $value) {
            // get meta-tag keys
            $valueKeys = array_keys($value);
            $nameKey = 'name';
            foreach ($valueKeys as $key) {
                if ($key != 'content') {
                    $nameKey = $key;
                }
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

            // HTML does not allow ID for meta-tags
            $oMetaTagGen->removeAttribute('id');

            // check if metatag already exists
            $sPattern = '/(<meta(?:\s+)' . $nameKey . '(?:\s*)=(?:\s*)(?:\\"|\\\')(?:\s*)' . $value[$nameKey] . '(?:\s*)(?:\\"|\\\')(?:[^>]+)>\n?)/i';
            if (preg_match($sPattern, $this->layoutCode, $aMatch)) {
                // the meta-tag is already specified in the layout
                // replace it only if its attributes are not empty
                $replace = true;
                foreach ($value as $test) {
                    if ($test == '') {
                        $replace = false;
                        break;
                    }
                }
                if ($replace) {
                    $this->layoutCode = str_replace($aMatch[1], $oMetaTagGen->render() . "\n", $this->layoutCode);
                }
            } else {
                $sMetaTags .= $oMetaTagGen->render() . "\n";
            }
        }

        return $sMetaTags;
    }

    /**
     * Saves the generated code if layout flag is false and save flag is true.
     *
     * @param int $categoryArticleId Category article id
     * @param string $code [optional] Parameter for setting code manually instead of using the generated layout code
     * @param bool $flagCreateCode [optional] Whether the "create code" flag in cat_art should be set or not (optional)
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _saveGeneratedCode(int $categoryArticleId, string $code = '', bool $flagCreateCode = true): void
    {
        $cfgClient = cRegistry::getClientConfig();
        $codePath = $cfgClient[$this->clientId]['code']['path'];

        // Write code in the cache of the client. If the folder does not exist
        // create one.
        // do not write code cache into root directory of client
        if (cRegistry::getFrontendPath() === $codePath) {
            return;
        }

        // parent directory must be named cache
        $directoryName = basename(dirname($codePath));
        if ('cache' !== $directoryName) {
            // directory name is not cache -> abort
            return;
        }

        // CON-2113
        // Do not overwrite an existing .htaccess file to prevent misconfiguring permissions
        if (!$this->layoutId && $this->isSaveCode && isset($codePath)) {
            if (!is_dir($codePath)) {
                mkdir($codePath);
                @chmod($codePath, cDirHandler::getDefaultPermissions());
            }

            if (!cFileHandler::exists($codePath . '.htaccess')) {
                cFileHandler::write($codePath . '.htaccess', "Order Deny,Allow\nDeny from all\n");
            }

            if (is_dir($codePath)) {
                $fileCode = $code == '' ? $this->layoutCode : $code;
                $code = "<?php defined('CON_FRAMEWORK') or die('Illegal call'); ?>" . $fileCode;
                $filename = sprintf('%s.%s.%s.php', $codePath . $this->clientId, $this->languageId, $categoryArticleId);
                cFileHandler::write($filename, $code, false);

                // Update create code flag
                if ($flagCreateCode) {
                    $oCatArtColl = new cApiCategoryArticleCollection();
                    $oCatArtColl->setCreateCodeFlag($categoryArticleId, 0);
                }
            }
        }
    }

    /**
     * Collects and return basic meta-tags/elements.
     *
     * @return array List of associative meta-tag values
     * @throws cDbException|cException
     */
    protected function _getBasicMetaTags(): array
    {
        $metaTypeService = new cMetaTypeService();
        $metaTypes = $metaTypeService->getAll();

        $metaTagItems = (new cApiMetaTagCollection())
            ->fetchByArticleLanguageId(cSecurity::toInteger($this->articleLanguageId));

        // collect all available meta-tag entries with non-empty values
        $metaTags = [];
        foreach ($metaTypes as $metaType) {
            $metaTag = $metaTypeService::findMetaTagByMetaType($metaType, $metaTagItems);
            if ($metaTag && cString::getStringLength($metaTag->get('metavalue') ?? '') > 0) {
                $metaTags[] = [
                    $metaType->get('fieldname') => $metaType->get('metatype'),
                    'content' => $metaTag->get('metavalue')
                ];
            }
        }

        // add generator meta-tag
        $generator = 'CMS CONTENIDO';
        if ((getEffectiveSetting('generator', 'add_version', 'true') === 'true')) {
            $aVersion = explode('.', CON_VERSION);
            $generator .= ' ' . $aVersion[0] . '.' . $aVersion[1];
        }
        $metaTags[] = [
            'name' => 'generator',
            'content' => $generator
        ];

        $encoding = cRegistry::getEncoding();
        if (!$encoding) {
            $encoding = 'utf-8';
        }

        // add charset or content type meta-tag
        if (getEffectiveSetting('generator', 'html5', 'false') === 'true') {
            $metaTags[] = [
                'charset' => $encoding
            ];
        } elseif (getEffectiveSetting('generator', 'xhtml', 'false') === 'true') {
            $metaTags[] = [
                'http-equiv' => 'Content-Type',
                'content' => 'application/xhtml+xml; charset=' . $encoding
            ];
        } else {
            $metaTags[] = [
                'http-equiv' => 'Content-Type',
                'content' => 'text/html; charset=' . $encoding
            ];
        }

        // update (!) index setting of robots meta-tag
        // the following value will not be changed
        // $index = (bool) $this->getArtLangObject()->get('searchable');
        // $metaTags = $this->_updateMetaRobots($metaTags, $index, NULL);

        return $metaTags;
    }

    /**
     * This method allows to set new values for the robots meta element.
     * If NULL is given for $index or $follow, existing settings are *not* overwritten.
     * If article should be indexed and followed, 'all' will be set.
     *
     * @param array $metaTags Array of meta elements to amend
     * @param bool|NULL $index If article should be indexed
     * @param bool|NULL $follow If links in article should be followed
     */
    protected function _updateMetaRobots(array $metaTags, $index, $follow): array
    {
        // extract robots setting from current meta elements
        list($metaTags, $metaRobots) = $this->_extractMetaElement($metaTags, 'name', 'robots');

        if (is_null($metaRobots)) {
            // build new meta element if none could be found
            $metaRobots = [
                'name' => 'robots',
                'content' => ''
            ];
        } else {
            $content = array_map('trim', explode(',', $metaRobots['content']));
            // determine index from extracted element if given value is NULL
            if (is_null($index)) {
                $index = (bool)(in_array('all', $content) || in_array('index', $content));
                if (in_array('index', $content) || in_array('all', $content)) {
                    $index = true;
                } elseif (in_array('noindex', $content)) {
                    $index = true;
                } else {
                    $index = NULL;
                }
            }
            // determine follow from extracted element if given value is NULL
            if (is_null($follow)) {
                if (in_array('follow', $content) || in_array('all', $content)) {
                    $follow = true;
                } elseif (in_array('nofollow', $content)) {
                    $follow = true;
                } else {
                    $follow = NULL;
                }
            }
        }

        // build and set new content for robots element
        $content = [];
        if (true === $index && true === $follow) {
            $content[] = 'all';
        } else {
            if (!is_null($index)) {
                $content[] = $index ? 'index' : 'noindex';
            }
            if (!is_null($follow)) {
                $content[] = $follow ? 'follow' : 'nofollow';
            }
        }
        $metaRobots['content'] = implode(',', $content);

        // add robots meta element
        $metaTags[] = $metaRobots;

        // what do you expect?
        return $metaTags;
    }

    /**
     * Extracts a meta element of type $type (either 'name' or 'http-equiv') and name or HTTP
     * header equivalent $nameOrEquiv from the given array of meta elements.
     * Both, the reduced array of meta elements and the meta element to be extracted are returned as an
     * array. If the meta element to be extracted could not be found, NULL will be returned in its place.
     *
     * @param string $type Either 'name' or 'http-equiv'
     */
    protected function _extractMetaElement(array $metaTags, $type, $nameOrEquiv): array
    {
        // prepare result structure
        $result = [
            [],
            NULL
        ];

        // loop all given meta elements
        foreach ($metaTags as $metaTag) {
            if (!is_array($metaTag)) {
                // skip $metaTag if it's no array
                continue;
            } elseif (!array_key_exists($type, $metaTag)) {
                // add element to reduced array if it's of different type
                $result[0][] = $metaTag;
            } elseif ($metaTag[$type] !== $nameOrEquiv) {
                // add element to reduced array if it has different name
                $result[0][] = $metaTag;
            } else {
                // set element as extracted element
                $result[1] = $metaTag;
            }
        }

        // what do you expect?
        return $result;
    }

}
