<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Class for handling CMS Type File List
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Content Types
 * @version    1.0.0
 * @author     Dominik Ziegler, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.13
 *
 * {@internal
 *   created 2009-10-01
 *   modified 2010-10-29, Dominik Ziegler - fixed CON-362 (removed whitespace from client setting)
 *
 *   $Id$:
 * }}
 *
 */


if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('includes', 'functions.con.php');
cInclude("includes", "functions.upl.php");

/**
 * This class provides all methods for the content type CMS_FILELIST. All properties of the file list are
 * stored as a xml document in the database.
 */
class Cms_FileList {
	/**
	 * Contenido configuration array
	 * @var 	array
	 * @access 	private
	 */
	private $aCfg = array();

	/**
	 * Current id of content type CMS_FILELIST[3] -> 3
	 * @var 	integer
	 * @access 	private
	 */
	private $iId = 0;

	/**
	 * Contenido database object
	 * @var 	object
	 * @access 	private
	 */
	private $oDb = null;

	/**
	 * Idartlang of article, which is currently in edit- or viewmode
	 * @var 	integer
	 * @access 	private
	 */
	private $iIdArtLang = 0;

	/**
	 * List of fieldnames in frontend (properties) which the file list has
	 * and which were also described in the config xml document
	 * @var 	array
	 * @access 	private
	 */
	private $aFileListData = array();

	/**
	 * String contains value of stored content in database
	 * in this case this is the config xml document which is
	 * later parsed and its settings were stored in $aSettings
	 * @var		string
	 * @access 	private
	 */
	private $sContent = "";

	/**
	 * Array which contains current file list settings
	 * @var 	array
	 * @access 	private
	 */
	private $aSettings = array();

	/**
	 * Current Contenido client id
	 * @var 	integer
	 * @access 	private
	 */
	private $iClient = 0;

	/**
	 * Current Contenido language id
	 * @var 	integer
	 * @access 	private
	 */
	private $iLang = 0;

	/**
	 * Contenido session object
	 * @var 	object
	 * @access 	private
	 */
	private $oSess = null;

	/**
	 * Contenido configuration array for current active client
	 * @var 	array
	 * @access 	private
	 */
	private $aCfgClient = array();

	/**
	 * Array with default file extensions.
	 * @var 	array
	 * @access 	private
	 */
	private $aFileExtensions = array();

	/**
	 * Array with available meta data identifiers.
	 * @var 	array
	 * @access 	private
	 */
	private $aMetaDataIdents = array();

	/**
	 * Array with the date fields.
	 * @var 	array
	 * @access 	private
	 */
	private $aDateFields = array();

	/**
	 * Contenido upload path for current client
	 * @var		string
	 * @access	private
	 */
	private $sUploadPath = "";

	/**
	 * Placeholders for labels in frontend.
	 * Important: This must be a static array!
	 * @var		array
	 * @access	private
	 */
	private static $aTranslations = array("LABEL_FILESIZE" => "Dateigr&ouml;&szlig;e",
	                                      "LABEL_UPLOAD_DATE" => "Hochgeladen am");

	/**
	 * Constructor of class inits some important class variables and
	 * gets some Contenido global vars, so this class has no need to
	 * use ugly and buggy global commands
	 *
	 * @param string $sContent - xml document from database containing the settings
	 * @param integer $iNumberOfCms - CMS_FILELIST[4] => 4
	 * @param integer $iIdArtLang - Idartlang of current article
	 * @param array $sEditLink - sEditlink for editbuttons, not currently used
	 * @param array $aCfg - Contenido configuration array
	 * @param array $oDB - Contenido database object
	 * @param string $sContenidoLang - Contenido Backend language string
	 * @param integer $iClient - Contenido client id
	 * @param integer $iLang - Contenido frontend language id
	 * @param array $aCfgClient - Contenido Client configuration array
	 * @param object $oSess - Contenido session object
	 *
	 * @access public
	 */
	function __construct($sContent, $iNumberOfCms, $iIdArtLang, $sEditLink, $aCfg, $oDB, $sContenidoLang, $iClient, $iLang, $aCfgClient, $oSess) {
		//set arguments to class variables directly
		$this->aCfg 		= $aCfg;
		$this->iId 			= $iNumberOfCms;
		$this->iIdArtLang 	= $iIdArtLang;
		$this->sContent 	= urldecode($sContent);
		$this->iClient 		= $iClient;
		$this->iLang 		= $iLang;
		$this->aCfgClient 	= $aCfgClient;
		$this->oSess 		= $oSess;

		//init other variables with default values
		$this->oDb 			= new DB_Contenido();
		$this->sUploadPath 	= $this->aCfgClient[$this->iClient]['upl']['path'];

		//define class array which contains all names of the filelist properties. They were also base for generating dynamic javascripts for
		//retrival this properties out of html forms and retriving their values to screen
		$this->aFileListData 	= array(	'filelist_title', 'filelist_style', 'filelist_directories', 'filelist_incl_subdirectories',
											'filelist_manual', 'filelist_sort', 'filelist_incl_metadata', 'filelist_extensions',
											'filelist_sortorder', 'filelist_filesizefilter_from', 'filelist_filesizefilter_to',
											'filelist_ignore_extensions', 'filelist_manual_files', 'filelist_filecount');

		// defines the default extensions displayed in the filelist
		// additional extensions can be added via client settings
		$this->aFileExtensions 	= array(	'gif', 'jpeg', 'jpg', 'png', 'doc', 'xls', 'pdf', 'txt', 'zip', 'ppt' );

		$this->aDateFields 		= array(	'ctime' => 'creationdate', 'mtime' => 'modifydate' );

		$this->aMetaDataIdents 	= array(	'description' => 'Description',
											'medianame' => 'Media name',
											'copyright' => 'Copyright',
											'keywords' => 'Keywords',
											'internal_notice' => 'Internal notes');

		// dynamically add file list data based on the meta data idents
		foreach ( $this->aMetaDataIdents as $sIdentName => $sTranslation ) {
			$this->aFileListData[] = 'filelist_md_' . $sIdentName . '_limit';
		}

		// dynamically add file list data based on the date fields
		foreach ( $this->aDateFields as $sIdentName => $sDateField ) {
			$this->aFileListData[] = 'filelist_' . $sDateField . 'filter_from';
			$this->aFileListData[] = 'filelist_' . $sDateField . 'filter_to';
		}

		//if form is submitted there is a need to store current file list settings
		//notice: there is also a need, that filelist_id is the same (case: more than one cms file list is used on the same page
		if (isset($_POST['filelist_action']) && $_POST['filelist_action'] == 'store' &&
		    isset($_POST['filelist_id']) && (int)$_POST['filelist_id'] == $this->iId) {
			$this->storeFileList();
		}

		//in sContent XML Document is stored, which contains files settings, call function which parses this document and store
		//properties as easy accessible array into $aSettings
		if (trim($this->sContent) != '') {
			$this->readSettings();
		}
	}

