<?php

/**
 * This file contains CONTENIDO content functions.
 *
 * Please add only stuff which is relevant for the frontend
 * AND the backend. This file should NOT contain any backend editing
 * functions to improve frontend performance:
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Willi Man
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Generates the code for one article
 *
 * @param int      $idcat
 *                           Id of category
 * @param int      $idart
 *                           Id of article
 * @param int      $lang
 *                           Id of language
 * @param int      $client
 *                           Id of client
 * @param bool     $layout   [optional]
 *                           Layout-ID of alternate Layout (if false, use associated layout)
 * @param bool     $save     [optional]
 *                           Flag to persist generated code in database
 * @param bool     $contype  [optional]
 *                           Flag to enable/disable replacement of CMS_TAGS[].
 * @param bool     $editable [optional]
 *                           deprecated?
 * @param int|NULL $version  [optional]
 *                           version number if article is a revision, else NULL;
 *
 * @return string
 *         The generated code or "0601" if neither article
 *         nor category configuration was found.
 * 
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function conGenerateCode($idcat, $idart, $lang, $client, $layout = false, $save = true, $contype = true, $editable = false, $version = NULL) {
    global $cfg, $frontend_debug;

    // @todo make generator configurable
    $codeGen = cCodeGeneratorFactory::getInstance($cfg['code_generator']['name']);
    if (isset($frontend_debug) && is_array($frontend_debug)) {
        $codeGen->setFrontendDebugOptions($frontend_debug);
    }

    $code = $codeGen->generate($idcat, $idart, $lang, $client, $layout, $save, $contype, $editable, $version);

    // execute CEC hook
    $code = cApiCecHook::executeAndReturn('Contenido.Content.conGenerateCode', $code);

    return $code;
}

/**
 * Returns the idartlang for a given article and language
 *
 * @param int $idart
 *         ID of the article
 * @param int $idlang
 *         ID of the language
 *
 * @return mixed
 *         idartlang of the article or false if nothing was found
 * 
 * @throws cDbException
 */
function getArtLang($idart, $idlang) {
    $oArtLangColl = new cApiArticleLanguageCollection();
    $idartlang = $oArtLangColl->getIdByArticleIdAndLanguageId($idart, $idlang);
    return ($idartlang) ? $idartlang : false;
}

/**
 * Returns all available meta tag types
 *
 * @return array
 *         Assoziative meta tags list
 * 
 * @throws cDbException
 * @throws cException
 */
function conGetAvailableMetaTagTypes() {
    $oMetaTypeColl = new cApiMetaTypeCollection();
    $oMetaTypeColl->select();
    $aMetaTypes = array();

    while (($oMetaType = $oMetaTypeColl->next()) !== false) {
        $rs = $oMetaType->toArray();
        $aMetaTypes[$rs['idmetatype']] = array(
            'metatype' => $rs['metatype'],
            'fieldtype' => $rs['fieldtype'],
            'maxlength' => $rs['maxlength'],
            'fieldname' => $rs['fieldname'],
            'idmetatype' => $rs["idmetatype"]
        );
    }

    return $aMetaTypes;
}

/**
 * Get the meta tag value or its version for a specific article
 *
 * @param int $idartlang
 *         ID of the article
 * @param int $idmetatype
 *         Metatype-ID
 * @param int $version
 *         version number
 *
 * @return string
 * 
 * @throws cDbException
 * @throws cException
 */
function conGetMetaValue($idartlang, $idmetatype, $version  = NULL) {
    static $oMetaTagColl = NULL;
    static $metaTagVersionColl = NULL;

    if ($version ==  NULL) {
        if (!isset($oMetaTagColl)) {
            $oMetaTagColl = new cApiMetaTagCollection();
        }

        if ((int) $idartlang <= 0) {
            return '';
        }

        $oMetaTag = $oMetaTagColl->fetchByArtLangAndMetaType($idartlang, $idmetatype);
        if (is_object($oMetaTag)) {
            return stripslashes($oMetaTag->get('metavalue'));
        } else {
            return '';
        }
    } else if (is_numeric ($version)) {
        if (!isset($metaTagVersionColl)) {
            $metaTagVersionColl = new cApiMetaTagVersionCollection();
        }

        if ((int) $idartlang <= 0) {
            return '';
        }

        $metaTagVersion = $metaTagVersionColl->fetchByArtLangMetaTypeAndVersion($idartlang, $idmetatype, $version);
        if (is_object($metaTagVersion)) {
            return stripslashes($metaTagVersion->get('metavalue'));
        } else {
            return '';
        }

    }
}

