<?php

/**
 * This file contains the abstract CONTENIDO code generator class.
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
 * Abstract CONTENIDO code generator class.
 *
 * @package    Core
 * @subpackage ContentType
 */
abstract class cCodeGeneratorAbstract
{

    /**
     * CONTENIDO database instance.
     */
    protected cDb $db;

    /**
     * @var string[] Frontend debug options.
     * @see $frontend_debug in __FRONTEND_PATH__/data/config/config.php
     */
    protected array $feDebugOptions = [];

    /**
     * Collected CSS data for the current template.
     */
    protected string $cssData = '';

    /**
     * Collected JS data for the current template.
     */
    protected string $jsData = '';

    /**
     * Template name.
     */
    protected string $templateName = '';

    protected ?int $categoryId = null;

    protected ?int $articleId = null;

    protected ?int $languageId = null;

    protected ?int $clientId = null;

    /**
     * ID of layout to process.
     */
    protected ?int $layoutId = null;

    /**
     * Flag to persist generated code.
     */
    protected bool $isSaveCode = false;

    protected ?int $articleLanguageId = null;

    protected ?int $templateConfigurationId = null;

    /**
     * Page title, usually from article language table.
     */
    protected string $pageTitle = '';

    /**
     * Layout code, initially with container tags which will be replaced by module output.
     */
    protected string $layoutCode = '';

    /**
     * @var string[] Module output code prefix.
     */
    protected array $modulePrefixes = [];

    /**
     * Module output code.
     */
    protected string $moduleCode = '';

    /**
     * @var string[] Module output code suffix.
     */
    protected array $moduleSuffixes = [];

    /**
     * Article language.
     */
    protected ?cApiArticleLanguage $articleLanguage = null;

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct()
    {
        $this->db = cRegistry::getDb();
    }

    /**
     * Setter for frontend debug options.
     *
     * @param array $debugOptions
     * @see $frontend_debug in __FRONTEND_PATH__/data/config/config.php
     *         located in clients frontend directory
     */
    public function setFrontendDebugOptions(array $debugOptions): void
    {
        $this->feDebugOptions = $debugOptions;
    }

    /**
     * Generates the code for a specific article (article for a client in a language).
     *
     * @param int $categoryId
     * @param int $articleId
     * @param int $languageId
     * @param int $clientId
     * @param ?int $layoutId [optional] The id of layout to process. Use associated layout if not set.
     * @param bool $isSaveCode [optional] Flag to persist generated code.
     * @param bool $contype [optional] Flag to enable/disable replacement of CMS_TAGS[].
     * @param bool $editable [optional]
     * @param ?int $version [optional]
     * @return string Generated code or error code '0601' if no template configuration was
     *      found for category or article.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function generate(
        int $categoryId,
        int $articleId,
        int $languageId,
        int $clientId,
        ?int $layoutId = null,
        bool $isSaveCode = true,
        bool $contype = true,
        bool $editable = true,
        ?int $version = null
    ): string {
        $this->categoryId = $categoryId;
        $this->articleId = $articleId;
        $this->languageId = $languageId;
        $this->clientId = $clientId;
        $this->layoutId = $layoutId;
        $this->isSaveCode = $isSaveCode;

        $this->articleLanguage = new cApiArticleLanguage();
        $this->articleLanguage->loadByArticleAndLanguageId($this->articleId, $this->languageId);
        if (!$this->articleLanguage->isLoaded()) {
            throw new cInvalidArgumentException('Could not load article language for idart=' . $this->articleId . 'AND idlang=' . $this->languageId);
        }

        $this->articleLanguageId = $this->articleLanguage->get('idartlang');
        $this->pageTitle = stripslashes($this->articleLanguage->get('pagetitle'));

        return $this->_generate($contype, $editable, $version);
    }

    /**
     * Generates the code for a specific article (article for a client in a language).
     *
     * @param bool $contype Flag to enable/disable replacement of CMS_TAGS[].
     * @return string The generated code.
     */
    abstract function _generate(bool $contype = true, bool $editable = true, ?int $version = null): string;

