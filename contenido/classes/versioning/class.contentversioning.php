<?php
/**
 * This file contains the versioning class
 *
 * @package         Core   
 * @subpackage      Versioning  
 * @version          
 *
 * @author           Jann Dieckmann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Versioning
 *
 * @package          Core
 * @subpackage       Versioning
 */
class cContentVersioning {
 	
    /**
     * db instance
     *
     * @var cDb
     */
    protected $db;
	
    /**
     * article type (current, version, editable)
     *
     * @var string
     */
    protected $articleType;
	
    /**
     * selected article language (version)
     *
     * @var cApiArticleLanguageVersion or cApiArticleLanguage
     */
    public $selectedArticle;
    
    /**
     * editable article language version id
     *
     * @var integer
     */
    public $editableArticleId;
	
    /**
     * Create new versioning class
     *
     */
    public function __construct() {
            $this->db = cRegistry::getDb();
    }	
	
    /**
     * cms type sort function for article output
     *
     * @param array $result[cms type][typeId] = value
     * @return array $result[cms type][typeId] = value
     */
    public function sortResults($result) {

        uksort($result, function($a, $b) {

            // cms type sort sequence
            $cmsType = array(
                "CMS_HTMLHEAD",
                "CMS_HEAD",
                "CMS_HTML",
                "CMS_TEXT",
                "CMS_IMG",
                "CMS_IMGDESCR",
                "CMS_IMGEDITOR",
                "CMS_LINK",
                "CMS_LINKTARGET",
                "CMS_LINKDESCR",
                "CMS_LINKEDITOR",
                "CMS_DATE",
                "CMS_TEASER",
                "CMS_FILELIST",
                "CMS_RAW"
            );

            return array_search($a, $cmsType) - array_search($b, $cmsType);

        });

        return $result;
		
    }	
    
    /**
     * Return date for select box 
     * if current time - lastModified < 1 hour return "%d minutes ago"
     * else return "Y-M-D H:I:S"
     * 
     * @param $lastModified 
     * @return string
     */
    public function getTimeDiff($lastModified) {
        $currentTime = new DateTime(date('Y-m-d H:i:s'));        
        $modifiedTime = new DateTime($lastModified);
        $diff = $currentTime->diff($modifiedTime);
        $diff2 = (int) $diff->format('%Y%M%D%H');
        
        if ($diff2 === 0) {
            return sprintf(i18n("%d minutes ago"), $diff->format('%i'));
        } else {       
            return date('d.m.Y, H:i\h', strtotime($lastModified)); 
        }
        
    }

    /**
     * Returns the current versioning state (disabled (default), simple, advanced)
     *
     * @return string $versioningState
     */
    public static function getState() {

        static $versioningState;

        if (!isset($versioningState)) {

            // versioning enabled is a tri-state => disabled (default), simple, advanced
            $systemPropColl = new cApiSystemPropertyCollection();
            $prop = $systemPropColl->fetchByTypeName('versioning', 'enabled');
            $versioningState = $prop ? $prop->get('value') : false;

            if (false === $versioningState || NULL === $versioningState) {
                $versioningState = 'disabled';
            } else if ('' === $versioningState) {
                // NOTE: An non empty default value overrides an empty value
                $versioningState = 'disabled';
            }

        }

        return $versioningState;

    }
	
    /**
     * Returns selected article
     * TODO $idArtlangVersion <-> $selectedArticleId
     *
     * @param int $idArtLangVersion
     * @param int $idArtLang
     * @param string $articleType
     * @param int $selectedArticleId
     * @return cApiArticleLanguage/cApiArticleLanguageVersion $this->selectedArticle
     */	
    public function getSelectedArticle($idArtLangVersion, $idArtLang, $articleType, $selectedArticleId) {

        $this->editableArticleId = $this->getEditableArticleId($idArtLang);
        $versioningState = $this->getState();
        $this->selectedArticle = NULL;
        if (is_numeric($selectedArticleId)) {
            $idArtLangVersion = $selectedArticleId;
        }
        
        if (($articleType == 'version' || $articleType == 'editable')  && ($versioningState == 'advanced') 
            || ($articleType == 'version' && $versioningState == 'simple')) {
            if (is_numeric($idArtLangVersion) && $articleType == 'version') {
                $this->selectedArticle = new cApiArticleLanguageVersion($idArtLangVersion);
            } else if (isset($this->editableArticleId)) {
                $this->selectedArticle = new cApiArticleLanguageVersion($this->editableArticleId);            
             }             
        } else if ($articleType == 'current' || $articleType == 'editable') {
            $this->selectedArticle = new cApiArticleLanguage($idArtLang);            
        }
        return $this->selectedArticle;

    }
	
