<?php
/**
 * This file contains the article language collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Article language collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiArticleLanguageCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select [optional]
     *         where clause to use for selection (see ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art_lang'], 'idartlang');
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
     * @global object $auth
     * @param int $idart
     * @param int $idlang
     * @param string $title
     * @param string $urlname
     * @param string $pagetitle
     * @param string $summary
     * @param int $artspec [optional]
     * @param string $created [optional]
     * @param string $author [optional]
     * @param string $lastmodified [optional]
     * @param string $modifiedby [optional]
     * @param string $published [optional]
     * @param string $publishedby [optional]
     * @param int $online [optional]
     * @param int $redirect [optional]
     * @param string $redirect_url [optional]
     * @param int $external_redirect [optional]
     * @param int $artsort [optional]
     * @param int $timemgmt [optional]
     * @param string $datestart [optional]
     * @param string $dateend [optional]
     * @param int $status [optional]
     * @param int $time_move_cat [optional]
     * @param int $time_target_cat [optional]
     * @param int $time_online_move [optional]
     * @param int $locked [optional]
     * @param mixed $free_use_01 [optional]
     * @param mixed $free_use_02 [optional]
     * @param mixed $free_use_03 [optional]
     * @param int $searchable [optional]
     * @param float $sitemapprio [optional]
     * @param string $changefreq [optional]
     * @return cApiArticleLanguage
     */
    public function create($idart, $idlang, $title, $urlname, $pagetitle,
            $summary, $artspec = 0, $created = '', $author = '',
            $lastmodified = '', $modifiedby = '', $published = '',
            $publishedby = '', $online = 0, $redirect = 0, $redirect_url = '',
            $external_redirect = 0, $artsort = 0, $timemgmt = 0, $datestart = '',
            $dateend = '', $status = 0, $time_move_cat = 0, $time_target_cat = 0,
            $time_online_move = 0, $locked = 0, $free_use_01 = '', $free_use_02 = '',
            $free_use_03 = '', $searchable = 1, $sitemapprio = 0.5, $changefreq = '') {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $urlname = (trim($urlname) == '') ? trim($title) : trim($urlname);

        $item = $this->createNewItem();

        $item->set('idart', $idart);
        $item->set('idlang', $idlang);
        $item->set('title', $title);
        $item->set('urlname', $urlname);
        $item->set('pagetitle', $pagetitle);
        $item->set('summary', $summary);
        $item->set('artspec', $artspec);
        $item->set('created', $created);
        $item->set('author', $author);
        $item->set('lastmodified', $lastmodified);
        $item->set('modifiedby', $modifiedby);
        $item->set('published', $published);
        $item->set('publishedby', $publishedby);
        $item->set('online', $online);
        $item->set('redirect', $redirect);
        $item->set('redirect_url', $redirect_url);
        $item->set('external_redirect', $external_redirect);
        $item->set('artsort', $artsort);
        $item->set('timemgmt', $timemgmt);
        $item->set('datestart', $datestart);
        $item->set('dateend', $dateend);
        $item->set('status', $status);
        $item->set('time_move_cat', $time_move_cat);
        $item->set('time_target_cat', $time_target_cat);
        $item->set('time_online_move', $time_online_move);
        $item->set('locked', $locked);
        $item->set('free_use_01', $free_use_01);
        $item->set('free_use_02', $free_use_02);
        $item->set('free_use_03', $free_use_03);
        $item->set('searchable', $searchable);
        $item->set('sitemapprio', $sitemapprio);
        $item->set('changefreq', $changefreq);

        $item->store();

        return $item;
    }

    /**
     * Returns id (idartlang) of articlelanguage by article id and language id
     *
     * @param int $idcat
     * @param int $idlang
     * @return int
     */
    public function getIdByArticleIdAndLanguageId($idart, $idlang) {
        $sql = "SELECT idartlang FROM `%s` WHERE idart = %d AND idlang = %d";
        $this->db->query($sql, $this->table, $idart, $idlang);
        return ($this->db->nextRecord()) ? $this->db->f('idartlang') : 0;
    }

}

