<?php


defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Article Version language collection
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
     * @param int $idart
     * @param int $idlang
     * @param string $title
     * @param string $urlname
     * @param string $pagetitle
     * @param string $summary
     * @param int $artspec
     * @param string $created
     * @param string $author
     * @param string $lastmodified
     * @param string $modifiedby
     * @param string $published
     * @param string $publishedby
     * @param int $online
     * @param int $redirect
     * @param string $redirect_url
     * @param int $external_redirect
     * @param int $artsort
     * @param int $timemgmt
     * @param string $datestart
     * @param string $dateend
     * @param int $status
     * @param int $time_move_cat
     * @param int $time_target_cat
     * @param int $time_online_move
     * @param int $locked
     * @param mixed $free_use_01
     * @param mixed $free_use_02
     * @param mixed $free_use_03
     * @param int $searchable
     * @param float $sitemapprio
     * @param string $changefreq
     * @return cApiArticleLanguage
     */
    public function create($aParameter) {
        global $auth;

        if (empty($aParameter['author'])) {
            $aParameter['author'] = $auth->auth['uname'];
        }
        if (empty($aParameter['created'])) {
            $aParameter['created'] = date('Y-m-d H:i:s');
        }
        if (empty($aParameter['lastmodified'])) {
            $aParameter['lastmodified'] = date('Y-m-d H:i:s');
        }

        $aParameter['urlname'] = (trim($aParameter['urlname']) == '') ? trim($aParameter['title']) : trim($aParameter['urlname']);
		
		//set version
		$version = 1;
		$sql = "LOCK TABLE " . $cfg['tab']['art_lang_version'] . " READ;";
		$this->db->query($sql);	
		
		$sql = "SELECT MAX(version) AS maxversion FROM con_art_lang_version WHERE idartlang = '%d';"; // geht mit cfg nicht?!
		$sql = $this->db->prepare($sql, $aParameter['idartlang']);
		$this->db->query($sql);		
		if($this->db->nextRecord()){
			$version = $this->db->f('maxversion');
			++$version;
		}		
				
        $item = $this->createNewItem();		
		
		$item->set('idartlang', $aParameter['idartlang']);
        $item->set('idart', $aParameter['idart']);
        $item->set('idlang', $aParameter['idlang']);
        $item->set('title', $aParameter['title']);
        $item->set('urlname', $aParameter['urlname']);
        $item->set('pagetitle', $aParameter['pagetitle']);
        $item->set('summary', $aParameter['summary']);
        $item->set('artspec', $aParameter['artspec']);
        $item->set('created', $aParameter['created']);
		$item->set('version', $version);
        $item->set('author', $aParameter['author']);
        $item->set('lastmodified', $aParameter['lastmodified']);
        $item->set('modifiedby', $aParameter['modifiedby']);
        $item->set('published', $aParameter['published']);
        $item->set('publishedby', $aParameter['publishedby']);
        $item->set('online', $aParameter['online']);
        $item->set('redirect', $aParameter['redirect']);
        $item->set('redirect_url', $aParameter['redirect_url']);
        $item->set('external_redirect', $aParameter['external_redirect']);
        $item->set('artsort', $aParameter['artsort']);
        $item->set('timemgmt', $aParameter['timemgmt']);
        $item->set('datestart', $aParameter['datestart']);
        $item->set('dateend', $aParameter['dateend']);
        $item->set('status', $aParameter['status']);
        $item->set('time_move_cat', $aParameter['time_move_cat']);
        $item->set('time_target_cat', $aParameter['time_target_cat']);
        $item->set('time_online_move', $aParameter['time_online_move']);
        $item->set('locked', $aParameter['locked']);
        $item->set('free_use_01', $aParameter['free_use_01']);
        $item->set('free_use_02', $aParameter['free_use_02']);
        $item->set('free_use_03', $aParameter['free_use_03']);
        $item->set('searchable', $aParameter['searchable']);
        $item->set('sitemapprio', $aParameter['sitemapprio']);
        $item->set('changefreq', $aParameter['changefreq']);		
		$item->setIsCurrentVersion($aParameter['iscurrentversion']);
        $item->store();		
		
		$sql = "UNLOCK TABLE;";
		$this->db->query($sql);	

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
class cApiArticleLanguageVersion extends Item {

    /**
     * Config array
     *
     * @var array
     */
    public $tab;

    /**
     * Article content
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
                $this->_getArticleContent();
            }
        }
    }

	/**
	 * Set iscurrentversion = 0 in the current version and set iscurrentversion = 1 in this version	 
	 * 
	 */
	public function setIsCurrentVersion($iscurrentversion){
		$aAttributes = array(
				'idartlang' => $this->get('idartlang'), 
				'iscurrentversion' => $iscurrentversion
		);
		if($iscurrentversion == 1){
			$oArtLangVersion = new cApiArticleLanguageVersion();
			if($oArtLangVersion->loadByMany($aAttributes)){
				$oArtLangVersion->set('iscurrentversion', 0);
				$oArtLangVersion->store();
			}
			$this->set('iscurrentversion', 1);
		}else{
	        $this->set('iscurrentversion', 0);
		}
	}	
	
	/**
	 * Copy the data from this ArticleLanguageVersion in ArticleLanguage	 
	 * TODO: ArticleLanguageVersion->getContentVersions->foreach{setAsCurrent}
	 */
	public function setAsCurrent(){ 
		//Prepare the data and update ArticleLanguage
		$aParameter = $this->values;
		$oArtLang = new cApiArticleLanguage($aParameter['idartlang']);
		unset($aParameter['idartlang']);
		unset($aParameter['iscurrentversion']);
		unset($aParameter['version']);
		foreach($aParameter as $key => $value){
			$oArtLang->set($key, $value);
		}
		$oArtLang->store();
		
		//Update Contents
		$oContentVersion = new cApiContentVersion();
		$oType = new cApiType();	
		$this->loadArticleContent();
		foreach($this->content AS $type => $typeids){
			foreach($typeids AS $typeid => $value){
				$oType->loadByType($type);
				$contentParameters = array(
					'idartlang' => $this->get('idartlang'),
					'idtype' => $oType->get('idtype'),
					'typeid' => $typeid,
					'version' => $this->get('version')
				);
				$oContentVersion->loadByArticleLanguageIdTypeAndTypeId($contentParameters);
				$oContentVersion->setAsCurrent();
			}
		}
	}
		
    /**
     * Load data by article and language id
     *
     * @param int $idart Article id
     * @param int $idlang Language id
     * @param bool $fetchContent Flag to fetch content
     * @return bool true on success, otherwhise false
     */
    public function loadByArticleAndLanguageId($idart, $idlang, $fetchContent = false) {
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

        if (true === $fetchContent) {
            $this->_getArticleContent();
        }

        return $result;
    }

    /**
     * Extract 'idartlang' for a specified 'idart' and 'idlang'
     *
     * @param int $idart Article id
     * @param int $idlang Language id
     * @return int Language dependant article id
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
     */
    public function loadArticleContent() {
        $this->_getArticleContent();
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object.
     *
     * $article->content[type][number] = value;
     */
    protected function _getArticleContent() {
        global $cfg;

        if (NULL !== $this->content) {
            return;
        }

		$sql = 'SELECT b.type, a.typeid, a.value
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
     * @param bool $bSafe Flag to run defined outFilter on passed value
     *        NOTE: It's not used ATM!
     * @return string Value of property
     */
    public function getField($name, $bSafe = true) {
        return $this->values[$name];
    }

    /**
     * Userdefined setter for article language fields.
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
     * Get content(s) from an article.
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
    public function getContent($type, $id = NULL) {
        if (NULL === $this->content) {
            $this->_getArticleContent();
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
