<?php

/**
 * This file contains CONTENIDO content functions.
 *
 * Please add only stuff which is relevant for the frontend
 * AND the backend. This file should NOT contain any backend editing
 * functions to improve frontend performance:
 *
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Generates the code for one article
 *
 * @param int $idcat Id of category
 * @param int $idart Id of article
 * @param int $lang Id of language
 * @param int $client Id of client
 * @param bool $layout [optional] Layout-ID of alternate Layout (if false, use associated layout)
 * @param bool $save [optional] Flag to persist generated code in database
 * @param bool $contype [optional] Flag to enable/disable replacement of CMS_TAGS[].
 * @param bool $editable [optional] deprecated?
 * @param int|NULL $version [optional] version number if article is a revision, else NULL;
 * @return string The generated code or "0601" if neither article nor category configuration was found.
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conGenerateCode(
    $idcat,
    $idart,
    $lang,
    $client,
    $layout = false,
    $save = true,
    $contype = true,
    $editable = false,
    $version = NULL
)
{
    global $frontend_debug;

    $cfg = cRegistry::getConfig();

    // @todo make generator configurable
    $codeGen = cCodeGeneratorFactory::getInstance($cfg['code_generator']['name']);
    if (isset($frontend_debug) && is_array($frontend_debug)) {
        $codeGen->setFrontendDebugOptions($frontend_debug);
    }

    $code = $codeGen->generate($idcat, $idart, $lang, $client, $layout, $save, $contype, $editable, $version);

    // execute CEC hook
    return cApiCecHook::executeAndReturn('Contenido.Content.conGenerateCode', $code);
}

/**
 * Returns the idartlang for a given article and language
 *
 * @param int $idart ID of the article
 * @param int $idlang ID of the language
 * @return mixed idartlang of the article or false if nothing was found
 * @throws cDbException|cInvalidArgumentException
 */
function getArtLang($idart, $idlang)
{
    $idartlang = (new cApiArticleLanguageCollection())
        ->getIdByArticleIdAndLanguageId($idart, $idlang);
    return $idartlang ? $idartlang : false;
}

/**
 * Returns all available meta tag types
 *
 * @return array Associative meta tags list
 * @throws cDbException|cException
 */
function conGetAvailableMetaTagTypes()
{
    $oMetaTypeColl = new cApiMetaTypeCollection();
    $oMetaTypeColl->select();

    $aMetaTypes = [];
    while ($oMetaType = $oMetaTypeColl->next()) {
        $rs = $oMetaType->toArray();
        $aMetaTypes[$rs['idmetatype']] = [
            'metatype' => $rs['metatype'],
            'fieldtype' => $rs['fieldtype'],
            'maxlength' => $rs['maxlength'],
            'fieldname' => $rs['fieldname'],
            'idmetatype' => $rs['idmetatype'],
        ];
    }

    return $aMetaTypes;
}

/**
 * Get the meta tag value or its version for a specific article
 *
 * @param int $idartlang ID of the article
 * @param int $idmetatype Metatype-ID
 * @param int $version version number
 * @throws cDbException|cException
 */
function conGetMetaValue($idartlang, $idmetatype, $version = null): string
{
    static $oMetaTagColl = null;
    static $metaTagVersionColl = null;

    if ((int)$idartlang <= 0) {
        return '';
    }

    if ($version === null) {
        if (!isset($oMetaTagColl)) {
            $oMetaTagColl = new cApiMetaTagCollection();
        }
        $oMetaTag = $oMetaTagColl->fetchByArtLangAndMetaType($idartlang, $idmetatype);
    } elseif (is_numeric($version)) {
        if (!isset($metaTagVersionColl)) {
            $metaTagVersionColl = new cApiMetaTagVersionCollection();
        }
        $oMetaTag = $metaTagVersionColl->fetchByArtLangMetaTypeAndVersion($idartlang, $idmetatype, $version);
    } else {
        $oMetaTag = null;
    }

    if (is_object($oMetaTag)) {
        $metavalue = $oMetaTag->get('metavalue');
        $metavalue = stripslashes($metavalue);
    } else {
        $metavalue = '';
    }

    return $metavalue;
}