	/**
	 * Returns all translation strings for mi18n.
	 *
	 * @param	array	$aTranslationStrings	Array with translation strings
	 * @return	array	Translation strings
	 */
	static public function addModuleTranslations($aTranslationStrings) {
		foreach(self::$aTranslations as $sValue) {
			$aTranslationStrings[] = $sValue;
		}

		return $aTranslationStrings;
	}


	/**
	 * Function parses XML document which contains file list settings
	 * and store properties as array into $aSettings
	 *
	 * @access 	private
	 * @return	void
	 */
	private function readSettings() {
		//use XMLReader for parsing XML document
		$oXmlReader = new XMLReader();
		$oXmlReader->XML($this->sContent);

		$sLastNode = '';

		$bPutInExtArray = $bPutInDirArray = $bPutInFileArray = false;

		$this->aSettings['filelist_extensions'] 	= array();
		$this->aSettings['filelist_directories'] 	= array();
		$this->aSettings['filelist_manual_files'] 	= array();

		while($oXmlReader->read()) {
			switch ($oXmlReader->nodeType) {
			  //read property name (ignore root node or block of manual arts for teaser)
			  case XMLReader::ELEMENT:
			  if (	$oXmlReader->name != 'filelist' &&
					$oXmlReader->name != 'extensions' &&
					$oXmlReader->name != 'ext' &&
					$oXmlReader->name != 'directories' &&
					$oXmlReader->name != 'dir' &&
					$oXmlReader->name != 'manual_files' &&
					$oXmlReader->name != 'file' ) {
				$sLastNode = 'filelist_'.$oXmlReader->name;
				$this->aSettings[$sLastNode] = '';
			  }

			  if ($oXmlReader->name == 'ext') {
				$bPutInExtArray = true;
			  }

			  if ($oXmlReader->name == 'dir') {
				$bPutInDirArray = true;
			  }

			  if ($oXmlReader->name == 'file') {
				$bPutInFileArray = true;
			  }
			  break;

			  case XMLReader::TEXT:
				if ($bPutInExtArray == true) {
					$bPutInExtArray = false;
					array_push($this->aSettings['filelist_extensions'], $oXmlReader->value);
				} else if ($bPutInDirArray == true) {
					$bPutInDirArray = false;
					array_push($this->aSettings['filelist_directories'], $oXmlReader->value);
				} else if ($bPutInFileArray == true) {
					$bPutInFileArray = false;
					array_push($this->aSettings['filelist_manual_files'], $oXmlReader->value);
				} else {
					$this->aSettings[$sLastNode] = $oXmlReader->value;
				}
				break;
		  }
		}
	}

	/**
	 * Function gets all submitted values for new file list properties from
	 * $_POST array, generates new corresponding config XML Document and
	 * stores it as content, using contenido conSaveContentEntry() function
	 *
	 * @access 	private
	 * @return	void
	 */
	private function storeFileList() {
		//create new xml document, its encoding and root node
		$oXmlDom = new DOMDocument('1.0', 'iso-8859-1');
		$oXmlDom->formatOutput = true;
		$oRootNode = $oXmlDom->createElement('filelist');
		$oXmlDom->appendChild($oRootNode);

		// $this->aFileListData defines all file list properties, so try to read them from $_POST
		foreach ($this->aFileListData as $sParam) {
			//in case of article list for manual teaser do a special behaviour
		    if ($sParam == 'filelist_extensions') {
				$oParam = $oXmlDom->createElement(str_replace('filelist_', '', $sParam));
				//split all arts to array
				$aExts = explode(';', Contenido_Security::toString($_POST[$sParam]));

				//for each artid generate subnote in xml document and store its value
				foreach ($aExts as $sExt) {
					$sExt = (string) $sExt;
					if ($sExt != "") {
						$oExt = $oXmlDom->createElement('ext', $sExt);
						$oParam->appendChild($oExt);
					}
				}
			} else if ($sParam == 'filelist_directories') {
				$oParam = $oXmlDom->createElement(str_replace('filelist_', '', $sParam));
				//split all arts to array
				$aDirs = explode(';', Contenido_Security::toString($_POST[$sParam]));

				//for each artid generate subnote in xml document and store its value
				foreach ($aDirs as $sDir) {
					$sDir = (string) $sDir;
					if ($sDir != "") {
						$oDir = $oXmlDom->createElement('dir', $sDir);
						$oParam->appendChild($oDir);
					}
				}
			} else if ($sParam == 'filelist_manual_files') {
				$oParam = $oXmlDom->createElement(str_replace('filelist_', '', $sParam));
				//split all arts to array
				$aFiles = explode(';', Contenido_Security::toString($_POST[$sParam]));

				//for each artid generate subnote in xml document and store its value
				foreach ($aFiles as $sFile) {
					$sFile = (string) $sFile;
					if ($sFile != "") {
						$oFile = $oXmlDom->createElement('file', $sFile);
						$oParam->appendChild($oFile);
					}
				}
			} else if ( $sParam == 'filelist_creationdatefilter_from' || $sParam == 'filelist_creationdatefilter_to' ||
						$sParam == 'filelist_modifydatefilter_from' || $sParam == 'filelist_modifydatefilter_to' ) {

				$sValue = Contenido_Security::toString($_POST[$sParam]);
				// check if value is set and if its length equals ten characters
				// (two for day, two for month, four for year and two for the points)
				if ( $sValue != "" && $sValue != "DD.MM.YYYY" && strlen( $sValue ) == 10 ) {
					$aDateSplits = explode(".", $sValue);
					$iTimestamp = mktime(0, 0, 0, (int) $aDateSplits[1], (int) $aDateSplits[0], (int) $aDateSplits[2]);
				} else {
					$iTimestamp = 0;
				}

				$oParam = $oXmlDom->createElement(str_replace('filelist_', '', $sParam), $iTimestamp);
			} else {
				//generate xml node for current property and store its value
				$oParam = $oXmlDom->createElement(str_replace('filelist_', '', $sParam), utf8_encode(Contenido_Security::toString($_POST[$sParam])));
			}

			$oXmlDom->firstChild->appendChild($oParam);
		}

		//serialize xml document and store new version in class variable and database
		conSaveContentEntry($this->iIdArtLang, 'CMS_FILELIST', $this->iId, $oXmlDom->saveXML(), true);
		$this->sContent = $oXmlDom->saveXML();
	}