    /**
     * Returns the template configuration id, either by configured article or by configured category.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    protected function getTemplateConfigurationId(): ?int
    {
        // get configuration for article
        $idtplcfg = conGetTemplateConfigurationIdForArticle($this->articleId, $this->categoryId, $this->languageId, $this->clientId);
        if (is_numeric($idtplcfg) && $idtplcfg != 0) {
            // article is configured
            cDebug::out("configuration for article found: $idtplcfg");
        } else {
            // check whether category is configured
            $idtplcfg = conGetTemplateConfigurationIdForCategory($this->categoryId, $this->languageId, $this->clientId);
            if (NULL !== $idtplcfg) {
                // category is configured
                cDebug::out("configuration for category found: $idtplcfg");
            }
        }

        return (is_numeric($idtplcfg)) ? (int) $idtplcfg : NULL;
    }

    /**
     * Will be invoked, if code generation wasn't able to find a configured
     * article or category.
     *
     * @param int $categoryArticleId Category article id.
     * @todo This method is not required as it is only used in the standard code generator.
     */
    abstract protected function processNoConfigurationError(int $categoryArticleId): void;

    /**
     * Returns array containing used layout, template and template name.
     * Returns array with default values (0 for int ans '' for string), if no entry was found.
     *
     * @return array{
     *     'idlang': int,
     *     'idtpl': int,
     *     'name': string,
     * }
     * @throws cDbException|cInvalidArgumentException
     */
    protected function getTemplateData(): array
    {
        // get IDLAY and IDMOD array
        $this->db->query(
            "SELECT
                    a.idlay AS idlay, a.idtpl AS idtpl, a.name AS name
                FROM
                    `%s` AS a, `%s` AS b
                WHERE
                    b.idtplcfg = %d AND b.idtpl = a.idtpl
                ;",
            cDb::getTableName('tpl'),
            cDb::getTableName('tpl_conf'),
            $this->templateConfigurationId
        );
        $this->db->nextRecord();
        $data = $this->db->toArray();

        if ($this->layoutId) {
            $data['idlay'] = $this->layoutId;
        }

        $data['idlay'] = cSecurity::toInteger($data['idlay'] ?? '0');
        $data['idtpl'] = cSecurity::toInteger($data['idtpl'] ?? '0');
        $data['name'] = $data['name'] ?? '';

        cDebug::out("Using Layout: {$data['idlay']} and Template: {$data['idtpl']} for generation of code.");

        return $data;
    }

    /**
     * Processes replacements of all existing CMS_* tags within passed code.
     *
     * @param array $contentList Associative list of CMS variables.
     * @param bool $saveKeywords [optional] Flag to save collected keywords during replacement process.
     * @param bool $editable [optional]
     * @throws cDbException|cException
     */
    protected function processCmsTags($contentList, $saveKeywords = true, $editable = true): void
    {
        // NOTE: Variables below are required in included/evaluated content type codes!
        global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

        // NOTE: Variables below are additionally required in included/evaluated
        // content type codes within backend edit mode!
        global $edit, $editLink, $belang;

        $idcat = $this->categoryId;
        $idart = $this->articleId;
        $lang = $this->languageId;
        $client = $this->clientId;
        $idartlang = $this->articleLanguageId;

        if (!is_object($db2)) {
            $db2 = cRegistry::getDb();
        }
        // End: Variables required in content type codes

        $keycode = [];

        // NOTE: $a_content is used by included/evaluated content type codes below
        $a_content = $contentList;

        // select all cms_type entries
        $_typeList = [];
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();
        while ($oType = $oTypeColl->next()) {
            $_typeList[] = $oType->toObject();
        }

        // Find all Content Types and the Content-IDs
        $allContentTypes = [];
        foreach ($_typeList as $_typeItem) {
            $key = cString::toLowerCase($_typeItem->type);
            $type = $_typeItem->type;
            // Find all Content Types and the Content-IDs CMS_{type}[{number}] values, e.g. CMS_HTML[1]
            $tmp = preg_match_all(
                sprintf('/(%s\[(\d+)\])/', preg_quote($type, '/')),
                $this->layoutCode,
                $matches
            );

            // We need the content IDs, `$matches[2]` contains them
            $allContentTypes[$_typeItem->type] = array_unique($matches[2]) ?? [];
        }

        // Replace all Content Types by their values.
        foreach ($allContentTypes as $contentType => $contentIds) {
            $search = [];
            $replacements = [];

            $typeClassName = $this->getContentTypeClassName($contentType);
            $typeCodeFile = $this->getContentTypeCodeFilePathName(cString::toLowerCase($contentType));
            $tmp = '';

            foreach ($contentIds as $contentId) {
                if (class_exists($typeClassName)) {
                    // we have a class for the content type, use it
                    $tmp = !empty($a_content[$contentType][$contentId]) ? $a_content[$contentType][$contentId] : '';
                    /** @var cContentTypeAbstract $cTypeObject */
                    $cTypeObject = new $typeClassName($tmp, $contentId, $a_content);
                    global $edit;

                    if (cRegistry::isBackendEditMode()) {
                        $tmp = $cTypeObject->generateEditCode();
                    } else {
                        $tmp = $cTypeObject->generateViewCode();
                    }
                } elseif (cFileHandler::exists($typeCodeFile)) {
                    // include CMS type code file
                    include($typeCodeFile);
                }

                $search[$contentId] = sprintf('%s[%s]', $contentType, $contentId);
                $replacements[$contentId] = $tmp;
                $keycode[$contentType][$contentId] = $tmp;
            }

            $this->layoutCode = str_ireplace($search, $replacements, $this->layoutCode);
        }
    }