/**
 * CONTENIDO API - Article Object
 *
 * This object represents a CONTENIDO article
 *
 * Create object with
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
 * idart - Language indepenant article id
 * idclient - Id of the client
 * idtplcfg - Template configuration id
 * title - Internal Title
 * pagetitle - HTML Title
 * summary - Article summary
 * created - Date created
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
 * You can extract article content with the
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
class cApiArticleLanguage extends Item {

    /**
     * Config array
     *
     * @var array
     */
    public $tab;

    /**
     * Article content
     *
     * @deprecated [2015-05-27]
     * @var array
     */
    public $content = NULL;

    /**
     * Constructor Function
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art_lang'], 'idartlang');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Create a version of this article language with its contents/metatags;
     * the version is the new editable article language version
     *
     * @param string $type
     *         meta, content or complete
     */
    public function markAsEditable($type = '') {
        global $cfg;

        // create new editable version
        $sql = 'SELECT max(version) AS max FROM %s WHERE idartlang = %d';
        $this->db->query($sql, $cfg['tab']['art_lang_version'], $this->get('idartlang'));
        while ($this->db->nextRecord()) {
                $maxVersion = $this->db->f('max');
        }

        $parameters = $this->values;
        $parameters['version'] = $maxVersion + 1;
        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersion = $artLangVersionColl->create($parameters);

        if ($type == 'content' || $type == 'complete') {
            // load content of article language version into $artLangVersion->content
            $artLangVersion->loadByArticleLanguageIdAndVersion($artLangVersion->get('idartlang'), $artLangVersion->get('version'), true);
            $contentVersion = new cApiContent();
            $oType = new cApiType();
            $this->_loadArticleContent();

            // get all Contents/Versions
            $mergedContent = array();
            foreach ($this->content AS $type => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                        $mergedContent[$type][$typeid] = '';
                }
            }
            foreach ($artLangVersion->content AS $type => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                        $mergedContent[$type][$typeid] = '';
                }
            }

            // set new Content Versions
            foreach ($mergedContent AS $type => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                    $oType->loadByType($type);
                    if (isset($this->content[$type][$typeid])) {
                        $contentVersion->loadByArticleLanguageIdTypeAndTypeId($this->get('idartlang'), $oType->get('idtype'), $typeid);
                        if (isset($contentVersion)) {
                                $contentVersion->markAsEditable($artLangVersion->get('version'), 0);
                        }
                    } else {
                        $contentParameters = array(
                                'idartlang' => $artLangVersion->get('idartlang'),
                                'idtype' => $oType->get('idtype'),
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
            $metaTag = new cApiMetaTag();
            $sql = 'SELECT idmetatag AS id
                    FROM `%s`
                    WHERE idartlang = %d';
            $this->db->query(
                $sql,
                cRegistry::getDbTableName('meta_tag'),
                $this->get('idartlang')
            );
            while ($this->db->nextRecord()) {
                    $metaTagIds[] = $this->db->f('id');
            }
            if(isset($metaTagIds)) {
                foreach ($metaTagIds AS $id) {
                    $metaTag->loadBy('idmetatag', $id);
                    $metaTag->markAsEditable($artLangVersion->get('version'));
                }
            }
        }
    }

    /**
     * Load data by article and language id
     *
     * @param int $idart
     *         Article id
     * @param int $idlang
     *         Language id
     *         Flag to fetch content
     * @return bool
     *         true on success, otherwise false
     */
    public function loadByArticleAndLanguageId($idart, $idlang) {
        $result = true;
        if (!$this->isLoaded()) {
            $aProps = array(
                'idart' => $idart,
                'idlang' => $idlang
            );
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
     * @param int $idart
     *         Article id
     * @param int $idlang
     *         Language id
     * @return int
     *         Language dependant article id
     */
    protected function _getIdArtLang($idart, $idlang) {
        global $cfg;

        $sql = 'SELECT idartlang FROM `%s` WHERE idart = %d AND idlang = %d';
        $this->db->query($sql, $cfg['tab']['art_lang'], $idart, $idlang);
        $this->db->nextRecord();

        return $this->db->f('idartlang');
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object.
     *
     * $article->content[type][number] = value;
     *
     * @deprecated [2015-05-15]
     *         use _loadArticleContent
     */
    public function loadArticleContent() {
        cDeprecated('This method is deprecated and is not needed any longer');
        $this->_loadArticleContent();
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object.
     *
     * $article->content[type][number] = value;
     *
     * @deprecated [2015-05-15]
     *         use _loadArticleContent
     */
    protected function _getArticleContent() {
        cDeprecated('This method is deprecated and is not needed any longer');
        $this->_loadArticleContent();
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object, whenever it is needed to get the content of the article.
     *
     * $article->content[type][number] = value;
     */
    protected function _loadArticleContent() {
        global $cfg;

        if (NULL !== $this->content) {
            return;
        }

        $sql = 'SELECT b.type, a.typeid, a.value FROM `%s` AS a, `%s` AS b ' . 'WHERE a.idartlang = %d AND b.idtype = a.idtype ORDER BY a.idtype, a.typeid';

        $this->db->query($sql, $cfg['tab']['content'], $cfg['tab']['type'], $this->get('idartlang'));

        $this->content = array();
        while ($this->db->nextRecord()) {
            $this->content[strtolower($this->db->f('type'))][$this->db->f('typeid')] = $this->db->f('value');
        }
    }

    /**
     * Get the value of an article property
     *
     * List of article properties:
     *
     * idartlang - Language dependant article id
     * idart - Language indepenant article id
     * idclient - Id of the client
     * idtplcfg - Template configuration id
     * title - Internal Title
     * pagetitle - HTML Title
     * summary - Article summary
     * created - Date created
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
     * @param string $name
     * @param bool $bSafe [optional]
     *         Flag to run defined outFilter on passed value
     *         NOTE: It's not used ATM!
     * @return string
     *         Value of property
     */
    public function getField($name, $bSafe = true) {
        return $this->values[$name];
    }

    /**
     * Userdefined setter for article language fields.
     *
     * @todo should return return value of overloaded method
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
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
                $value = (int) $value;
                break;
            case 'redirect_url':
                $value = ($value == 'http://' || $value == '') ? '0' : $value;
                break;
        }

        parent::setField($name, $value, $bSafe);
    }

    /**
     * Get content(s) from an article.
     *
     * Returns the specified content element or an array("id"=>"value") if the
     * second parameter is omitted.
     *
     * Legal content type string are defined in the CONTENIDO system table
     * 'con_type'.
     * Default content types are:
     *
     * NOTE: Parameter is case insensitive, you can use html or cms_HTML or
     * CmS_HtMl.
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
     * linkdescr - Linkdescription
     * swf - Upload id of the element
     *
     * @param string $type
     *         CMS_TYPE - Legal cms type string
     * @param int|NULL $id [optional]
     *         Id of the content
     * @return string|array
     *         data
     */
    public function getContent($type = '', $id = NULL) {
        if (NULL === $this->content) {
            $this->_loadArticleContent();
        }

        if (empty($this->content)) {
            return '';
        }

        if ($type == '') {
            return $this->content;
        }

        $type = strtolower($type);

        if (false === stripos($type, 'cms_')) {
            $type = 'cms_' . $type;
        }

        if (is_null($id)) {
            // return Array
            return $this->content[$type];
        }

        // return String
        return (isset($this->content[$type][$id])) ? $this->content[$type][$id] : '';
    }

    /**
     * Similar to getContent this function returns the cContentType object
     *
     * @param string $type
     *         Name of the content type
     * @param int $id
     *         Id of the content type in this article
     * @return bool|cContenType
     *         Returns false if the name was invalid
     */
    public function getContentObject($type, $id) {
        $typeClassName = 'cContentType' . ucfirst(strtolower(str_replace('CMS_', '', $type)));

        if (!class_exists($typeClassName)) {
            return false;
        }

        return new $typeClassName($this->getContent($type, $id), $id, $this->content);
    }

    /**
     * Similar to getContent this function returns the view voce of the cContentType object
     *
     * @param string $type
     *         Name of the content type
     * @param int  $id
     *         Id of the content type in this article
     * @return string
     */
    public function getContentViewCode($type, $id) {
        $object = $this->getContentObject($type, $id);
        if ($object === false) {
            return "";
        }

        return $object->generateViewCode();
    }

    /**
     * Returns all available content types
     *
     * @throws cException
     *         if no content has been loaded
     * @return array
     */
    public function getContentTypes() {
        if (empty($this->content)) {
            $this->_loadArticleContent();
        }

        return (is_array($this->content)) ? array_keys($this->content) : array();
    }

    /**
     * Returns the link to the current object.
     *
     * @param int $changeLangId [optional]
     *         change language id for URL (optional)
     * @return string
     *         link
     */
    public function getLink($changeLangId = 0) {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = array();
        $options['idart'] = $this->get('idart');
        $options['lang'] = ($changeLangId == 0) ? $this->get('idlang') : $changeLangId;
        if ($changeLangId > 0) {
            $options['changelang'] = $changeLangId;
        }

        return cUri::getInstance()->build($options);
    }

}
