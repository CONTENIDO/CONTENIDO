<?php
/**
 * This file contains the article language version collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Bjoern Behrens
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
class cApiArticleLanguageVersionCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *        ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art_lang_version'], 'idartlangversion'); 
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
	 *	@type int $idart
	 *  @type int $idlang
	 *	@type string $title
	 *  @type string $urlname
	 *	@type string $pagetitle
	 *  @type string $summary
	 *  @type int $artspec
	 *  @type string $created
	 *  @type int $iscurrentverseion
	 *  @type string $author
	 *	@type string $lastmodified
	 *  @type string $modifiedby
	 *  @type string $published
	 *  @type string $publishedby
	 *  @type int $online
	 *  @type int $redirect
	 *	@type string $redirect_url
	 *  @type int $external_redirect
	 *  @type int $artsort
	 *  @type int $timemgmt
	 *  @type string $datestart
	 *  @type string $dateend
	 *	@type int $status
	 *  @type int $time_move_cat
	 *  @type int $time_target_cat
	 *  @type int $time_online_move
	 *  @type int $locked
	 *	@type mixed $free_use_01
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
		
		//set version
		$version = 1;
		$sql = 'LOCK TABLE ' . $cfg['tab']['art_lang_version'] . ' READ;';
		$this->db->query($sql);	
		
		$sql = 'SELECT MAX(version) AS maxversion FROM con_art_lang_version WHERE idartlang = %d;'; // geht mit cfg nicht?!
		$sql = $this->db->prepare($sql, $parameters['idartlang']);
		$this->db->query($sql);		
		if($this->db->nextRecord()){
			$version = $this->db->f('maxversion');
			++$version;
		}		
				
        $item = $this->createNewItem();		
		
		$item->set('idartlang', $parameters['idartlang']);
        $item->set('idart', $parameters['idart']);
        $item->set('idlang', $parameters['idlang']);
        $item->set('title', $parameters['title']);
        $item->set('urlname', $parameters['urlname']);
        $item->set('pagetitle', $parameters['pagetitle']);
        $item->set('summary', $parameters['summary']);
        $item->set('artspec', $parameters['artspec']);
        $item->set('created', $parameters['created']);
		$item->set('version', $version);
        $item->set('author', $parameters['author']);
        $item->set('lastmodified', $parameters['lastmodified']);
        $item->set('modifiedby', $parameters['modifiedby']);
        $item->set('published', $parameters['published']);
        $item->set('publishedby', $parameters['publishedby']);
        $item->set('online', $parameters['online']);
        $item->set('redirect', $parameters['redirect']);
        $item->set('redirect_url', $parameters['redirect_url']);
        $item->set('external_redirect', $parameters['external_redirect']);
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
        $item->set('sitemapprio', $parameters['sitemapprio']);
        $item->set('changefreq', $parameters['changefreq']);		
		$item->setIsCurrentVersion($parameters['iscurrentversion']);
        $item->store();		
		
		$sql = 'UNLOCK TABLE;';
		$this->db->query($sql);	

        return $item;
    }
	
    /**
     * Returns id (idartlangversion) of articlelanguageversion by article 
	 * language id and version
     *
     * @param int $idartlang
     * @param int $version
     * @return int
     */
    public function getIdByArticleIdAndLanguageId($idartlang, $version) {
        $sql = 'SELECT idartlangversion FROM `%s` WHERE idartlang = %d AND version = %d';
        $this->db->query($sql, $this->table, $idartlang, $version);
        return ($this->db->nextRecord()) ? $this->db->f('idartlangversion') : 0;
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
class cApiArticleLanguageVersion extends Item {

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
     * @param mixed $mId Specifies the ID of item to load
     * @param bool $fetchContent Flag to fetch content
     */
    public function __construct($mId = false, $fetchContent = false) {
        global $cfg;
        parent::__construct($cfg['tab']['art_lang_version'], 'idartlangversion');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
            if (true === $fetchContent) {
                $this->_getArticleVersionContent();
            }
        }
    }

	/**
	 * Set isCurrentVersion = 0 in the current version and set isCurrentVersion = 1 in this version	 
	 *
	 * @param int $isCurrentVersion 0 = false, 1 = true
	 */
	public function setIsCurrentVersion($isCurrentVersion){
		$attributes = array(
				'idartlang' => $this->get('idartlang'), 
				'iscurrentversion' => $isCurrentVersion
		);
		if($isCurrentVersion == 1){
			$artLangVersion = new cApiArticleLanguageVersion();
			if($artLangVersion->loadByMany($attributes)){
				$artLangVersion->set('iscurrentversion', 0);
				$artLangVersion->store();
			}
			$this->set('iscurrentversion', 1);
		}else{
	        $this->set('iscurrentversion', 0);
		}
	}	
	
	/**
	 * Set this ArticleVersion with its ContentVersions as current:
	 * Copy data from this ArticleLanguageVersion to ArticleLanguage	 
	 * Update Contents in ArticleLanguage
	 * Set property iscurrentversion = 1 in this ArticleLanguageVersion
	 * and 0 in the current ArticleLanguageVersions
	 */
	public function setAsCurrent(){
		global $cfg;
		//Prepare the data and update ArticleLanguage
		$parameters = $this->values;
		$artLang = new cApiArticleLanguage($parameters['idartlang']);
		unset($parameters['idartlang']);
		unset($parameters['iscurrentversion']);
		unset($parameters['version']);
		foreach($parameters as $key => $value){
			$artLang->set($key, $value);
		}
		$artLang->store();
		
		//Update Contents
		$sql = 'SELECT idcontent FROM `%s`
				WHERE idartlang = %d AND idcontent NOT IN
					(SELECT idcontent
					FROM `%s` AS a
					WHERE idartlang = %d AND a.version <= %s)';					
		$this->db->query($sql, $cfg['tab']['content'], $this->values['idartlang'], $cfg['tab']['content_version'], $this->values['idartlang'], $this->values['version']);
		$contentColl = new cApiContentCollection();
		while($this->db->nextRecord()){
			 $contentColl->delete($this->db->f('idcontent'));
		}		
		
		$contentVersion = new cApiContentVersion();
		$oType = new cApiType();	
		$this->loadArticleVersionContent();
		foreach($this->content AS $type => $typeids){
			foreach($typeids AS $typeid => $value){
				$oType->loadByType($type);
				$contentParameters = array(
					'idArtLang' => $this->get('idartlang'),
					'idType' => $oType->get('idtype'),
					'typeId' => $typeid,
					'version' => $this->get('version')
				);
				$contentVersion->loadByArticleLanguageIdTypeTypeIdAndVersion($contentParameters);
				$contentVersion->setAsCurrent();
			}
		}
		//Set this ArticleVersion as current
		$this->setIsCurrentVersion(1);
	}
		
    /**
     * Load data by article language id and version
     *
     * @param int $idArtLang Article language id
     * @param int $idlang version number
     * @param bool $fetchContent Flag to fetch content
     * @return bool true on success, otherwhise false
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
     * @param int $idArtLang Article language id
     * @param int $version version number
     * @return int Article language version id
     */
    protected function _getIdArtLangVersion($idArtLang, $version) {
        global $cfg;

        $sql = 'SELECT idartlangversion FROM `%s` WHERE idartlang = %d AND version = %d';
        $this->db->query($sql, $cfg['tab']['art_lang_version'], $idArtLang, $version);
        $this->db->nextRecord();

        return $this->db->f('idartlangversion');
    }

    /**
     * Load the articles version content and stores it in the 'content' property of the
     * article object.
     *
     * $article->content[type][number] = value;
     */
    public function loadArticleVersionContent() {
        $this->_getArticleVersionContent();
    }

    /**
     * Load the articles version content and stores it in the 'content' property of the
     * article version object.
     *
     * $article->content[type][number] = value;
     */
    protected function _getArticleVersionContent() {
        global $cfg;

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
				ORDER BY a.idtype, a.typeid;';

		$this->db->query($sql, $cfg['tab']['content_version'], $cfg['tab']['type'], $cfg['tab']['content_version'], $this->get('idartlang'), $this->get('version'), $this->get('idartlang'));
		
        $this->content = array();
        while ($this->db->nextRecord()) {
            $this->content[strtolower($this->db->f('type'))][$this->db->f('typeid')][$this->db->f('version')] = $this->db->f('value');
        }
    }

    /**
     * Get the value of an article version property
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
	 * iscurrentversion - (bool) if the Article Version is the current version
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
     * @param bool $bSafe Flag to run defined outFilter on passed value
     *        NOTE: It's not used ATM!
     * @return string Value of property
     */
    public function getField($name, $bSafe = true) {
        return $this->values[$name];
    }

    /**
     * Userdefined setter for article language version fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'urlname':
                $value = conHtmlSpecialChars(cApiStrCleanURLCharacters($value), ENT_QUOTES);
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
     * Get content(s) from an article version.
     *
     * Returns the specified content element or an array("id"=>"value") if the
     * second parameter is omitted.
     *
     * Legal content type string are defined in the CONTENIDO system table
     * 'con_type'.
     * Default content types are:
     *
     * NOTE: Parameter is case insesitive, you can use html or cms_HTML or
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
     * @param string $type CMS_TYPE - Legal cms type string
     * @param int|NULL $id Id of the content
     * @return string array data
     */
    public function getContent($type = '', $id = NULL) {
        if (NULL === $this->content) {
            $this->_getArticleVersionContent();
        }

        if (empty($this->content)) {
            return '';
        }

        if ($type == '') {
            return 'Class ' . get_class($this) . ': content-type must be specified!';
        }

        $type = strtolower($type);

        if (!strstr($type, 'cms_')) {
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
     * @param string $type Name of the content type
     * @param int $id Id of the content type in this article
     * @return boolean|cContenType Returns false if the name was invalid
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
     * @param string $type Name of the content type
     * @param int  $id Id of the content type in this article
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
     * @throws cException if no content has been loaded
     * @return array
     */
    public function getContentTypes() {
        if (empty($this->content)) {
            throw new cException('getContentTypes() No content loaded');
        }
        return array_keys($this->content);
    }

    /**
     * Returns the link to the current object.
     *
     * @param int $changeLangId change language id for URL (optional)
     * @return string link
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
