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
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.13
 * 
 * {@internal 
 *   created 2009-10-26
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
 * This class provides all methods for the content type CMS_IMAGE. All properties of the file list are
 * stored as a xml document in the database.
 */
class Cms_Image {
	/**
	 * Contenido configuration array
	 * @var 	array
	 * @access 	private
	 */
	private $aCfg = array();
	
	/**
	 * Current id of content type CMS_IMAGE[3] -> 3
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
	private $aImageData = array();
	
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
	 * Contenido upload path for current client
	 * @var		string
	 * @access	private
	 */
	private $sUploadPath = "";
	

	/**
	 * Constructor of class inits some important class variables and
	 * gets some Contenido global vars, so this class has no need to
	 * use ugly and buggy global commands
	 *
	 * @param string $sContent - xml document from database containing the settings
	 * @param integer $iNumberOfCms - CMS_IMAGE[4] => 4
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
		
		//define class array which contains all names of the image properties. They were also base for generating dynamic javascripts for
		//retrival this properties out of html forms and retriving their values to screen
		$this->aImageData 	= array('image_filename','image_medianame', 'image_description', 'image_keywords', 'image_internal_notice', 'image_copyright');
		
		//if form is submitted there is a need to store current file list settings
		//notice: there is also a need, that filelist_id is the same (case: more than one cms file list is used on the same page
		if (isset($_POST['image_action']) && $_POST['image_action'] == 'store' && isset($_POST['image_id']) && (int)$_POST['image_id'] == $this->iId) {
			$this->storeImage();
		}
				
		//in sContent XML Document is stored, which contains files settings, call function which parses this document and store
		//properties as easy accessible array into $aSettings

	}
		

	
	/**
	 * Function gets all submitted values for new file list properties from 
	 * $_POST array, generates new corresponding config XML Document and
	 * stores it as content, using contenido conSaveContentEntry() function
	 *
	 * @access 	private
	 * @return	void
	 */
	private function storeImage() {		
		$aFilenameData['filename'] = basename($_REQUEST['image_filename']);
		$aFilenameData['dirname'] = dirname($_REQUEST['image_filename']);
		$query = 'SELECT idupl FROM ' . $this->aCfg['tab']['upl'] . ' WHERE filename=\''.$aFilenameData['filename'].'\' AND dirname=\''.$aFilenameData['dirname'].'/\' AND idclient=\''.$this->iClient.'\'';
		$this->oDb->query($query);
		if($this->oDb->next_record()) {
			$this->iUplId = $this->oDb->f('idupl');                 
		}
		$this->sContent = $this->iUplId;		
		conSaveContentEntry($this->iIdArtLang, 'CMS_IMAGE', $this->iId, $this->iUplId, true);
		
		//Insert auf metadatentabelle
		$idupl = $this->iUplId;
		$idlang = $this->iLang;
		$medianame = $_REQUEST['image_medianame'];
		$description = $_REQUEST['image_description'];
		$keywords = $_REQUEST['image_keywords'];
		$internal_notice = $_REQUEST['image_internal_notice']; 
		$copyright = $_REQUEST['image_copyright']; 
		$query = "SELECT id_uplmeta FROM " . $this->aCfg['tab']['upl_meta'] . " WHERE idupl='".$idupl."' AND idlang='".$idlang."'";
		$this->oDb->query($query);
		//echo '1'.$this->oDb->Error;
		if($this->oDb->next_record()) {
			$id_uplmeta = $this->oDb->f('id_uplmeta');    
		}
		if(!isset($id_uplmeta)){
			$id = $this->oDb->nextid($this->aCfg['tab']['upl_meta']);
			$query = "INSERT INTO ".$this->aCfg['tab']['upl_meta']."(id_uplmeta, idupl, idlang, medianame, description, keywords, internal_notice, copyright) VALUES('".
					$id."', '".$idupl."', '".$idlang."', '".$medianame."', '".$description."', '".$keywords."', '".$internal_notice."', '".$copyright."')";
			$this->oDb->query($query);
			//echo '2'.$this->oDb->Error;
		} else {
			$query = "UPDATE ".$this->aCfg['tab']['upl_meta'].
					" SET idupl='".$idupl."', idlang='".$idlang."', medianame='".$medianame."', description='".$description."', keywords='".$keywords."', internal_notice='".$internal_notice."', copyright='".$copyright."'
					WHERE id_uplmeta='".$id_uplmeta."'";
			$this->oDb->query($query);
			//echo '3'.$this->oDb->Error;
		}
	}
	
	
	
	 /**
	  * Function which generate a select box for the manual files.
	  *
	  * @param 	array 	$sDirectoryPath	Path to directory of the files
	  * @return 	string	rendered cHTMLSelectElement
	  */
	public function getFileSelect($sDirectoryPath = "", $iImageId = "") {
		$oHtmlSelect = new cHTMLSelectElement ('image_filename', "", 'image_filename_'.$iImageId);
		$oHtmlSelect->setSize(16);
		
		$oHtmlSelectOption = new cHTMLOptionElement('Kein', '', false);
		$oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

		$i = 1;
		if ($sDirectoryPath != "" && $sDirectoryPath!='upload') {
			$sUploadPath = $this->aCfgClient[$this->iClient]['upl']['path'];
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
		
		if ( $i == 0 ) {
			$oHtmlSelectOption = new cHTMLOptionElement( i18n('No files found'), '', false );
			$oHtmlSelectOption->setAlt( i18n('No files found') );
			$oHtmlSelectOption->setDisabled( true );
			$oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);	
			$oHtmlSelect->setDisabled( true );
		} 
		
		//set default value
		if(isset($this->activeFilename)){
			$oHtmlSelect->setDefault($this->activeFilename."/".$this->filename);
		} else {
			$oHtmlSelect->setDefault('');
		}
		
		
		return $oHtmlSelect->render();
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
			if ($sRelativePath."/"==$this->dirname) {
				$this->activeFilename = $sRelativePath; 
				$oTpl->set('d', 'DIVCLASS', ' class="active"');
			} else {
				$oTpl->set('d', 'DIVCLASS', '');
			}
			$oTpl->set('d', 'TITLE', $sRelativePath);
			$oTpl->set('d', 'DIRNAME', $aDirData['name']);
			
			$bGo = false;
            if (isset($this->dirname)) {
				$this->image_directories = explode('/', $this->dirname);
				if ( $sRelativePath==dirname($this->dirname) ) {
					$bGo = true;
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
	 * Function is called in editmode of contenido an returns image view and editbutton
	 *
	 * @return	string	code for the backend edit view
	 */
	public function getAllWidgetEdit() {
		$oTpl = new Template();  
		//set meta
		/*Set some values into javascript for a better handling*/
		$oTpl->set('s', 'CON_PATH', 							$this->aCfg['path']['contenido_fullhtml']);
		$oTpl->set('s', 'ID', 									$this->iId);
		
		$oTpl->set('s', 'IDARTLANG',							$this->iIdArtLang);
		$oTpl->set('s', 'CONTENIDO', 							$_REQUEST['contenido']);
		$oTpl->set('s', 'FIELDS', 								"'".implode("','",$this->aImageData)."'");
		
		/*Start set a lot of translations*/
		$oTpl->set('s', 'LABEL_IMAGE_SETTINGS', 				i18n("Image settings"));
		
		$oTpl->set('s', 'DIRECTORIES', 							i18n("Directories"));
		$oTpl->set('s', 'META', 								i18n("Meta"));
		$oTpl->set('s', 'UPLOAD', 								i18n("Upload"));
				
		$oTpl->set('s', 'LABEL_IMAGE_TITLE', 					i18n("Title"));			
		$oTpl->set('s', 'LABEL_IMAGE_DESC', 					i18n("Description"));		
		$oTpl->set('s', 'LABEL_IMAGE_KEYWORDS', 				i18n("Keywords"));
		$oTpl->set('s', 'LABEL_IMAGE_INTERNAL_NOTICE', 			i18n("Internal notes"));
		$oTpl->set('s', 'LABEL_IMAGE_COPYRIGHT', 				i18n("Copyright"));
		
		$oTpl->set('s', 'INDEX', 								i18n("Erstelle Verzeichnis in"));
		$oTpl->set('s', 'PFAD', 								i18n("Pfad"));
		$oTpl->set('s', 'CONTENIDO', 							$contenido);
		
		$oTpl->set('s', 'sUploadPath', 							$this->sUploadPath);	           
		
		$idupl = $this->sContent;
		$idlang = $this->iLang;
		$this->oDb->query('SELECT filename,dirname FROM ' . $this->aCfg['tab']['upl'] . ' WHERE idupl=\''.$idupl.'\' AND idclient=\''.$this->iClient.'\'');
		if($this->oDb->next_record()) {
			$this->filename = $this->oDb->f('filename');   
			$this->dirname = $this->oDb->f('dirname');                
		}
		
		$oTpl->set('s', 'sContent', 							$this->aCfgClient[$this->iClient]['path']['htmlpath'].'upload/'.$this->dirname.$this->filename);
		$oTpl->set('s', 'DIRECTORY_LIST', 						$this->getDirectoryList( $this->buildDirectoryList() ));
		/*$medianame = $_REQUEST['image_medianame'];
		$description = $_REQUEST['image_description'];
		$keywords = $_REQUEST['image_keywords'];
		$internal_notice = $_REQUEST['image_internal_notice']; 
		$copyright = $_REQUEST['image_copyright']; 
		$query = "SELECT id_uplmeta FROM " . $this->aCfg['tab']['upl_meta'] . " WHERE idupl='".$idupl."' AND idlang='".$idlang."' AND medianame='".$medianame.
				"' AND description='".$description."' AND keywords='".$keywords."' AND internal_notice='".$internal_notice."' AND copyright='".$copyright."'";*/				
		$query = "SELECT * FROM " . $this->aCfg['tab']['upl_meta'] . " WHERE idupl='".$idupl."' AND idlang='".$idlang."'";
		$this->oDb->query($query);
		//echo '4'.$this->oDb->Error;
		if($this->oDb->next_record() && $idupl!='') {
			$id_uplmeta = $this->oDb->f('id_uplmeta');   	
			
			$oTpl->set('s', 'DIRECTORY_FILE', 						$this->getFileSelect($this->activeFilename, $this->iId));
			$oTpl->set('s', 'DIRECTORY_SRC', 						$this->aCfgClient[$this->iClient]['path']['htmlpath'].'upload/'.$this->dirname.$this->filename);
			$oTpl->set('s', 'IMAGE_TITLE', 							$this->oDb->f('medianame'));	
			$oTpl->set('s', 'IMAGE_DESC', 							$this->oDb->f('description'));
			$oTpl->set('s', 'IMAGE_KEYWORDS', 						$this->oDb->f('keywords'));
			$oTpl->set('s', 'IMAGE_INTERNAL_NOTICE', 				$this->oDb->f('internal_notice'));
			$oTpl->set('s', 'IMAGE_COPYRIGHT', 						$this->oDb->f('copyright'));			
		} else {
			$oTpl->set('s', 'DIRECTORY_FILE', 						'');
			$oTpl->set('s', 'DIRECTORY_SRC', 						'');
			$oTpl->set('s', 'IMAGE_TITLE', 							'');	
			$oTpl->set('s', 'IMAGE_DESC', 							'');
			$oTpl->set('s', 'IMAGE_KEYWORDS', 						'');
			$oTpl->set('s', 'IMAGE_INTERNAL_NOTICE', 				'');
			$oTpl->set('s', 'IMAGE_COPYRIGHT', 						'');
		}
		
		//generate template
		$sCode .= $oTpl->generate($this->aCfg['path']['contenido'].'templates/standard/template.cms_image_edit.html', 1);
		
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
	 * Function is called in edit- and viewmode in order to generate code for output.
	 *
	 * @return	string	generated code
	 */
	public function getAllWidgetView() {
		$oTpl = new Template();
		$sCode = '';
		//select metadaten
		$this->oDb->query('SELECT * FROM ' . $this->aCfg['tab']['upl'] . ' WHERE idupl=\''.$this->sContent.'\' AND idclient=\''.$this->iClient.'\'');
		if ($this->oDb->next_record()) {
			//set title of teaser
			$oTpl->set('s', 'TITLE', $this->oDb->f('filename'));
			if($this->oDb->f('dirname')!='' && $this->oDb->f('filename')!=''){
				$oTpl->set('s', 'SRC', $this->aCfgClient[$this->iClient]['path']['htmlpath'].'upload/'.$this->oDb->f('dirname').$this->oDb->f('filename'));
				$sCode = $this->aCfgClient[$this->iClient]['path']['htmlpath'].'upload/'.$this->oDb->f('dirname').$this->oDb->f('filename');
			} else {
				$oTpl->set('s', 'SRC', '');
				$sCode = "";
			}
			$oTpl->set('s', 'DESC', $this->oDb->f('filename'));			
			
			//generate template
			//$sCode = $oTpl->generate($this->aCfgClient[$this->iClient]['path']['frontend'].'templates/cms_image_style_default.html', 1);
		}
		return $sCode;
	}
	
	public function getImageMeta( $filename, $dirname, $iImageId ){
		$this->oDb->query('SELECT idupl FROM ' . $this->aCfg['tab']['upl'] . ' WHERE filename=\''.$filename.'\' AND dirname=\''.$dirname.'/\' AND idclient=\''.$this->iClient.'\'');
		if($this->oDb->next_record()) {
			$idupl = $this->oDb->f('idupl');                 
		}
		$query = "SELECT * FROM " . $this->aCfg['tab']['upl_meta'] . " WHERE idupl='".$idupl."' AND idlang='".$this->iLang."'";
		$this->oDb->query($query);
		$array = array();
		if($this->oDb->next_record() && $idupl!='') { 	
			echo $array[$iImageId]['medianame'] = $this->oDb->f('medianame');
			echo '+++';	
			echo $array[$iImageId]['description'] = $this->oDb->f('description');
			echo '+++';	
			echo $array[$iImageId]['keywords'] = $this->oDb->f('keywords');
			echo '+++';	
			echo $array[$iImageId]['internal_notice'] = $this->oDb->f('internal_notice');
			echo '+++';	
			echo $array[$iImageId]['copyright'] = $this->oDb->f('copyright');
			echo '+++';				
		} else {
			echo '';
			echo '+++';	
			echo '';
			echo '+++';	
			echo '';
			echo '+++';	
			echo '';
			echo '+++';	
			echo '';
			echo '+++';	
		}
	}
	
}
?>