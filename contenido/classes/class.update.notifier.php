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
 *   modified 2008-07-01 timo trautmann - added rss update functionality
 *   modified 2008-07-02, Dominik Ziegler, added language support for rss
 *   $Id$:
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
 
class Contenido_UpdateNotifier {
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
	 * Path to rss cache file
	 * @access protected
	 * @var string
	 */
	protected $sRssCacheFile;
    
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
	 * Content of the RSS file
	 * @access protected
	 * @var string
	 */
	protected $sXMLContentRss;
	
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
	 * Current backend language
	 * @access protected
	 * @var string
	 */
	protected $sBackendLanguage;

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
	 * Session object
	 * @access protected
	 * @var object
	 */
	protected $oSession;

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
	protected $bEnableCheck = false;
	
	/**
	 * Check for system setting Rss
	 * @access protected
	 * @var boolean
	 */
	protected $bEnableCheckRss = false;

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
	 * Property configuration array
	 * @access protected
	 * @var array
	 */
	protected $aPropConf = array("itemType" => "update", "itemID" => 1, "type" => "file_check", "name" => "xml");

	/**
	 * System property configuration array for update notification
	 * @access protected
	 * @var array
	 */
	protected $aSysPropConf = array("type" => "update", "name" => "check");
	
	/**
	 * System property configuration array for rss notification
	 * @access protected
	 * @var array
	 */
	protected $aSysPropConfRss = array("type" => "update", "name" => "news_feed");
    
	/**
	 * Contenido configuration array
	 * @access protected
	 * @var array
	 */
	protected $aCfg = array();

	/**
	 * RSS feeds based on current backend language
	 * @access protected
	 * @var array
	 */
	protected $aRSSFiles = array("en_US" => "rss_en.xml", "de_DE" => "rss_de.xml", "default" => "rss_de.xml");

	
	/**
	 * Constructor of Contenido_UpdateNotifier
	 * @access public
	 * @param  string $sConVersion
	 * @return void
	 */
	public function __construct($aCfg, $oUser, $oPerm, $oSession, $sBackendLanguage) {
		$this->oProperties = new PropertyCollection;
		$this->oSession = $oSession;
		$this->aCfg = $aCfg;
		$this->sBackendLanguage = $sBackendLanguage;

		if ($oPerm->isSysadmin($oUser) != 1) {
			$this->bEnableView = false;
		} else {
			$sAction = $_GET['do'];
			if ($sAction == "activate") {
				setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "true");
			} else if ($sAction == "deactivate") {
				setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "false");
			} else if ($sAction == "activate_rss"){
				setSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name'], "true");
			} else if ($sAction == "deactivate_rss"){
				setSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name'], "false");
			}
			
			$this->bEnableView = true;
			$this->setCachePath();

