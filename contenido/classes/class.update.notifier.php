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
 *
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
	 * Download URL
	 * @access protected
	 * @var string
	 */
	protected $sVendorURL = "http://www.contenido.org/de/redir";

	/**
	 * SimpleXML object
	 * @access protected
	 * @var string
	 */
	protected $oXML;

	/**
	 * Timeout for the fsockopen connection
	 * @access protected
	 * @var string
	 */
	protected $iConnectTimeout = 1;

	/**
	 * Check for system setting
	 * @access protected
	 * @var string
	 */
	protected $bEnableCheck;
    

	/**
	 * Constructor of Contenido_UpdateNotifier
	 * @access public
	 * @param  string $sConVersion
	 * @return void
	 */
	public function __construct($sConVersion) {
		$this->sContenidoVersion = $sConVersion;

		if (getSystemProperty("update", "check") == "true") {
			$this->bEnableCheck = true;
			$this->detectMinorRelease();
			$this->readVendorXML();
		} else {
			$this->bEnableCheck = false;
		}
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
	 * Connects to vendor host and loads the XML file content
	 * @access protected
	 * @return void
	 */
	protected function readVendorXML() {
		$oSocket = fsockopen($this->sVendorXMLHost, 80, $errno, $errstr, $this->iConnectTimeout); 
		if (!$oSocket) { 
   			$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Connection to contenido.org failed!');
			$this->sErrorOutput = $this->renderOutput($sErrorMessage);
		} else { 
   			fputs($oSocket, "GET /".$this->sVendorXMLFile." HTTP/1.0\r\n\r\n"); 
   			while(!feof($oSocket)) { 
       			$this->sXMLContent .= fgets($oSocket, 128); 
   			} 
  			fclose($oSocket); 

			$sSeperator = strpos($this->sXMLContent, "\r\n\r\n");
               	$sXMLBody = substr($this->sXMLContent, $sSeperator + 4);
			$this->sXMLContent = $sXMLBody;
		 
			$this->oXML = simplexml_load_string($this->sXMLContent);
			if (!is_object($this->oXML)) {
   				$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Could not handle server response!');
				$this->sErrorOutput = $this->renderOutput($sErrorMessage);			
			} else {
				$oVersion = $this->oXML->xpath("/fourforbusiness/contenido/releases/".$this->sMinorRelease);
				if (!is_array($oVersion)) {
					$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Could not determine vendor version!');
					$this->sErrorOutput = $this->renderOutput($sErrorMessage);
				} else {
					$this->sVendorVersion = $oVersion[0];
				}
			}
		}
		
	}

	/**
	 * Checks the patch level of system and vendor version
	 * @access protected
	 * @return mixed
	 */
	protected function checkPatchLevel() {
		$mVersionCompare = version_compare($this->sContenidoVersion, $this->sVendorVersion);
		return $mVersionCompare;
	}

	/**
	 * Generates the Download URL
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
		$sHTML = '<h2 class="content_box" style="color:'.$sColor.'">'.i18n('Update notification').'</h2>';
		$sHTML .= '<span style="color:'.$sColor.'">'.$sMessage.'</a>';
		return $sHTML;
	}
    
	/**
	 * Displays the rendered output
	 * @access public
	 * @return string
	 */
    	public function displayOutput() {
		if (!$this->bEnableCheck) {
			$sMessage = i18n('Update notification is disabled!');
			$sOutput = $this->renderOutput($sMessage);
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
			$sMessage = sprintf(i18n('A new version of Contenido (<b>%s</b>) is available! - <a href="%s">Download now!</a>'), $this->sVendorVersion, $sVendorDownloadURL);
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->checkPatchLevel() == "1") {
			$sVendorDownloadURL = $this->getDownloadURL();
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