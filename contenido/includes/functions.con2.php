<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Content Functions
 *
 * Requirements:
 * @con_php_req 5.0
 * @con_notice Please add only stuff which is relevant for the frontend
 *             AND the backend. This file should NOT contain any backend editing
 *             functions to improve frontend performance:
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.8
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-12-15
 *   modified 2008-06-25, Timo Trautmann, user meta tags and system meta tags were merged, not replaced
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-08-29, Murat Purc, add new chain execution
 *   modified 2009-03-27, Andreas Lindner, Add title tag generation via chain
 *   modified 2009-10-29, Murat Purc, removed deprecated functions (PHP 5.3 ready)
 *   modified 2009-12-18, Murat Purc, fixed meta tag generation, see [#CON-272]
 *   modified 2009-10-27, Murat Purc, fixed/modified CEC_Hook, see [#CON-256]
 *   modified 2010-10-11, Dominik Ziegler, display only major and minor version of version number
 *   modified 2010-12-09, Dominik Ziegler, fixed multiple replacements of title tags [#CON-373]
 *   modified 2011-01-11, Rusmir Jusufovic
 *       - load input and output of moduls from files
 *   modified 2011-06-24, Rusmir Jusufovic , load layout code from file
 *   modified 2011-07-20, Murat Purc, partly refactored function conGenerateCode(), cleanup, documenting and formatting.
 *   modified 2011-08-11, Murat Purc, code generation replaced by new Contenido_CodeGenerator implementation
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Generates the code for one article
 *
 * @param int $idcat Id of category
 * @param int $idart Id of article
 * @param int $lang Id of language
 * @param int $client Id of client
 * @param int $layout Layout-ID of alternate Layout (if false, use associated layout)
 * @param bool $save  Flag to persist generated code in database
 * @return string The generated code or "0601" if neither article nor category configuration
 *                was found
 */
function conGenerateCode($idcat, $idart, $lang, $client, $layout = false, $save = true, $contype = true)
{
    global $frontend_debug;

    // @todo make generator configurable
    $codeGen = cCodeGeneratorFactory::getInstance('Standard');
    if (isset($frontend_debug) && is_array($frontend_debug)) {
        $codeGen->setFrontendDebugOptions($frontend_debug);
    }

    $code = $codeGen->generate($idcat, $idart, $lang, $client, $layout, $save, $contype);

    // execute CEC hook
    $code = CEC_Hook::executeAndReturn('Contenido.Content.conGenerateCode', $code);

    return $code;
}


/**
 * Returns the idartlang for a given article and language
 *
 * @param  int  $idart ID of the article
 * @param  int  $idlang ID of the language
 * @return mixed idartlang of the article or false if nothing was found
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function getArtLang($idart, $idlang)
{
    $oArtLangColl = new cApiArticleLanguageCollection();
    $idartlang = $oArtLangColl->getIdByArticleIdAndLanguageId($idart, $idlang);
    return ($idartlang) ? $idartlang : false;
}


/**
 * Returns all available meta tag types
 *
 * @return  array  Assoziative meta tags list
 */
function conGetAvailableMetaTagTypes()
{
    $oMetaTypeColl = new cApiMetaTypeCollection();
    $oMetaTypeColl->select();
    $aMetaTypes = array();

    while ($oMetaType = $oMetaTypeColl->next()) {
        $rs = $oMetaType->toArray();
        $aMetaTypes[$rs['idmetatype']] = array(
            'metatype' => $rs['metatype'],
            'fieldtype' => $rs['fieldtype'],
            'maxlength' => $rs['maxlength'],
            'fieldname' => $rs['fieldname'],
        );
    }

    return $aMetaTypes;
}


/**
 * Get the meta tag value for a specific article
 *
 * @param int $idartlang ID of the article
 * @param int $idmetatype Metatype-ID
 * @return  string
 */
function conGetMetaValue($idartlang, $idmetatype)
{
    static $oMetaTagColl;
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
}


/**
 * Set the meta tag value for a specific article.
 *
 * @param  int  $idartlang ID of the article
 * @param  int  $idmetatype Metatype-ID
 * @param  string  $value Value of the meta tag
 */
function conSetMetaValue($idartlang, $idmetatype, $value)
{
    static $oMetaTagColl;
    if (!isset($oMetaTagColl)) {
        $oMetaTagColl = new cApiMetaTagCollection();
    }

    $oMetaTag = $oMetaTagColl->fetchByArtLangAndMetaType($idartlang, $idmetatype);
    if (is_object($oMetaTag)) {
        $oMetaTag->updateMetaValue($value);
    } else {
        $oMetaTagColl->create($idartlang, $idmetatype, $value);
    }
}


/**
 * (re)generate keywords for all articles of a given client (with specified language)
 * @param int $client Client
 * @param int $lang Language of a client
 * @return void
 *
 * @author Willi Man
 * Created   :   12.05.2004
 * Modified  :   13.05.2004
 * @copyright four for business AG 2003
 */
function conGenerateKeywords($client, $lang)
{
    global $cfg;

    static $oDB;
    if (!isset($oDB)) {
        $oDB = cRegistry::getDb();
    }

    $options = array('img', 'link', 'linktarget', 'swf'); // cms types to be excluded from indexing

    $sql = 'SELECT a.idart, b.idartlang FROM ' . $cfg['tab']['art'] . ' AS a, ' . $cfg['tab']['art_lang'] . ' AS b
            WHERE a.idart=b.idart AND a.idclient=' . (int) $client . ' AND b.idlang=' . (int) $lang;

    $oDB->query($sql);

    $aArticles = array();
    while ($oDB->next_record()) {
        $aArticles[$oDB->f('idart')] = $oDB->f('idartlang');
    }

    foreach ($aArticles as $artid => $artlangid) {
        $aContent = conGetContentFromArticle($artlangid);
        if (count($aContent) > 0) {
            $oIndex = new SearchIndex($oDB);
            $oIndex->lang = $lang;
            $oIndex->start($artid, $aContent, 'auto', $options);
        }
    }
}


/**
 * Get content from article by article language.
 * @param int $iIdArtLang ArticleLanguageId of an article (idartlang)
 * @return array Array with content of an article indexed by content-types as follows:
 *               - $arr[type][typeid] = value;
 *
 * @author Willi Man
 * Created   :   12.05.2004
 * Modified  :   13.05.2004
 * @copyright four for business AG 2003
 */
function conGetContentFromArticle($iIdArtLang)
{
    global $cfg;

    static $oDB;
    if (!isset($oDB)) {
        $oDB = cRegistry::getDb();
    }

    $aContent = array();

    $sql = 'SELECT * FROM ' . $cfg['tab']['content'] . ' AS A, ' . $cfg['tab']['art_lang'] . ' AS B, ' . $cfg['tab']['type'] . ' AS C
            WHERE A.idtype=C.idtype AND A.idartlang=B.idartlang AND A.idartlang='. (int) $iIdArtLang;
    $oDB->query($sql);
    while ($oDB->next_record()) {
        $aContent[$oDB->f('type')][$oDB->f('typeid')] = $oDB->f('value');
    }

    return $aContent;
}


/**
 * Returns list of all used modules by template id
 *
 * @param  int $idtpl  Template id
 * @return  array  Assoziative array where the key is the number and value the module id
 */
function conGetUsedModules($idtpl)
{
    global $cfg, $db;

    // List of used modules
    $modules = array();
    $sql = 'SELECT number, idmod FROM ' . $cfg['tab']['container'] . '
            WHERE idtpl=' . (int) $idtpl . ' ORDER BY number ASC';
    $db->query($sql);
    while ($db->next_record()) {
        $modules[(int) $db->f('number')] = (int) $db->f('idmod');
    }
    return $modules;
}

/**
 * Returns list of all configured container by template configuration id
 *
 * @param  int  $idtplcfg  Template configuration id
 * @return  array  Assoziative array where the key is the number and value the container
 *                 configuration
 */
function conGetContainerConfiguration($idtplcfg)
{
    global $cfg, $db;

    $configuration = array();
    $sql = 'SELECT * FROM ' . $cfg['tab']['container_conf'] . '
             WHERE idtplcfg=' . (int) $idtplcfg . ' ORDER BY number ASC';
    $db->query($sql);
    while ($db->next_record()) {
        $configuration[$db->f('number')] = $db->f('container');
    }
    return $configuration;
}


/**
 * Returns category article id
 *
 * @param  int  $idcat
 * @param  int  $idart
 * @return  int|null
 */
function conGetCategoryArticleId($idcat, $idart)
{
    global $cfg, $db;

    // get idcatart, we need this to retrieve the template configuration
    $sql = 'SELECT idcatart FROM ' . $cfg['tab']['cat_art'] . '
            WHERE idcat=' . (int) $idcat . ' AND idart = ' . (int) $idart;
    $db->query($sql);
    $db->next_record();
    return $db->f('idcatart');
}

/**
 * Returns template configuration id for a configured article.
 *
 * @param  int  $idart
 * @param  int  $idcat
 * @param  int  $lang
 * @param  int  $client
 * @return  int|null
 */
function conGetTemplateConfigurationIdForArticle($idart, $idcat, $lang, $client)
{
    global $cfg, $db;

    // retrieve template configuration id
    $sql = "SELECT a.idtplcfg AS idtplcfg
            FROM " . $cfg["tab"]["art_lang"] . " AS a, " . $cfg["tab"]["art"] . " AS b
            WHERE a.idart = " . (int) $idart . " AND a.idlang = " . (int) $lang . " AND
                  b.idart = a.idart AND b.idclient = " . (int) $client;

    $db->query($sql);
    $db->next_record();

    return ($db->f('idtplcfg') != 0) ? $db->f('idtplcfg') : null;
}

/**
 * Returns template configuration id for a configured category
 *
 * @param  int  $idcat
 * @param  int  $lang
 * @param  int  $client
 * @return  int|null
 */
function conGetTemplateConfigurationIdForCategory($idcat, $lang, $client)
{
    global $cfg, $db;

    // Check whether category is configured
    $sql = "SELECT a.idtplcfg AS idtplcfg
            FROM " . $cfg["tab"]["cat_lang"] . " AS a, " . $cfg["tab"]["cat"] . " AS b
            WHERE a.idcat = " . (int) $idcat . " AND a.idlang = " . (int) $lang . " AND
                b.idcat = a.idcat AND b.idclient = " . (int) $client;

    $db->query($sql);
    $db->next_record();

    return ($db->f('idtplcfg') != 0) ? $db->f('idtplcfg') : null;
}

?>