<?php
/**
 * This file contains the article language version collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Jann Dieckmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Article language version collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiArticleLanguageVersionCollection extends cApiArticleLanguageCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select
     *         where clause to use for selection
     * @see ItemCollection::select()
     */
    public function __construct($select = false) {

    	$sTable = cRegistry::getDbTableName('art_lang_version');
    	$sPrimaryKey = 'idartlangversion';
    	ItemCollection::__construct($sTable, $sPrimaryKey);

        $this->_setItemClass('cApiArticleLanguageVersion');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiArticleCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates an article language item entry.
     *
     * @global object $auth
     * @param mixed[] $parameters{
     *  @type int $idart
     *  @type int $idlang
     *  @type int $idartlang
     *  @type string $title
     *  @type string $urlname
     *  @type string $pagetitle
     *  @type string $summary
     *  @type int $artspec
     *  @type string $created
     *  @type int $iscurrentverseion
     *  @type string $author
     *  @type string $lastmodified
     *  @type string $modifiedby
     *  @type string $published
     *  @type string $publishedby
     *  @type int $online
     *  @type int $redirect
     *  @type string $redirect_url
     *  @type int $external_redirect
     *  @type int $artsort
     *  @type int $timemgmt
     *  @type string $datestart
     *  @type string $dateend
     *  @type int $status
     *  @type int $time_move_cat
     *  @type int $time_target_cat
     *  @type int $time_online_move
     *  @type int $locked
     *  @type mixed $free_use_01
     *  @type mixed $free_use_02
     *  @type mixed $free_use_03
     *  @type int $searchable
     *  @type float $sitemapprio
     *  @type string $changefreq
     * }
     * @return cApiArticleLanguageVersion
     */
    public function create(array $parameters) {
        global $auth;

        if (empty($parameters['author'])) {
            $parameters['author'] = $auth->auth['uname'];
        }
        if (empty($parameters['created'])) {
            $parameters['created'] = date('Y-m-d H:i:s');
        }
        if (empty($parameters['lastmodified'])) {
            $parameters['lastmodified'] = date('Y-m-d H:i:s');
        }

        $parameters['urlname'] = (trim($parameters['urlname']) == '') ? trim($parameters['title']) : trim($parameters['urlname']);

        // set version
        $parameters['version'] = 1;
        $sql = 'SELECT MAX(version) AS maxversion FROM ' . cRegistry::getDbTableName('art_lang_version') . ' WHERE idartlang = %d;';
        $sql = $this->db->prepare($sql, $parameters['idartlang']);
        $this->db->query($sql);
        if ($this->db->nextRecord()) {
            $parameters['version'] = $this->db->f('maxversion');
            ++$parameters['version'];
        }

        $item = $this->createNewItem();

        // populate item w/ values
        foreach (array_keys($parameters) as $key) {
            // skip columns idcontent & version
            if ($key == 'iscurrentversion') {
                continue;
            }
            $item->set($key, $parameters[$key]);
        }
        $item->markAsCurrentVersion($parameters['iscurrentversion']);
        $item->store();

        return $item;
    }

    /**
     * Returns id (idartlangversion) of articlelanguageversion by article
     * language id and version
     *
     * @param int $idArtLang
     * @param int $version
     * @return int
     */
    public function getIdByArticleIdAndLanguageId($idArtLang, $version) {

        $id = NULL;

        $where = 'idartlang = ' . $idArtLang . ' AND version = ' . $version;

        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersionColl->select($where);

        while($item = $artLangVersionColl->next()){
            $id = $item->get('idartlangversion');
        }

        return isset($id) ? $id : 0;

    }
}

