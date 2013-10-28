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
 * @version    1.0.2
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
 *   modified 2009-10-01, Dominik Ziegler, added some checks for directory write permissions
 *   modified 2010-10-01, Dominik Ziegler, added resource check of fsockopen stream
 *   modified 2011-03-18, Murat Purc, fixed thrown errors while invalid socket handles, see [CON-366]
 *
 *   $Id: class.update.notifier.php 1331 2011-03-18 22:14:29Z xmurrix $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class Contenido_UpdateNotifier
{
    /**
     * Minor release for the simplexml xpath() method
     * @access protected
     * @var string
     */
    protected $sMinorRelease = "";

    /**
     * Host for vendor XML
     * @access protected
     * @var string
     */
    protected $sVendorHost = "www.contenido.org";

    /**
     * Path to files
     * @access protected
     * @var string
     */
    protected $sVendorHostPath = "con_version_check_feeds/";

    /**
     * Vendor XML file
     * @access protected
     * @var string
     */
    protected $sVendorXMLFile = "vendor.xml";

    /**
     * German Vendor RSS file
     * @access protected
     * @var string
     */
    protected $sVendorRssDeFile = "rss_de.xml";

    /**
     * English Vendor RSS file
     * @access protected
     * @var string
     */
    protected $sVendorRssEnFile = "rss_en.xml";

    /**
     * Language specific RSS file
     * @access protected
     * @var string
     */
    protected $sRSSFile = "";

    /**
     * Timestamp cache file
     * @access protected
     * @var string
     */
    protected $sTimestampCacheFile = "update.txt";

    /**
     * Content of the XML file
     * @access protected
     * @var string
     */
    protected $sXMLContent = "";

        /**
     * Content of the language specific RSS file
     * @access protected
     * @var string
     */
    protected $sRSSContent = "";

    /**
     * Current available vendor version
     * @access protected
     * @var string
     */
    protected $sVendorVersion = "";

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
    protected $sBackendLanguage = "";

    /**
     * Contains the cache path.
     * @access protected
     * @var string
     */
    protected $sCacheDirectory = "";

    /**
     * SimpleXML object
     * @access protected
     * @var object
     */
    protected $oXML = null;

    /**
     * Properties object
     * @access protected
     * @var object
     */
    protected $oProperties = null;

    /**
     * Session object
     * @access protected
     * @var object
     */
    protected $oSession = null;

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
     * If true contenido displays a special error message due to missing write permissions.
     * @access protected
     * @var boolean
     */
    protected $bNoWritePermissions = false;

    /**
     * Display update notification based on user rights (sysadmin only)
     * @access protected
     * @var boolean
     */
    protected $bEnableView = false;

    /**
     * Update necessity
     * @access protected
     * @var boolean
     */
    protected $bUpdateNecessity = false;

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
     * System property configuration array for update period
     * @access protected
     * @var array
     */
    protected $aSysPropConfPeriod = array("type" => "update", "name" => "check_period");

    /**
     * Contenido configuration array
     * @access protected
     * @var array
     */
    protected $aCfg = array();


    /**
     * Constructor of Contenido_UpdateNotifier
     * @access public
     * @param  string $sConVersion
     * @return void
     */
    public function __construct($aCfg, $oUser, $oPerm, $oSession, $sBackendLanguage)
    {
        $this->oProperties      = new PropertyCollection;
        $this->oSession         = $oSession;
        $this->aCfg             = $aCfg;
        $this->sBackendLanguage = $sBackendLanguage;

        if ($oPerm->isSysadmin($oUser) != 1) {
            $this->bEnableView = false;
        } else {
            $this->bEnableView = true;

            $sAction = $_GET['do'];
            if($sAction != "") {
                $this->updateSystemProperty($sAction);
            }

            $sPropUpdate = getSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name']);
            $sPropRSS    = getSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name']);
            $sPeriod     = getSystemProperty($this->aSysPropConfPeriod['type'], $this->aSysPropConfPeriod['name']);
            $iPeriod     = Contenido_Security::toInteger($sPeriod);

            if ($sPropUpdate == "true" || $sPropRSS == "true") {

                if($sPropUpdate == "true") {
                    $this->bEnableCheck = true;
                }

                if ($sPropRSS == "true") {
                   $this->bEnableCheckRss = true;
                }

                // default cache duration of 60 minutes
                if ($iPeriod >= 60) {
                    $this->iCacheDuration = $iPeriod;
                } else {
                    $this->iCacheDuration = 60;
                }

                $this->setCachePath();
                if ( $this->sCacheDirectory != "" ) {
                    $this->setRSSFile();
                    $this->detectMinorRelease();
                    $this->checkUpdateNecessity();
                    $this->readVendorContent();
                }
            }
        }
    }

    /**
     * Sets the actual RSS file for the reader
     * @access protected
     * @return void
     */
    protected function setRSSFile()
    {
        if ($this->sBackendLanguage == "de_DE") {
            $this->sRSSFile = $this->sVendorRssDeFile;
        } else {
            $this->sRSSFile = $this->sVendorRssEnFile;
        }
    }

    /**
     * Updates the system property for activation/deactivation requests
     * @access protected
     * @param $sAction string
     * @return void
     */
    protected function updateSystemProperty($sAction)
    {
        if ($sAction == "activate") {
            setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "true");
        } else if ($sAction == "deactivate") {
            setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "false");
        } else if ($sAction == "activate_rss"){
            setSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name'], "true");
        } else if ($sAction == "deactivate_rss"){
            setSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name'], "false");
        }
    }

    /**
     * Sets the cache path
     * @access protected
     * @return void
     */
    protected function setCachePath()
    {
        $sConPath = $this->aCfg['path']['contenido'];
        $sCachePath = $sConPath."cache".DIRECTORY_SEPARATOR;
        if (!is_dir($sCachePath)) {
            mkdir($sCachePath, 0777);
        }

        if (!is_writable($sCachePath)) {
            // setting special flag for error message
            $this->bNoWritePermissions     = true;
        } else {
            $this->sCacheDirectory = $sCachePath;
        }
    }

    /**
     * Checks if the xml files must be loaded from the vendor host or local cache
     * @access protected
     * @return void
     */
    protected function checkUpdateNecessity()
    {
        $bUpdateNecessity = false;

        $aCheckFiles = array($this->sVendorXMLFile, $this->sVendorRssDeFile, $this->sVendorRssEnFile, $this->sTimestampCacheFile);
        foreach ($aCheckFiles as $sFilename) {
            if (!file_exists($this->sCacheDirectory.$sFilename)) {
                $bUpdateNecessity = true;
                break;
            }
        }

        if ($bUpdateNecessity == false) {
            $iLastUpdate = file_get_contents($this->sCacheDirectory.$this->sTimestampCacheFile);

            $iCheckTimestamp = $iLastUpdate + ($this->iCacheDuration * 60);
            $iCurrentTime = time();

            if ($iCheckTimestamp > $iCurrentTime) {
                $bUpdateNecessity = false;
            } else {
                $bUpdateNecessity = true;
            }
        }

        $this->bUpdateNecessity = $bUpdateNecessity;
    }

    /**
     * Detects and converts the minor release of the system version
     * @access protected
     * @return void
     */
    protected function detectMinorRelease()
    {
        $sVersion             = $this->aCfg['version'];
        $aExplode             = explode(".", $sVersion);
        $sMinorRelease        = "con".$aExplode[0].$aExplode[1];
        $this->sMinorRelease  = $sMinorRelease;
    }

    /**
     * Reads the xml files from vendor host or cache and checks for file manipulations
     * @access protected
     * @return void
     */
    protected function readVendorContent()
    {
        $this->sXMLContent = "";
        if ($this->bUpdateNecessity == true) {
            $aXmlContent = $this->getVendorHostFiles();
            if (isset($aXmlContent[$this->sVendorXMLFile]) && isset($aXmlContent[$this->sVendorRssDeFile]) && isset($aXmlContent[$this->sVendorRssEnFile])) {
                $this->handleVendorUpdate($aXmlContent);
            }
        } else {
            $sXMLContent                          = file_get_contents($this->sCacheDirectory.$this->sVendorXMLFile);
            $aRSSContent[$this->sVendorRssDeFile] = file_get_contents($this->sCacheDirectory.$this->sVendorRssDeFile);
            $aRSSContent[$this->sVendorRssEnFile] = file_get_contents($this->sCacheDirectory.$this->sVendorRssEnFile);

            $sXMLHash = md5($sXMLContent.$aRSSContent[$this->sVendorRssDeFile].$aRSSContent[$this->sVendorRssEnFile]);
            $sPropertyHash = $this->getHashProperty();
            if ($sXMLHash == $sPropertyHash) {
                $this->sXMLContent = $sXMLContent;
                $this->sRSSContent = $aRSSContent[$this->sRSSFile];
            } else {
                $aXmlContent = $this->getVendorHostFiles();
                if (isset($aXmlContent[$this->sVendorXMLFile]) && isset($aXmlContent[$this->sVendorRssDeFile]) && isset($aXmlContent[$this->sVendorRssEnFile])) {
                    $this->handleVendorUpdate($aXmlContent);
                }
            }
        }

        if ($this->sXMLContent != "") {
            $this->oXML = simplexml_load_string($this->sXMLContent);
			if (!is_object($this->oXML)) {
                   $sErrorMessage = i18n("Unable to check for new updates!")." ".i18n("Could not handle server response!");
                $this->sErrorOutput = $this->renderOutput($sErrorMessage);
            } else {
                $oVersion = $this->oXML->xpath("/fourforbusiness/contenido/releases/".$this->sMinorRelease);
                if (!isset($oVersion[0])) {
                    $sErrorMessage = i18n("Unable to check for new updates!")." ".i18n("Could not determine vendor version!");
                    $this->sErrorOutput = $this->renderOutput($sErrorMessage);
                } else {
                    $this->sVendorVersion = $oVersion[0];
                }
            }
        }
    }

    /**
     * Handles the update of files coming per vendor host
     * @access protected
     * @return void
     */
    protected function handleVendorUpdate($aXMLContent)
    {
        $bValidXMLFile   = true;
        $bValidDeRSSFile = true;
        $bValidEnRSSFile = true;

        $sCheckXML = stristr($aXMLContent[$this->sVendorXMLFile], "<fourforbusiness>");
        if ($sCheckXML == false) {
            $bValidXMLFile = false;
        }

        $sCheckDeRSS = stristr($aXMLContent[$this->sVendorRssDeFile], "<channel>");
        if ($sCheckDeRSS == false) {
            $bValidDeRSSFile = false;
        }

        $sCheckEnRSS = stristr($aXMLContent[$this->sVendorRssEnFile], "<channel>");
        if ($sCheckEnRSS == false) {
            $bValidEnRSSFile = false;
        }

        // To prevent simplexml and rss reader parser errors by loading an error page from the vendor host
        // the content will be replaced with the cached file (if existing) or a string
        if ($bValidXMLFile != true) {
            if (file_exists($this->sCacheDirectory.$this->sVendorXMLFile)) {
                $sXMLReplace = file_get_contents($this->sCacheDirectory.$this->sVendorXMLFile);
            } else {
                $sXMLReplace = "<error>The vendor host file at ".$this->sVendorHost." is not availiable!</error>";
            }
            $aXMLContent[$this->sVendorXMLFile] = $sXMLReplace;
        }

        if ($bValidDeRSSFile != true) {
            if (file_exists($this->sCacheDirectory.$this->sVendorRssDeFile)) {
                $sDeRSSReplace = file_get_contents($this->sCacheDirectory.$this->sVendorRssDeFile);
            } else {
                $sDeRSSReplace = "<rss></rss>";
            }
            $aXMLContent[$this->sVendorRssDeFile] = $sDeRSSReplace;
        }

        if ($bValidEnRSSFile != true) {
            if (file_exists($this->sCacheDirectory.$this->sVendorRssEnFile)) {
                $sEnRSSReplace = file_get_contents($this->sCacheDirectory.$this->sVendorRssEnFile);
            } else {
                $sEnRSSReplace = "<rss></rss>";
            }
            $aXMLContent[$this->sVendorRssEnFile] = $sEnRSSReplace;
        }

        $this->sXMLContent = $aXMLContent[$this->sVendorXMLFile];
        $this->sRSSContent = $aXMLContent[$this->sRSSFile];
        $this->updateCacheFiles($aXMLContent);
        $this->updateHashProperty($aXMLContent);
    }

    /**
     * Connects with vendor host and gets the xml files
     * @access protected
     * @return array
     */
    protected function getVendorHostFiles()
    {
        $aXMLContent = array();

        $hSocket = @fsockopen($this->sVendorHost, 80, $errno, $errstr, $this->iConnectTimeout);
        if (!is_resource($hSocket)) {
            $sErrorMessage = i18n("Unable to check for new updates!")." ".i18n("Connection to contenido.org failed!");
            $this->sErrorOutput = $this->renderOutput($sErrorMessage);
        } else {
            // get update file
            $sXMLUpdate = '';
            fputs($hSocket, "GET /".$this->sVendorHostPath.$this->sVendorXMLFile." HTTP/1.0\r\nHost: " . $this->sVendorHost . "\r\n\r\n");
            while(!feof($hSocket)) {
                $sXMLUpdate .= fgets($hSocket, 128);
            }
            $sSeparator = strpos($sXMLUpdate, "\r\n\r\n");
            $sXMLUpdate = substr($sXMLUpdate, $sSeparator + 4);
            fclose($hSocket);

            // get german rss file
            $sDeRSSContent = '';
            $hSocket = @fsockopen($this->sVendorHost, 80, $errno, $errstr, $this->iConnectTimeout);
            if (is_resource($hSocket)) {
                fputs($hSocket, "GET /".$this->sVendorHostPath.$this->sVendorRssDeFile." HTTP/1.0\r\nHost: " . $this->sVendorHost . "\r\n\r\n");
                while(!feof($hSocket)) {
                    $sDeRSSContent .= fgets($hSocket, 128);
                }
                $sSeparator     = strpos($sDeRSSContent, "\r\n\r\n");
                $sDeRSSContent  = substr($sDeRSSContent, $sSeparator + 4);
                fclose($hSocket);
            }

            // get english rss file
            $sEnRSSContent = '';
            $hSocket = @fsockopen($this->sVendorHost, 80, $errno, $errstr, $this->iConnectTimeout);
            if (is_resource($hSocket)) {
                fputs($hSocket, "GET /".$this->sVendorHostPath.$this->sVendorRssEnFile." HTTP/1.0\r\nHost: " . $this->sVendorHost . "\r\n\r\n");
                while(!feof($hSocket)) {
                    $sEnRSSContent .= fgets($hSocket, 128);
                }
                $sSeparator     = strpos($sEnRSSContent, "\r\n\r\n");
                $sEnRSSContent  = substr($sEnRSSContent, $sSeparator + 4);
                fclose($hSocket);
            }

            $aXMLContent[$this->sVendorXMLFile]   = $sXMLUpdate;
            $aXMLContent[$this->sVendorRssDeFile] = $sDeRSSContent;
            $aXMLContent[$this->sVendorRssEnFile] = $sEnRSSContent;
        }

        return $aXMLContent;
    }

    /**
     * Updates the files in cache
     * @access protected
     * @param $aRSSContent array
     * @return void
     */
    protected function updateCacheFiles($aRSSContent)
    {
        $aWriteCache = array();
        $aWriteCache[$this->sVendorXMLFile]      = $this->sXMLContent;
        $aWriteCache[$this->sVendorRssDeFile]    = $aRSSContent[$this->sVendorRssDeFile];
        $aWriteCache[$this->sVendorRssEnFile]    = $aRSSContent[$this->sVendorRssEnFile];
        $aWriteCache[$this->sTimestampCacheFile] = time();

        if (is_writable($this->sCacheDirectory)) {
            foreach ($aWriteCache as $sFile=>$sContent) {
                $sCacheFile = $this->sCacheDirectory.$sFile;
                $oFile = fopen($sCacheFile, "w+");
                ftruncate($oFile, 0);
                fwrite($oFile, $sContent);
                fclose($oFile);
            }
        }
    }

    /**
     * Gets the xml file hash from the property table
     * @access protected
     * @return string
     */
    protected function getHashProperty()
    {
        $sProperty = $this->oProperties->getValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name']);
        return $sProperty;
    }

    /**
     * Updates the xml file hash in the property table
     * @access protected
     * @param $aRSSContent array
     * @return void
     */
    protected function updateHashProperty($aXMLContent)
    {
        $sXML    = $aXMLContent[$this->sVendorXMLFile];
        $sDeRSS  = $aXMLContent[$this->sVendorRssDeFile];
        $sEnRSS  = $aXMLContent[$this->sVendorRssEnFile];

        $sPropValue = md5($sXML.$sDeRSS.$sEnRSS);
        $this->oProperties->setValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name'], $sPropValue);
    }

    /**
     * Checks the patch level of system and vendor version
     * @access protected
     * @return string
     */
    protected function checkPatchLevel()
    {
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
     * @param $sMessage string
     * @return string
     */
    protected function renderOutput($sMessage)
    {
        $oTpl = new Template();
        $oTpl->set('s', 'UPDATE_MESSAGE', $sMessage);

        if ($this->bEnableCheck == true) {
            $oTpl->set('s', 'UPDATE_ACTIVATION', i18n("Disable update notification"));
            $oTpl->set('s', 'IMG_BUT_UPDATE', 'but_cancel.gif');
            $oTpl->set('s', 'LABEL_BUT_UPDATE', i18n("Disable notification"));
            $oTpl->set('s', 'URL_UPDATE', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=deactivate'));
        } else {
            $oTpl->set('s', 'UPDATE_ACTIVATION', i18n("Enable update notification (recommended)"));
            $oTpl->set('s', 'IMG_BUT_UPDATE', 'but_ok.gif');
            $oTpl->set('s', 'LABEL_BUT_UPDATE', i18n("Enable notification"));
            $oTpl->set('s', 'URL_UPDATE', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=activate'));
        }

        if ($this->bEnableCheckRss == true) {
            $oTpl->set('s', 'RSS_ACTIVATION', i18n("Disable RSS notification"));
            $oTpl->set('s', 'IMG_BUT_RSS', 'but_cancel.gif');
            $oTpl->set('s', 'LABEL_BUT_RSS', i18n("Disable notification"));
            $oTpl->set('s', 'URL_RSS', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=deactivate_rss'));

            $oTpl = $this->renderRss($oTpl);
        } else {
            $oTpl->set('s', 'RSS_ACTIVATION', i18n("Enable RSS notification (recommended)"));
            $oTpl->set('s', 'IMG_BUT_RSS', 'but_ok.gif');
            $oTpl->set('s', 'LABEL_BUT_RSS', i18n("Enable notification"));
            $oTpl->set('s', 'URL_RSS', $this->oSession->url('main.php?frame=4&amp;area=mycontenido&amp;do=activate_rss'));
            $oTpl->set('s', 'NEWS_NOCONTENT', i18n("RSS notification is disabled"));
            $oTpl->set("s", "DISPLAY_DISABLED", 'block');
        }

        return $oTpl->generate('templates/standard/'.$this->aCfg['templates']['welcome_update'], 1);
    }

    /**
     * Generates the output for the rss informations
     * @access protected
     * @param $oTpl
     * @return contenido template object
     */
    protected function renderRss($oTpl)
    {
        if (!is_object($oTpl)) {
            $oTpl = new Template();
        }

        if ($this->sRSSContent != '') {
            $sFeedContent = substr($this->sRSSContent, 0, 1024);
            $sFeedContent = trim($sFeedContent);

            $aMatches = array();

            $sRegExp = "/<\?xml.*encoding=[\"\'](.*)[\"\']\?>/i";

            preg_match($sRegExp, $sFeedContent, $aMatches);

            if ($aMatches[1]) {
              $oRss = new XML_RSS($this->sCacheDirectory.$this->sRSSFile, $aMatches[1]);
            } else {
              $oRss = new XML_RSS($this->sCacheDirectory.$this->sRSSFile);
            }

            $oRss->parse();

            $iCnt = 0;
            foreach ($oRss->getItems() as $aItem) {
                $sText = conHtmlentities($aItem['description'],ENT_QUOTES);
                if (strlen($sText) > 150) {
                    $sText = capiStrTrimAfterWord($sText, 150).'...';
                }

                $oTpl->set("d", "NEWS_DATE", $aItem['pubdate']);
                $oTpl->set("d", "NEWS_TITLE", $aItem['title']);
                $oTpl->set("d", "NEWS_TEXT", $sText);
                $oTpl->set("d", "NEWS_URL", $aItem['link']);
                $oTpl->set("d", "LABEL_MORE", i18n("read more"));
                $oTpl->next();
                $iCnt++;

                if ($iCnt == 3) {
                    break;
                }
            }

            if ($iCnt == 0) {
                $oTpl->set("s", "NEWS_NOCONTENT", i18n("No RSS content available"));
                $oTpl->set("s", "DISPLAY_DISABLED", 'block');
            } else {
                $oTpl->set("s", "NEWS_NOCONTENT", "");
                $oTpl->set("s", "DISPLAY_DISABLED", 'none');
            }
        } else if ( $this->bNoWritePermissions == true ) {
            $oTpl->set("s", "NEWS_NOCONTENT", i18n("Your webserver does not have write permissions for the directory /contenido/cache/!"));
        } else {
            $oTpl->set("s", "NEWS_NOCONTENT", i18n("No RSS content available"));
        }

        return $oTpl;
    }

    /**
     * Displays the rendered output
     * @access public
     * @return string
     */
    public function displayOutput()
    {
        if (!$this->bEnableView) {
            $sOutput = "";
        } else if ($this->bNoWritePermissions == true ) {
            $sMessage = i18n("Your webserver does not have write permissions for the directory /contenido/cache/!");
            $sOutput = $this->renderOutput($sMessage);
        } else if (!$this->bEnableCheck) {
            $sMessage = i18n("Update notification is disabled! For actual update information, please activate.");
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->sErrorOutput != "") {
            $sOutput = $this->sErrorOutput;
		} else if ($this->sVendorVersion == '') {
            $sMessage = i18n("You have an unknown or unsupported version of Contenido!");
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->sVendorVersion == "deprecated") {
            $sMessage = sprintf(i18n("Your version of Contenido is deprecated and not longer supported for any updates. Please update to a higher version! <br /> <a href='%s' class='blue' target='_blank'>Download now!</a>"), 'http://www.contenido.org');
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->checkPatchLevel() == "-1") {
            $sVendorDownloadURL = $this->getDownloadURL();
            $sMessage = sprintf(i18n("A new version of Contenido is available! <br /> <a href='%s' class='blue' target='_blank'>Download %s now!</a>"), $sVendorDownloadURL, $this->sVendorVersion);
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->checkPatchLevel() == "1") {
            $sMessage = sprintf(i18n("It seems to be that your version string was manipulated. Contenido %s does not exist!"), $this->aCfg['version']);
            $sOutput = $this->renderOutput($sMessage);
        } else {
            $sMessage = i18n("Your version of Contenido is up to date!");
            $sOutput = $this->renderOutput($sMessage);
        }

        return $sOutput;
    }
}

?>