/**
 * Set the meta tag value or its version for a specific article.
 *
 * @param int $idartlang ID of the article
 * @param int $idmetatype Metatype-ID
 * @param string $value Value of the meta tag
 * @param int $version version number
 * @return bool Whether the meta value has been saved successfully
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conSetMetaValue($idartlang, $idmetatype, $value, $version = NULL)
{
    static $metaTagColl = NULL;

    $versioning = new cContentVersioning();

    if (!isset($metaTagColl)) {
        $metaTagColl = new cApiMetaTagCollection();
    }

    $metaTag = $metaTagColl->fetchByArtLangAndMetaType($idartlang, $idmetatype);

    switch ($versioning->getState()) {
        case $versioning::STATE_SIMPLE:
            // if it's only a robot-update, only update and don't create a version
            if ($version == NULL) {
                if (is_object($metaTag)) {
                    return $metaTag->updateMetaValue($value);
                } else {
                    $metaTagColl->create($idartlang, $idmetatype, $value);
                    return true;
                }
            }

            // update article
            $artLang = new cApiArticleLanguage($idartlang);
            $artLang->set('lastmodified', date('Y-m-d H:i:s'));
            $artLang->store();
            // update or create meta tag
            if (is_object($metaTag)) {
                $metaTag->updateMetaValue($value);
            } else {
                $metaTag = $metaTagColl->create($idartlang, $idmetatype, $value);
            }

            // create meta tag version
            $metaTagVersionParameters = [
                'idmetatag' => cSecurity::toInteger($metaTag ? $metaTag->get('idmetatag') : 0),
                'idartlang' => $idartlang,
                'idmetatype' => $idmetatype,
                'value' => $value,
                'version' => $version,
            ];
            $versioning->createMetaTagVersion($metaTagVersionParameters);
        case $versioning::STATE_DISABLED:
            // update article
            $artLang = new cApiArticleLanguage($idartlang);
            $artLang->set('lastmodified', date('Y-m-d H:i:s'));
            $artLang->store();
            //update meta tag
            if (is_object($metaTag)) {
                return $metaTag->updateMetaValue($value);
            } else {
                $metaTagColl->create($idartlang, $idmetatype, $value);
            }

            break;
        case $versioning::STATE_ADVANCED:
            if ($version == NULL) {
                if (is_object($metaTag)) {
                    $metaTag->updateMetaValue($value);
                } else {
                    $metaTag = $metaTagColl->create($idartlang, $idmetatype, $value);
                }
                $version = 1;
            }

            $metaTagVersionParameters = [
                'idmetatag' => cSecurity::toInteger($metaTag ? $metaTag->get('idmetatag') : 0),
                'idartlang' => $idartlang,
                'idmetatype' => $idmetatype,
                'value' => $value,
                'version' => $version,
            ];
            $versioning->createMetaTagVersion($metaTagVersionParameters);

            break;
        default:
            break;
    }

    return false;
}

/**
 * @deprecated [2014-07-24] Not used anymore
 */
function conGenerateKeywords($client, $lang)
{
    $cfg = cRegistry::getConfig();

    static $db = NULL;
    if (!isset($db)) {
        $db = cRegistry::getDb();
    }

    // cms types to be excluded from indexing
    $options = $cfg['search_index']['excluded_content_types'];

    $sql = 'SELECT a.idart, b.idartlang FROM ' . cDb::getTableName('art') . ' AS a, ' . cDb::getTableName('art_lang') . ' AS b
            WHERE a.idart=b.idart AND a.idclient=' . (int)$client . ' AND b.idlang=' . (int)$lang;

    $db->query($sql);

    $aArticles = [];
    while ($db->nextRecord()) {
        $aArticles[$db->f('idart')] = $db->f('idartlang');
    }

    foreach ($aArticles as $artid => $artlangid) {
        $aContent = conGetContentFromArticle($artlangid);
        if (count($aContent) > 0) {
            $oIndex = new cSearchIndex($db);
            $oIndex->start($artid, $aContent, 'auto', $options);
        }
    }
}

