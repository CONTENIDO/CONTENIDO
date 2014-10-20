<?php
/**
 * This file contains the versioning class
 *
 * @package          
 * @subpackage       
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
 * @package 
 * @subpackage 
 */
class cVersioning {
 	
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

			//cms type sort sequence
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
	 * Returns the current versioning state (false (default), simple, advanced)
	 *
	 * @return string $versioningState
	 */
	public static function getState() {
	
		static $versioningState;
		
		if (!isset($versioningState)) {
	
			// versioning enabled is a tri-state => false (default), simple, advanced
			$systemPropColl = new cApiSystemPropertyCollection();
			$prop = $systemPropColl->fetchByTypeName('versioning', 'enabled');
			$versioningState = $prop ? $prop->get('value') : false;
			
			if (false === $versioningState || NULL === $versioningState) {
				$versioningState = 'false';
			} else if ('' === $versioningState) {
				// NOTE: An non empty default value overrides an empty value
				$versioningState = 'false';
			}

		}
		
		return $versioningState;
	
	}
	
	/**
	 * Returns selected article
	 *
	 * @param int $idArtLangVersion
	 * @param int $idArtLang
	 * @param string $articleType
	 * @return cApiArticleLanguage/cApiArticleLanguageVersion $this->selectedArticle
	 */	
	public function getSelectedArticle($idArtLangVersion, $idArtLang, $articleType) {
		
		$editableArticleId = $this->getEditableArticleId($idArtLang);
		$versioningState = $this->getState();
		
		if ( ($articleType == 'version' || $articleType == 'editable') 
			  && ($versioningState == 'advanced') || ($articleType == 'version' && $versioningState == 'simple') ) {
			if (is_numeric($idArtLangVersion)) {
				$this->selectedArticle = new cApiArticleLanguageVersion($idArtLangVersion);
			} else {
				$this->selectedArticle = new cApiArticleLanguageVersion($editableArticleId);
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
		global $cfg;
		
		$list = array();
		
		$sql = 'SELECT DISTINCT b.idtype as idtype, b.type as name
				FROM %s AS a, %s AS b
				WHERE a.idartlang = %d AND a.idtype = b.idtype
				ORDER BY idtype, name';
					
		if ($articleType == 'version' || 'editable') {
			$this->db->query($sql, $cfg['tab']['content_version'], $cfg['tab']['type'], $idArtLang);			
		} else if ($articleType == 'current') {
			$this->db->query($sql, $cfg['tab']['content'], $cfg['tab']['type'], $idArtLang);
		}
		while ($this->db->nextRecord()) {
			$list[$this->db->f('idtype')] = $this->db->f('name');
		}
		
		return $list;
		
	}
	
	/**
	 * Returns type of article (current, version or editable)
	 *
	 * @param int $copyTo
	 * @param int $idArtLangVersion
	 * @param int $idArtLang
	 * @param string $action
	 * @return string $this->articleType
	 */
	public function getArticleType($copyTo, $idArtLangVersion, $idArtLang, $action) {
	
		$editableArticleId = $this->getEditableArticleId($idArtLang);
		
		if ( (($copyTo == 1) && ( ($idArtLangVersion != NULL) && ($idArtLangVersion == $editableArticleId)))
			||  ($idArtLangVersion == NULL && $action == 'con_content')	|| (($idArtLangVersion == 'current') && ($copyTo == 0))) {
			$this->articleType = 'current';
		} else if ($copyTo == 0 && $idArtLangVersion == $editableArticleId 
			|| $copyTo == 1 && $idArtLangVersion != $editableArticleId
			|| $action == 'savecontype' || $action == 10 || $action == 'deletecontype' || $action == 'importrawcontent') {
			$this->articleType = 'editable';
		} else {
			$this->articleType = 'version';
		}
		
		return $this->articleType;
		
	}
	
	/**
	 * Returns idartlangversion of editable article
	 *
	 * @param int $idArtLang
	 * @return int $editableArticleId
	 */
	public function getEditableArticleId($idArtLang) {
		global $cfg;
		
		if ($this->getState() == 'advanced') {
			$sql = 'SELECT max(idartlangversion) AS max FROM %s WHERE idartlang = %d';
			$this->db->query($sql, $cfg['tab']['art_lang_version'], $idArtLang);
			while ($this->db->nextRecord()) {
				$editableArticleId = $this->db->f('max');
			}
			
			
		} else if ($this->getState() == 'simple' || $this->getState() == 'false') {
			return $idArtLang;
		}		
		
		return $editableArticleId;
		
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
		global $cfg;
		
		if ($versioningState == 'simple' && $articleType != 'version'
			|| $versioningState == 'advanced' && $articleType == 'current'
			|| $versioningState == 'false') {
			$sql = "SELECT a.idcontent
			FROM " . $cfg["tab"]["content"] . " as a, " . $cfg["tab"]["type"] . " as b
			WHERE a.idartlang=" . $idArtLang . " AND a.idtype=b.idtype AND a.typeid = " . $typeId . " AND b.type = '" . $type . "'
			ORDER BY a.idartlang, a.idtype, a.typeid";
			$this->db->query($sql);
			while ($this->db->nextRecord()) {
				$idContent = $this->db->f('idcontent');
			}
		} else {
			$sql = "SELECT a.idcontentversion
			FROM " . $cfg["tab"]["content_version"] . " as a, " . $cfg["tab"]["type"] . " as b
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
     * from every Version
	 *
	 * @param int $idArtLang
	 * @return array $artLangVersionMap
     */	
	public function getAllVersionIdArtLangVersionAndLastModified($idArtLang) {
		global $cfg;
		
		$artLangVersionColl = new cApiArticleLanguageVersionCollection();
		$artLangVersionColl->addResultField('version');
		$artLangVersionColl->addResultField('lastmodified');
		$artLangVersionColl->setWhere('idartlang', $idArtLang);
		$artLangVersionColl->query();
		
		$fields['idartlangversion'] = 'idartlangversion';
		$fields['version'] = 'version';
		$fields['lastmodified'] = 'lastmodified';
		$table = $artLangVersionColl->fetchTable($fields);
		
		$artLangVersionMap = array();
		foreach ($table AS $key => $item) {
			$artLangVersionMap[$item['version']][$item['idartlangversion']] = $item['lastmodified'];
		}
		
		return $artLangVersionMap;
		
	}
	
	
	/**
     * Returns all Content or Content Version numbers
     *
	 * @param $idArtLang
     * @param $articleType
	 * @return $versions
     */
/*	public function getVersionNumbersOld($idArtLang, $articleType) {
		global $cfg;
		if ($articleType == 'version' || 'editable') {
			$sql = 'SELECT DISTINCT version
					FROM %s
					WHERE idartlang = %d
					ORDER BY version;';
			$this->db->query($sql, $cfg['tab']['content_version'], $idArtLang);
			while ($this->db->nextRecord()) {
				$versions[] = $this->db->f('version');
			} 
		} else if ($articleType == 'current') {
			$versions[] = 0;
		}
		
		return $versions;
	}*/
	
	/**
     * Returns array with contents from article with
	 *
	 * @param $idartlang
	 * @return $content
     */		
	/*public function getContents($idartlang) {
		$artLang = new cApiArticleLanguage(201);
		$artLang->loadArticleContent();
		$content = $artLang->content;		
		
		return $content;
	}*/
	
	    /**
     * Returns all Content or Content Version typeIds
     *
	 * @param $idArtLang
     * @param $articleType
	 * @return $idType
     */	
	/*public function getTypeIds($idArtLang, $articleType) {
		global $cfg;
		//
		$sql = 'SELECT DISTINCT typeid
				FROM %s
				WHERE idartlang = %d
				ORDER BY typeid;';
		if ($articleType == 'version' || 'editable') {
			$this->db->query($sql, $cfg['tab']['content_version'], $idArtLang);			
		} else {
			$this->db->query($sql, $cfg['tab']['content'], $idArtLang);
		}
		while ($this->db->nextRecord()) {
			$idType[] = $this->db->f('typeid');
		}
		
		return $idType;
	}*/
	
}