	/**
	 * Function which generate a select box for setting filelist style.
	 * @access 	private
	 *
	 * @param 	string 	$sSelected	value of select box which is selected
	 * @return 	string	rendered cHTMLSelectElement
	 */
	private function getStyleSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('filelist_style', "", 'filelist_style');

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Default style"), 'cms_filelist_style_default.html', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		$aAdditionalOptions = getEffectiveSettingsByType('cms_filelist_style');
		$i = 1;
		foreach ($aAdditionalOptions as $sLabel => $sTemplate) {
			$oHtmlSelectOption = new cHTMLOptionElement($sLabel, $sTemplate, false);
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
			$i++;
		}

		$oHtmlSelect->setDefault($sSelected);
		return $oHtmlSelect->render();
	}

	/**
	 * Function which generate a select box for the filelist sort.
	 * @access 	private
	 *
	 * @param 	string 	$sSelected	value of select box which is selected
	 * @return 	string	rendered cHTMLSelectElement
	 */
	private function getSortSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('filelist_sort', "", 'filelist_sort');

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Filename"), 'filename', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("File size"), 'filesize', false);
		$oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Date created"), 'createdate', false);
		$oHtmlSelect->addOptionElement(2, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Date modified"), 'modifydate', false);
		$oHtmlSelect->addOptionElement(3, $oHtmlSelectOption);

		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

	/**
	 * Function which generate a select box for the filelist sort order.
	 * @access 	private
	 *
	 * @param 	string 	$sSelected	value of select box which is selected
	 * @return 	string	rendered cHTMLSelectElement
	 */
	private function getSortOrderSelect($sSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('filelist_sortorder', "", 'filelist_sortorder');

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Ascending"), 'asc', true);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		$oHtmlSelectOption = new cHTMLOptionElement(i18n("Descending"), 'desc', false);
		$oHtmlSelect->addOptionElement(1, $oHtmlSelectOption);

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

	/**
	 * Function which generate a select box for the filelist extensions.
	 * @access 	private
	 *
	 * @param 	array 	$aSelected	array with values which are selected
	 * @return 	string	rendered cHTMLSelectElement
	 */
	private function getExtensionSelect($aSelected) {
		$oHtmlSelect = new 	cHTMLSelectElement ('filelist_extensions', "", 'filelist_extensions');

		//set other avariable options manually
		$i = 1;
		foreach ( $this->aFileExtensions as $sFileExtension ) {
			$oHtmlSelectOption = new cHTMLOptionElement( uplGetFileTypeDescription( $sFileExtension ) . " (." . $sFileExtension . ")", $sFileExtension, false );
			$oHtmlSelectOption->setAlt(uplGetFileTypeDescription( $sFileExtension ) . " (." . $sFileExtension . ")");
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
			$i++;
		}

		$aAdditionalOptions = getEffectiveSettingsByType('cms_filelist_extensions');
		foreach ($aAdditionalOptions as $sLabel => $sExtension) {
			$oHtmlSelectOption = new cHTMLOptionElement( $sLabel . " (." . $sExtension . ")", $sExtension, false );
			$oHtmlSelectOption->setAlt($sLabel . " (." . $sExtension . ")");
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
			$i++;
		}

		//set default values
		$oHtmlSelect->setSelected ( $aSelected );
		$oHtmlSelect->setMultiselect();
		$oHtmlSelect->setSize(5);
		$oHtmlSelect->setClass("manual");

		return $oHtmlSelect->render();
	}

	 /**
	  * Function which generate a select box for the manual files.
	  *
	  * @param 	array 	$sDirectoryPath	Path to directory of the files
	  * @return 	string	rendered cHTMLSelectElement
	  */
	public function getFileSelect($sDirectoryPath = "") {
		$oHtmlSelect = new cHTMLSelectElement ('filelist_filename', "", 'filelist_filename');

		$i = 0;
		if ($sDirectoryPath != "" ) {
			$sUploadPath = $this->aCfgClient[$this->iClient]['upl']['path'];
			$oHandle = opendir($sUploadPath.$sDirectoryPath);
			while($sEntry = readdir($oHandle)) {
				if ( $sEntry != "." && $sEntry != ".." &&
					 file_exists( $sUploadPath.$sDirectoryPath."/".$sEntry ) &&
					 !is_dir( $sUploadPath.$sDirectoryPath."/".$sEntry ) ) {
					$oHtmlSelectOption = new cHTMLOptionElement($sEntry, $sDirectoryPath."/".$sEntry, false);
					$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
					$i++;
				}
			}

			closedir($oHandle);
		}

		if ( $i == 0 ) {
			$oHtmlSelectOption = new cHTMLOptionElement( i18n('No files found'), '', false );
			$oHtmlSelectOption->setAlt( i18n('No files found') );
			$oHtmlSelectOption->setDisabled( true );
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
			$oHtmlSelect->setDisabled( true );
		}

		//set default value
		$oHtmlSelect->setDefault($sSelected);

		return $oHtmlSelect->render();
	}

	 /**
	  * Function which generate a select box for existing files in the manual filelist.
	  *
	  * @param 	array 	$sDirectoryPath	Path to directory of the files
	  * @return	string	rendered cHTMLSelectElement
	  */
	private function getExistingFileSelect() {
		$aSelectedFiles = $this->aSettings['filelist_manual_files'];
		$oHtmlSelect = new 	cHTMLSelectElement ('filelist_manual_files', "", 'filelist_manual_files');
		$i = 0;

        if (is_array($aSelectedFiles)) {
    		foreach ( $aSelectedFiles as $sSelectedFile ) {
    			$aSplits = explode("/", $sSelectedFile);
    			$iSplitCount = count( $aSplits );
    			$sFileName = $aSplits[$iSplitCount - 1];
    			$oHtmlSelectOption = new cHTMLOptionElement( $sFileName, $sSelectedFile, false );
    			$oHtmlSelectOption->setAlt( $sFileName );
    			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
    			$i++;
    		}
        }

		//set default values
		$oHtmlSelect->setMultiselect();
		$oHtmlSelect->setSize(5);
		$oHtmlSelect->setClass("manual");

		return $oHtmlSelect->render();
	}

		public function getMetaDataList() {
		$oTpl = new Template();

		$i = 1;
		foreach ( $this->aMetaDataIdents as $sIdentName => $sTranslation ) {

			$iMetaDataLimit = $this->aSettings['filelist_md_' . $sIdentName . '_limit'];
			if ( !isset ( $iMetaDataLimit ) || $iMetaDataLimit == "" ) {
				$iMetaDataLimit = 0;
			}

			$oTpl->set('d', 'METADATA_NAME', 		$sIdentName);
			$oTpl->set('d', 'METADATA_DISPLAYNAME',	i18n($sTranslation));
			$oTpl->set('d', 'METADATA_LIMIT', 		$iMetaDataLimit);

			$i++;
			$oTpl->next();
		}

		return $oTpl->generate($this->aCfg['path']['contenido'] . 'templates/standard/template.cms_filelist_metadata_limititem.html', 1);
	}

	/**
	 * Returns the directory list of an given directory array (by buildDirectoryList()).
	 *
	 * @param 	array 	$aDirs	Array with directory information
	 * @return	string	html of the directory list
	 */
	public function getDirectoryList( $aDirs ) {
		$oTpl = new Template();
		$i = 1;

		foreach ( $aDirs as $aDirData ) {
			$sRelativePath = str_replace( $this->sUploadPath, '', $aDirData['path'] ) . $aDirData['name'];
			$sLiClasses = '';

			if ( is_array( $this->aSettings['filelist_directories'] ) && in_array( $sRelativePath, $this->aSettings['filelist_directories'] ) ) {
				$oTpl->set('d', 'DIVCLASS', ' class="active"');
			} else {
				$oTpl->set('d', 'DIVCLASS', '');
			}

			$oTpl->set('d', 'TITLE', $sRelativePath);
			$oTpl->set('d', 'DIRNAME', $aDirData['name']);

			$bGo = false;

            if (is_array($this->aSettings['filelist_directories'])) {
    			foreach ( $this->aSettings['filelist_directories'] as $sDirectoryName ) {
    				if ( preg_match ('#^'.$sRelativePath.'/.*#', $sDirectoryName) ) {
    					$bGo = true;
    					break;
    				}
    			}
            }

			if ( $bGo == true ) {
				$oTpl->set('d', 'SUBDIRLIST', $this->getDirectoryList( $aDirData['sub'] ) );
			} else if ( isset( $aDirData['sub'] ) && count( $aDirData['sub'] ) > 0 ) {
				$sLiClasses .= " collapsed";
				$oTpl->set('d', 'SUBDIRLIST', '');
			} else {
				$oTpl->set('d', 'SUBDIRLIST', '');
			}

			if ($i == count($aDirs)) {
				$sLiClasses .= " last";
			}

			if ( $sLiClasses != "" ) {
				$oTpl->set('d', 'LICLASS', ' class="'.substr($sLiClasses, 1).'"');
			} else {
				$oTpl->set('d', 'LICLASS', '');
			}

			$i++;
			$oTpl->next();
		}

		return $oTpl->generate($this->aCfg['path']['contenido'] . 'templates/standard/template.cms_filelist_dirlistitem.html', 1);
	}

	 /**
	  * Builds a directory list by a given upload directory path.
	  *
	  * @param 	string 	$sUploadPath	Path to directory (per default the root upload path of client)
	  * @return	array	Array with directory information
	  */
	public function buildDirectoryList( $sUploadPath = "" ) {
		if ( $sUploadPath == "") {
			$sUploadPath = $this->sUploadPath;
		}

		if ( substr( $sUploadPath, -1 ) != "/" ) {
			$sUploadPath = $sUploadPath."/";
		}

		$aDirectories = array();
		$oHandle = opendir($sUploadPath);
		$i = 0;
        while($sEntry = readdir($oHandle)) {
			if ( $sEntry != "." && $sEntry != ".." && is_dir( $sUploadPath . $sEntry ) ) {
				$aDirectories[$i]['name'] = $sEntry;
				$aDirectories[$i]['path'] = $sUploadPath;
				$aDirectories[$i]['sub'] = $this->buildDirectoryList( $sUploadPath . $sEntry );
				$i++;
			}
		}

		closedir($oHandle);
		return $aDirectories;
	}

	/**
	 * Function is called in editmode of contenido an returns filelist view and editbutton
	 *
	 * @return	string	code for the backend edit view
	 */
	public function getAllWidgetEdit() {
		$oTpl = new Template();

		/*Set some values into javascript for a better handling*/
		$oTpl->set('s', 'CON_PATH', 							$this->aCfg['path']['contenido_fullhtml']);
		$oTpl->set('s', 'ID', 									$this->iId);
		$oTpl->set('s', 'IDARTLANG',							$this->iIdArtLang);
		$oTpl->set('s', 'CONTENIDO', 							$_REQUEST['contenido']);
		$oTpl->set('s', 'FIELDS', 								"'".implode("','",$this->aFileListData)."'");

		if ( $this->aSettings['filelist_ignore_extensions'] == 'on' ) {
			$oTpl->set('s', 'IGNOREEXTENSIONS', 				'true');
		} else {
			$oTpl->set('s', 'IGNOREEXTENSIONS', 				'false');
		}

		/*Start set a lot of translations*/
		$oTpl->set('s', 'DIRECTORIES', 							i18n("Directories"));
		$oTpl->set('s', 'GENERAL', 								i18n("General"));
		$oTpl->set('s', 'MANUAL', 								i18n("Manual"));
		$oTpl->set('s', 'FILTER', 								i18n("Filter"));

		$oTpl->set('s', 'LABEL_GENERAL', 						i18n("General settings"));
		$oTpl->set('s', 'LABEL_MANUAL', 						i18n("Manual settings"));
		$oTpl->set('s', 'LABEL_FILTER', 						i18n("Filter settings"));
		$oTpl->set('s', 'LABEL_FILELIST_SETTINGS', 				i18n("File list settings"));
		$oTpl->set('s', 'LABEL_FILELIST_TITLE', 				i18n("File list title"));
		$oTpl->set('s', 'LABEL_FILELIST_FILESIZE_LIMIT', 		i18n("File size limit"));
		$oTpl->set('s', 'LABEL_FILELIST_CREATIONDATE_LIMIT', 	i18n("Creation date limit"));
		$oTpl->set('s', 'LABEL_FILELIST_MODIFYDATE_LIMIT', 		i18n("Modify date limit"));

		$oTpl->set('s', 'LABEL_STYLE', 							i18n("File list style"));
		$oTpl->set('s', 'LABEL_SOURCE_DIRECTORY', 				i18n("Source directory"));
		$oTpl->set('s', 'LABEL_INCLUDE_SUBDIRECTORIES', 		i18n('Include subdirectories?'));
		$oTpl->set('s', 'LABEL_INCLUDE_METADATA', 				i18n('Include meta data?'));
		$oTpl->set('s', 'LABEL_SORT', 							i18n("File list sort"));
		$oTpl->set('s', 'LABEL_SORTORDER', 						i18n("Sort order"));
		$oTpl->set('s', 'LABEL_FILE_EXTENSIONS', 				i18n("Displayed file extensions"));
		$oTpl->set('s', 'LABEL_IGNORESELECTION', 				i18n('Ignore selection (use all)'));
		$oTpl->set('s', 'LABEL_SELECTIONWILLBEIGNORED', 		i18n('Selection will be ignored!'));
		$oTpl->set('s', 'LABEL_SELECTALLENTRIES', 				i18n('Select all entries'));
		$oTpl->set('s', 'LABEL_MANUAL_FILELIST', 				i18n("Use manual file list?"));
		$oTpl->set('s', 'LABEL_DIRECTORY',						i18n("Directory"));
		$oTpl->set('s', 'LABEL_FILE', 							i18n("File"));
		$oTpl->set('s', 'LABEL_FILES', 							i18n("Files"));
		$oTpl->set('s', 'LABEL_EXISTING_FILES',					i18n("Existing files"));
		$oTpl->set('s', 'LABEL_ADD_FILE',						i18n("Add file"));
		$oTpl->set('s', 'LABEL_FILECOUNT',						i18n("File count"));
		/*End set a lot of translations*/

		/*Start set values into configuration array and generate select boxes used previous defined values*/
		$oTpl->set('s', 'FILELIST_TITLE', 						$this->aSettings['filelist_title']);
		$oTpl->set('s', 'STYLE_SELECT', 						$this->getStyleSelect($this->aSettings['filelist_style']));
		$oTpl->set('s', 'DIRECTORY_LIST', 						$this->getDirectoryList( $this->buildDirectoryList() ));
		$oTpl->set('s', 'EXTENSION_LIST', 						$this->getExtensionSelect($this->aSettings['filelist_extensions']));

		if ( $this->aSettings['filelist_incl_subdirectories'] == 'true' ) {
			$oTpl->set('s', 'FILELIST_INCL_SUBDIRECTORIES', 	'checked="checked"');
		} else {
			$oTpl->set('s', 'FILELIST_INCL_SUBDIRECTORIES', 	'');
		}

		if ( $this->aSettings['filelist_incl_metadata'] == 'true' ) {
			$oTpl->set('s', 'FILELIST_INCL_METADATA', 			'checked="checked"');
		} else {
			$oTpl->set('s', 'FILELIST_INCL_METADATA', 			'');
		}

		if ( $this->aSettings['filelist_manual'] == 'true' ) {
			$oTpl->set('s', 'FILELIST_MANUAL', 					'checked="checked"');
		} else {
			$oTpl->set('s', 'FILELIST_MANUAL', 					'');
		}

		foreach ( $this->aDateFields as $sDateField ) {
			if ( $this->aSettings['filelist_' . $sDateField . 'filter_from'] != 0 ) {
				$oTpl->set('s', 'FILELIST_' . strtoupper( $sDateField ) . 'FILTER_FROM', date("d.m.Y", $this->aSettings['filelist_' . $sDateField . 'filter_from']));
			} else {
				$oTpl->set('s', 'FILELIST_' . strtoupper( $sDateField ) . 'FILTER_FROM', 'DD.MM.YYYY');
			}

			if ( $this->aSettings['filelist_' . $sDateField . 'filter_to'] != 0 ) {
				$oTpl->set('s', 'FILELIST_' . strtoupper( $sDateField ) . 'FILTER_TO', date("d.m.Y", $this->aSettings['filelist_' . $sDateField . 'filter_to']));
			} else {
				$oTpl->set('s', 'FILELIST_' . strtoupper( $sDateField ) . 'FILTER_TO', 'DD.MM.YYYY');
			}
		}

		$iFilesizeLimitFrom = $this->aSettings['filelist_filesizefilter_from'];
		if ( $iFilesizeLimitFrom == "" ) {
			$iFilesizeLimitFrom = 0;
		}

		$iFilesizeLimitTo = $this->aSettings['filelist_filesizefilter_to'];
		if ( $iFilesizeLimitTo == "" ) {
			$iFilesizeLimitTo = 0;
		}

		$iFileCount = $this->aSettings['filelist_filecount'];
		if ( $iFileCount == "" ) {
			$iFileCount = 0;
		}

		$oTpl->set('s', 'FILELIST_FILESIZEFILTER_FROM', 		$iFilesizeLimitFrom);
		$oTpl->set('s', 'FILELIST_FILESIZEFILTER_TO', 			$iFilesizeLimitTo);
		$oTpl->set('s', 'FILELIST_FILECOUNT',					$iFileCount);
		$oTpl->set('s', 'SORT_SELECT', 							$this->getSortSelect($this->aSettings['filelist_sort']));
		$oTpl->set('s', 'FILE_SELECT', 							$this->getFileSelect());
		$oTpl->set('s', 'SORTORDER_SELECT', 					$this->getSortOrderSelect($this->aSettings['filelist_sortorder']));
		$oTpl->set('s', 'METADATALIST', 						$this->getMetaDataList());
		$oTpl->set('s', 'MANUAL_OPTIONS', 						$this->getExistingFileSelect());
		/*End set values into configuration array and generate select boxes used previous defined values*/

		$sCode = $oTpl->generate($this->aCfg['path']['contenido'].'templates/standard/template.cms_filelist_edit.html', 1);
		return $this->getAllWidgetView( true ) . $this->encodeForOutput($sCode);
	}

	/**
	 * In Contenido content type code is evaled by php. To make this possible,
	 * this function prepares code for evaluation
	 *
	 * @access 	private
	 *
	 * @param 	string 	$sCode 	code to escape
	 * @return 	string	escaped code
	 */
	private function encodeForOutput($sCode) {
		$sCode = (string) $sCode;

		$sCode = AddSlashes(AddSlashes($sCode));
		$sCode = str_replace("\\\'", "'", $sCode);
		$sCode = str_replace("\$", '\\\$', $sCode);

		return $sCode;
	}

	/**
	 * Method to fill single entry (file) of the file list.
	 *
	 * @access 	private
	 *
	 * @param 	array	$aFileData	Array with information about the file
	 * @param	object	$oTpl		Reference of the used template object
	 * @return 	void
	 */
	private function fillFileListTemplateEntry($aFileData, &$oTpl) {
		global $cCurrentModule;

		$sFilename 			= $aFileData['filename'];
		$sDirectoryName 	= $aFileData['path'];
		$sFileExtension 	= $aFileData['extension'];
		$sFileLink 			= $this->aCfgClient[$this->iClient]['upl']['htmlpath'] . $sDirectoryName . "/" . $sFilename;

		$iFilesize			= $aFileData['filesize'];
		$sFileCreationDate	= date( "d.m.Y", $aFileData['filecreationdate'] );
		$sFileModifyDate 	= date( "d.m.Y", $aFileData['filemodifydate'] );

		$aMetaData			= $aFileData['metadata'];

		$sFilesizeUnit = "Byte";
		if ( $iFilesize < 1024 ) {
			$sFilesizeUnit = "Byte";
		} else if ( $iFilesize < ( 1024 * 1024) ) {
			$iFilesize = $iFilesize / 1024;
			$sFilesizeUnit = "KB";
		} else if ( $iFilesize < ( 1024 * 1024 * 1024 ) ) {
			$iFilesize = $iFilesize / 1024 / 1024;
			$sFilesizeUnit = "MB";
		} else if ( $iFilesize < ( 1024 * 1024 * 1024 * 1024 ) ) {
			$iFilesize = $iFilesize / 1024 / 1024 / 1024;
			$sFilesizeUnit = "GB";
		}

		$sFilesize = number_format( $iFilesize, 2, ',', '.');

		if ( $this->aSettings['filelist_incl_metadata'] == 'true' && count ( $aMetaData ) != 0 ) {
			$oTpl->set('d', 'FILEMETA_DESCRIPTION', 	$aMetaData['description']);
			$oTpl->set('d', 'FILEMETA_MEDIANAME', 		$aMetaData['medianame']);
			$oTpl->set('d', 'FILEMETA_KEYWORDS',		$aMetaData['keywords']);
			$oTpl->set('d', 'FILEMETA_INTERNAL_NOTICE', $aMetaData['internal_notice']);
			$oTpl->set('d', 'FILEMETA_COPYRIGHT',		$aMetaData['copyright']);
		} else {
			$oTpl->set('d', 'FILEMETA_DESCRIPTION', 	'');
			$oTpl->set('d', 'FILEMETA_MEDIANAME', 		'');
			$oTpl->set('d', 'FILEMETA_KEYWORDS',		'');
			$oTpl->set('d', 'FILEMETA_INTERNAL_NOTICE', '');
			$oTpl->set('d', 'FILEMETA_COPYRIGHT', 		'');
		}

		$oTpl->set('d', 'FILESIZE_UNIT', 	$sFilesizeUnit);
		$oTpl->set('d', 'FILENAME', 		$sFilename);
		$oTpl->set('d', 'FILESIZE', 		$sFilesize);
		$oTpl->set('d', 'FILEEXTENSION', 	$sFileExtension);
		$oTpl->set('d', 'FILECREATIONDATE', $sFileCreationDate);
		$oTpl->set('d', 'FILEMODIFYDATE', 	$sFileModifyDate);
		$oTpl->set('d', 'FILEDIRECTORY', 	$sDirectoryName);
		$oTpl->set('d', 'FILELINK',		 	$sFileLink);

		foreach( self::$aTranslations as $sKey => $sValue ) {
			$oTpl->set('d', $sKey, mi18n( $sValue ));
		}

		$oTpl->next();
		return true;
	}

	/**
	 * Dynamic filelist generator.
	 * This method is executed every time the filelist is displayed.
	 *
	 * @return	string	output of the filelist
	 */
	public function getAllWidgetView() {
		$sCode = '\";?><?php
					$oFileList = new Cms_FileList(\'%s\', %s, 0, "", $cfg, null, "", $client, $lang, $cfgClient, null);

					echo $oFileList->getAllWidgetOutput();
				 ?><?php echo \"';

		$sCode = sprintf($sCode, $this->sContent, $this->iId);
		return $sCode;
	}

	/**
	 * Checks recursively for sub directories
	 *
	 * @param	string	$sDirectoryPath	Path to directory
	 * @param	array	$aDirectories	Directory array
	 *
	 * @return	array	Directory array
	 */
	public function recursiveCheckForSubdirectories( $sDirectoryPath, $aDirectories ) {
		$oHandle = opendir($this->sUploadPath.$sDirectoryPath);
		while($sEntry = readdir($oHandle)) {
			if ( $sEntry != "." && $sEntry != ".." &&
				is_dir( $this->sUploadPath.$sDirectoryPath."/".$sEntry ) ) {
				$aDirectories[] = $sDirectoryPath."/".$sEntry;
				$aDirectories = $this->recursiveCheckForSubdirectories( $sDirectoryPath."/".$sEntry, $aDirectories );
			}
		}

		return $aDirectories;
	}

	/**
	 * Performs all date filters of a file.
	 *
	 * @param	array	$aFileStats	Array with file information
	 *
	 * @return	boolean	check state (true = tests passed, false = tests not passed)
	 */
	private function performFileDateFilters ( $aFileStats ) {
		$bDateCheck = false;
		foreach ( $this->aDateFields as $sIndex => $sDateField ) {
			$iDate = $aFileStats[$sIndex];
			if ( $this->aSettings['filelist_' . $sDateField . 'filter_from'] == 0 && $this->aSettings['filelist_' . $sDateField . 'filter_from'] == 0 ) {
				$bDateCheck = true;
			} else if ( $this->aSettings['filelist_' . $sDateField . 'filter_to'] == 0 &&
						$iDate >= $this->aSettings['filelist_' . $sDateField . 'filter_from']  )  {
				$bDateCheck = true;
			} else if ( $this->aSettings['filelist_' . $sDateField . 'filter_from'] == 0 &&
						$iDate <= $this->aSettings['filelist_' . $sDateField . 'filter_to']  )  {
				$bDateCheck = true;
			} else if ( $this->aSettings['filelist_' . $sDateField . 'filter_from'] != 0 &&
						$this->aSettings['filelist_' . $sDateField . 'filter_to'] != 0 &&
						$iDate >= $this->aSettings['filelist_' . $sDateField . 'filter_from'] &&
						$iDate <= $this->aSettings['filelist_' . $sDateField . 'filter_to'] )  {
				$bDateCheck = true;
			}
		}

		return $bDateCheck;
	}

	/**
	 * Executes the file filters which removes all files not matching the filter criterias.
	 *
	 * @param	array	$aFileList	array with files to check
	 *
	 * @return	array	array with filtered files
	 */
	private function applyFileFilters ( $aFileList ) {
		foreach ( $aFileList as $iIndex => $sFullname ) {
			$sFilename = basename( $sFullname );
			$sDirectoryName = str_replace( "/" . $sFilename, '', $sFullname );

			// checking the extension stuff
			$sExtensionName = uplGetFileExtension( $sFilename );

			if ( $this->aSettings['filelist_ignore_extensions'] == "on" || count( $this->aSettings['filelist_extensions'] ) == 0 ||
				( $this->aSettings['filelist_ignore_extensions'] == "off" && in_array( $sExtensionName, $this->aSettings['filelist_extensions'] ) ) ) {

				// checking filesize filter
				$aFileStats = stat( $this->sUploadPath.$sDirectoryName."/".$sFilename );
				$iFilesize	= $aFileStats['size'];

				$iFilesizeMib = $iFilesize / 1024 / 1024;
				if ( ( $this->aSettings['filelist_filesizefilter_from'] == 0 &&
					   $this->aSettings['filelist_filesizefilter_to'] == 0 )
					   ||
					 ( $this->aSettings['filelist_filesizefilter_from'] <= $iFilesizeMib &&
					   $this->aSettings['filelist_filesizefilter_to'] >= $iFilesizeMib) ) {

					$bDateCheck = $this->performFileDateFilters ( $aFileStats );

					$iCreationDate = $aFileStats['ctime'];
					$iModifyFate = $aFileStats['mtime'];

					if ( $bDateCheck == true ) {
						// conditional stuff is completed, start sorting
						switch ( $this->aSettings['filelist_sort'] ) {
							case "filesize":
								$sIndexName = $iFilesize;
								break;
							case "createdate":
								$sIndexName = $iCreationDate;
								break;
							case "modifydate":
								$sIndexName = $iModifyDate;
								break;
							case "filename":
							default:
								$sIndexName = strtolower ( $sFilename );
						}

						if ( $sLastIndex == $sIndexName ) {
							$j++;
						} else {
							$j = 1;
						}
						// save current index name
						$sLastIndex = $sIndexName;
						$sIndexName = $sIndexName . "." . $j;

						$aFiles[$sIndexName] = array();
						$aFiles[$sIndexName]['filename'] 			= $sFilename;
						$aFiles[$sIndexName]['path'] 				= $sDirectoryName;
						$aFiles[$sIndexName]['extension'] 			= $sExtensionName;
						$aFiles[$sIndexName]['filesize']			= $iFilesize;
						$aFiles[$sIndexName]['filemodifydate'] 		= $iModifyDate;
						$aFiles[$sIndexName]['filecreationdate']	= $iCreationDate;
						$i++;
					} // end if date check
				} // end if filesize filter
			} // end if extensions
		} // end foreach

		return $aFiles;
	}

	/**
	 * Function is called in edit- and viewmode in order to generate code for output.
	 *
	 * @return	string	generated code
	 */
	public function getAllWidgetOutput() {
		$oTpl = new Template();
		$aFileList = array();
		//set title of teaser
		if ( $this->aSettings['filelist_style'] != "" ) {
			$oTpl->set('s', 'TITLE', htmlentities($this->aSettings['filelist_title']));
			$oTpl->set('s', 'ERROR', '-', 1);

			if ( $this->aSettings['filelist_manual'] == 'true' && count( $this->aSettings['filelist_manual_files'] ) > 0 ) {
				$aFileList = $this->aSettings['filelist_manual_files'];
			} else {
				if ( count ( $this->aSettings['filelist_directories'] ) > 0 ) {
					$aDirectories = $this->aSettings['filelist_directories'];

					if ( $this->aSettings['filelist_incl_subdirectories'] == 'true' ) {
						foreach ( $aDirectories as $sDirectoryName ) {
							$aDirectories = $this->recursiveCheckForSubdirectories($sDirectoryName, $aDirectories);
						}
					}

					// strip duplicate directories to save performance
					$aDirectories = array_unique( $aDirectories );

					foreach ( $aDirectories as $sDirectoryName ) {
						$oHandle = opendir( $this->sUploadPath . $sDirectoryName );

						while( $sEntry = readdir( $oHandle ) ) {
							// checking if entry is file and is not a directory
							if ( $sEntry != "." && $sEntry != ".." && !is_dir( $this->sUploadPath . $sDirectoryName . "/" . $sEntry ) ) {
								$aFileList[] = $sDirectoryName . "/" . $sEntry;
							}
						}
						closedir( $oHandle );
					}
				}
			}

			$aFiles = $this->applyFileFilters( $aFileList );
			unset( $aFileList );

			if ( count ( $aFiles ) > 0 ) {
				// check for descending sort order...
				if ( $this->aSettings['filelist_sortorder'] == 'desc' ) {
					krsort( $aFiles ) ;
				} else {
					ksort ( $aFiles );
				}

				$i = 1;
				foreach ( $aFiles as $aFilenameData ) {
					if ( ( $this->aSettings['filelist_filecount'] != 0 && $i <= $this->aSettings['filelist_filecount'] ) ||
						$this->aSettings['filelist_filecount'] == 0 ) {
						if ( $this->aSettings['filelist_incl_metadata'] == 'true' ) {
							$aMetaData = array();
							$this->oDb->query('SELECT upl.idupl, uplmeta.* FROM ' . $this->aCfg['tab']['upl'] . ' AS upl, ' . $this->aCfg['tab']['upl_meta'] . ' AS uplmeta WHERE upl.idupl = uplmeta.idupl AND upl.filename=\''.$aFilenameData['filename'].'\' AND upl.dirname=\''.$aFilenameData['path'].'/\' AND upl.idclient=\''.$this->iClient.'\' AND uplmeta.idlang=\''.$this->iLang.'\'');
							$this->oDb->next_record();

							foreach ( $this->aMetaDataIdents as $sIdentName => $sTranslation ) {
								if ( $this->aSettings['filelist_md_' . $sIdentName . '_limit'] > 0 ) {
									$aMetaData[$sIdentName] = capiStrTrimAfterWord(	Contenido_Security::unFilter( $this->oDb->f($sIdentName) ),
																					$this->aSettings['filelist_md_' . $sIdentName . '_limit'] ) . '...';
								} else {
									$aMetaData[$sIdentName] = Contenido_Security::unFilter( $this->oDb->f($sIdentName) );
								}
							}

							$aFilenameData['metadata'] = $aMetaData;
						} else {
							$aFilenameData['metadata'] = array();
						}
						$this->fillFileListTemplateEntry( $aFilenameData, $oTpl );
						$i++;
					}
				}

				//generate template
				$sCode = $oTpl->generate($this->aCfgClient[$this->iClient]['path']['frontend'].'templates/' . $this->aSettings['filelist_style'], 1);
			}
		}

		return $sCode;
	}
}
?>