/**
 * Get content from article by article language.
 *
 * @param int $iIdArtLang ArticleLanguageId of an article (idartlang)
 * @return array Array with content of an article indexed by content-types as follows:
 *      - $arr[type][typeid] = value;
 * @throws cDbException
 */
function conGetContentFromArticle($iIdArtLang): array
{
    static $db = NULL;
    if (!isset($db)) {
        $db = cRegistry::getDb();
    }

    return (new cArticleContentHelper($db))->getContentByIdArtLang(
        cSecurity::toInteger($iIdArtLang)
    );
}

/**
 * Returns list of all container with configured modules by template id
 *
 * @param int $idtpl Template id
 * @return array Associative array where the key is the number and value the module id
 * @throws cDbException|cException
 */
function conGetUsedModules($idtpl): array
{
    $containerColl = new cApiContainerCollection();
    $containerColl->select('`idtpl` = ' . cSecurity::toInteger($idtpl), '', '`number` ASC');

    $modules = [];
    while ($container = $containerColl->next()) {
        $modules[cSecurity::toInteger($container->get('number'))] = cSecurity::toInteger($container->get('idmod'));
    }

    return $modules;
}

/**
 * Returns list of all configured container configurations by template configuration id
 *
 * @param int $idtplcfg Template configuration id
 * @return array Associative array where the key is the number and value the container configuration.
 * @throws cDbException|cException
 */
function conGetContainerConfiguration($idtplcfg): array
{
    return (new cApiContainerConfigurationCollection())
        ->getByTemplateConfiguration(cSecurity::toInteger($idtplcfg));
}

/**
 * Returns category article id
 *
 * @param int $idcat
 * @param int $idart
 * @throws cDbException
 */
function conGetCategoryArticleId($idcat, $idart): ?int
{
    global $db;

    // Get idcatart, we need this to retrieve the template configuration
    $db->query(
        'SELECT idcatart FROM `%s` WHERE idcat = %d AND idart = %d',
        cDb::getTableName('cat_art'),
        $idcat,
        $idart
    );

    return $db->nextRecord() ? cSecurity::toInteger($db->f('idcatart')) : NULL;
}

/**
 * Returns template configuration id for a configured article.
 *
 * @param int $idart
 * @param int $idcat NOT used
 * @param int $lang
 * @param int $client
 * @throws cDbException
 */
function conGetTemplateConfigurationIdForArticle($idart, $idcat, $lang, $client): ?int
{
    global $db;

    $db->query(
        "SELECT a.idtplcfg AS idtplcfg FROM `%s` AS a, `%s` AS b WHERE a.idart = %d "
        . "AND a.idlang = %d AND b.idart = a.idart AND b.idclient = %d",
        cDb::getTableName('art_lang'),
        cDb::getTableName('art'),
        $idart,
        $lang,
        $client
    );

    return $db->nextRecord() ? (int) $db->f('idtplcfg') : NULL;
}

/**
 * Returns template configuration id for a configured category
 *
 * @param int $idcat
 * @param int $lang
 * @param int $client
 * @throws cDbException
 */
function conGetTemplateConfigurationIdForCategory($idcat, $lang, $client): ?int
{
    global $db;

    // Retrieve template configuration id
    $db->query(
        "SELECT a.idtplcfg AS idtplcfg FROM `%s` AS a, `%s` AS b WHERE a.idcat = %d AND "
        . "a.idlang = %d AND b.idcat = a.idcat AND b.idclient = %d",
        cDb::getTableName('cat_lang'),
        cDb::getTableName('cat'),
        $idcat,
        $lang,
        $client
    );

    return $db->nextRecord() ? cSecurity::toInteger($db->f('idtplcfg')) : NULL;
}
