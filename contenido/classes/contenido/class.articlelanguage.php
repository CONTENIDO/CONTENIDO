<?php

/**
 * This file contains the article language collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Article language collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiArticleLanguage>
 */
class cApiArticleLanguageCollection extends ItemCollection
{

    use cItemCollectionIdsByLanguageIdTrait;

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cDb::getTableName('art_lang'), 'idartlang');
        $this->_setItemClass('cApiArticleLanguage');

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
     * @return cApiArticleLanguage
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(array $parameters)
    {
        if (empty($parameters['author'])) {
            $auth = cRegistry::getAuth();
            $parameters['author'] = $auth->getUsername();
        }
        if (empty($parameters['created'])) {
            $parameters['created'] = date('Y-m-d H:i:s');
        }
        if (empty($parameters['lastmodified'])) {
            $parameters['lastmodified'] = date('Y-m-d H:i:s');
        }

        $parameters['urlname'] = (trim($parameters['urlname']) == '') ? trim($parameters['title']) : trim($parameters['urlname']);

        $item = $this->createNewItem();

        $item->set('idart', $parameters['idart']);
        $item->set('idlang', $parameters['idlang']);
        $item->set('title', $parameters['title']);
        $item->set('urlname', $parameters['urlname']);
        $item->set('pagetitle', $parameters['pagetitle']);
        $item->set('summary', $parameters['summary']);
        $item->set('artspec', $parameters['artspec']);
        $item->set('created', $parameters['created']);
        $item->set('author', $parameters['author']);
        $item->set('lastmodified', $parameters['lastmodified']);
        $item->set('modifiedby', $parameters['modifiedby']);
        $item->set('published', $parameters['published']);
        $item->set('publishedby', $parameters['publishedby']);
        $item->set('online', $parameters['online']);
        $item->set('redirect', $parameters['redirect']);
        $item->set('redirect_url', $parameters['redirect_url']);
        $item->set('external_redirect', $parameters['external_redirect'] ?? '');
        $item->set('artsort', $parameters['artsort']);
        $item->set('timemgmt', $parameters['timemgmt']);
        $item->set('datestart', $parameters['datestart']);
        $item->set('dateend', $parameters['dateend']);
        $item->set('status', $parameters['status']);
        $item->set('time_move_cat', $parameters['time_move_cat']);
        $item->set('time_target_cat', $parameters['time_target_cat']);
        $item->set('time_online_move', $parameters['time_online_move']);
        $item->set('locked', $parameters['locked']);
        $item->set('free_use_01', $parameters['free_use_01']);
        $item->set('free_use_02', $parameters['free_use_02']);
        $item->set('free_use_03', $parameters['free_use_03']);
        $item->set('searchable', $parameters['searchable']);
        $item->set('sitemapprio', $parameters['sitemapprio'] ?? '0.5');
        $item->set('changefreq', $parameters['changefreq']);

        $item->store();

        return $item;
    }

    /**
     * Returns id (idartlang) of articlelanguage by article id and language id
     *
     * @param int $idart
     * @param int $idlang
     * @throws cDbException
     */
    public function getIdByArticleIdAndLanguageId($idart, $idlang): int
    {
        $this->db->query(
            "SELECT `idartlang` FROM `%s` WHERE `idart` = %d AND `idlang` = %d",
            $this->table,
            $idart,
            $idlang
        );

        return $this->db->nextRecord() ? cSecurity::toInteger($this->db->f('idartlang')) : 0;
    }

    /**
     * Resets all articles having an associated artspec.
     *
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function resetArtSpec(int $idArtSpec): bool
    {
        return cSecurity::toBoolean($this->db->query(
            'UPDATE `%s` SET `artspec` = 0 WHERE `artspec` = %d',
            $this->table,
            $idArtSpec
        ));
    }

}

/**
 * CONTENIDO API - Article Object
 *
 * This object represents a CONTENIDO article
 *
 * Create an object with
 * $obj = new cApiArticleLanguage(idartlang);
 * or with
 * $obj = new cApiArticleLanguage();
 * $obj->loadByArticleAndLanguageId(idart, lang);
 *
 * You can now read the article properties with
 * $obj->getField(property);
 *
 * List of article properties:
 *
 * idartlang - Language dependant article id
 * idart - Language independent article id
 * idclient - Id of the client
 * idtplcfg - Template configuration id
 * title - Internal Title
 * pagetitle - HTML Title
 * summary - Article summary
 * created - Date created
 * lastmodified - Date lastmodified
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
 * You can extract article content with the
 * $obj->getContent(contype [, number]) method.
 *
 * To extract the first headline, you can use:
 *
 * $headline = $obj->getContent("htmlhead", 1);
 *
 * If the second parameter is omitted, the method returns an array with all available
 * content of this type. The array has the following schema:
 *
 * [number => content];
 *
 * $headlines = $obj->getContent("htmlhead");
 *
 * $headlines[1] First headline
 * $headlines[2] Second headline
 * $headlines[6] Sixth headline
 *
 * Legal content type strings are defined in the CONTENIDO system table 'con_type'.
 * Default content types are:
 *
 * NOTE: This parameter is case-insensitive, you can use html or cms_HTML or CmS_HtMl.
 * You don't need to start with cms, but it won't crash if you do so.
 *
 * htmlhead - HTML Headline
 * html - HTML Text
 * headline - Headline (no HTML)
 * text - Text (no HTML)
 * img - Upload id of the element
 * imgdescr - Image description
 * link - Link (URL)
 * linktarget - Linktarget (_self, _blank, _top ...)
 * linkdescr - Link description
 * swf - Upload id of the element
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiArticleLanguage extends Item
{

    /**
     * @var array Config array
     */
    public $tab;

    /**
     * @deprecated [2015-05-27]
     * @var array Article content
     */
    public $content = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of the item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('art_lang'), 'idartlang');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Create a version of this article language with its contents/meta-tags;
     * the version is the new editable article language version
     *
     * @param string $type meta, content or complete
     * @throws cDbException|cException
     */
    public function markAsEditable($type = '')
    {
        // create a new editable version
        $maxVersion = 0;
        $this->db->query(
            'SELECT MAX(version) AS `max` FROM `%s` WHERE `idartlang` = %d',
            cDb::getTableName('art_lang_version'),
            $this->get('idartlang')
        );
        while ($this->db->nextRecord()) {
            $maxVersion = cSecurity::toInteger($this->db->f('max'));
        }

        $parameters = $this->values;
        $parameters['version'] = $maxVersion + 1;
        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersion = $artLangVersionColl->create($parameters);

        if ($type == 'content' || $type == 'complete') {
            // load content of article language version into $artLangVersion->content
            $artLangVersion->loadByArticleLanguageIdAndVersion(
                cSecurity::toInteger($artLangVersion->get('idartlang')),
                cSecurity::toInteger($artLangVersion->get('version')),
                true
            );
            $contentVersion = new cApiContent();
            $oType = new cApiType();
            $this->_loadArticleContent();

            // get all Contents/Versions
            $mergedContent = [];
            foreach ($this->content as $type => $typeids) {
                foreach ($typeids as $typeid => $value) {
                    $mergedContent[$type][$typeid] = '';
                }
            }
            foreach ($artLangVersion->content as $type => $typeids) {
                foreach ($typeids as $typeid => $value) {
                    $mergedContent[$type][$typeid] = '';
                }
            }

            // set new Content Versions
            foreach ($mergedContent as $type => $typeids) {
                foreach ($typeids as $typeid => $value) {
                    $oType->loadByType($type);
                    if (isset($this->content[$type][$typeid])) {
                        $contentVersion->loadByArticleLanguageIdTypeAndTypeId($this->get('idartlang'), $oType->get('idtype'), $typeid);
                        if ($contentVersion->isLoaded()) {
                            $contentVersion->markAsEditable($artLangVersion->get('version'), 0);
                        }
                    } else {
                        $contentParameters = [
                            'idartlang' => $artLangVersion->get('idartlang'),
                            'idtype' => $oType->get('idtype'),
                            'typeid' => $typeid,
                            'version' => $artLangVersion->get('version'),
                            'author' => $this->get('author'),
                            'deleted' => 1
                        ];
                        $contentVersionColl = new cApiContentVersionCollection();
                        $contentVersionColl->create($contentParameters);
                    }
                }
            }
        }

        if ($type == 'meta' || $type == 'complete') {
            // set new meta-tag versions
            $metaTag = new cApiMetaTag();

            $oMetaTagCollection = new cApiMetaTagCollection();
            $metaTagIds = $oMetaTagCollection->getIdMetatagsByIdArtLang(
                cSecurity::toInteger($this->get('idartlang'))
            );
            foreach ($metaTagIds as $id) {
                $metaTag->loadBy('idmetatag', $id);
                $metaTag->markAsEditable($artLangVersion->get('version'));
            }
        }
    }

    /**
     * Load data by article and language id
     *
     * @param int $idart Article id
     * @param int $idlang Language id
     * @return bool true on success, otherwise false
     * @throws cDbException|cException
     */
    public function loadByArticleAndLanguageId($idart, $idlang): bool
    {
        $result = true;
        if (!$this->isLoaded()) {
            $aProps = [
                'idart' => $idart,
                'idlang' => $idlang
            ];
            $aRecordSet = $this->_oCache->getItemByProperties($aProps);
            if ($aRecordSet) {
                // entry in cache found, load entry from cache
                $this->loadByRecordSet($aRecordSet);
            } else {
                $idartlang = $this->_getIdArtLang($idart, $idlang);
                $result = $this->loadByPrimaryKey($idartlang);
            }
        }

        return $result;
    }

    /**
     * Extract 'idartlang' for a specified 'idart' and 'idlang'
     *
     * @param int $idart Article id
     * @param int $idlang Language id
     * @return int Language dependant article id
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _getIdArtLang($idart, $idlang)
    {
        $sql = 'SELECT `idartlang` FROM `%s` WHERE `idart` = %d AND `idlang` = %d';
        $this->db->query($sql, cDb::getTableName('art_lang'), $idart, $idlang);
        $this->db->nextRecord();

        return $this->db->f('idartlang');
    }

    /**
     * @deprecated [2015-05-15] use _loadArticleContent, automatically loaded with getContent()
     */
    public function loadArticleContent()
    {
        cDeprecated('This method is deprecated and is not needed any longer');
        $this->_loadArticleContent();
    }

    /**
     * @deprecated [2015-05-15] use _loadArticleContent, automatically loaded with getContent()
     */
    protected function _getArticleContent()
    {
        cDeprecated('This method is deprecated and is not needed any longer');
        $this->_loadArticleContent();
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object, whenever it is needed to get the content of the article.
     *
     * $article->content[type][number] = value;
     *
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _loadArticleContent()
    {
        if (NULL !== $this->content) {
            return;
        }

        $sql = 'SELECT b.type, a.typeid, a.value FROM `%s` AS a, `%s` AS b ' . 'WHERE a.idartlang = %d AND b.idtype = a.idtype ORDER BY a.idtype, a.typeid';

        $this->db->query($sql, cDb::getTableName('content'), cDb::getTableName('type'), $this->get('idartlang'));

        $this->content = [];
        while ($this->db->nextRecord()) {
            $this->content[cString::toLowerCase($this->db->f('type'))][$this->db->f('typeid')] = $this->db->f('value');
        }
    }

    /**
     * Get the value of an article property
     *
     * List of article properties:
     *
     * idartlang - Language dependant article id
     * idart - Language independent article id
     * idclient - Id of the client
     * idtplcfg - Template configuration id
     * title - Internal Title
     * pagetitle - HTML Title
     * summary - Article summary
     * created - Date created
     * lastmodified - Date lastmodified
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
     * @param string $name
     * @param bool $safe Flag to run defined outFilter on passed value
     *         NOTE: It's not used ATM!
     * @return ?string Value of property
     */
    public function getField($name, $safe = true)
    {
        return $this->values[$name] ?? null;
    }

    /**
     * Predefined setter for article language fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'urlname':
                $value = conHtmlSpecialChars(cString::cleanURLCharacters($value), ENT_QUOTES);
                break;
            case 'timemgmt':
            case 'time_move_cat':
            case 'time_online_move':
            case 'redirect':
            case 'external_redirect':
            case 'locked':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idart':
            case 'idlang':
            case 'artspec':
            case 'online':
            case 'searchable':
            case 'artsort':
            case 'status':
                $value = cSecurity::toInteger($value);
                break;
            case 'redirect_url':
                $value = ($value == 'http://' || $value == '') ? '0' : $value;
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Get content(s) from an article.
     *
     * Returns the specified content element or an ["id" => "value"] if the
     * second parameter is omitted.
     *
     * Legal content type string are defined in the CONTENIDO system table 'con_type'.
     * Default content types are:
     *
     * NOTE: Parameter is case-insensitive, you can use html or cms_HTML or CMS_HtMl.
     * You don't need to start with cms, but it won't crash if you do so.
     *
     * htmlhead - HTML Headline
     * html - HTML Text
     * headline - Headline (no HTML)
     * text - Text (no HTML)
     * img - Upload id of the element
     * imgdescr - Image description
     * link - Link (URL)
     * linktarget - Linktarget (_self, _blank, _top ...)
     * linkdescr - Link description
     * swf - Upload id of the element
     *
     * @param string $type CMS_TYPE - Legal cms type string
     * @param ?int $id Id of the content
     * @return string|array
     * @throws cDbException|cInvalidArgumentException
     */
    public function getContent($type = '', $id = NULL)
    {
        if (NULL === $this->content) {
            $this->_loadArticleContent();
        }

        if (empty($this->content)) {
            return '';
        }

        if ($type == '') {
            return $this->content;
        }

        $type = cString::toLowerCase($type);

        if (cString::findFirstPosCI($type, 'cms_') === false) {
            $type = 'cms_' . $type;
        }

        if (is_null($id)) {
            // return Array
            return $this->content[$type] ?? [];
        }

        // return String
        return $this->content[$type][$id] ?? '';
    }

    /**
     * Similar to getContent this function returns the cContentType object
     *
     * @param string $type Name of the content type
     * @param int $id Id of the content type in this article
     * @return bool|cContentTypeAbstract Returns false if the name was invalid
     * @throws cDbException|cInvalidArgumentException
     */
    public function getContentObject($type, $id)
    {
        $typeClassName = 'cContentType' . ucfirst(cString::toLowerCase(str_replace('CMS_', '', $type)));

        if (!class_exists($typeClassName)) {
            return false;
        }

        return new $typeClassName($this->getContent($type, $id), $id, $this->content);
    }

    /**
     * Similar to getContent this function returns the view code of the cContentType object
     *
     * @param string $type Name of the content type
     * @param int $id Id of the content type in this article
     * @return string
     * @throws cDbException|cInvalidArgumentException
     */
    public function getContentViewCode($type, $id)
    {
        $object = $this->getContentObject($type, $id);
        if ($object === false) {
            return '';
        }

        return $object->generateViewCode();
    }

    /**
     * Returns all available content types
     *
     * @throws cException if no content has been loaded
     */
    public function getContentTypes(): array
    {
        if (empty($this->content)) {
            $this->_loadArticleContent();
        }

        return (is_array($this->content)) ? array_keys($this->content) : [];
    }

    /**
     * Returns the link to the current object.
     *
     * @param int $changeLanguageId [optional] Change language id for URL (optional)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getLink($changeLanguageId = 0): string
    {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = [];
        $options['idart'] = $this->get('idart');
        $options['lang'] = $changeLanguageId == 0 ? $this->get('idlang') : $changeLanguageId;
        if ($changeLanguageId > 0) {
            $options['changelang'] = $changeLanguageId;
        }

        return cUri::getInstance()->build($options);
    }

}