/**
 * CONTENIDO API - Article Version Object
 *
 * This object represents a CONTENIDO article version
 *
 * Create object with
 * $obj = new cApiArticleLanguageVersion(idartlangversion);
 * or with
 * $obj = new cApiArticleLanguageVersion();
 * $obj->loadByArticleLanguageIdAndVersion(idartlang, version);
 *
 * You can now read the article version properties with
 * $obj->getField(property);
 *
 * List of article version properties:
 *
 * idartlang - Language dependant article id
 * idart - Language indepenant article id
 * idclient - Id of the client
 * idtplcfg - Template configuration id
 * title - Internal Title
 * pagetitle - HTML Title
 * summary - Article summary
 * created - Date created
 * version - Version number
 * iscurrentversion - 0 = false, 1 = true
 * lastmodified - Date lastmodiefied
 * author - Article author (username)
 * online - On-/offline
 * redirect - Redirect
 * redirect_url - Redirect URL
 * artsort - Article sort key
 * timemgmt - Time management
 * datestart - Time management start date
 * dateend - Time management end date
 * status - Article status
 * free_use_01 - Free to use
 * free_use_02 - Free to use
 * free_use_03 - Free to use
 * time_move_cat - Move category after time management
 * time_target_cat - Move category to this cat after time management
 * time_online_move - Set article online after move
 * external_redirect - Open article in new window
 * locked - Article is locked for editing
 * searchable - Whether article should be found via search
 * sitemapprio - The priority for the sitemap
 *
 * You can extract article version content with the
 * $obj->getContent(contype [, number]) method.
 *
 * To extract the first headline you can use:
 *
 * $headline = $obj->getContent("htmlhead", 1);
 *
 * If the second parameter is ommitted the method returns an array with all
 * available
 * content of this type. The array has the following schema:
 *
 * array(number => content);
 *
 * $headlines = $obj->getContent("htmlhead");
 *
 * $headlines[1] First headline
 * $headlines[2] Second headline
 * $headlines[6] Sixth headline
 *
 * Legal content type string are defined in the CONTENIDO system table
 * 'con_type'.
 * Default content types are:
 *
 * NOTE: This parameter is case insesitive, you can use html or cms_HTML or
 * CmS_HtMl.
 * Your don't need start with cms, but it won't crash if you do so.
 *
 * htmlhead - HTML Headline
 * html - HTML Text
 * headline - Headline (no HTML)
 * text - Text (no HTML)
 * img - Upload id of the element
 * imgdescr - Image description
 * link - Link (URL)
 * linktarget - Linktarget (_self, _blank, _top ...)
 * linkdescr - Linkdescription
 * swf - Upload id of the element
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiArticleLanguageVersion extends cApiArticleLanguage {

    /**
     * Config array
     *
     * @var array
     */
    public $tab;

    /**
     * Article Version content
     *
     * @var array
     */
    public $content = NULL;

    /**
     * Constructor Function
     *
     * @param mixed $id
     *         Specifies the ID of item to load
     * @param bool $fetchContent
     *         Flag to fetch content
     */
    public function __construct($id = false, $fetchContent = false) {

    	$sTable = cRegistry::getDbTableName('art_lang_version');
    	$sPrimaryKey = 'idartlangversion';
    	Item::__construct($sTable, $sPrimaryKey);

        $this->setFilters(array(), array());
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
            if (true === $fetchContent) {
                $this->_getArticleVersionContent();
            }
        }
    }

    /**
     * Set iscurrentversion = 0 in the current version and set iscurrentversion = 1 in this version
     *
     * @param int $iscurrentversion
     *         0 = false, 1 = true
     */
    public function markAsCurrentVersion($isCurrentVersion){
        $attributes = array(
            'idartlang' => $this->get('idartlang'),
            'iscurrentversion' => $isCurrentVersion
        );
        if ($isCurrentVersion == 1) {
            $artLangVersion = new cApiArticleLanguageVersion();
            if ($artLangVersion->loadByMany($attributes)) {
                $artLangVersion->set('iscurrentversion', 0);
                $artLangVersion->store();
            }
            $this->set('iscurrentversion', 1);
        } else {
            $this->set('iscurrentversion', 0);
        }
        $this->store();

    }

    /**
     * Set this ArticleVersion with its ContentVersions as current:
     * Copy data from this ArticleLanguageVersion to ArticleLanguage
     * Update Contents in ArticleLanguage
     * Set property iscurrentversion = 1 in this ArticleLanguageVersion
     * and 0 in the current ArticleLanguageVersions
     *
     * @param string $type
     *         meta, content or complete
     */
    public function markAsCurrent($type = ''){

        if ($type == 'complete') {
           // Prepare data and update ArticleLanguage
            $parameters = $this->toArray();
            $artLang = new cApiArticleLanguage($parameters['idartlang']);
            unset($parameters['idartlang']);
            unset($parameters['idartlangversion']);
            unset($parameters['iscurrentversion']);
            unset($parameters['version']);
            foreach ($parameters as $key => $value) {
                $artLang->set($key, $value);
            }
            $artLang->store();
        }

        if ($type == 'content' || $type == 'complete') {

            $where = 'idartlang = ' . $this->get('idartlang');
            $contentVersionColl = new cApiContentVersionCollection();

            // Update Contents if contents are versioned
            $contents = $contentVersionColl->getIdsByWhereClause($where);
            if (isset($contents)) {
                $sql = 'SELECT a.idcontent
                        FROM `%s` AS a
                        WHERE a.idartlang = %d AND a.idcontent NOT IN
                            (SELECT DISTINCT b.idcontent
                            FROM `%s` AS b
                            WHERE (b.deleted < 1 OR b.deleted IS NULL)
                            AND (b.idtype, b.typeid, b.version) IN
                                (SELECT idtype, typeid, max(version)
                                FROM `%s`
                                WHERE idartlang = %d AND version <= %d
                                GROUP BY idtype, typeid))';
                $this->db->query(
                    $sql,
                    cRegistry::getDbTableName('content'),
                    $this->get('idartlang'),
                    cRegistry::getDbTableName('content_version'),
                    cRegistry::getDbTableName('content_version'),
                    $this->get('idartlang'), $this->get('version')
                );
                $contentColl = new cApiContentCollection();
                while ($this->db->nextRecord()) {
                    $contentColl->delete($this->db->f('idcontent'));
                }
                $contentVersion = new cApiContentVersion();
                $ctype = new cApiType();
                $this->loadArticleVersionContent();
                foreach ($this->content AS $typeName => $typeids) {
                    foreach ($typeids AS $typeid => $value) {
                        $ctype->loadByType($typeName);
                        $contentParameters = array(
                            'idartlang' => $this->get('idartlang'),
                            'idtype' => $ctype->get('idtype'),
                            'typeid' => $typeid,
                            'version' => $this->get('version')
                        );
                        $contentVersion->loadByArticleLanguageIdTypeTypeIdAndVersion($contentParameters);
                        $contentVersion->markAsCurrent();
                    }
                }
            }
        }

        if ($type == 'meta' || $type == 'complete') {

            // mark meta tags versions as current
            $metaTagVersion = new cApiMetaTagVersion();
            $sql = 'SELECT idmetatagversion AS id
                    FROM `%s`
                    WHERE idartlang = %d AND version IN (
                        SELECT max(version)
                        FROM `%s`
                        WHERE idartlang = %d AND version <= %d)';
            $this->db->query(
                $sql,
                cRegistry::getDbTableName('meta_tag_version'),
                $this->get('idartlang'),
                cRegistry::getDbTableName('meta_tag_version'),
                $this->get('idartlang'),
                $this->get('version')
            );
            while ($this->db->nextRecord()) {
                    $metaTagVersionIds[] = $this->db->f('id');
            }
            if (isset($metaTagVersionIds)) {
                foreach ($metaTagVersionIds AS $id) {
                    $metaTagVersion->loadBy('idmetatagversion', $id);
                    $metaTagVersion->markAsCurrent();
                }
            }
        }

        // Set this ArticleVersion as current and make article index
        $this->markAsCurrentVersion(1);
        conMakeArticleIndex($this->get('idartlang'), $this->get('idart'));
        $purge = new cSystemPurge();
        $purge->clearArticleCache($this->get('idartlang'));
    }

    /**
     * Create a copy of this article language version with its contents,
     * the copy is the new editable article language version
     *
     * @param string $type
     *         meta, content or complete
     */
    public function markAsEditable($type = '') {

        // create new editable Version
        $parameters = $this->toArray();
        $parameters['lastmodified'] = date('Y-m-d H:i:s');
        unset($parameters['idartlangversion']);
        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersion = $artLangVersionColl->create($parameters);

        if ($type == 'content' || $type == 'complete') {
            $artLangVersion->loadArticleVersionContent();
            $contentVersion = new cApiContentVersion();
            $apiType = new cApiType();
            $this->loadArticleVersionContent();

            // get all Content Versions
            $mergedContent = array();
            foreach ($this->content AS $typeName => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                    $mergedContent[$typeName][$typeid] = '';
                }
            }
            foreach ($artLangVersion->content AS $typeName => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                    $mergedContent[$typeName][$typeid] = '';
                }
            }
            // set new Content Versions
            foreach ($mergedContent AS $typeName => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                    $apiType->loadByType($typeName);
                    if (isset($this->content[$typeName][$typeid])) {
                        $contentParameters = array(
                            'idartlang' => $this->get('idartlang'),
                            'idtype' => $apiType->get('idtype'),
                            'typeid' => $typeid,
                            'version' => $this->get('version')
                        );
                        $contentVersion->loadByArticleLanguageIdTypeTypeIdAndVersion($contentParameters);

                        if (isset($contentVersion)) {
                            $contentVersion->markAsEditable($artLangVersion->get('version'), 0);
                        }
                    } else { // muss bleiben, um contents zu löschen;
                    //      vorsicht bei "als entwurf nutzen" wenn artikelversion jünger als contentversion
                        $contentParameters = array(
                            'idartlang' => $artLangVersion->get('idartlang'),
                            'idtype' => $apiType->get('idtype'),
                            'typeid' => $typeid,
                            'version' => $artLangVersion->get('version'),
                            'author' => $this->get('author'),
                            'deleted' => 1
                        );
                        $contentVersionColl = new cApiContentVersionCollection();
                        $contentVersionColl->create($contentParameters);
                    }
                }
            }
        }

        if ($type == 'meta' || $type == 'complete') {

            // set new meta tag versions
            $metaTagVersion = new cApiMetaTagVersion();
            $sql = 'SELECT idmetatagversion AS id
                    FROM `%s`
                    WHERE idartlang = %d AND version IN (
                        SELECT max(version)
                        FROM `%s`
                        WHERE idartlang = %d AND version <= %d);';
            $this->db->query(
                $sql,
                cRegistry::getDbTableName('meta_tag_version'),
                $this->get('idartlang'),
                cRegistry::getDbTableName('meta_tag_version'),
                $this->get('idartlang'),
                $this->get('version')
            );
            while ($this->db->nextRecord()) {
                    $metaTagVersionIds[] = $this->db->f('id');
            }
            if (!empty($metaTagVersionIds)) {
                foreach ($metaTagVersionIds AS $id) {
                    $metaTagVersion->loadBy('idmetatagversion', $id);
                    $metaTagVersion->markAsEditable($artLangVersion->get('version'));
                }
            } else  { // use published meta tags
                $metaTagColl = new cApiMetaTagCollection();
                $metaTag = new cApiMetaTag();
                $ids = $metaTagColl->getIdsByWhereClause('idartlang = ' . $this->get('idartlang'));
                foreach ($ids AS $id) {
                    $metaTag->loadByPrimaryKey($id);
                    $metaTag->markAsEditable($artLangVersion->get('version'));
                }
            }
        }

    }

    /**
     * Load data by article language id and version
     *
     * @param int $idArtLang
     *         Article language id
     * @param int $version
     *         version number
     * @param bool $fetchContent
     *         Flag to fetch content
     * @return bool
     *         true on success, otherwhise false
     */
    public function loadByArticleLanguageIdAndVersion($idArtLang, $version, $fetchContent = false) {
        $result = true;
        if (!$this->isLoaded()) {
            $props = array(
                'idartlang' => $idArtLang,
                'version' => $version
            );
            $recordSet = $this->_oCache->getItemByProperties($props);
            if ($recordSet) {
                // entry in cache found, load entry from cache
                $this->loadByRecordSet($recordSet);
            } else {
                $idArtLangVersion = $this->_getIdArtLangVersion($idArtLang, $version);
                $result = $this->loadByPrimaryKey($idArtLangVersion);
            }
        }

        if (true === $fetchContent) {
            $this->_getArticleVersionContent();
        }

        return $result;
    }

    /**
     * Extract 'idartlangversion' for a specified 'idartlang' and 'version'
     *
     * @param int $idArtLang
     *         Article language id
     * @param int $version
     *         version number
     * @return int
     *         Article language version id
     */
    protected function _getIdArtLangVersion($idArtLang, $version) {

        $id = NULL;

        $where = 'idartlang = ' . $idArtLang . ' AND version = ' . $version;

        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersionColl->select($where);

        while($item = $artLangVersionColl->next()){
            $id = $item->get('idartlangversion');
        }

        return isset($id) ? $id : 0;

    }

    /**
     * Load the articles version content and store it in the 'content' property of the
     * article version object: $article->content[type][number] = value;
     *
     */
    protected function _getArticleVersionContent() {

        if (NULL !== $this->content) {
            return;
        }

        $sql = 'SELECT b.type as type, a.typeid as typeid, a.value as value, a.version as version
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

        $this->db->query(
            $sql,
            cRegistry::getDbTableName('content_version'),
            cRegistry::getDbTableName('type'),
            cRegistry::getDbTableName('content_version'),
            $this->get('idartlang'), $this->get('version'),
            $this->get('idartlang')
        );

        $this->content = array();
        while ($this->db->nextRecord()) {
            $this->content[strtolower($this->db->f('type'))][$this->db->f('typeid')] = $this->db->f('value');
        }

    }

}
