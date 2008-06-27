<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Update Notifier Functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * @con_php_req simplexml
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 * 
 * {@internal 
 *   created 2008-06-21
 *   $Id$:
 * }}
 * 
 */

class Contenido_UpdateNotifier {
	/**
	 * System version of Contenido
	 * @access protected
	 * @var string
	 */
	protected $sContenidoVersion;

	/**
	 * Minor release for the simplexml xpath() method
	 * @access protected
	 * @var string
	 */
	protected $sMinorRelease;

	/**
	 * Host for vendor XML
	 * @access protected
	 * @var string
	 */
	protected $sVendorXMLHost = "dodohome.ath.cx"; 

	/**
	 * Path to vendor XML file
	 * @access protected
	 * @var string
	 */
	protected $sVendorXMLFile = "vendor.xml";

	/**
	 * Path to timestamp cache file
	 * @access protected
	 * @var string
	 */
	protected $sTimestampCacheFile = "update.txt";

	/**
	 * Content of the XML file
	 * @access protected
	 * @var string
	 */
	protected $sXMLContent;

	/**
	 * Current available vendor version
	 * @access protected
	 * @var string
	 */
	protected $sVendorVersion;

	/**
	 * Path to cache directory
	 * @access protected
	 * @var string
	 */
	protected $sCacheDirectory;

	/**
	 * Download URL
	 * @access protected
	 * @var string
	 */
	protected $sVendorURL = "http://www.contenido.org/de/redir";

	/**
	 * SimpleXML object
	 * @access protected
	 * @var object
	 */
	protected $oXML;

	/**
	 * Properties object
	 * @access protected
	 * @var object
	 */
	protected $oProperties;

	/**
	 * Timeout for the fsockopen connection
	 * @access protected
	 * @var integer
	 */
	protected $iConnectTimeout = 3;

	/**
	 * Cache duration in minutes
	 * @access protected
	 * @var integer
	 */
	protected $iCacheDuration = 60;

	/**
	 * Check for system setting
	 * @access protected
	 * @var boolean
	 */
	protected $bEnableCheck;

	/**
	 * Display update notification based on user rights (sysadmin only)
	 * @access protected
	 * @var boolean
	 */
	protected $bEnableView;

	/**
	 * Update necessity
	 * @access protected
	 * @var boolean
	 */
	protected $bUpdateNecessity;

	/**
	 * Path configuration array
	 * @access protected
	 * @var array
	 */
	protected $aCfgPath;

	/**
	 * Property configuration array
	 * @access protected
	 * @var array
	 */
	protected $aPropConf = array("itemType" => "update", "itemID" => 1, "type" => "file_check", "name" => "xml");

	/**
	 * System property configuration array
	 * @access protected
	 * @var array
	 */
	protected $aSysPropConf = array("type" => "update", "name" => "check");
    

