<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Class for handling CMS Type Link Editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Content Types
 * @version    1.0.0
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.13
 * 
 * {@internal 
 *   created 2011-07-18
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
 * This class provides all methods for the content type CMS_LINKEDITOR. All properties of the file list are
 * stored as a xml document in the database.
 */
class Cms_LinkEditor {
	/**
	 * CONTENIDO configuration array
	 * @var 	array
	 * @access 	private
	 */
	private $aCfg = array();
	
	/**
	 * Current id of content type CMS_LINKEDITOR[3] -> 3
	 * @var 	integer
	 * @access 	private
	 */
	private $iId = 0;
	
	/**
	 * CONTENIDO database object
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
	 * Current CONTENIDO client id
	 * @var 	integer
	 * @access 	private
	 */
	private $iClient = 0;
	
	/**
	 * Current CONTENIDO language id
	 * @var 	integer
	 * @access 	private
	 */
	private $iLang = 0;
	
	/**
	 * CONTENIDO session object
	 * @var 	object
	 * @access 	private
	 */
	private $oSess = null;
	
	/**
	 * CONTENIDO configuration array for current active client
	 * @var 	array
	 * @access 	private
	 */
	private $aCfgClient = array();	
	
	/**
	 * CONTENIDO Kategorie Root
	 * @var		integer
	 * @access	private
	 */
	private $iRootLevelId = '';
	private $iRootIdcat = '';
	private $sUploadPath = "";
	
	private $hostName = '';
	/**
	 * Constructor of class inits some important class variables and
	 * gets some CONTENIDO global vars, so this class has no need to
	 * use ugly and buggy global commands
	 *
	 * @param string $sContent - xml document from database containing the settings
	 * @param integer $iNumberOfCms - CMS_LINKEDITOR[4] => 4
	 * @param integer $iIdArtLang - Idartlang of current article
	 * @param array $sEditLink - sEditlink for editbuttons, not currently used
	 * @param array $aCfg - CONTENIDO configuration array
	 * @param array $oDB - CONTENIDO database object
	 * @param string $sContenidoLang - CONTENIDO Backend language string
	 * @param integer $iClient - CONTENIDO client id
	 * @param integer $iLang - CONTENIDO frontend language id
	 * @param array $aCfgClient - CONTENIDO Client configuration array
	 * @param object $oSess - CONTENIDO session object
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
		$this->iRootLevelId	= 0;
		$this->iRootIdcat 	= 0;
		$this->imaxLevel 	= $this->getMaxLevel();
		$this->sUploadPath 	= $this->aCfgClient[$this->iClient]['upl']['path'];
		
		//if form is submitted there is a need to store current file list settings
		//notice: there is also a need, that filelist_id is the same (case: more than one cms file list is used on the same page
		if (isset($_POST['linkeditor_action']) && $_POST['linkeditor_action'] == 'store' && isset($_POST['linkeditor_id']) && (int)$_POST['linkeditor_id'] == $this->iId) {
			$this->storeLinkEditor();
		}
				
		//get values
		$aCode = explode(']+[', $this->sContent);
		$this->aLink = array();
		$this->aLink['link_type'] = substr($aCode[0], 1);
		$this->aLink['link_src'] = $aCode[1];
		$this->aLink['link_target'] = $aCode[2];
		$this->aLink['link_title'] = substr($aCode[3], 0, -1);
		//print_r($this->aLink);
		
		$this->getActiveIdcat();
	    //print_r($this->activeIdcat);
	    
	    // Is the user using HTTPS or HTTP?
		$this->hostName = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://';
		if( substr_count(strtolower($this->aLink['link_src']) , "https")){
	    	$this->hostName = 'https://';
		}
	}
		

	
	/**
	 * Function gets all submitted values for new linkeditor properties from 
	 * $_REQUEST array, generates new values and
	 * stores it as content, using CONTENIDO conSaveContentEntry() function
	 *
	 * @access 	private
	 * @return	void
	 */
	private function storeLinkEditor() {	
		$aLink = array();
		$aLink['link_type'] = $_REQUEST['link_type'];
		$aLink['link_src'] = $_REQUEST['link_src'];
		$aLink['link_target'] = $_REQUEST['link_target'];	
		$aLink['link_title'] = $_REQUEST['link_title'];
		$sLink = "[". implode("]+[", $aLink) . "]";
		conSaveContentEntry($this->iIdArtLang, 'CMS_LINKEDITOR', $this->iId, $sLink, true);
		
		//rewrite values
		$this->sContent = $sLink;
		$aCode = explode(']+[', $this->sContent);
		//$this->aLink = array();
		$this->aLink['link_type'] = substr($aCode[0], 1);
		$this->aLink['link_src'] = $aCode[1];
		$this->aLink['link_target'] = $aCode[2];
		$this->aLink['link_title'] = substr($aCode[3], 0, -1);
		
		$this->getActiveIdcat();
	    //print_r($this->activeIdcat);
	    
	}
	
	private function getActiveIdcat(){
		$this->activeIdcat = array();
		if($this->aLink['link_type'] == 'intern'){
			$sql = "SELECT distinct
	                    *
	                FROM
	                    ".$this->aCfg["tab"]["cat_tree"]." AS a,
	                    ".$this->aCfg["tab"]["cat_art"]." AS b,
	                    ".$this->aCfg["tab"]["cat"]." AS c,
	                    ".$this->aCfg["tab"]["cat_lang"]." AS d
	                WHERE          
	                    b.idart = ".$this->aLink['link_src']." AND
	                    a.idcat = d.idcat AND
	                    b.idcat = c.idcat AND
	                    c.idcat = a.idcat AND
	                    d.idlang = '".Contenido_Security::toInteger($this->iLang)."' AND
	                    c.idclient = '".Contenido_Security::toInteger($this->iClient)."' 
	                ORDER BY
	                    a.idtree";     
	        $this->oDb->query($sql);
	        while ( $this->oDb->next_record() ) { 
	        	$this->activeIdcat = $this->getParentIdcatByIdcat($this->oDb->f("idcat"), array());
	        }
		} 
		return $this->activeIdcat;
	}
	
	private function getParentIdcatByIdcat($idcat, $aIdcat){ 
        array_push($aIdcat, $idcat); 
        //print_r($aIdcat);
		$sqlDb = new DB_Contenido;	
		$sql = "SELECT distinct
	                    *
	                FROM
	                    ".$this->aCfg["tab"]["cat"]." AS a
	                WHERE          
	                    a.idcat = ".$idcat;
        $sqlDb->query($sql); 
         
        while ( $sqlDb->next_record() ) {  
        	if($sqlDb->f("parentid") == 0){ 
        		break;
        	} else {
        		$aIdcat = $this->getParentIdcatByIdcat($sqlDb->f("parentid"), $aIdcat);
        	} 
        }
        return $aIdcat;
	}
	
	/**
	 * Function is called in editmode of CONTENIDO an returns linkeditor view and editbutton
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
		
		/*Start set a lot of translations*/
		$oTpl->set('s', 'LABEL_LINKEDITOR_SETTINGS', 			i18n("Link settings"));
		$oTpl->set('s', 'TEXTLINK2FILE', 						i18n("Link to a file"));
		$oTpl->set('s', 'TEXTLINKEX', 							i18n("External link"));
		$oTpl->set('s', 'TEXTLINKIN', 							i18n("Internal link"));
		$oTpl->set('s', 'INDEX', 								i18n("Create a directory in"));
		$oTpl->set('s', 'PFAD', 								i18n("Path"));
		$oTpl->set('s', 'EXTERN', 								i18n("External"));
		$oTpl->set('s', 'INTERN', 								i18n("Internal"));
		$oTpl->set('s', 'TARGET', 								i18n("Open in a new window"));
		$oTpl->set('s', 'TITLE', 								i18n("Title"));
		$oTpl->set('s', 'extern_title_value', 					$this->aLink['link_title']);
		$oTpl->set('s', 'HTTP', 								i18n("Href"));
		
		switch ($this->aLink['link_type']){
			case 'extern':
				$oTpl->set('s', 'extern_value', 				$this->aLink['link_src']);
				$oTpl->set('s', 'DIRECTORY_FILE', 				$this->getFileSelect('', $this->iId));
				$oTpl->set('s', 'DIRECTORY_LIST', 				$this->getDirectoryList( $this->buildDirectoryList() ));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_FILE', 		$this->getUploadFileSelect('', $this->iId));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_LIST', 		$this->getUploadDirectoryList( $this->buildUploadDirectoryList() ));
				$oTpl->set('s', 'style_upload_tab', 			'');
				$oTpl->set('s', 'style_upload', 				'');
				$oTpl->set('s', 'style_extern_tab', 			'style="font-weight:bold;"');
				$oTpl->set('s', 'style_extern', 				'style="display:block;"');
				$oTpl->set('s', 'style_intern_tab', 			'');
				$oTpl->set('s', 'style_intern', 				'');	
				break;
			case 'intern':
				$oTpl->set('s', 'extern_value', 				$this->hostName);
				$oTpl->set('s', 'DIRECTORY_FILE', 				$this->getFileSelect($this->aLink['link_src'], $this->iId));
				$oTpl->set('s', 'DIRECTORY_LIST', 				$this->getDirectoryList( $this->buildDirectoryList() ));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_FILE', 		$this->getUploadFileSelect('', $this->iId));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_LIST', 		$this->getUploadDirectoryList( $this->buildUploadDirectoryList() ));
				$oTpl->set('s', 'style_upload_tab', 			'');
				$oTpl->set('s', 'style_upload', 				'');
				$oTpl->set('s', 'style_extern_tab', 			'style="font-weight:normal;"');
				$oTpl->set('s', 'style_extern', 				'style="display:none;"');
				$oTpl->set('s', 'style_intern_tab', 			'style="font-weight:bold;"');
				$oTpl->set('s', 'style_intern', 				'style="display:block;"');	
				break;
			case 'upload':
				$oTpl->set('s', 'extern_value', 				$this->hostName);
				$oTpl->set('s', 'DIRECTORY_FILE', 				$this->getFileSelect('', $this->iId));
				$oTpl->set('s', 'DIRECTORY_LIST', 				$this->getDirectoryList( $this->buildDirectoryList() ));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_FILE', 		$this->getUploadFileSelect(dirname($this->aLink['link_src']), $this->iId));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_LIST', 		$this->getUploadDirectoryList( $this->buildUploadDirectoryList() ));
				$oTpl->set('s', 'style_upload_tab', 			'style="font-weight:bold;"');
				$oTpl->set('s', 'style_upload', 				'style="display:block;"');
				$oTpl->set('s', 'style_extern_tab', 			'style="font-weight:normal;"');
				$oTpl->set('s', 'style_extern', 				'style="display:none;"');
				$oTpl->set('s', 'style_intern_tab', 			'');
				$oTpl->set('s', 'style_intern', 				'');
				break;
			default:
				$oTpl->set('s', 'extern_value', 				$this->hostName);	
				$oTpl->set('s', 'DIRECTORY_FILE', 				$this->getFileSelect('', $this->iId));	
				$oTpl->set('s', 'DIRECTORY_LIST', 				$this->getDirectoryList( $this->buildDirectoryList() ));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_FILE', 		$this->getUploadFileSelect('', $this->iId));
				$oTpl->set('s', 'UPLOAD_DIRECTORY_LIST', 		$this->getUploadDirectoryList( $this->buildUploadDirectoryList() ));
				$oTpl->set('s', 'style_upload_tab', 			'');
				$oTpl->set('s', 'style_upload', 				'');
				$oTpl->set('s', 'style_extern_tab', 			'');
				$oTpl->set('s', 'style_extern', 				'');
				$oTpl->set('s', 'style_intern_tab', 			'');
				$oTpl->set('s', 'style_intern', 				'');		
		};
		
		$oTpl->set('s', 'checked', 								($this->aLink['link_target'] == '_blank') ? "checked='checked'" : "");
		//generate template
		$sCode .= $oTpl->generate($this->aCfg['path']['contenido'].'templates/standard/template.cms_linkeditor_edit.html', 1);
		
		return $this->getAllWidgetView( true ) . $this->encodeForOutput($sCode);
	}

	/**
	 * Returns the directory list of an given directory array (by buildDirectoryList()).
	 *
	 * @param 	array 	$aDirs	Array with directory information
	 * @return	string	html of the directory list
	 */
	public function getDirectoryList( $aDirs ) {
	//print_r($this->activeIdcat);print_r($this->aLink);
		$oTpl = new Template(); 
		$i = 1;
		foreach ( $aDirs as $aDirData ) {
			$sLiClasses = '';
			if(isset($this->activeIdcat[0]) && $aDirData['idcat'] == $this->activeIdcat[0]){
				$oTpl->set('d', 'DIVCLASS', ' class="active"');
				$bGo = true;
			} else {
				$oTpl->set('d', 'DIVCLASS', '');
			}
			$oTpl->set('d', 'TITLE', $aDirData['idcat']);
			$oTpl->set('d', 'DIRNAME', $aDirData['name']);
			
			$bGo = false;
			if(isset($this->activeIdcat) && in_array($aDirData['idcat'],$this->activeIdcat)){			
				$bGo = true;
			}       
			if( $bGo == true && $aDirData['sub'] != '' ){	
				$oTpl->set('d', 'SUBDIRLIST', $this->getDirectoryList( $aDirData['sub'] ) );
			} else if($aDirData['sub'] != '' && count( $aDirData['sub'] ) > 0 ){
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
	
	private function getMaxLevel(){
		$sql = "SELECT distinct
                    a.level
                FROM
                    ".$this->aCfg["tab"]["cat_tree"]." AS a,
                    ".$this->aCfg["tab"]["cat_art"]." AS b,
                    ".$this->aCfg["tab"]["cat"]." AS c,
                    ".$this->aCfg["tab"]["cat_lang"]." AS d,
                    ".$this->aCfg["tab"]["art_lang"]." AS e
                WHERE                
                    e.online = 1 AND
                    a.idcat = b.idcat AND
                    b.idcat = d.idcat AND
                    d.idlang = '".Contenido_Security::toInteger($this->iLang)."' AND
                    b.idart  = e.idart AND
                    c.idcat = a.idcat AND
                    c.idclient = '".Contenido_Security::toInteger($this->iClient)."' AND
				e.idlang = '".Contenido_Security::toInteger($this->iLang)."'
                ORDER BY
                    a.idtree";        
        
        $this->oDb->query($sql);
        while ( $this->oDb->next_record() ) {  
        	if($this->oDb->f("level") > $this->maxLevel ){
				$imaxLevel = $this->oDb->f("level");
        	}
        }
        return $imaxLevel;
	}
	 /**
	  * Builds a directory list by a given root directory path.
	  *
	  * @param 	string 	$iRootLevelId	Path to directory (per default the root upload path of client)
	  * @return	array	Array with directory information
	  */
	public function buildDirectoryList( $iLevelId = 0, $iParentidcat = 0, $aDirectories = array() ) {
		
		$sqlDb = new DB_Contenido;
		$sql = "SELECT distinct
                    *
                FROM
                    ".$this->aCfg["tab"]["cat_tree"]." AS a,
                    ".$this->aCfg["tab"]["cat"]." AS c,
                    ".$this->aCfg["tab"]["cat_lang"]." AS d
                WHERE                
                    a.level = ".$iLevelId." AND
                    c.parentid = " . $iParentidcat . " AND
                    a.idcat = d.idcat AND
                    c.idcat = a.idcat AND
                    d.idlang = '".Contenido_Security::toInteger($this->iLang)."' AND
                    c.idclient = '".Contenido_Security::toInteger($this->iClient)."' 
                ORDER BY
                    a.idtree";     
        $sqlDb->query($sql);
        $i = 0;
        while ( $sqlDb->next_record() ) {  
			$aDirectories[$i]["idcat"] = $sqlDb->f("idcat");
			$aDirectories[$i]["level"] = $sqlDb->f("level");
			$aDirectories[$i]["parentid"] = $sqlDb->f("parentid");
			$aDirectories[$i]["name"] = $sqlDb->f("name");
			//$aDirectories[$i]["path"] = $sPath . "/" .$sqlDb->f("name");
			if($aDirectories[$i]["level"]+1 <= $this->imaxLevel){
				$aDirectories[$i]["sub"] = $this->buildDirectoryList( $aDirectories[$i]["level"]+1, $aDirectories[$i]["idcat"], $aDirectories[$i]["sub"] );
			} else {
				$aDirectories[$i]["sub"] = '';
			}
			$i++;
        }
               	
		return $aDirectories;
	}
	
	 /**
	  * Function which generate a select box for the manual files.
	  *
	  * @param 	array 	$sDirectoryPath	Path to directory of the files
	  * @return 	string	rendered cHTMLSelectElement
	  */
	public function getFileSelect($iIdCat = "", $iLinkeditorId = "") {
		$oHtmlSelect = new cHTMLSelectElement ('linkeditor_filename', "", 'linkeditor_filename_'.$iLinkeditorId);
		$oHtmlSelect->setSize(16);
		//print_r($this->aLink);
		//print_r($_REQUEST);
		$oHtmlSelectOption = new cHTMLOptionElement('Kein', '', false);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);
		if($this->aLink['link_type'] == 'intern' && $iIdCat == $this->aLink['link_src']){//exist really a value, search for reload
			$sqlDb = new DB_Contenido;
			$sql = "SELECT distinct
	                    e.*
	                FROM
	                    ".$this->aCfg["tab"]["cat_tree"]." AS a,
	                    ".$this->aCfg["tab"]["cat_art"]." AS b,
	                    ".$this->aCfg["tab"]["cat"]." AS c,
	                    ".$this->aCfg["tab"]["cat_lang"]." AS d,
	                    ".$this->aCfg["tab"]["art_lang"]." AS e
	                WHERE                
	                    b.idart = " . $this->aLink['link_src'] . " AND
	                    e.online = 1 AND
	                    a.idcat = b.idcat AND
	                    b.idcat = d.idcat AND
	                    d.idlang = '".Contenido_Security::toInteger($this->iLang)."' AND
	                    b.idart  = e.idart AND
	                    c.idcat = a.idcat AND
	                    c.idclient = '".Contenido_Security::toInteger($this->iClient)."' AND
					e.idlang = '".Contenido_Security::toInteger($this->iLang)."'
	                ORDER BY
	                    a.idtree";      
	        $sqlDb->query($sql);
			$i = 1;
	        while ( $sqlDb->next_record() ) { 
				$oHtmlSelectOption = new cHTMLOptionElement($sqlDb->f("title"), $sqlDb->f("idart"));					
				$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
				$i++;
	        }
			//set default value
			$oHtmlSelect->setDefault($this->aLink['link_src']);							
		} else if($iIdCat != 0){// first, if not value saved, search from js			
			$sqlDb = new DB_Contenido;
			$sql = "SELECT distinct
	                    e.*
	                FROM
	                    ".$this->aCfg["tab"]["cat_tree"]." AS a,
	                    ".$this->aCfg["tab"]["cat_art"]." AS b,
	                    ".$this->aCfg["tab"]["cat"]." AS c,
	                    ".$this->aCfg["tab"]["cat_lang"]." AS d,
	                    ".$this->aCfg["tab"]["art_lang"]." AS e
	                WHERE                
	                    c.idcat = " . $iIdCat . " AND
	                    e.online = 1 AND
	                    a.idcat = b.idcat AND
	                    b.idcat = d.idcat AND
	                    d.idlang = '".Contenido_Security::toInteger($this->iLang)."' AND
	                    b.idart  = e.idart AND
	                    c.idcat = a.idcat AND
	                    c.idclient = '".Contenido_Security::toInteger($this->iClient)."' AND
					e.idlang = '".Contenido_Security::toInteger($this->iLang)."'
	                ORDER BY
	                    a.idtree";      
	        $sqlDb->query($sql);
			$i = 1;
	        while ( $sqlDb->next_record() ) { 
				$oHtmlSelectOption = new cHTMLOptionElement($sqlDb->f("title"), $sqlDb->f("idart"));					
				$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
				$i++;
	        }

		} 
		/*if ( $i == 0 ) {
			$oHtmlSelectOption = new cHTMLOptionElement( i18n('No files found'), '', false );
			$oHtmlSelectOption->setAlt( i18n('No files found') );
			$oHtmlSelectOption->setDisabled( true );
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);	
			$oHtmlSelect->setDisabled( true );
		} 
		*/
				
		
		return $oHtmlSelect->render();
	}
	

	 /**
	  * Function which generate a select box for the manual files (in "Upload" Reiter).
	  *
	  * @param 	array 	$sDirectoryPath	Path to directory of the files
	  * @return 	string	rendered cHTMLSelectElement
	  */
	public function getUploadFileSelect($sDirectoryPath = "", $iImageId = "") {
		$oHtmlSelect = new cHTMLSelectElement ('image_filename', "", 'image_filename_'.$iImageId);
		$oHtmlSelect->setSize(16);
		
		$oHtmlSelectOption = new cHTMLOptionElement('Kein', '', false);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		$i = 1;
		//if ($sDirectoryPath != "" && $sDirectoryPath!='upload') {
			$sUploadPath = $this->aCfgClient[$this->iClient]['upl']['path'];
			if(is_dir($sUploadPath.$sDirectoryPath)){
			$oHandle = opendir($sUploadPath.$sDirectoryPath);
			while($sEntry = readdir($oHandle)) {
				if ( $sEntry != "." && $sEntry != ".." && file_exists( $sUploadPath.$sDirectoryPath."/".$sEntry ) && !is_dir( $sUploadPath.$sDirectoryPath."/".$sEntry ) ) {
					$oHtmlSelectOption = new cHTMLOptionElement($sEntry, $sDirectoryPath."/".$sEntry);					
					$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
					$i++;
				}
			}		
			closedir($oHandle);
			}
		//}
		
		if ( $i == 0 ) {
			$oHtmlSelectOption = new cHTMLOptionElement( i18n('No files found'), '', false );
			$oHtmlSelectOption->setAlt( i18n('No files found') );
			$oHtmlSelectOption->setDisabled( true );
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);	
			$oHtmlSelect->setDisabled( true );
		} 
		
		//set default value
		if($this->aLink['link_type'] == 'upload'){
			$oHtmlSelect->setDefault($this->aLink['link_src']);							
		}	
		return $oHtmlSelect->render();
	}
	
			

	/**
	 * Returns the directory list of an given directory array (by buildDirectoryList()).
	 *
	 * @param 	array 	$aDirs	Array with directory information
	 * @return	string	html of the directory list
	 */
	public function getUploadDirectoryList( $aDirs ) {
		//print_r($aDirs);
		$oTpl = new Template(); 
		$i = 1;
		foreach ( $aDirs as $aDirData ) {
			$sLiClasses = '';	
			if ( $aDirData['path'].$aDirData['name'] == dirname($this->aLink['link_src'])) {
				$oTpl->set('d', 'DIVCLASS', ' class="active"');
			} else {
				$oTpl->set('d', 'DIVCLASS', '');
			}
			$oTpl->set('d', 'TITLE', $aDirData['path'].$aDirData['name']);
			$oTpl->set('d', 'DIRNAME', $aDirData['name']);
			
			$bGo = false;
			if ( $this->fileIsOrNotInPath($aDirData['path'].$aDirData['name']) ) {
				$bGo = true;
			}
			if ( $bGo == true ) {
				$oTpl->set('d', 'SUBDIRLIST', $this->getUploadDirectoryList( $aDirData['sub'] ) );
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
	
	private function fileIsOrNotInPath($activeFile){
		$aLevelPath = explode('/',$this->aLink['link_src']);
		$error = false;
		foreach ($aLevelPath as $levelPath){
			$sLevelPath .= '/'. $levelPath;
			if($sLevelPath == '/'.$activeFile){
				$error = true;
			} 
		}
		return $error;	
	}
	
	 /**
	  * Builds a directory list by a given upload directory path.
	  *
	  * @param 	string 	$sUploadPath	Path to directory (per default the root upload path of client)
	  * @return	array	Array with directory information
	  */
	public function buildUploadDirectoryList( $sUploadPath = "" ) {
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
				$aDirectories[$i]['path'] = str_replace($this->sUploadPath, '', $sUploadPath);
				$aDirectories[$i]['sub'] = $this->buildUploadDirectoryList( $sUploadPath . $sEntry );
				$i++;
			}
		}
		closedir($oHandle);
		return $aDirectories;
	}
	/**
	 * In CONTENIDO content type code is evaled by php. To make this possible,
	 * this function prepares code for evaluation
	 *
	 * @access 	private
	 *
	 * @param 	string 	$sCode 	code to escape
	 * @return 	string	escaped code
	 */
	private function encodeForOutput($sCode) {
		$sCode = (string) $sCode;

		$sCode = addslashes($sCode);
		$sCode = str_replace("\\'", "'", $sCode);
		$sCode = str_replace("\$", '\\$', $sCode);

		return $sCode;
	}
	
	
	/**
	 * Function is called in edit- and viewmode in order to generate code for output.
	 *
	 * @return	string	generated code
	 */
	public function getAllWidgetView() {		
		
		$sCode = '';
		$this->oDb->query('SELECT * FROM ' . $this->aCfg['tab']['content'] . ' WHERE idartlang='. $this->iIdArtLang .' AND idtype=24 AND typeid='. $this->iId);
		if ($this->oDb->next_record()) {
			$sCode = $this->oDb->f('value');
		} else {
			$sCode = "";
		}
		$aCode = explode(']+[', urldecode($sCode));	
		$aCode[0] = substr($aCode[0], 1);
		$aCode[3] = substr($aCode[3], 0, -1);	
		//echo $this->hostName;
		//print_r($aCode);echo "<br>";
		if($aCode[0] == 'extern' && $aCode[1] != ''){	
			if(!(substr_count(strtolower($this->aLink['link_src']) , "https") || substr_count(strtolower($this->aLink['link_src']) , "http"))){		
	        	$aCode[1] = $this->hostName.$aCode[1];
			}
		}
		if($aCode[0] == 'intern' && $aCode[1] != ''){
	        $aCode[1] = $this->aCfgClient[$this->iClient]['path']['htmlpath'].'front_content.php?idart='.$aCode[1];
		}
		if($aCode[0] == 'upload' && $aCode[1] != ''){
	        $aCode[1] = $this->aCfgClient[$this->iClient]['upl']['htmlpath'].$aCode[1];
		}
		
		if($_REQUEST['changeview'] != 'edit'){
			return "<a alt='". $aCode[3] . "' href='". $aCode[1] . "' target='". $aCode[2] . "'>". $aCode[3] . "</a>";			
		} else {
			return "<a alt='". $aCode[3] . "' href='". $aCode[1] . "' target='". $aCode[2] . "'>". $aCode[3] . "</a>";	
		}
	}
	
}
?>