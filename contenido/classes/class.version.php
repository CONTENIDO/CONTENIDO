<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * We use super class Version to create a new Version.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2008-08-12
 *
 * }}
 * 
 */

class Version {

	/**
	* Id of Type
	* @access protected
	*/	
	protected $sType;
	
	/**
	* md5 coded name of author 
	* @access protected
	*/
	protected $sAuthor;
	
	/**
	* Time of created
	* @access protected
	*/
	protected $dCreated;
	
	/**
	* Time of last modified
	* @access protected
	*/
	protected $dLastModified;
	
	/**
	* Body data of xml file
	* @access protected
	*/
	protected $aBodyData;
	
	/**
	* For init global variable
	* @access protected
	*/
	protected $aCfg;
	
	/**
	* For init global variable $cfgClient
	* @access protected
	*/
	protected $aCfgClient;

	/**
	* Database object
	* @access protected
	*/
	protected $oDB;
	
	/**
	* For init global variable $client
	* @access protected
	*/
	protected $iClient;
	
	/**
	* Revision files of current file
	* @access public
	*/ 
	public $aRevisionFiles;
	
	/**
	* Number of Revision
	* @access private
	*/
	protected $iRevisionNumber;
	
	/**
	* Timestamp
	* @access protected
	*/
	protected $dTimestamp;
	
	/**
	* For init global variable $area
	* @access protected
	*/
	protected $sArea;
	
	/**
	* For init global variable $frame
	* @access protected
	*/
	protected $iFrame;
	
	/**
	* For init variables
	* @access protected
	*/
	protected $aVarForm;
	
	/**
	* Identity the Id of Content Type
	* @access protected
	*/
	protected $iIdentity;
	
	private $bVersionCreatActive;
    
    protected $dActualTimestamp;
	