			if (getSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name']) == "true" ||
			    getSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name']) == "true") {
				if(getSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name']) == "true") {
				    $this->bEnableCheck = true;   
				}
				if (getSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name']) == "true") {
				   $this->bEnableCheckRss = true;
				}
				
				$this->setRSSFile();
				$this->detectMinorRelease();
				$this->checkUpdateNecessity();
				$this->readVendorXML();
			}
		}
	}

	/**
	 * Sets the RSS file based on the current backend language
	 * @access protected
	 * @return void
	 */
	protected function setRSSFile() {
		if (isset($this->aRSSFiles[$this->sBackendLanguage])) {
			$this->sRssCacheFile = $this->aRSSFiles[$this->sBackendLanguage];
		} else {
			$this->sRssCacheFile = $this->aRSSFiles['default'];
		}
	}

	/**
	 * Sets the cache path
	 * @access protected
	 * @return void
	 */
	protected function setCachePath() {
		$sConPath = $this->aCfg['path']['contenido'];
		$sCachePath = $sConPath."cache".DIRECTORY_SEPARATOR;
		if (!is_dir($sCachePath)) {
			mkdir($sCachePath, 0777);
			chmod($sCachePath, 0777);
		}
		$this->sCacheDirectory = $sCachePath;
	}

	/**
	 * Checks if the xml or rss file must be loaded from the vendor host or local cache
	 * @access protected
	 * @return boolean
	 */
	protected function checkUpdateNecessity() {
		if (!file_exists($this->sCacheDirectory.$this->sVendorXMLFile)) {
			$bUpdateNecessity = true;
		} else if (!file_exists($this->sCacheDirectory.$this->sRssCacheFile)) {
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
		$sVersion = $this->aCfg['version'];
		$sExplode = explode(".", $sVersion);

		$sMinorRelease = "con".$sExplode[0].$sExplode[1];
		$this->sMinorRelease = $sMinorRelease;
	}

	/**
	 * Reads the xml and rss file from vendor host or cache and checks for file manipulations
	 * @access protected
	 * @return void
	 */
	protected function readVendorXML() {
		if ($this->bUpdateNecessity == true) {
			$aXmlContent = $this->getVendorHostFile();
			if ($aXmlContent['update'] != "") {
				$this->sXMLContent = $aXmlContent['update'];
				$this->sXMLContentRss = $aXmlContent['rss'];
				$this->updateCacheFiles();
				$this->updateHashProperty();
			} 
		} else {
			$sXMLContent = file_get_contents($this->sCacheDirectory.$this->sVendorXMLFile);
			$sXMLContentRss = file_get_contents($this->sCacheDirectory.$this->sRssCacheFile);
			$sXMLHash = md5($sXMLContent.$sXMLContentRss);
			$sPropertyHash = $this->getHashProperty();

			if ($sXMLHash == $sPropertyHash) {
				$this->sXMLContent = $sXMLContent;
				$this->sXMLContentRss = $sXMLContentRss;
			} else {
				$aXmlContent = $this->getVendorHostFile();
				if ($aXmlContent['update'] != "") {
					$this->sXMLContent = $aXmlContent['update'];
					$this->sXMLContentRss = $aXmlContent['rss'];
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
	 * Connects with vendor host and gets the xml and rss file
	 * @access protected
	 * @return array
	 */
	protected function getVendorHostFile() {
		$aXMLContent = array();

		$oSocket = fsockopen($this->sVendorXMLHost, 80, $errno, $errstr, $this->iConnectTimeout); 
		if (!$oSocket) { 
   			$sErrorMessage = i18n('Unable to check for new updates!')." ".i18n('Connection to contenido.org failed!');
			$this->sErrorOutput = $this->renderOutput($sErrorMessage);
		} else { 
		    #get update file
   			fputs($oSocket, "GET /".$this->sVendorXMLFile." HTTP/1.0\r\n\r\n"); 
   			while(!feof($oSocket)) { 
       			$aXMLContent['update'] .= fgets($oSocket, 128); 
   			} 
			$sSeperator = strpos($aXMLContent['update'], "\r\n\r\n");
          	$aXMLContent['update'] = substr($aXMLContent['update'], $sSeperator + 4);
			fclose($oSocket); 
			
			#get rss file
			$oSocket = fsockopen($this->sVendorXMLHost, 80, $errno, $errstr, $this->iConnectTimeout); 
			fputs($oSocket, "GET /".$this->sRssCacheFile." HTTP/1.0\r\n\r\n"); 
   			while(!feof($oSocket)) { 
       			$aXMLContent['rss'] .= fgets($oSocket, 128); 
   			} 
			$sSeperator = strpos($aXMLContent['rss'], "\r\n\r\n");
          	$aXMLContent['rss'] = substr($aXMLContent['rss'], $sSeperator + 4);

			fclose($oSocket); 
		}

		return $aXMLContent;
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
		
		$sRssCacheFile = $this->sCacheDirectory.$this->sRssCacheFile;
		$oRssFile = fopen($sRssCacheFile, "w+");
		ftruncate($oRssFile, 0);
		fwrite($oRssFile, $this->sXMLContentRss);
		fclose($oRssFile);

		$sTimeCacheFile = $this->sCacheDirectory.$this->sTimestampCacheFile;
		$oTimeFile = fopen($sTimeCacheFile, "w+");
		ftruncate($oTimeFile, 0);
		fwrite($oTimeFile, time());
		fclose($oTimeFile);
	}

	/**
	 * Gets the xml file hash from the property table
	 * @access protected
	 * @return string
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
		$sPropValue = md5($this->sXMLContent.$this->sXMLContentRss);
		$this->oProperties->setValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name'], $sPropValue);
	}

	/**
	 * Checks the patch level of system and vendor version
	 * @access protected
	 * @return string
	 */
	protected function checkPatchLevel() {
		$sVersionCompare = version_compare($this->aCfg['version'], $this->sVendorVersion);
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
	 * @return string
	 */
	protected function renderOutput($sMessage) {
		$oTpl = new Template();
		$oTpl->set('s', 'UPDATE_MESSAGE', $sMessage);
		
		if ($this->bEnableCheck == true) {
			$oTpl->set('s', 'UPDATE_ACTIVATION', i18n('Disable update notification'));
			$oTpl->set('s', 'IMG_BUT_UPDATE', 'but_cancel.gif');
			$oTpl->set('s', 'LABEL_BUT_UPDATE', i18n('Disable notification'));
			$oTpl->set('s', 'URL_UPDATE', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=deactivate'));
		} else {
			$oTpl->set('s', 'UPDATE_ACTIVATION', i18n('Enable update notification (recommended)'));
			$oTpl->set('s', 'IMG_BUT_UPDATE', 'but_ok.gif');
			$oTpl->set('s', 'LABEL_BUT_UPDATE', i18n('Enable notification'));
			$oTpl->set('s', 'URL_UPDATE', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=activate'));
		}
		
		if ($this->bEnableCheckRss == true) {
		    $oTpl->set('s', 'RSS_ACTIVATION', i18n('Disable RSS notification'));
			$oTpl->set('s', 'IMG_BUT_RSS', 'but_cancel.gif');
			$oTpl->set('s', 'LABEL_BUT_RSS', i18n('Disable notification'));
			$oTpl->set('s', 'URL_RSS', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=deactivate_rss'));
			
			$oTpl = $this->renderRss($oTpl);
		} else {
		    $oTpl->set('s', 'RSS_ACTIVATION', i18n('Enable RSS notification (recommended)'));
			$oTpl->set('s', 'IMG_BUT_RSS', 'but_ok.gif');
			$oTpl->set('s', 'LABEL_BUT_RSS', i18n('Enable notification'));
			$oTpl->set('s', 'URL_RSS', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=activate_rss'));
		}
		
		return $oTpl->generate('templates/standard/'.$this->aCfg['templates']['welcome_update'], 1);;
	}
    
    /**
	 * Generates the output for the rss informations
	 * @access protected
	 * @param $oTpl
	 * @return contenido template object
	 */
	protected function renderRss($oTpl) {
		if (!is_object($oTpl)) {
			$oTpl = new Template();
		}
		
		if ($this->sXMLContentRss != '') {
			$sFeedContent = substr($this->sXMLContentRss, 0, 1024);
			
			$regExp = "/<\?xml.*encoding=[\"\'](.*)[\"\']\?>/i";
					
			preg_match($regExp,trim($sFeedContent),$matches);

			if ($matches[1])
			{
			  $rss =new XML_RSS($this->sCacheDirectory.$this->sRssCacheFile, $matches[1]);
			} else {
			  $rss =new XML_RSS($this->sCacheDirectory.$this->sRssCacheFile);
			}

			$rss->parse();		
			
			$i = 0;
			foreach ($rss->getItems() as $item)
			{
				$sText = htmlentities($item['description'],ENT_QUOTES);
				if (strlen($sText) > 150) {
				    $sText = capiStrTrimAfterWord($sText, 150).'...';
				}
				
				$oTpl->set("d", "NEWS_DATE", $item['pubdate']);
				$oTpl->set("d", "NEWS_TITLE", $item['title']);
				$oTpl->set("d", "NEWS_TEXT", $sText);
				$oTpl->set("d", "NEWS_URL", $item['link']);
				$oTpl->set("d", "LABEL_MORE", i18n('read more'));
				$oTpl->next();
				$i++;
				
				if ($i == 3) {
					break;
				}
			}
		}
		return $oTpl;
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
			$sMessage = i18n('Update notification is disabled! For actual update information, please acrivate.');
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->sErrorOutput != "") {
			$sOutput = $this->sErrorOutput;
		} else if (!$this->sVendorVersion) {
			$sMessage = i18n('You have an unknown or unsupported version of Contenido!');
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->sVendorVersion == "deprecated") {
			$sMessage = sprintf(i18n('Your version of Contenido is deprecated and not longer supported for any updates. Please update to a higher version! <br /> <a href="%s" class="blue" target="_blank">Download now!</a>'), 'http://www.contenido.org');
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->checkPatchLevel() == "-1") {
			$sVendorDownloadURL = $this->getDownloadURL();
			$sMessage = sprintf(i18n('A new version of Contenido is available! <br /> <a href="%s" class="blue" target="_blank">%s download now!</a>'), $sVendorDownloadURL, $this->sVendorVersion);
			$sOutput = $this->renderOutput($sMessage);
		} else if ($this->checkPatchLevel() == "1") {
			$sMessage = sprintf(i18n('It seems to be that your version string was manipulated. Contenido %s does not exist!'), $this->aCfg['version']);
			$sOutput = $this->renderOutput($sMessage);
		} else {
			$sMessage = i18n('Your version of Contenido is up to date!');
			$sOutput = $this->renderOutput($sMessage);
		}

       	return $sOutput;
    	}
}
?>