    /**
     * Processes and adds or replaces title tag for an article.
     */
    abstract protected function processCodeTitleTag(): string;

    /**
     * Processes and adds or replaces all meta-tags for an article.
     */
    abstract protected function processCodeMetaTags(): string;

    /**
     * Replaces all container/module configuration tags (CMS_VALUE[n] values)
     * by their settings.
     *
     * @param int $containerNumber Container number
     * @param string $containerCfg A string being formatted like a concatenated query
     *         parameter, e.g. param1=value1&param2=value2...
     * @return string Concatenated PHP code containing CMS_VALUE variables and their values
     */
    protected function processCmsValueTags(int $containerNumber, string $containerCfg): string
    {
        return cApiModule::processContainerOutputCode($containerNumber, $containerCfg, $this->moduleCode);
    }

    /**
     * Extends container code by adding several debug features,
     * if enabled and configured.
     *
     * @param int $containerNumber Container number (the id attribute in container tag).
     * @param ?cApiModule $module Mdule item instance
     * @throws cDbException|cInvalidArgumentException
     */
    protected function processFrontendDebug(int $containerNumber, ?cApiModule $module = null): void
    {
        global $containerinf;

        if (empty($this->feDebugOptions) || !$module) {
            return;
        }

        $data = $this->getTemplateData();

        if ($this->getFeDebugOption('container_display')) {
            $this->modulePrefixes[] = 'if ($frontend_debug[\'container_display\']) echo "<!-- START CONTAINER ' . $containerinf[$data['idlay']][$containerNumber]['name'] . ' (' . $containerNumber . ') -->";';
        }

        if ($this->getFeDebugOption('module_display')) {
            $this->modulePrefixes[] = 'if ($frontend_debug[\'module_display\']) echo "<!-- START MODULE ' . $module->get('name') . ' (' . $module->getId() . ') -->";';
        }

        if ($this->getFeDebugOption('module_timing')) {
            $this->modulePrefixes[] = '$modTime' . $containerNumber . ' = -getmicrotime(true);';
            $this->moduleSuffixes[] = '$modTime' . $containerNumber . ' += getmicrotime(true);';
        }

        if ($this->getFeDebugOption('module_display')) {
            $this->moduleSuffixes[] = 'if ($frontend_debug[\'module_display\']) echo "<!-- END MODULE ' . $module->get('name') . ' (' . $module->getId() . ')";';
            if ($this->getFeDebugOption('module_timing')) {
                $this->moduleSuffixes[] = 'if ($frontend_debug[\'module_timing\']) echo(" AFTER " . $modTime' . $containerNumber . ');';
            }
            $this->moduleSuffixes[] = 'if ($frontend_debug[\'module_display\']) echo " -->";';
        }
        if ($this->getFeDebugOption('container_display')) {
            $this->moduleSuffixes[] = 'if ($frontend_debug[\'container_display\']) echo "<!-- END CONTAINER ' . $containerinf[$data['idlay']][$containerNumber]['name'] . ' (' . $containerNumber . ') -->";';
        }
    }

