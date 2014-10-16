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
	
	public function sortResults($result) {
	
		uksort($result, function($a, $b) {

			$cmsct = array(
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

			return array_search(ab, $cmsct) - array_search($b, $cmsct);
			
		});
		
		return $result;
		
	}	

	/**
	 * Returns the current versioning state (false (default), simple, advanced)
	 *
	 * @return $versioningEnabled
	 */
	public static function getEnabled() {
	
		static $versioningEnabled;
		
		if (!isset($versioningEnabled)) {
	
			// versioning enabled is a tri-state => false (default), simple, advanced
			$systemPropColl = new cApiSystemPropertyCollection();
			$prop = $systemPropColl->fetchByTypeName('versioning', 'enabled');
			$versioningEnabled = $prop ? $prop->get('value') : false;
			
			if (false === $versioningEnabled || NULL === $versioningEnabled) {
				$versioningEnabled = 'false';
			} else if ('' === $versioningEnabled) {
				// NOTE: An non empty default value overrides an empty value
				$versioningEnabled = 'false';
			}

		}
		
		return $versioningEnabled;
	
	}
	
	/**
	 * Returns selected article
	 *
	 * @param $idArtLangVersion
	 * @param $idArtLang
	 * @param $articleType
	 * @return $this->selectedArticle
	 */	
	public function getSelectedArticle($idArtLangVersion, $idArtLang, $articleType) {
		
		$editableArticleId = $this->getEditableArticleId($idArtLang);
		
		if ($articleType == 'version' || $articleType == 'editable') {
			if (is_numeric($idArtLangVersion)) {
				$this->selectedArticle = new cApiArticleLanguageVersion($idArtLangVersion);
			} else {
				$this->selectedArticle = new cApiArticleLanguageVersion($editableArticleId);
			}
		} else if ($articleType == 'current') {
			$this->selectedArticle = new cApiArticleLanguage($idArtLang);
		}

		return $this->selectedArticle;
	}
	
	/**
     * Returns $list[1] = CMS_HTMLHEAD for every content
     * existing in article/version with $idArtLang 
	 *
	 * @param $idArtLang
	 * @param $articleType
	 * @return $list
     */		
	public function getList($idArtLang, $articleType) {
		global $cfg;
		
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
	 * @param $copyTo
	 * @param $idArtLangVersion
	 * @param $idArtLang
	 * @param $action
	 * @return $this->articleType
	 */
	public function getArticleType($copyTo, $idArtLangVersion, $idArtLang, $action) {
	
		$editableArticleId = $this->getEditableArticleId($idArtLang);
		
		if ( (($copyTo == 1) && ( ($idArtLangVersion != NULL) && ($idArtLangVersion == $editableArticleId)))
			||  ($idArtLangVersion == NULL && $action == 'con_content')	|| (($idArtLangVersion == 'current') && ($copyTo == 0))) {
			$this->articleType = 'current';
		} else if ($copyTo == 0 && $idArtLangVersion == $editableArticleId 
			|| $copyTo == 1 && $idArtLangVersion != $editableArticleId
			|| $action == 'savecontype' || $action == 10) {
			$this->articleType = 'editable';
		} else {
			$this->articleType = 'version';
		}
		
		return $this->articleType;
		
	}
	
	/**
	 * Returns idartlangversion of editable article
	 *
	 * @param $idArtLang
	 * @return $editableArticleId
	 */
	public function getEditableArticleId($idArtLang) {
		global $cfg;
		
		if ($this->getEnabled() == 'advanced') {
			$sql = 'SELECT max(idartlangversion) AS max FROM %s WHERE idartlang = %d';
			$this->db->query($sql, $cfg['tab']['art_lang_version'], $idArtLang);
			while ($this->db->nextRecord()) {
				$editableArticleId = $this->db->f('max');
			}
			
			
		} else if ($this->getEnabled() == 'simple' || $this->getEnabled() == 'false') {
			return $idArtLang;
		}		
		
		return $editableArticleId;
		
	}
		
	/**
     * Returns $artLangVersionMap[version][idartlangversion] = lastmodified
     * from every Version
	 *
	 * @return $artLangVersionMap
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