	/**
	 * The Version object constructor, initializes class variables
	 * 
	 * @param {Array} $aCfg
	 * @param {Array} $aCfgClient
	 * @param {Object} $oDB	
	 * @param {Integer} $iClient
	 * @param {Object} $sArea
	 * @param {Object} $iFrame
	 */	
	public function __construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame) {
		$this->aBodyData = array ();
		$this->aRevisionFiles = array ();
		$this->aCfg = $aCfg;
		
		$this->aCfgClient = $aCfgClient;
		
		$this->oDB = $oDB;
		$this->iClient = $iClient;
		$this->iRevisionNumber = 0;
		$this->sArea = $sArea;
		$this->iFrame = $iFrame;
        
        $this->dActualTimestamp = time();
		
		$this->aVarForm = array();	

 //		Look if versioning is allowed, default is false		
		$this->bVersionCreatActive = getEffectiveSetting('versioning', 'activated', 'false'); 
        
        $this->checkPaths();
	}
    
    /**
	 * This function checks if needed version paths exists and were created if neccessary
	 * 
	 */	
    protected function checkPaths() {
        $aPath = array('', '/css', '/js', '/layout', '/module', '/templates');
        $sFrontEndPath = $this->aCfgClient[$this->iClient]["path"]["frontend"] . "version";
        
        foreach ($aPath as $sSubPath) {
            if(!is_dir($sFrontEndPath.$sSubPath)){
    			mkdir($sFrontEndPath.$sSubPath, 0777);
    			chmod ($sFrontEndPath.$sSubPath, 0777);
    		}  
        }        
    }

	/**
	 * This function initialize the body node of xml file
	 * 
	 * @param {String} $sKey
	 * @param {String} $sValue
	 * 
	 * @return {Array} returns an array for body node
	 */	
	public function setData($sKey, $sValue) {
		$this->aBodyData[$sKey] = $sValue;

	}

	/**
	 * This function creats an xml file. XML Writer helps for create this file.
	 * 
	 * @return {String} returns content of xml file
	 */	
	public function createNewXml() {
		$xw = new xmlWriter();
		$xw->openMemory();
		$xw->setIndent(true);
		$xw->startDocument('1.0', 'UTF-8');
	
		$xw->startElement('version');
		$xw->writeAttribute('xmlns', 'http://www.wapforum.org/DTD/xhtml-mobile10.dtd');
		$xw->writeAttribute('xml:lang', 'de');
	
		$xw->startElement('head');
		$xw->writeElement('version_id', $this->iIdentity.'_'.$this->iVersion);
		$xw->writeElement('type', $this->sType);
		$xw->writeElement('date', date("Y-m-d H:i:s"));
		$xw->writeElement('author', $this->sAuthor);
		$xw->writeElement('client', $this->iClient);
		$xw->writeElement('created', $this->dCreated);
		$xw->writeElement('lastmodified', $this->dLastModified);
		$xw->endElement();

		$xw->startElement('body');
		
		foreach ($this->aBodyData as $sKey => $sValue) {
			$xw->writeElement($sKey, htmlentities($sValue));
		}

		$xw->endElement();

		$xw->endElement();
				
		return $xw->outputMemory(true);
	}
	
	/**
	 * This function creats new version in right folder.
	 * 
	 * @return {void}
	 */	
	public function createNewVersion() {
		if($this->bVersionCreatActive == "true"){
			// Get version Name
			$sRevisionName = $this->getRevision();
			
			// Create xml version file
			$sXmlFile = $this->createNewXml();
		
			if(!is_dir($this->getFilePath())){
				
				mkdir($this->getFilePath(), 0777);
				chmod ($this->getFilePath(), 0777);
			}
			
			$sHandle = fopen($this->getFilePath().$sRevisionName.'.xml', "w");
			fputs($sHandle, $sXmlFile);
			fclose($sHandle);
		}
	}
	
	/**
	 * This function inits version files. Its filter also timestamp and version files
	 * 
	 * @return {array} returns xml file names
	 */		
	protected function initRevisions() {		
		// Open this Filepath and read then the content.
		$sDir = $this->getFilePath();
		
		if (is_dir($sDir)) {
		    if ($dh = opendir($sDir)) {
		        
		        while (($file = readdir($dh)) !== false) {
		        	if($file != "."  && $file !=".."){
		           			 $aData = split('\.', $file);
		           			 $aValues = split ('_', $aData[0]);
		           			if ($aValues[0] > $this->iRevisionNumber) {
		           				$this->iRevisionNumber = $aValues[0];
		           			}
		        		
		        		$this->dTimestamp[$aValues[0]] = $aValues[1];
		        		$this->aRevisionFiles[$aValues[0]] = $file;
		           	}
		        }
		        closedir($dh);
		    }
		}
		
		return krsort($this->aRevisionFiles);	
	}
	
	/**
	 * Get the frontendpath to revision
	 * 
	 * @return {String} returns path to revision file
	 */		
	public function getFilePath() {
		$sFrontEndPath = $this->aCfgClient[$this->iClient]["path"]["frontend"] . "version/";
		return $sFrontEndPath . $this->sType.'/'. $this->iIdentity. '/';
	}

	/**
	 * Get the last revision file
	 *  
	 * @return {Array} returns Last Revision
	 */		
    public function getLastRevision() {
        return $this->aRevisionFiles[count($this->aRevisionFiles)];
    }
    
    /**
	 * Makes new and init Revision Name
	 *  
	 * @return {Integer} returns number of Revison File
	 */	
	private function getRevision() {
		
		$this->iVersion = ($this->iRevisionNumber +1 ).'_'.$this->dActualTimestamp;
		return $this->iVersion;
	}
	
    /**
	 * Revision Files
	 *  
	 * @return {Array} returns all Revison File
	 */	
	public function getRevisionFiles() {
		return $this->aRevisionFiles;
	}
	
	/**
	 * This function generate version names for select-box
	 *  
	 * @return {Array} returns an array of revision file names
	 */	
	public function getFormatTimestamp() {
		$aTimes = array();
		if(count($this->dTimestamp) > 0){
			krsort($this->dTimestamp);
			foreach($this->dTimestamp as $iKey=>$sTimeValue){
				$aTimes[$this->aRevisionFiles[$iKey]] = date('d.m.Y H:i:s', $sTimeValue). " - Revision: " .$iKey;
			}
		}
		
		return $aTimes;
	}

	/**
	 * This function generate version names for select-box
	 *  
	 * @return {Array} returns an array of revision file names
	 */	
	public function setVarForm($sKey, $sValue) {
		$this->aVarForm[$sKey] = $sValue;
	}
	
	/**
	 * The general SelectBox function for get Revision. 
	 * 
	 * @param {string} $sTableForm The name of Table_Form class
	 * @param {string} $sAddHeader The Header Label of SelectBox Widget
	 * @param {string} $sLabelOfSelectBox  The Label of SelectBox Widget
	 * @param {string} $sIdOfSelectBox  Id of Select Box
	 * 
	 * return {string} if is exists Revision, then returns HTML Code of full SelectBox else returns empty string
	 */
    public function buildSelectBox($sTableForm, $sAddHeader, $sLabelOfSelectBox, $sIdOfSelectBox) {
		$oForm = new UI_Table_Form("lay_history");
		
		// if exists xml files 
		if(count($this->dTimestamp) > 0) {
			
			foreach($this->aVarForm as $sKey=>$sValue) {
				$oForm ->setVar($sKey, $sValue);				
			}
			
			$oForm ->addHeader(i18n($sAddHeader));	
			$oForm ->add(i18n($sLabelOfSelectBox),  $this->getSelectBox($this->getFormatTimestamp(), $sIdOfSelectBox));
			#$oForm ->setActionButton("clearhistory", "images/but_delete.gif", i18n("Clear module history"), "c", "mod_history_clear");
			#$oForm ->setConfirm("clearhistory", i18n("Clear module history"), i18n("Do you really want to clear the module history?")."<br><br>".i18n("Note: This only affects the current module."));
			$oForm ->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");

			return $oForm ->render().'<div style="margin-top:20px;"></div>';
		} else {
			return '';
		}
    }
    
  	/**
	 * A Class Function for fill version files
	 * 
	 * @param {string} $sTableForm The name of Table_Form class
	 * @param {string} $sAddHeader The Header Label of SelectBox Widget
	 * 
	 * return {string} returns select-box with filled files
	 */
    private function getSelectBox($aTempVesions, $sIdOfSelectBox) {
		$sSelected = $_POST[$sIdOfSelectBox];
		$oSelectMenue = new cHTMLSelectElement($sIdOfSelectBox);
		$oSelectMenue->autoFill($aTempVesions);
		
		if($sSelected !=""){
			$oSelectMenue->setDefault($sSelected);
		}
	
		return $oSelectMenue->render();
	}

	/**
	 * Build new Textarea with below parameters
	 * 
	 * @param {String} $sName The name of Textarea.
	 * @param {String} $sValue The value of Input Textarea
	 * @param {Integer} $iWidth width of Textarea
	 * @param {Integer} $iHeight height of Textarea
	 * 
	 * @return {String} HTML Code of Textarea
	 */	
	public function getTextarea($sName, $sInitValue, $iWidth, $iHeight, $sId = "") {
		if($sId !="") {
			$oHTMLTextarea = new cHTMLTextarea($sName, $sInitValue, $iWidth, $iHeight, $sId);
		} else {
			$oHTMLTextarea = new cHTMLTextarea($sName, $sInitValue, $iWidth, $iHeight);
		}	
		
		$oHTMLTextarea->setStyle("font-family: monospace; width: 100%;");
		$oHTMLTextarea->updateAttributes(array("wrap" => "off"));
		
		return $oHTMLTextarea->render();
		
	}
	
	/**
	 * Build new Textfield with below parameters
	 * 
	 * @param {String} $sName The name of Input Textfield.
	 * @param {String} $sValue The value of Input Textfield
	 * @param {Integer} $iWidth width of Input Textfield
	 * 
	 * @return {String} HTML Code of Input Textfield
	 */		
	public function getTextBox($sName, $sInitValue, $iWidth, $bDisabled = false) {
		$oHTMLTextbox = new cHTMLTextbox($sName, html_entity_decode($sInitValue), $iWidth, "", "", $bDisabled);
		$oHTMLTextbox->setStyle("font-family: monospace; width: 100%;");
		$oHTMLTextbox->updateAttributes(array("wrap" => "off"));
		
		return $oHTMLTextbox->render();
	}
	
	/**
	 * Displays your notification
	 * 
	 * @param {string} $sOutPut
	 * 
	 * @return {void}
	 */	
	public function displayNotification($sOutPut) {
		if($sOutPut !="") {
			print $sOutPut;
		}
	}
	
	 /**
	  * Set new node for xml file of description
	  * 
	  * @param {string} $sDesc Content of node
	  * 
	  */   
    public function setBodyNodeDescription($sDesc) {
    	if($sDesc != ""){
    		$this->sDescripion = htmlentities($sDesc);
    		$this->setData("description", $this->sDescripion);
    	}	
    }
    
    
    

} // end of class

?>