    protected function processGeneralFrontendDebug(int $layoutId, int $templateId): void
    {
        if (!$this->getFeDebugOption('general_information')) {
            return;
        }

        $debugPrefix = '';

        $debugPrefix .= "<?php\nif (\$frontend_debug['general_information']) {\n";
        $debugPrefix .= "\techo(\"<!-- \\n\\n\");\n";

        $layout = new cApiLayout($layoutId);
        $layoutName = $layout->get('name');
        $debugPrefix .= "\techo(\"Layout: " . $layoutName . " (" . $layoutId . ")\\n\");\n";

        $debugPrefix .= "\techo(\"Template: " . $this->templateName . " (" . $templateId . ")\\n\");\n";

        $article = new cApiArticleLanguage($this->articleLanguageId);
        $catart = new cApiCategoryArticle();
        $cat = new cApiCategoryLanguage();
        $cat->loadByCategoryIdAndLanguageId($this->categoryId, $article->get('idlang'));
        $catart->loadByMany([
            'idcat' => $cat->get('idcat'),
            'idart' => $article->get('idart')
        ]);
        $language = new cApiLanguage($article->get('idlang'));
        $debugPrefix .= "\techo(\"Language: " . $language->get('idlang') . " (" . $language->get('name') . ")\\n\");\n";

        $debugPrefix .= "\techo(\"Category: " . $cat->get('idcat') . " (" . $cat->get('name') . ")\\n\");\n";

        $articleName = $article->get('title');
        $debugPrefix .= "\techo(\"Article: " . $articleName . " (catart = " . $catart->get('idcatart') . ", artlang = " . $this->articleLanguageId . ", art = " . $article->get('idart') . ")\\n\");\n";

        $debugPrefix .= "\techo(\"\\n--> \\n\");\n";
        $debugPrefix .= "}\n?>";

        $this->layoutCode = $debugPrefix . $this->layoutCode;
    }

    /**
     * Replaces container tag in layout by the parsed container code (module code).
     *
     * @param int $containerNumber Container number (the id attribute in container tag).
     */
    protected function processCmsContainer(int $containerNumber): void
    {
        $cmsContainer = "CMS_CONTAINER[$containerNumber]";

        // replace new container (<container id="n"..>) against old one
        // (CMS_CONTAINER[n])
        $this->layoutCode = preg_replace("/<container( +)id=\\\"$containerNumber\\\"(.*)>(.*)<\/container>/Uis", $cmsContainer, $this->layoutCode);
        $this->layoutCode = preg_replace("/<container( +)id=\\\"$containerNumber\\\"(.*)\/>/i", $cmsContainer, $this->layoutCode);

        // concatenate final container/module output code,
        // but generate PHP code only if there is something to generate
        $modulePrefix = trim(implode("\n", $this->modulePrefixes));
        if (!empty($modulePrefix)) {
            $modulePrefix = "<?php\n" . $modulePrefix . "\n?>";
        }
        $moduleSuffix = trim(implode("\n", $this->moduleSuffixes));
        if (!empty($moduleSuffix)) {
            $moduleSuffix = "<?php\n" . $moduleSuffix . "\n?>";
        }
        $moduleOutput = $modulePrefix . $this->moduleCode . $moduleSuffix;

        // replace container (CMS_CONTAINER[n]) against the container code
        $this->layoutCode = str_ireplace($cmsContainer, $moduleOutput, $this->layoutCode);
        // $this->layoutCode = addslashes($this->layoutCode);
    }