/**
 * Set the meta tag value or its version for a specific article.
 *
 * @param int    $idartlang
 *         ID of the article
 * @param int    $idmetatype
 *         Metatype-ID
 * @param string $value
 *         Value of the meta tag
 * @param int    $version
 *         version number
 *
 * @return bool
 *         whether the meta value has been saved successfully
 * 
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function conSetMetaValue($idartlang, $idmetatype, $value, $version = NULL) {
    static $metaTagColl = NULL;
    //$ids[] = array ();
    $versioning = new cContentVersioning();

    if (!isset($metaTagColl)) {
        $metaTagColl = new cApiMetaTagCollection();
    }
    //echo "version0:";var_export($version);
    $metaTag = $metaTagColl->fetchByArtLangAndMetaType($idartlang, $idmetatype);

    // check if the original version already has been saved
    //$where = 'idartlang = ' . $idartlang . ' AND idmetatype = ' . $idmetatype . '';
    //$metaTagVersionColl = new cApiMetaTagVersionCollection();
    //$ids = $metaTagVersionColl->getIdsByWhereClause($where);

    switch ($versioning->getState()) {
        case 'simple':
            // if its only a robot-update, only update and don't create a version
            if ($version == NULL) {
                if (is_object($metaTag)) {
                    $return = $metaTag->updateMetaValue($value);
                    return $return;
                } else {
                    $metaTag = $metaTagColl->create($idartlang, $idmetatype, $value);
                    return true;
                }
            }

            // get metatagid
            if (is_object($metaTag)) {
                $idmetatag = $metaTag->get('idmetatag');
                //$valueTemp = $metaTag->getField('value');
            }

            // safe original version if nothing has been versioned yet
            // foreach ($ids AS $key => $id) {
            //     $metaTagTemp = new cApiMetaTagVersion();
            //     if ($metaTagTemp->getField('idmetatype') == 7) {
            //         unset($ids[$key]);
            //     }
            // }
            //
            // if (empty($ids)) {
            //         $metaTagVersionParameters = array(
            //             'idmetatag' => $idmetatag,
            //             'idartlang' => $idartlang,
            //             'idmetatype' => $idmetatype,
            //             'value' => $valueTemp,
            //             'version' => $version
            //         );
            //         $versioning->createMetaTagVersion($metaTagVersionParameters);
            //
            //         // create new article version for the change
            //         $artLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
            //         $artLangVersion = $versioning->createArticleLanguageVersion($artLang->toArray());
            //         $version = $artLangVersion->getField('version');
            // }
            // echo "version1:";var_export($version);
            
            // update article
            $artLang = new cApiArticleLanguage($idartlang);
            $artLang->set('lastmodified', date('Y-m-d H:i:s'));
            $artLang->store();
            // update or create meta tag
            if (is_object($metaTag)) {
                $return = $metaTag->updateMetaValue($value);

            } else {
                $metaTag = $metaTagColl->create($idartlang, $idmetatype, $value);
            }

            // create meta tag version
            $metaTagVersionParameters = array(
                'idmetatag' => $idmetatag,
                'idartlang' => $idartlang,
                'idmetatype' => $idmetatype,
                'value' => $value,
                'version' => $version
            );
            $versioning->createMetaTagVersion($metaTagVersionParameters);
            //echo "version2:";var_export($version);echo "<hr>";
            break;
        case 'disabled':
            // update article
            $artLang = new cApiArticleLanguage($idartlang);
            $artLang->set('lastmodified', date('Y-m-d H:i:s'));
            $artLang->store();
            //update meta tag
            if (is_object($metaTag)) {
                $return = $metaTag->updateMetaValue($value);
                return $return;

            } else {
                $metaTag = $metaTagColl->create($idartlang, $idmetatype, $value);
                return true;

            }

            break;
        case 'advanced':
            if ($version == NULL) {
                if (is_object($metaTag)) {
                    $return = $metaTag->updateMetaValue($value);
                } else {
                    $metaTag = $metaTagColl->create($idartlang, $idmetatype, $value);
                }
                $version = 1;
            }

            if (is_object($metaTag)) {
                $idmetatag = $metaTag->get('idmetatag');
            }
            $metaTagVersionParameters = array(
                'idmetatag' => $idmetatag,
                'idartlang' => $idartlang,
                'idmetatype' => $idmetatype,
                'value' => $value,
                'version' => $version
            );
            $versioning->createMetaTagVersion($metaTagVersionParameters);

            break;
        default:
            break;

    }

}

/**
 * (Re-)generate keywords for all articles of a given client (with specified language)
 *
 * @param int $client
 *         Client
 * @param int $lang
 *         Language of a client
 *
 * @throws cDbException
 * @throws cInvalidArgumentException
 * 
 * @deprecated [2014-07-24]
 *         Not used anymore
 */
function conGenerateKeywords($client, $lang) {
    $cfg = cRegistry::getConfig();

    static $oDB = NULL;
    if (!isset($oDB)) {
        $oDB = cRegistry::getDb();
    }

    // cms types to be excluded from indexing
    $options = $cfg['search_index']['excluded_content_types'];

    $sql = 'SELECT a.idart, b.idartlang FROM ' . $cfg['tab']['art'] . ' AS a, ' . $cfg['tab']['art_lang'] . ' AS b
            WHERE a.idart=b.idart AND a.idclient=' . (int) $client . ' AND b.idlang=' . (int) $lang;

    $oDB->query($sql);

    $aArticles = array();
    while ($oDB->nextRecord()) {
        $aArticles[$oDB->f('idart')] = $oDB->f('idartlang');
    }

    foreach ($aArticles as $artid => $artlangid) {
        $aContent = conGetContentFromArticle($artlangid);
        if (count($aContent) > 0) {
            $oIndex = new cSearchIndex($oDB);
            $oIndex->start($artid, $aContent, 'auto', $options);
        }
    }
}

