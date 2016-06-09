<?php
/**
 * This file contains the update notifier class.
 *
 * @package Core
 * @subpackage Security
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains function for the update notifier.
 *
 * @package Core
 * @subpackage Security
 */
class cUpdateNotifier {

    /**
     * Minor release for the simplexml xpath() method
     *
     * @var string
     */
    protected $sMinorRelease = "";

    /**
     * Host for vendor XML
     *
     * @var string
     */
    protected $sVendorHost = "www.contenido.org";

    /**
     * Path to files
     *
     * @var string
     */
    protected $sVendorHostPath = "con_version_check_feeds/";

    /**
     * Vendor XML file
     *
     * @var string
     */
    protected $sVendorXMLFile = "vendor.xml";

    /**
     * German Vendor RSS file
     *
     * @var string
     */
    protected $sVendorRssDeFile = "rss_de.xml";

    /**
     * English Vendor RSS file
     *
     * @var string
     */
    protected $sVendorRssEnFile = "rss_en.xml";

    /**
     * Language specific RSS file
     *
     * @var string
     */
    protected $sRSSFile = "";

    /**
     * Timestamp cache file
     *
     * @var string
     */
    protected $sTimestampCacheFile = "update.txt";

    /**
     * Content of the XML file
     *
     * @var string
     */
    protected $sXMLContent = "";

    /**
     * Content of the language specific RSS file
     *
     * @var string
     */
    protected $sRSSContent = "";

    /**
     * Current available vendor version
     *
     * @var string
     */
    protected $sVendorVersion = "";

    /**
     * Download URL
     *
     * @var string
     */
    protected $sVendorURL = "http://www.contenido.org/de/redir";

    /**
     * Current backend language
     *
     * @var string
     */
    protected $sBackendLanguage = "";

    /**
     * Contains the cache path.
     *
     * @var string
     */
    protected $sCacheDirectory = "";

    /**
     * SimpleXML object
     *
     * @var object
     */
    protected $oXML = NULL;

    /**
     * Properties object
     *
     * @var object
     */
    protected $oProperties = NULL;

    /**
     * Session object
     *
     * @var object
     */
    protected $oSession = NULL;

    /**
     * Timeout for the fsockopen connection
     *
     * @var int
     */
    protected $iConnectTimeout = 3;

    /**
     * Cache duration in minutes
     *
     * @var int
     */
    protected $iCacheDuration = 60;

    /**
     * Check for system setting
     *
     * @var bool
     */
    protected $bEnableCheck = false;

    /**
     * Check for system setting Rss
     *
     * @var bool
     */
    protected $bEnableCheckRss = false;

    /**
     * If true CONTENIDO displays a special error message due to missing write
     * permissions.
     *
     * @var bool
     */
    protected $bNoWritePermissions = false;

    /**
     * Display update notification based on user rights (sysadmin only)
     *
     * @var bool
     */
    protected $bEnableView = false;

    /**
     * Update necessity
     *
     * @var bool
     */
    protected $bUpdateNecessity = false;

    /**
     * Vendor host reachability.
     *
     * @var bool
     */
    private $bVendorHostReachable = true;

    /**
     * Property configuration array
     *
     * @var array
     */
    protected $aPropConf = array(
        "itemType" => "update",
        "itemID" => 1,
        "type" => "file_check",
        "name" => "xml"
    );

    /**
     * System property configuration array for update notification
     *
     * @var array
     */
    protected $aSysPropConf = array(
        "type" => "update",
        "name" => "check"
    );

    /**
     * System property configuration array for rss notification
     *
     * @var array
     */
    protected $aSysPropConfRss = array(
        "type" => "update",
        "name" => "news_feed"
    );

    /**
     * System property configuration array for update period
     *
     * @var array
     */
    protected $aSysPropConfPeriod = array(
        "type" => "update",
        "name" => "check_period"
    );