    /**
     * Returns array of all CMS_* vars being used by current article and language
     *
     * @return array Like $arr[type][typeid] = value;
     * @throws cDbException
     */
    protected function getUsedCmsTypesData(bool $editable = true, ?int $version = null): array
    {
        $return = [];

        // find out what kind of CMS_... vars are in use
        if ($version == NULL) {
            $sql = "SELECT * FROM `%s` AS A, `%s` AS B, `%s` AS C
                    WHERE A.idtype = C.idtype AND A.idartlang = B.idartlang AND B.idart = %d AND B.idlang = %d";
            $sql = $this->db->prepare(
                $sql,
                cDb::getTableName('content'),
                cDb::getTableName('art_lang'),
                cDb::getTableName('type'),
                $this->articleId,
                $this->languageId
            );
        } elseif (is_numeric($version)) {
            $sql = 'SELECT b.type as type, a.typeid as typeid, a.value as value
                    FROM `%s` AS a
                    INNER JOIN `%s` as b
                            ON b.idtype = a.idtype
                    WHERE (a.idtype, a.typeid, a.version) IN
                            (SELECT idtype, typeid, max(version)
                            FROM %s
                            WHERE idartlang = %d AND version <= %d
                            GROUP BY idtype, typeid)
                    AND a.idartlang = %d
                    AND (a.deleted < 1 OR a.deleted IS NULL)
                    ORDER BY a.idtype, a.typeid;';
            $sql = $this->db->prepare(
                $sql,
                cDb::getTableName('content_version'),
                cDb::getTableName('type'),
                cDb::getTableName('content_version'),
                $this->articleLanguageId,
                $version,
                $this->articleLanguageId
            );
        }

        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $return[$this->db->f('type')][$this->db->f('typeid')] = $this->db->f('value');
        }

        return $return;
    }

    /**
     * Resets module related variables.
     */
    protected function resetModule()
    {
        $this->modulePrefixes = [];
        $this->moduleCode = '';
        $this->moduleSuffixes = [];
    }

    /**
     * @see cTypeGenerator::getContentTypeClassName()
     */
    protected function getContentTypeClassName(string $type): string
    {
        return cTypeGenerator::getContentTypeClassName($type);
    }

    /**
     * @see cTypeGenerator::getContentTypeCodeFilePathName()
     */
    protected function getContentTypeCodeFilePathName(string $type): string
    {
        return cTypeGenerator::getContentTypeCodeFilePathName($type);
    }

    /**
     * Strips all comments and whitespace from passed PHP code.
     *
     * @param string $code Code to clean from comments and whitespace
     * @return string Cleaned code
     * @throws cInvalidArgumentException
     */
    protected function stripWhitespace(string $code): string
    {
        $cfg = cRegistry::getConfig();

        // Check if stripping white spaces and comments is active, it is enabled by default
        // and has to be disabled explicitly.
        $stripWhiteSpaces = !isset($cfg['code_generator']['strip_white_spaces'])
            || $cfg['code_generator']['strip_white_spaces'] === true;

        if (!$stripWhiteSpaces) {
            return $code;
        }

        // CON-1536 strip comments from module code
        // regex is not enough to correctly remove comments
        // use php_strip_whitespace instead of writing own parser
        // downside: php_strip_whitespace requires a file as parameter
        $tmpFile = dirname(cRegistry::getBackendPath()) . '/' . $cfg['path']['temp'] . uniqid('code_gen_') . '.php';
        if (cFileHandler::exists(dirname($tmpFile))
            && cFileHandler::readable(dirname($tmpFile))
            && cFileHandler::writeable(dirname($tmpFile))) {
            if (false !== cFileHandler::write($tmpFile, $code)) {
                $code = php_strip_whitespace($tmpFile);
                // delete file
                cFileHandler::remove($tmpFile);
            }
        }

        return $code;
    }

    /**
     * Getter for frontend debug option (see global variable $frontend_debug)
     */
    protected function getFeDebugOption(string $key): bool
    {
        return cSecurity::toBoolean($this->feDebugOptions[$key] ?? '0');
    }

    /**
     * @deprecated [2026-09-20] Since CONTENIDO 4.10.2, use {@see cCodeGeneratorAbstract::articleLanguage} instead
     */
    protected function getArtLangObject(): ?cApiArticleLanguage
    {
        cDeprecated("The function isArtInMultipleUse() is deprecated since CONTENIDO 4.10.2, use cCodeGeneratorAbstract::articleLanguage instead.");
        return $this->articleLanguage;
    }
}
