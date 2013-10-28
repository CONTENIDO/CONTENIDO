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
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release >= 4.8.8
 *
 * {@internal
 *   created 2008-08-12
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

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

   /**
	* To take control versioning is switched off
	* @access private
	*/
	private $bVersionCreatActive;

   /**
	* Timestamp
	* @access protected
	*/
    protected $dActualTimestamp;

    /**
     * Alternative Path for save version files
	 * @access protected
     */
    protected $sAlternativePath;

    /**
     * Displays Notification only onetimes per object
     *
     */
    public static $iDisplayNotification;

	/**
	 * The Version object constructor, initializes class variables
	 *
	 * @param array $aCfg
	 * @param array $aCfgClient
	 * @param object $oDB
	 * @param integer $iClient
	 * @param object $sArea
	 * @param object $iFrame
	 *
	 * @return void
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

		Version::$iDisplayNotification++;

		// Look if versioning is allowed, default is false
        if (function_exists('getEffectiveSetting')) {
            $this->bVersionCreatActive = getEffectiveSetting('versioning', 'activated', 'true');
            $this->sAlternativePath = getEffectiveSetting('versioning', 'path');
        } else {
            $this->bVersionCreatActive = true;
            $this->sAlternativePath = "";
        }

		if($this->bVersionCreatActive == "true"){
			if(!is_dir($this->sAlternativePath) ){
				// Alternative Path is not true or is not exist, we use the frontendpath
    			if($this->sAlternativePath !="" AND self::$iDisplayNotification  < 2){
    				$oNotification = new Contenido_Notification();
					$sNotification = i18n("Alternative path %s does not exist. Version was saved in frondendpath.");
    				$oNotification->displayNotification("warning",  sprintf($sNotification, $this->sAlternativePath));

    			}

    			$this->sAlternativePath = "";
            }

			// Look if versioning is set alternative path to save
			$this->checkPaths();
		}
	}

	/**
	 * This function looks if maximum number of stored versions is achieved. If true, it will be delete the first version.
	 *
	 * @return void
	 */
    protected function prune() {
        $this->initRevisions();
        if (function_exists('getEffectiveSetting')) {
            $sVar = getEffectiveSetting('versioning', 'prune_limit', '0');
        } else {
            $sVar = 0;
        }

 		$bDelete = true;

		while(count($this->aRevisionFiles) >= $sVar AND $bDelete AND (int) $sVar > 0) {
            $iIndex = end(array_keys($this->aRevisionFiles));
            $bDelete = $this->deleteFile($this->getFirstRevision());
            unset($this->aRevisionFiles[$iIndex]);
		}
    }

    /**
	 * This function checks if needed version paths exists and were created if neccessary
	 * @return void
	 */
    protected function checkPaths() {
        $aPath = array('', '/css', '/js', '/layout', '/module', '/templates');
        $sFrontEndPath = "";
        if($this->sAlternativePath == "") {
        	$sFrontEndPath = $this->aCfgClient[$this->iClient]["path"]["frontend"] . "version";
        } else {
        	$sFrontEndPath = $this->sAlternativePath . "/" . $this->iClient;
        }

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
	 * @param string $sKey
	 * @param string $sValue
	 *
	 * @return array returns an array for body node
	 */
	public function setData($sKey, $sValue) {
		$this->aBodyData[$sKey] = $sValue;

	}

	/**
	 * This function creats an xml file. XML Writer helps for create this file.
	 *
	 * @return string returns content of xml file
	 */
	public function createNewXml() {
		$oXW = new xmlWriter();
		$oXW->openMemory();
		$oXW->setIndent(true);
		$oXW->startDocument('1.0', 'UTF-8');

		$oXW->startElement('version');
		$oXW->writeAttribute('xml:lang', 'de');

		$oXW->startElement('head');
		$oXW->writeElement('version_id', $this->iIdentity.'_'.$this->iVersion);
		$oXW->writeElement('type', $this->sType);
		$oXW->writeElement('date', date("Y-m-d H:i:s"));
		$oXW->writeElement('author', $this->sAuthor);
		$oXW->writeElement('client', $this->iClient);
		$oXW->writeElement('created', $this->dCreated);
		$oXW->writeElement('lastmodified', $this->dLastModified);
		$oXW->endElement();

		$oXW->startElement('body');

		foreach ($this->aBodyData as $sKey => $sValue) {
			$oXW->writeElement($sKey, conHtmlentities($sValue));
		}

		$oXW->endElement();

		$oXW->endElement();

		return $oXW->outputMemory(true);
	}

	/**
	 * This function creats new version in right folder.
	 *
	 * @return void
	 */
	public function createNewVersion() {
		$bCreate = false;
		if($this->bVersionCreatActive == "true"){
			try { // Get version Name
				$sRevisionName = $this->getRevision();

				// Create xml version file
				$sXmlFile = $this->createNewXml();

				if(!is_dir($this->getFilePath())){

					$bCreate = mkdir($this->getFilePath(), 0777);
					chmod ($this->getFilePath(), 0777);
				}

				$sHandle = fopen($this->getFilePath().$sRevisionName.'.xml', "w");
				#fputs($sHandle, $sXmlFile);
								fputs($sHandle, $sXmlFile);
				$bCreate = fclose($sHandle);

				if($bCreate == false){
					throw new Exception('Couldnt Create New Version');
				}

			} catch(Exception $e) {
				$bCreate = false;
				 echo '<br>Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
			}
		}
		return $bCreate;
	}

	/**
	 * This function inits version files. Its filter also timestamp and version files
	 *
	 * @return array returns xml file names
	 */
	protected function initRevisions() {
        $this->aRevisionFiles = array();
        $this->dTimestamp = array();

		// Open this Filepath and read then the content.
		$sDir = $this->getFilePath();
		if (is_dir($sDir)) {
		    if ($dh = opendir($sDir)) {

		        while (($file = readdir($dh)) !== false) {
		        	if ($file != '.' && $file != '..'){
		           			 $aData = explode('.', $file);
		           			 $aValues = explode('_', $aData[0]);
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
	 * This function deletes files and the the folder, for given path.
	 *
	 * @return bool return true if successful
	 */
	public function deleteFile($sFirstFile = "") {
		// Open this Filepath and read then the content.
		$sDir = $this->getFilePath();

		$bDelet = false;
		if (is_dir($sDir) AND $sFirstFile =="") {
		    if ($dh = opendir($sDir)) {
		    	  while (($sFile = readdir($dh)) !== false) {
					if($sFile != "."  && $sFile !=".."){
						// Delete the files
						$bDelete = unlink($sDir.$sFile);
					}
		    	  }
//		    	  if the files be cleared, the delete the folder
		    	  	$bDelete = rmdir($sDir);
		    }
		} else if($sFirstFile !="") {
				$bDelete = unlink($sDir . $sFirstFile);
		}
		if($bDelete){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the frontendpath to revision
	 *
	 * @return string returns path to revision file
	 */
	public function getFilePath() {
		if($this->sAlternativePath =="") {
        	$sFrontEndPath = $this->aCfgClient[$this->iClient]["path"]["frontend"] . "version/";
        } else {
        	$sFrontEndPath = $this->sAlternativePath . "/" . $this->iClient . "/";
        }
		return $sFrontEndPath . $this->sType.'/'. $this->iIdentity. '/';
	}

	/**
	 * Get the last revision file
	 *
	 * @return array returns Last Revision
	 */
    public function getLastRevision() {
        return reset($this->aRevisionFiles);
    }

    /**
	 * Makes new and init Revision Name
	 *
	 * @return integer returns number of Revison File
	 */
	private function getRevision() {
		$this->iVersion = ($this->iRevisionNumber +1 ).'_'.$this->dActualTimestamp;
		return $this->iVersion;
	}

	/**
	 * Inits the first element of revision files
	 *
	 * @return string the name of xml files
	 */
	protected function getFirstRevision() {
		$aKey = array();
		$this->initRevisions();
		$aKey = $this->aRevisionFiles;
		$sFirstRevision = "";

//		to take first element, we use right sort
		ksort($aKey);
		foreach($aKey as $value){
			return $sFirstRevision = $value;
		}
		return $sFirstRevision;
	}

    /**
	 * Revision Files
	 *
	 * @return array returns all Revison File
	 */
	public function getRevisionFiles() {
		return $this->aRevisionFiles;
	}

	/**
	 * This function generate version names for select-box
	 *
	 * @return array returns an array of revision file names
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
	 * @return array returns an array of revision file names
	 */
	public function setVarForm($sKey, $sValue) {
		$this->aVarForm[$sKey] = $sValue;
	}

	/**
	 * The general SelectBox function for get Revision.
	 *
	 * @param string  $sTableForm The name of Table_Form class
	 * @param string  $sAddHeader The Header Label of SelectBox Widget
	 * @param string  $sLabelOfSelectBox  The Label of SelectBox Widget
	 * @param string  $sIdOfSelectBox  Id of Select Box
	 *
	 * return string  if is exists Revision, then returns HTML Code of full SelectBox else returns empty string
	 */
    public function buildSelectBox($sTableForm, $sAddHeader, $sLabelOfSelectBox, $sIdOfSelectBox) {
		$oForm = new UI_Table_Form("lay_history");
		$aMessage = array();
		// if exists xml files
		if(count($this->dTimestamp) > 0) {

			foreach($this->aVarForm as $sKey=>$sValue) {
				$oForm ->setVar($sKey, $sValue);
			}
			$aMessage = $this->getMessages();
			$oForm ->addHeader(i18n($sAddHeader));
			$oForm ->add(i18n($sLabelOfSelectBox),  $this->getSelectBox($this->getFormatTimestamp(), $sIdOfSelectBox));
			$oForm ->setActionButton("clearhistory", "images/but_delete.gif", $aMessage["alt"], "c", "history_truncate");
			$oForm ->setConfirm("clearhistory", $aMessage["alt"], $aMessage["popup"]);
			$oForm ->setActionButton("submit", "images/but_refresh.gif", i18n("Refresh"), "s");

			return $oForm ->render().'<div style="margin-top:20px;"></div>';
		} else {
			return '';
		}
    }

    /**
     * Messagebox for build selectBox. Dynamic allocation for type.
	 * return array the attributes alt and poput returns
	 */
    private function getMessages(){
    	$aMessage = array();
    	switch($this->sType){
    		case 'layout':
    			$aMessage["alt"] = i18n("Clear layout history");
    			$aMessage["popup"] = i18n("Do you really want to clear layout history?")."<br><br>".i18n("Note: This only affects the current layout.");
    		break;
    		case 'module':
    			$aMessage["alt"] = i18n("Clear module history");
    			$aMessage["popup"] = i18n("Do you really want to clear module history?")."<br><br>".i18n("Note: This only affects the current module.");
    		break;
    		case 'css':
    			$aMessage["alt"] = i18n("Clear style history");
    			$aMessage["popup"] = i18n("Do you really want to clear style history?")."<br><br>".i18n("Note: This only affects the current style.");
    		break;
    		case 'js':
    			$aMessage["alt"] = i18n("Clear Java-Script history");
    			$aMessage["popup"] = i18n("Do you really want to clear Java-Script history?")."<br><br>".i18n("Note: This only affects the current Java-Script.");
    		break;
    		case 'templates':
    			$aMessage["alt"] = i18n("Clear HTML-Template history");
    			$aMessage["popup"] = i18n("Do you really want to clear HTML-Template history?")."<br><br>".i18n("Note: This only the affects current HTML-Template.");
    		break;
      		default:
      			$aMessage["alt"] = i18n("Clear history");
    			$aMessage["popup"] = i18n("Do you really want to clear history?")."<br><br>".i18n("Note: This only affects the current history.");
      		break;

    	}
    	return $aMessage;
    }

  	/**
	 * A Class Function for fill version files
	 *
	 * @param string  $sTableForm The name of Table_Form class
	 * @param string  $sAddHeader The Header Label of SelectBox Widget
	 *
	 * return string  returns select-box with filled files
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
	 * @param string $sName The name of Textarea.
	 * @param string $sValue The value of Input Textarea
	 * @param integer $iWidth width of Textarea
	 * @param integer $iHeight height of Textarea
	 *
	 * @return string HTML Code of Textarea
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
	 * @param string $sName The name of Input Textfield.
	 * @param string $sValue The value of Input Textfield
	 * @param integer $iWidth width of Input Textfield
	 *
	 * @return string HTML Code of Input Textfield
	 */
	public function getTextBox($sName, $sInitValue, $iWidth, $bDisabled = false) {
		$oHTMLTextbox = new cHTMLTextbox($sName, conHtmlEntityDecode($sInitValue), $iWidth, "", "", $bDisabled);
		$oHTMLTextbox->setStyle("font-family: monospace; width: 100%;");
		$oHTMLTextbox->updateAttributes(array("wrap" => "off"));

		return $oHTMLTextbox->render();
	}

	/**
	 * Displays your notification
	 *
	 * @param string $sOutPut
	 *
	 * @return void
	 */
	public function displayNotification($sOutPut) {
		if($sOutPut !="") {
			print $sOutPut;
		}
	}

	 /**
	  * Set new node for xml file of description
	  *
	  * @param string $sDesc Content of node
	  *
	  */
    public function setBodyNodeDescription($sDesc) {
    	if($sDesc != ""){
    		$this->sDescripion = conHtmlentities($sDesc);
    		$this->setData("description", $this->sDescripion);
    	}
    }

} // end of class

?>