    /**
     * CONTENIDO configuration array
     *
     * @var array
     */
    protected $aCfg = array();

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $aCfg
     * @param cApiUser $oUser
     * @param cPermission $oPerm
     * @param cSession $oSession
     * @param string $sBackendLanguage
     */
    public function __construct($aCfg, $oUser, $oPerm, $oSession, $sBackendLanguage) {
        $this->oProperties = new cApiPropertyCollection();
        $this->oSession = $oSession;
        $this->aCfg = $aCfg;
        $this->sBackendLanguage = $sBackendLanguage;

        if ($oPerm->isSysadmin($oUser) != 1) {
            $this->bEnableView = false;
        } else {
            $this->bEnableView = true;

            $sAction = $_GET['do'];
            if ($sAction != "") {
                $this->updateSystemProperty($sAction);
            }

            $sPropUpdate = getSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name']);
            $sPropRSS = getSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name']);
            $sPeriod = getSystemProperty($this->aSysPropConfPeriod['type'], $this->aSysPropConfPeriod['name']);
            $iPeriod = cSecurity::toInteger($sPeriod);

            if ($sPropUpdate == "true" || $sPropRSS == "true") {

                if ($sPropUpdate == "true") {
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
                if ($this->sCacheDirectory != "") {
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
     */
    protected function setRSSFile() {
        if ($this->sBackendLanguage == "de_DE") {
            $this->sRSSFile = $this->sVendorRssDeFile;
        } else {
            $this->sRSSFile = $this->sVendorRssEnFile;
        }
    }

    /**
     * Updates the system property for activation/deactivation requests
     *
     * @param string $sAction
     */
    protected function updateSystemProperty($sAction) {
        if ($sAction == "activate") {
            setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "true");
        } else if ($sAction == "deactivate") {
            setSystemProperty($this->aSysPropConf['type'], $this->aSysPropConf['name'], "false");
        } else if ($sAction == "activate_rss") {
            setSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name'], "true");
        } else if ($sAction == "deactivate_rss") {
            setSystemProperty($this->aSysPropConfRss['type'], $this->aSysPropConfRss['name'], "false");
        }
    }

    /**
     * Sets the cache path
     */
    protected function setCachePath() {
        $sCachePath = $this->aCfg['path']['contenido_cache'];
        if (!is_dir($sCachePath)) {
            mkdir($sCachePath, 0777);
        }

        if (!is_writable($sCachePath)) {
            // setting special flag for error message
            $this->bNoWritePermissions = true;
        } else {
            $this->sCacheDirectory = $sCachePath;
        }
    }

    /**
     * Checks if the xml files must be loaded from the vendor host or local
     * cache
     */
    protected function checkUpdateNecessity() {
        $bUpdateNecessity = false;

        $aCheckFiles = array(
            $this->sVendorXMLFile,
            $this->sVendorRssDeFile,
            $this->sVendorRssEnFile,
            $this->sTimestampCacheFile
        );
        foreach ($aCheckFiles as $sFilename) {
            if (!cFileHandler::exists($this->sCacheDirectory . $sFilename)) {
                $bUpdateNecessity = true;
                break;
            }
        }

        if ($bUpdateNecessity == false) {
            $iLastUpdate = (int) cFileHandler::read($this->sCacheDirectory . $this->sTimestampCacheFile);

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
     */
    protected function detectMinorRelease() {
        $sVersion = CON_VERSION;
        $aExplode = explode(".", $sVersion);
        $sMinorRelease = "con" . $aExplode[0] . $aExplode[1];
        $this->sMinorRelease = $sMinorRelease;
    }

    /**
     * Reads the xml files from vendor host or cache and checks for file
     * manipulations
     */
    protected function readVendorContent() {
        $this->sXMLContent = "";

        if ($this->bUpdateNecessity == true) {
            $aXmlContent = $this->getVendorHostFiles();

            if (isset($aXmlContent[$this->sVendorXMLFile]) && isset($aXmlContent[$this->sVendorRssDeFile]) && isset($aXmlContent[$this->sVendorRssEnFile])) {
                $this->handleVendorUpdate($aXmlContent);
            }
        } else {

            $sXMLContent = cFileHandler::read($this->sCacheDirectory . $this->sVendorXMLFile);
            $aRSSContent[$this->sVendorRssDeFile] = cFileHandler::read($this->sCacheDirectory . $this->sVendorRssDeFile);
            $aRSSContent[$this->sVendorRssEnFile] = cFileHandler::read($this->sCacheDirectory . $this->sVendorRssEnFile);

            $sXMLHash = md5($sXMLContent . $aRSSContent[$this->sVendorRssDeFile] . $aRSSContent[$this->sVendorRssEnFile]);
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

        // echo $this->sXMLContent;
        // echo $this->sRSSContent;
        if ($this->sXMLContent != "") {

            $this->oXML = simplexml_load_string($this->sXMLContent);
            if (!is_object($this->oXML)) {
                $sErrorMessage = i18n('Unable to check for new updates!') . " " . i18n('Could not handle server response!');
                $this->sErrorOutput = $this->renderOutput($sErrorMessage);
            } else {
                $oVersion = $this->oXML->xpath("/fourforbusiness/contenido/releases/" . $this->sMinorRelease);
                if (!isset($oVersion[0])) {
                    $sErrorMessage = i18n('Unable to check for new updates!') . " " . i18n('Could not determine vendor version!');
                    $this->sErrorOutput = $this->renderOutput($sErrorMessage);
                } else {
                    $this->sVendorVersion = $oVersion[0];
                }
            }
        }
    }

    /**
     * Handles the update of files coming per vendor host
     *
     * @param array $aXMLContent
     */
    protected function handleVendorUpdate($aXMLContent) {
        $bValidXMLFile = true;
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

        // To prevent simplexml and rss reader parser errors by loading an error
        // page from the vendor host
        // the content will be replaced with the cached file (if existing) or a
        // string
        if ($bValidXMLFile != true) {
            if (cFileHandler::exists($this->sCacheDirectory . $this->sVendorXMLFile)) {
                $sXMLReplace = cFileHandler::read($this->sCacheDirectory . $this->sVendorXMLFile);
            } else {
                $sXMLReplace = "<error>The vendor host file at " . $this->sVendorHost . " is not availiable!</error>";
            }
            $aXMLContent[$this->sVendorXMLFile] = $sXMLReplace;
        }

        if ($bValidDeRSSFile != true) {
            if (cFileHandler::exists($this->sCacheDirectory . $this->sVendorRssDeFile)) {
                $sDeRSSReplace = cFileHandler::read($this->sCacheDirectory . $this->sVendorRssDeFile);
            } else {
                $sDeRSSReplace = "<rss></rss>";
            }
            $aXMLContent[$this->sVendorRssDeFile] = $sDeRSSReplace;
        }

        if ($bValidEnRSSFile != true) {
            if (cFileHandler::exists($this->sCacheDirectory . $this->sVendorRssEnFile)) {
                $sEnRSSReplace = cFileHandler::read($this->sCacheDirectory . $this->sVendorRssEnFile);
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
     *
     * @return array
     */
    protected function getVendorHostFiles() {
        $aXMLContent = array();
        // get update file
        $sXMLUpdate = $this->fetchUrl($this->sVendorHostPath . $this->sVendorXMLFile);

        // get german rss file
        $sDeRSSContent = $this->fetchUrl($this->sVendorHostPath . $this->sVendorRssDeFile);

        // get english rss file
        $sEnRSSContent = $this->fetchUrl($this->sVendorHostPath . $this->sVendorRssEnFile);

        $aXMLContent[$this->sVendorXMLFile] = $sXMLUpdate;
        $aXMLContent[$this->sVendorRssDeFile] = $sDeRSSContent;
        $aXMLContent[$this->sVendorRssEnFile] = $sEnRSSContent;

        return $aXMLContent;
    }

    /**
     * Updates the files in cache
     *
     * @param array $aRSSContent
     */
    protected function updateCacheFiles($aRSSContent) {
        $aWriteCache = array();
        $aWriteCache[$this->sVendorXMLFile] = $this->sXMLContent;
        $aWriteCache[$this->sVendorRssDeFile] = $aRSSContent[$this->sVendorRssDeFile];
        $aWriteCache[$this->sVendorRssEnFile] = $aRSSContent[$this->sVendorRssEnFile];
        $aWriteCache[$this->sTimestampCacheFile] = time();

        if (is_writable($this->sCacheDirectory)) {
            foreach ($aWriteCache as $sFile => $sContent) {
                $sCacheFile = $this->sCacheDirectory . $sFile;
                cFileHandler::write($sCacheFile, $sContent, false);
            }
        }
    }

    /**
     * Gets the xml file hash from the property table
     *
     * @return string
     */
    protected function getHashProperty() {
        $sProperty = $this->oProperties->getValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name']);
        return $sProperty;
    }

    /**
     * Updates the xml file hash in the property table
     *
     * @param array $aRSSContent
     */
    protected function updateHashProperty($aXMLContent) {
        $sXML = $aXMLContent[$this->sVendorXMLFile];
        $sDeRSS = $aXMLContent[$this->sVendorRssDeFile];
        $sEnRSS = $aXMLContent[$this->sVendorRssEnFile];

        $sPropValue = md5($sXML . $sDeRSS . $sEnRSS);
        $this->oProperties->setValue($this->aPropConf['itemType'], $this->aPropConf['itemID'], $this->aPropConf['type'], $this->aPropConf['name'], $sPropValue);
    }

    /**
     * Checks the patch level of system and vendor version
     *
     * @return string
     */
    protected function checkPatchLevel() {
        $sVersionCompare = version_compare(CON_VERSION, $this->sVendorVersion);
        return $sVersionCompare;
    }

    /**
     * Generates the download URL
     *
     * @return string
     */
    protected function getDownloadURL() {
        $sVendorURLVersion = str_replace(".", "_", $this->sVendorVersion);
        $sVendorURL = $this->sVendorURL . "/Contenido_" . $sVendorURLVersion;
        return $sVendorURL;
    }

    /**
     * Generates the output for the backend
     *
     * @param string $sMessage
     * @return string
     */
    protected function renderOutput($sMessage) {
        $oTpl = new cTemplate();
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
            $oTpl->set('s', 'NEWS_NOCONTENT', i18n('RSS notification is disabled'));
            $oTpl->set("s", "DISPLAY_DISABLED", 'block');
        }

        return $oTpl->generate('templates/standard/' . $this->aCfg['templates']['welcome_update'], 1);
    }

    /**
     * Generates the output for the rss informations
     *
     * @param cTemplate $oTpl
     * @return cTemplate
     *         CONTENIDO template object
     */
    protected function renderRss($oTpl) {
        if (!is_object($oTpl)) {
            $oTpl = new cTemplate();
        }

        if ($this->sRSSContent != '<rss></rss>') {
            $doc = new cXmlReader();
            $doc->load($this->sCacheDirectory . $this->sRSSFile);

            $maxFeedItems = 3;

            for ($iCnt = 0; $iCnt < $maxFeedItems; $iCnt++) {
                $title = $doc->getXpathValue('*/channel/item/title', $iCnt);
                $link = $doc->getXpathValue('*/channel/item/link', $iCnt);
                $description = $doc->getXpathValue('*/channel/item/description', $iCnt);
                $date = $doc->getXpathValue('*/channel/item/pubDate', $iCnt);

                // hotfix do not call conHtmlentities because of different
                // umlaut handling on PHP 5.3 and PHP 5.4
                // perhaps it is a bug in conHtmlentities.
                $title = utf8_encode($title);
                $sText = utf8_encode($description);

                if (strlen($sText) > 150) {
                    $sText = cString::trimAfterWord($sText, 150) . '...';
                }

                $date = date(getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s"), strtotime($date));

                // highlight newest rss news
                if ($iCnt == 0) {
                    $oTpl->set("d", "NEWS_NEWEST_CSS", "newest_news");
                } else {
                    $oTpl->set("d", "NEWS_NEWEST_CSS", "");
                }

                $oTpl->set("d", "NEWS_DATE", $date);
                $oTpl->set("d", "NEWS_TITLE", $title);
                $oTpl->set("d", "NEWS_TEXT", $sText);
                $oTpl->set("d", "NEWS_URL", $link);
                $oTpl->set("d", "LABEL_MORE", i18n('read more'));
                $oTpl->next();
            }

            if ($iCnt == 0) {
                $oTpl->set("s", "NEWS_NOCONTENT", i18n("No RSS content available"));
                $oTpl->set("s", "DISPLAY_DISABLED", 'block');
            } else {
                $oTpl->set("s", "NEWS_NOCONTENT", "");
                $oTpl->set("s", "DISPLAY_DISABLED", 'none');
            }
        } else if ($this->bNoWritePermissions == true) {
            $oTpl->set("s", "NEWS_NOCONTENT", i18n('Your webserver does not have write permissions for the directory /contenido/data/cache/!'));
        } else {
            $oTpl->set("s", "NEWS_NOCONTENT", i18n("No RSS content available"));
        }

        return $oTpl;
    }

    /**
     * fetches given url for vendorfiles
     *
     * @todo add a retry counter and a deathpoint with warning in errorlog
     * @param string $sUrl
     * @return string|bool
     */
    private function fetchUrl($sUrl) {
        if ($this->bVendorHostReachable != true) {
            return false;
        }

        $handler = cHttpRequest::getHttpRequest($this->sVendorHost . '/' . $sUrl);
        $output = $handler->getRequest();

        if (!$output) {
            $sErrorMessage = i18n('Unable to check for new updates!') . " " . i18n('Connection to contenido.org failed!');
            $this->sErrorOutput = $this->renderOutput($sErrorMessage);
            $this->bVendorHostReachable = false;
            return false;
        }

        return ($output != "") ? $output : false;
    }

    /**
     * Displays the rendered output
     *
     * @return string
     */
    public function displayOutput() {
        if (!$this->bEnableView) {
            $sOutput = "";
        } else if ($this->bNoWritePermissions == true) {
            $sMessage = i18n('Your webserver does not have write permissions for the directory /contenido/data/cache/!');
            $sOutput = $this->renderOutput($sMessage);
        } else if (!$this->bEnableCheck) {
            $sMessage = i18n('Update notification is disabled! For actual update information, please activate.');
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->sErrorOutput != "") {
            $sOutput = $this->sErrorOutput;
        } else if ($this->sVendorVersion == '') {
            $sMessage = i18n('You have an unknown or unsupported version of CONTENIDO!');
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->sVendorVersion == "deprecated") {
            $sMessage = sprintf(i18n("Your version of CONTENIDO is deprecated and not longer supported for any updates. Please update to a higher version! <br /> <a href='%s' class='blue' target='_blank'>Download now!</a>"), 'http://www.contenido.org');
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->checkPatchLevel() == "-1") {
            $sVendorDownloadURL = $this->getDownloadURL();
            $sMessage = sprintf(i18n("A new version of CONTENIDO is available! <br /> <a href='%s' class='blue' target='_blank'>Download %s now!</a>"), $sVendorDownloadURL, $this->sVendorVersion);
            $sOutput = $this->renderOutput($sMessage);
        } else if ($this->checkPatchLevel() == "1") {
            $sMessage = sprintf(i18n('It seems to be that your version string was manipulated. CONTENIDO %s does not exist!'), CON_VERSION);
            $sOutput = $this->renderOutput($sMessage);
        } else {
            $sMessage = i18n('Your version of CONTENIDO is up to date!');
            $sOutput = $this->renderOutput($sMessage);
        }

        return $sOutput;
    }
}