    /**
     * Returns $list[1] = CMS_HTMLHEAD for every content
     * existing in article/version with $idArtLang 
     *
     * @param int $idArtLang
     * @param string $articleType
     * @return array $list
     */		
    public function getList($idArtLang, $articleType) {

        $list = array();

        $sql = 'SELECT DISTINCT b.idtype as idtype, b.type as name
                FROM %s AS a, %s AS b
                WHERE a.idartlang = %d AND a.idtype = b.idtype
                ORDER BY idtype, name';

        if (($articleType == 'version' || $articleType == 'editable') && $this->getState() == 'advanced'
                || $articleType == 'version' && $this->getState() == 'simple') {
            $this->db->query($sql, cRegistry::getDbTableName('content_version'), cRegistry::getDbTableName('type'), $idArtLang);			
        } else if ($articleType == 'current' || $articleType == 'editable' && $this->getState() != 'advanced') {
            $this->db->query($sql, cRegistry::getDbTableName('content'), cRegistry::getDbTableName('type'), $idArtLang);
        }
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idtype')] = $this->db->f('name');
        }

        return $list;

    }    

    /**
     * Return max idcontent
     *
     * @return int
     */    
    public function getMaxIdContent() {
        $sql = 'SELECT max(idcontent) AS max FROM %s';
        $this->db->query($sql, cRegistry::getDbTableName('content'));
        
        $this->db->nextRecord();
        
        return $this->db->f('max');
    }
	
    /**
     * Returns type of article (current, version or editable)
     *
     * @param int $idArtLangVersion
     * @param int $idArtLang
     * @param string $action
     * @param mixed $selectedArticleId
     * @return string $this->articleType
     * 
     */
    public function getArticleType($idArtLangVersion, $idArtLang, $action, $selectedArticleId) {
        
        $this->editableArticleId = $this->getEditableArticleId($idArtLang);
                
        if ($this->getState() == 'disabled' // disabled               
            || ($this->getState() == 'simple' && ($selectedArticleId == 'current' 
                || $selectedArticleId == NULL) 
                && ($action == 'con_meta_deletetype' || $action == 'copyto'
                 || $action == 'con_content' || $idArtLangVersion == NULL
                 || $action == 'con_saveart' || $action == 'con_edit' || $action == 'con_meta_edit' || $action == 'con_editart')) 
            || $idArtLangVersion == 'current' && $action != 'copyto'
            || $action == 'copyto' && $idArtLangVersion == $this->editableArticleId
            || $action == 'con_meta_change_version' && $idArtLang == 'current'
            || $selectedArticleId == 'current' && $action != 'copyto'
            || $this->editableArticleId == NULL
            && $action != 'con_meta_saveart' && $action != 'con_newart') { // advanced
            $this->articleType = 'current';
        } else if ($this->getState() == 'advanced' && ($selectedArticleId == 'editable' 
            || $selectedArticleId == NULL)
            && ($action == 'con_content' || $action == 'con_meta_deletetype'
                || $action == 'con_meta_edit' || $action == 'con_edit' || $action == 'con_editart')
            || $action == 'copyto' || $idArtLangVersion == 'current'
            || $idArtLangVersion == $this->editableArticleId
            || $action == 'importrawcontent' || $action == 'savecontype'
            || $action == 'con_editart' && $this->getState() == 'advanced' && $selectedArticleId == 'editable'
            || $action == 'con_edit' && $this->getState() == 'advanced' && $selectedArticleId == NULL
            || $action == '20' && $idArtLangVersion == NULL
            || $action == 'con_meta_saveart' || $action == 'con_saveart' 
            || $action == 'con_newart' || $action == 'con_meta_change_version' 
            && $idArtLangVersion == $this->editableArticleId) {
            $this->articleType = 'editable';
        } else {
            $this->articleType = 'version';
        }
        
        return $this->articleType;

    }
    
    public function getVersionSelectionField($class, $selectElement, $copyToButton, $infoText) {
        $versionselection = '
            <div class="%s">
                <div>
                    <span style="width: 280px; display: inline; padding: 0px 0px 0px 2px;">
                        <span style="font-weight:bold;color:black;">' . i18n('Select Article Version') . '</span>
                        <span style="margin: 0px 0px 0px 0px;"> %s %s
                        </span>
                        <a 
                            href="#" 
                            id="pluginInfoDetails-link" 
                            class="main i-link infoButton" 
                            title="">                    
                        </a>
                    </span>                  
                </div>
                <div id="pluginInfoDetails" style="display:none;" class="nodisplay">
                       %s
                </div>
            </div>
            ';
        return sprintf(
                    $versionselection,
                    $class,
                    $selectElement,
                    $copyToButton,
                    $infoText
                );
    }

    /**
     * Returns idartlangversion of editable article
     *
     * @param int $idArtLang
     * @return int $editableArticleId
     */
    public function getEditableArticleId($idArtLang) {

        if ($this->getState() == 'advanced') {
            $sql = 'SELECT max(idartlangversion) AS max FROM %s WHERE idartlang = %d';
            $this->db->query(
                $sql,
                cRegistry::getDbTableName('art_lang_version'),
                $idArtLang
            );
            $this->db->nextRecord();
            $this->editableArticleId = $this->db->f('max');
            return $this->editableArticleId;
            
        } else if ($this->getState() == 'simple' || $this->getState() == 'disabled') {
            return $idArtLang;
        }	

    }
	
    /**
     * Returns idcontent or idcontentversion
     *
     * @param int $idArtLang
     * @param int $typeId
     * @param int $type
     * @param int $versioningState
     * @param int $articleType
     * @param int $version
     * @return array $idContent
     */	
    public function getContentId($idArtLang, $typeId, $type, $versioningState, $articleType, $version) {

        $idContent = array();
        $type = addslashes($type);
        if ($versioningState == 'simple' && $articleType != 'version'
            || $versioningState == 'advanced' && $articleType == 'current'
            || $versioningState == 'disabled') {
            $sql = "SELECT a.idcontent
            FROM " . cRegistry::getDbTableName('content') . " as a, " . cRegistry::getDbTableName('type') . " as b
            WHERE a.idartlang=" . $idArtLang . " AND a.idtype=b.idtype AND a.typeid = " . $typeId . " AND b.type = '" . $type . "'
            ORDER BY a.idartlang, a.idtype, a.typeid";
            $this->db->query($sql);
            while ($this->db->nextRecord()) {
                    $idContent = $this->db->f('idcontent');
            }
        } else {
            $sql = "SELECT a.idcontentversion
            FROM " . cRegistry::getDbTableName('content_version') . " as a, " . cRegistry::getDbTableName('type') . " as b
            WHERE a.version <= " . $version . " AND a.idartlang = " . $idArtLang . " AND a.idtype = b.idtype AND a.typeid = " . $typeId . " AND b.type = '" . $type . "'
            ORDER BY a.version DESC, a.idartlang, a.idtype, a.typeid LIMIT 1";
            $this->db->query($sql);
            while ($this->db->nextRecord()) {
                $idContent = $this->db->f('idcontentversion');                                   
            }			
        }

        return $idContent;
		
    }
	    
    /**
     * Returns $artLangVersionMap[version][idartlangversion] = lastmodified
     * either from each article-/content- or metatag-version 
     *
     * @param int $idArtLang
     * @param string $selectElementType either 'content', 'seo' or 'config'
     * @return array $artLangVersionMap
     */	
    public function getDataForSelectElement($idArtLang, $selectElementType = '') {
        
        $artLangVersionMap = array();
        
        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersionColl->addResultField('version');
        $artLangVersionColl->addResultField('lastmodified');
        $artLangVersionColl->setWhere('idartlang', $idArtLang);
        $artLangVersionColl->setOrder('version desc');
        
        try {
            if ($selectElementType == 'content') {
                // select only versions with different content versions
                $contentVersionColl = new cApiContentVersionCollection();
                $contentVersionColl->addResultField('version');
                $contentVersionColl->setWhere('idartlang', $idArtLang);
                $contentVersionColl->query();
                $contentFields['version'] = 'version';

                // check ...
                if (0 >= $contentVersionColl->count()) {
                    throw new cException('no content versions');
                }   

                $table = $contentVersionColl->fetchTable($contentFields);

                // add ...
                $contentVersionMap = array();
                foreach ($table AS $key => $item) {
                    $contentVersionMap[] = $item['version'];
                }
                $contentVersionMap = array_unique($contentVersionMap);
                $artLangVersionColl->setWhere('version', $contentVersionMap, 'IN');

            } else if ($selectElementType == 'seo') {
                // select only versions with different seo versions
                $metaVersionColl = new cApiMetaTagVersionCollection();
                $metaVersionColl->addResultField('version');
                $metaVersionColl->setWhere('idartlang', $idArtLang);
                $metaVersionColl->query();
                $metaFields['version'] = 'version';
                
                // check...
                if (0 >= $metaVersionColl->count()) {
                    throw new cException('no meta versions');
                }   

                $table = $metaVersionColl->fetchTable($metaFields);
                
                // add ...
                $metaVersionMap = array();
                foreach ($table AS $key => $item) {
                    $metaVersionMap[] = $item['version'];
                }
                $metaVersionMap = array_unique($metaVersionMap);
                $artLangVersionColl->setWhere('version', $metaVersionMap, 'IN');
                
            } else if ($selectElementType == 'config') {
                // select all versions
            }
        } catch (cException $e) {
            return $artLangVersionMap;
        }
        
        $artLangVersionColl->query();

        $fields['idartlangversion'] = 'idartlangversion';
        $fields['version'] = 'version';
        $fields['lastmodified'] = 'lastmodified';        

        if (0 < $artLangVersionColl->count()) {
            $table = $artLangVersionColl->fetchTable($fields);

            foreach ($table AS $key => $item) {
                $artLangVersionMap[$item['version']][$item['idartlangversion']] = $item['lastmodified'];
            }
        }        
        
        return $artLangVersionMap;

    }
    
    /**
     * Prepares content for saving (consider versioning-mode; prevents multiple storings for filelists e.g.)
     *
     * @param $idartlang the contents idartlang
     * @param cApiContent $content the content to store
     * @param $value the contents value to store
     *
     */
    public function prepareContentForSaving($idartlang, cApiContent $content, $value) {
        $versioningState = $this->getState();
        $date = date('Y-m-d H:i:s');
        $author = $auth->auth['uname'];
        
        // through a contenido bug filelists save each of their changes multiple times. 
        // therefor its nececcary to check if the same change already has been saved and
        // prevent multiple savings
        static $savedTypes = array ();
        $isAlreadySaved = false;
        
        if (!array_key_exists($content->get('idtype').$content->get('typeid'), $savedTypes)) {
            $savedTypes[$content->get('idtype').$content->get('typeid')] = $value;
        } else {
            $isAlreadySaved = true;
        }
        
       if ($isAlreadySaved == false) { 
        switch ($versioningState) {
            case 'simple':
                // Create Content Version
                $idContent = NULL;
                if ($content->isLoaded()) {
                    $idContent = $content->getField('idcontent');
                }
        
                if ($idContent == NULL) {
                    $idContent = $this->getMaxIdContent() + 1;
                }
        
                $parameters = array(
                    'idcontent' => $idContent,
                    'idartlang' => $idartlang,
                    'idtype' => $content->get('idtype'),
                    'typeid' => $content->get('typeid'),
                    'value' => $value,
                    'author' => $author,
                    'created' => $date,
                    'lastmodified' => $date
                );
        
                $this->createContentVersion($parameters);
            case 'disabled':
                if ($content->isLoaded()) {
                    // Update existing entry
                    $content->set('value', $value);
                    $content->set('author', $author);
                    $content->set('lastmodified', date('Y-m-d H:i:s'));
                    $content->store();
                } else {
                    // Create new entry
                    $contentColl = new cApiContentCollection();
                    $content = $contentColl->create(
                        $idartlang,
                        $content->get('idtype'),
                        $content->get('typeid'),
                        $value,
                        0,
                        $author,
                        $date,
                        $date
                    );
                }
        
                // Touch the article to update last modified date
                $lastmodified = date('Y-m-d H:i:s');
                $artLang = new cApiArticleLanguage($idartlang);
                $artLang->set('lastmodified', $lastmodified);
                $artLang->set('modifiedby', $author);
                $artLang->store();
        
                break;
            case 'advanced':
                // Create Content Version
                $idContent = NULL;
                if ($content->isLoaded()) {
                    $idContent = $content->getField('idcontent');
                }
        
                if ($idContent == NULL) {
                    $idContent = $this->getMaxIdContent() + 1;
                }
        
                $parameters = array(
                    'idcontent' => $idContent,
                    'idartlang' => $idartlang,
                    'idtype' => $content->get('idtype'),
                    'typeid' => $content->get('typeid'),
                    'value' => $value,
                    'author' => $author,
                    'created' => $date,
                    'lastmodified' => $date
                );
        
                $this->createContentVersion($parameters);
            default:
                break;
        }
       }
    }
    
    
    
    /**
     * Create new Content Version
     *
     * @param mixed[] $parameters {
     *  @type int $idContent
     *  @type int $idArtLang
     *  @type int $idType
     *  @type int $typeId
     *  @type string $value
     *  @type string $author
     *  @type string $created
     *  @type string $lastModified
     * }
    */
    public function createContentVersion(array $parameters) {
    	// Create new Article Language Version and get the version number
    		
    	// set parameters for Article Language Version
    	$currentArticle = cRegistry::getArticleLanguage();
    	
    	$parametersArticleVersion = array(
            'idartlang' => $currentArticle->getField('idartlang'),
            'idart' => $currentArticle->getField('idart'),
            'idlang' => $currentArticle->getField('idlang'),
            'title' => $currentArticle->getField('title'),
            'urlname' => $currentArticle->getField('urlname'),
            'pagetitle' => $currentArticle->getField('pagetitle'),
            'summary' => $currentArticle->getField('summary'),
            'artspec' => $currentArticle->getField('artspec'),
            'created' => $currentArticle->getField('created'),
            'iscurrentversion' => 1,
            'author' => $currentArticle->getField('author'),
            'lastmodified' => date('Y-m-d H:i:s'),
            'modifiedby' => $currentArticle->getField('author'),
            'published' => $currentArticle->getField('published'),
            'publishedby' => $currentArticle->getField('publishedby'),
            'online' => $currentArticle->getField('online'),
            'redirect' => $currentArticle->getField('redirect'),
            'redirect_url' => $currentArticle->getField('redirect_url'),
            'external_redirect' => $currentArticle->getField('external_redirect'),
            'artsort' => $currentArticle->getField('artsort'),
            'timemgmt' => $currentArticle->getField('timemgmt'),
            'datestart' => $currentArticle->getField('datestart'),
            'dateend' => $currentArticle->getField('dateend'),
            'status' => $currentArticle->getField('status'),
            'time_move_cat' => $currentArticle->getField('time_move_cat'),
            'time_target_cat' => $currentArticle->getField('time_target_cat'),
            'time_online_move' => $currentArticle->getField('time_online_move'),
            'locked' => $currentArticle->getField('locked'),
            'free_use_01' => $currentArticle->getField('free_use_01'),
            'free_use_02' => $currentArticle->getField('free_use_02'),
            'free_use_03' => $currentArticle->getField('free_use_03'),
            'searchable' => $currentArticle->getField('searchable'),
            'sitemapprio' => $currentArticle->getField('sitemapprio'),
            'changefreq' => $currentArticle->getField('changefreq')
    	);
    	
    	$artLangVersion = $this->createArticleLanguageVersion($parametersArticleVersion);
    	
    	// Get the version number of the new Article Language Version that belongs to the Content
    	$parameters['version'] = $artLangVersion->getField('version');
            
        $parametersToCheck = $parameters;
        unset(
                $parametersToCheck['lastmodified'],
                $parametersToCheck['author'],
                $parametersToCheck['value'],
                $parametersToCheck['created']
        );
        
        // if there already is a content type like this in this version, 
        // create a new article version, too (needed for storing a version after
        // first change in simple-mode)
        $contentVersion = new cApiContentVersion();
        //$contentVersion->loadByMany($parameters);
        $contentVersion->loadByMany($parametersToCheck);
        if ($contentVersion->isLoaded()) {
            //foreach ($parameters AS $key => $value) {
                //$contentVersion->set($key, $value);
                $artLangVersion = $this->createArticleLanguageVersion($parametersArticleVersion);
                $parameters['version'] = $artLangVersion->getField('version');
                $contentVersionColl = new cApiContentVersionCollection();
                $contentVersionColl->create($parameters);
            //}
            //$contentVersion->store();     
        } else {
            // if there is no content type like this in this version, create one
            $contentVersionColl = new cApiContentVersionCollection();
            $contentVersionColl->create($parameters);
        }
    }
    
    /**
     * Create new Article Language Version
     *
     * @global int $lang
     * @global object $auth
     * @global string $urlname
     * @global string $page_title
     * @param mixed[] $parameters {
     *  @type int $idart
     *  @type int $idlang
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
    public function createArticleLanguageVersion(array $parameters) {
        global $lang, $auth, $urlname, $page_title;
        // Some stuff for the redirect
        global $redirect, $redirect_url, $external_redirect;
        global $time_move_cat; // Used to indicate "move to cat"
        global $time_target_cat; // Used to indicate the target category
        global $time_online_move; // Used to indicate if the moved article should be
                                  // online
        global $timemgmt;
        
        $page_title = addslashes($page_title);
        $parameters['title'] = stripslashes($parameters['title']);
        $redirect_url = stripslashes($redirect_url);
        $urlname = (trim($urlname) == '')? trim($parameters['title']) : trim($urlname);

        if ($parameters['isstart'] == 1) {
            $timemgmt = 0;
        }

        if (!is_array($parameters['idcatnew'])) {
            $parameters['idcatnew'][0] = 0;
        }	

        // Set parameters for article language version
        $artLangVersionParameters = array(
            'idartlang' => $parameters['idartlang'],
            'idart' => $parameters['idart'],
            'idlang' => $lang,
            'title' => $parameters['title'],
            'urlname' => $urlname,
            'pagetitle' => $page_title,
            'summary' => $parameters['summary'],
            'artspec' => $parameters['artspec'],
            'created' => $parameters['created'],
            'iscurrentversion' => $parameters['iscurrentversion'],
            'author' => $parameters['author'],
            'lastmodified' => date('Y-m-d H:i:s'),
            'modifiedby' => $auth->auth['uname'],
            'published' => $parameters['published'],
            'publishedby' => $parameters['publishedby'],
            'online' => $parameters['online'],
            'redirect' => $redirect,
            'redirect_url' => $redirect_url,
            'external_redirect' => $external_redirect,
            'artsort' => $parameters['artsort'],
            'timemgmt' => $timemgmt,
            'datestart' => $parameters['datestart'],
            'dateend' => $parameters['dateend'],
            'status' => 0,
            'time_move_cat' => $time_move_cat,
            'time_target_cat' => $time_target_cat,
            'time_online_move' => $time_online_move,
            'locked' => 0,
            'free_use_01' => '',
            'free_use_02' => '',
            'free_use_03' => '',
            'searchable' => $parameters['searchable'],
            'sitemapprio' => $parameters['sitemapprio'],
            'changefreq' => $parameters['changefreq']
        );

        // Create article language version entry
        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersion = $artLangVersionColl->create($artLangVersionParameters);
        
        // version Contents if contents are not versioned yet
        if (isset($parameters['idartlang'])){
            $where = 'idartlang = ' . $parameters['idartlang'];
            $contentVersionColl = new cApiContentVersionCollection();        
            $contentVersions = $contentVersionColl->getIdsByWhereClause($where);
        }
        
        if (empty($contentVersions)) {
            $artLang = new cApiArticleLanguage($parameters['idartlang']);
            $artLang->loadArticleContent();
            $conType = new cApiType();
            $content = new cApiContent();
            foreach ($artLang->content AS $type => $typeids) {
                foreach ($typeids AS $typeid => $value) {
                    $conType->loadByType($type);
                    $content->loadByArticleLanguageIdTypeAndTypeId(
                        $parameters['idartlang'],
                        $conType->get('idtype'),
                        $typeid
                    );
                    $content->markAsEditable($artLangVersion->get('version'), 0);
                }
            }
        }
        
        // version meta tags if they are not versioned yet
        if (isset($parameters['idartlang'])) {
            $where = 'idartlang = ' . $parameters['idartlang'];
            $metaTagVersionColl = new cApiMetaTagVersionCollection();        
            $metaTagVersions = $metaTagVersionColl->getIdsByWhereClause($where);  
        }
        
        if (empty($metaTagVersions)) {            
            $where = 'idartlang = ' . $parameters['idartlang'];
            $metaTagColl = new cApiMetaTagCollection();       
            $metaTags = $metaTagColl->getIdsByWhereClause($where);
            $metaTag = new cApiMetaTag();
            foreach ($metaTags AS $id) {
                $metaTag->loadBy('idmetatag', $id);
                $metaTag->markAsEditable($artLangVersion->get('version'));
            }
        }
        
        return $artLangVersion;
    }

    /**
     * Create new Meta Tag Version
     *
     * @param mixed[] $parameters {
     *  @type int $idmetatag
     *  @type int $idartlang
     *  @type string $idmetatype
     *  @type string $value
     *  @type int version
     * }
     * @return cApiMetaTagVersion
    */
    public function createMetaTagVersion(array $parameters) {
        
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        
        return $metaTagVersionColl->create(
                    $parameters['idmetatag'],
                    $parameters['idartlang'],
                    $parameters['idmetatype'],
                    $parameters['value'],
                    $parameters['version']
                ); 
        
    }
}