	/**
	 * Constructor of Contenido_UpdateNotifier
	 * @access public
	 * @param  string $sConVersion
	 * @return void
	 */
	public function __construct($sConVersion, $aCfgPath, $oUser, $oPerm, $oSession) {
		$this->sContenidoVersion = $sConVersion;
		$this->aCfgPath = $aCfgPath;
		$this->oProperties = new PropertyCollection;
		$this->oSession = $oSession;

		$sAction = $_GET['do'];
		if ($sAction == "activate") {
			setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "true");
		} else if ($sAction == "deactivate") {
			setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "false");
		}

		if ($oPerm->isSysadmin($oUser) != 1) {
			$this->bEnableView = false;
		} else {
			$this->bEnableView = true;
			$this->setCachePath();

			if (getSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name']) == "true") {
				$this->bEnableCheck = true;
				$this->detectMinorRelease();
				$this->checkUpdateNecissity();
				$this->readVendorXML();
			} else {
				$this->bEnableCheck = false;
			}
		}
    	}

	/**
	 * Sets the cache path
	 * @access protected
	 * @return void
	 */
	protected function setCachePath() {
		$sConPath = $this->aCfgPath['contenido'];
		$sCachePath = $sConPath."cache".DIRECTORY_SEPARATOR;
		if (!is_dir($sCachePath)) {
			mkdir($sCachePath, 0777);
		}
		$this->sCacheDirectory = $sCachePath;
	}

	/**
	 * Checks if the xml file must be loaded from the vendor host or local cache
	 * @access protected
	 * @return boolean
	 */
	protected function checkUpdateNecissity() {
		if (!file_exists($this->sCacheDirectory.$this->sVendorXMLFile)) {
			$bUpdateNecessity = true;
		} else	if (file_exists($this->sCacheDirectory.$this->sTimestampCacheFile)) {
			$iLastUpdate = file_get_contents($this->sCacheDirectory.$this->sTimestampCacheFile);

			$iCheckTimestamp = $iLastUpdate + ($this->iCacheDuration * 60);
			$iCurrentTime = time();

			if ($iCheckTimestamp > $iCurrentTime) {
				$bUpdateNecessity = false;
			} else {
				$bUpdateNecessity = true;
			}	
		} else {
			$bUpdateNecessity = true;
		}

		$this->bUpdateNecessity = $bUpdateNecessity;
	} 

	/**
	 * Detects and converts the minor release of the system version
	 * @access protected
	 * @return void
	 */
	protected function detectMinorRelease(){
		$sVersion = $this->sContenidoVersion;
		$sExplode = explode(".", $sVersion);

		$sMinorRelease = "con".$sExplode[0].$sExplode[1];
		$this->sMinorRelease = $sMinorRelease;
	}

	/**
	 * Reads the xml file from vendor host or cache and checks for file manipulations
	 * @access protected
	 * @return void
	 */
	protected function readVendorXML() {
		if ($this->bUpdateNecessity == true) {
			$sXMLContent = $this->getVendorHostFile();
			if ($sXMLContent != "") {
				$this->sXMLContent = $sXMLContent;
				$this->updateCacheFiles();
				$this->updateHashProperty();
			} 
		} else {
			$sXMLContent = file_get_contents($this->sCacheDirectory.$this->sVendorXMLFile);
			$sXMLHash = md5($sXMLContent);
			$sPropertyHash = $this->getHashProperty();

			if ($sXMLHash == $sPropertyHash) {
				$this->sXMLContent = $sXMLContent;
			} else {
				$sXMLContent = $this->getVendorHostFile();
				if ($sXMLContent != "") {
					$this->sXMLContent = $sXMLContent;
					$this->updateCacheFiles();
					$this->updateHashProperty();
				} 
			}
		}

		if ($this->sXMLContent) {
			$this->oXML = simplexml_load_string($this->sXMLContent);
			if (!is_object($this->oXML)) {
   				$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Could not handle server response!');
				$this->sErrorOutput = $this->renderOutput($sErrorMessage);			
			} else {
				$oVersion = $this->oXML->xpath("/fourforbusiness/contenido/releases/".$this->sMinorRelease);
				if (!isset($oVersion[0])) {
					$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Could not determine vendor version!');
					$this->sErrorOutput = $this->renderOutput($sErrorMessage);
				} else {
					$this->sVendorVersion = $oVersion[0];
				}
			}	
		}	
	}

	/**
	 * Connects with vendor host and gets the xml file
	 * @access protected
	 * @return string
	 */
	protected function getVendorHostFile() {
		$sXMLContent = "";

		$oSocket = fsockopen($this->sVendorXMLHost, 80, $errno, $errstr, $this->iConnectTimeout); 
		if (!$oSocket) { 
   			$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Connection to contenido.org failed!');
			$this->sErrorOutput = $this->renderOutput($sErrorMessage);
		} else { 
   			fputs($oSocket, "GET /".$this->sVendorXMLFile." HTTP/1.0\r\n\r\n"); 
   			while(!feof($oSocket)) { 
       			$sXMLContent .= fgets($oSocket, 128); 
   			} 
  			fclose($oSocket); 

			$sSeperator = strpos($sXMLContent, "\r\n\r\n");
          		$sXMLContent = substr($sXMLContent, $sSeperator + 4);
		}

		return $sXMLContent;
	}

	/**
	 * Updates the files in cache
	 * @access protected
	 * @return void
	 */
	protected function updateCacheFiles() {
		$sVendorCacheFile = $this->sCacheDirectory.$this->sVendorXMLFile;
		$oVendorFile = fopen($sVendorCacheFile, "w+");
		ftruncate($oVendorFile, 0);
		fwrite($oVendorFile, $this->sXMLContent);
		fclose($oVendorFile);

		$sTimeCacheFile = $this->sCacheDirectory.$this->sTimestampCacheFile;
		$oTimeFile = fopen($sTimeCacheFile, "w+");
		ftruncate($oTimeFile, 0);
		fwrite($oTimeFile, time());
		fclose($oTimeFile);
	}

	/**
	 * Gets the xml file hash from the property table
	 * @access protected
	 * @return void
	 */
	protected function getHashProperty() {
		$sProperty = $this->oProperties->getValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name']);
		return $sProperty;
	}

	/**
	 * Updates the xml file hash in the property table
	 * @access protected
	 * @return void
	 */
	protected function updateHashProperty() {
		$sPropValue = md5($this->sXMLContent);
		$sProperty = $this->getHashProperty();
		if(!$sProperty) {
			$this->oProperties->create($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name'], $sPropValue);
		} else {
			$this->oProperties->setValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name'], $sPropValue);
		}
	}

	/**
	 * Checks the patch level of system and vendor version
	 * @access protected
	 * @return string
	 */
	protected function checkPatchLevel() {
		$sVersionCompare = version_compare($this->sContenidoVersion, $this->sVendorVersion);
		return $sVersionCompare;
	}

	/**
	 * Generates the download URL
	 * @access protected
	 * @return string
	 */
	protected function getDownloadURL() {
		$sVendorURLVersion = str_replace(".", "_", $this->sVendorVersion);
		$sVendorURL = $this->sVendorURL."/Contenido_".$sVendorURLVersion;
		return $sVendorURL;
	}

	/**
	 * Generates the output for the backend
	 * @access protected
	 * @param $sMessage
	 * @param $sColor
	 * @return string
	 */
	protected function renderOutput($sMessage, $sColor = "red") {
		$sOutput  = '<div id="update_notifier" class="content_box_welcome">';
		$sOutput .= '<h2 class="content_box" style="color:'.$sColor.'">'.i18n('Update notification').'</h2>';
		$sOutput .= '<span style="color:'.$sColor.'">'.$sMessage.'</a>';
		$sOutput .= '</div>';
			
		return $sOutput;
	}
    
	/**
	 * Displays the rendered output
	 * @access public
	 * @return string
	 */
    	public function displayOutput() {
		if (!$this->bEnableView) {
			$sOutput = "";
		} else if (!$this->bEnableCheck) {
			$sEnableURL = $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=activate');
			$sMessage = sprintf(i18n('Update notification is disabled! <a href="%s">Enable notifications</a>'), $sEnableURL);
			$sOutput = $this->renderOutput($sMessage, "black");
		} else if ($this->sErrorOutput != "") {
			$sOutput = $this->sErrorOutput;
		} else if (!$this->sVendorVersion) {
			$sMessage = i18n('You have an unknown or unsupported version of Contenido!');
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->sVendorVersion == "deprecated") {
			$sMessage = i18n('Your version of Contenido is deprecated and not longer supported for any updates. Please update to a higher version!');
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->checkPatchLevel() == "-1") {
			$sVendorDownloadURL = $this->getDownloadURL();
			$sMessage = sprintf(i18n('A new version of Contenido (<b>%s</b>) is available! - <a href="%s" target="_blank">Download now!</a>'), $this->sVendorVersion, $sVendorDownloadURL);
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->checkPatchLevel() == "1") {
			$sMessage = sprintf(i18n('It seems to be that your version string was manipulated. Contenido %s does not exist!'), $this->sContenidoVersion);
			$sOutput = $this->renderOutput($sMessage);
		} else {
			$sMessage = i18n('Your version of Contenido is up to date!');
			$sOutput = $this->renderOutput($sMessage, "green");
		}

       	return $sOutput;
    	}
}
?>