/**
 * Get content from article by article language.
 *
 * @param int $iIdArtLang
 *         ArticleLanguageId of an article (idartlang)
 *
 * @return array
 *         Array with content of an article indexed by content-types as follows:
 *         - $arr[type][typeid] = value;
 * 
 * @throws cDbException
 */
function conGetContentFromArticle($iIdArtLang) {
    global $cfg;

    static $oDB = NULL;
    if (!isset($oDB)) {
        $oDB = cRegistry::getDb();
    }

    $aContent = array();

    $sql = 'SELECT * FROM ' . $cfg['tab']['content'] . ' AS A, ' . $cfg['tab']['art_lang'] . ' AS B, ' . $cfg['tab']['type'] . ' AS C
            WHERE A.idtype=C.idtype AND A.idartlang=B.idartlang AND A.idartlang=' . (int) $iIdArtLang;
    $oDB->query($sql);
    while ($oDB->nextRecord()) {
        $aContent[$oDB->f('type')][$oDB->f('typeid')] = $oDB->f('value');
    }

    return $aContent;
}

/**
 * Returns list of all container with configured modules by template id
 *
 * @param int $idtpl
 *         Template id
 *
 * @return array
 *         Assoziative array where the key is the number and value the module id
 * 
 * @throws cDbException
 * @throws cException
 */
function conGetUsedModules($idtpl) {
    $modules = array();

    $oContainerColl = new cApiContainerCollection();
    $oContainerColl->select('idtpl = ' . (int) $idtpl, '', 'number ASC');
    while (($oContainer = $oContainerColl->next()) !== false) {
        $modules[(int) $oContainer->get('number')] = (int) $oContainer->get('idmod');
    }

    return $modules;
}

/**
 * Returns list of all configured container configurations by template configuration id
 *
 * @param int $idtplcfg
 *         Template configuration id
 *
 * @return array
 *         Assoziative array where the key is the number
 *         and value the container configuration.
 * 
 * @throws cDbException
 * @throws cException
 */
function conGetContainerConfiguration($idtplcfg) {
    $containerConfColl = new cApiContainerConfigurationCollection();
    return $containerConfColl->getByTemplateConfiguration($idtplcfg);
}

/**
 * Returns category article id
 *
 * @param int $idcat
 * @param int $idart
 *
 * @return int|NULL
 * 
 * @throws cDbException
 */
function conGetCategoryArticleId($idcat, $idart) {
    global $cfg, $db;

    // Get idcatart, we need this to retrieve the template configuration
    $sql = 'SELECT idcatart FROM `%s` WHERE idcat = %d AND idart = %d';
    $sql = $db->prepare($sql, $cfg['tab']['cat_art'], $idcat, $idart);
    $db->query($sql);

    return ($db->nextRecord()) ? $db->f('idcatart') : NULL;
}

/**
 * Returns template configuration id for a configured article.
 *
 * @param int $idart
 * @param int $idcat
 *         NOT used
 * @param int $lang
 * @param int $client
 *
 * @return int|NULL
 * 
 * @throws cDbException
 */
function conGetTemplateConfigurationIdForArticle($idart, $idcat, $lang, $client) {
    global $cfg, $db;

    // Retrieve template configuration id
    $sql = "SELECT a.idtplcfg AS idtplcfg FROM `%s` AS a, `%s` AS b WHERE a.idart = %d "
         . "AND a.idlang = %d AND b.idart = a.idart AND b.idclient = %d";
    $sql = $db->prepare($sql, $cfg['tab']['art_lang'], $cfg['tab']['art'], $idart, $lang, $client);
    $db->query($sql);

    return ($db->nextRecord()) ? $db->f('idtplcfg') : NULL;
}

/**
 * Returns template configuration id for a configured category
 *
 * @param int $idcat
 * @param int $lang
 * @param int $client
 *
 * @return int|NULL
 * 
 * @throws cDbException
 */
function conGetTemplateConfigurationIdForCategory($idcat, $lang, $client) {
    global $cfg, $db;

    // Retrieve template configuration id
    $sql = "SELECT a.idtplcfg AS idtplcfg FROM `%s` AS a, `%s` AS b WHERE a.idcat = %d AND "
         . "a.idlang = %d AND b.idcat = a.idcat AND b.idclient = %d";
    $sql = $db->prepare($sql, $cfg['tab']['cat_lang'], $cfg['tab']['cat'], $idcat, $lang, $client);
    $db->query($sql);

    return ($db->nextRecord()) ? $db->f('idtplcfg') : NULL;
}
