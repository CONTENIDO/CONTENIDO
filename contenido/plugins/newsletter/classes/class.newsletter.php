<?php
/**
 * This file contains the Newsletter recipient class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @version SVN Revision $Rev:$
 *
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Newsletter recipient class.
 *
 * @package Plugin
 * @subpackage Newsletter
 */
class NewsletterCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news"], "idnews");
        $this->_setItemClass("Newsletter");
    }

    /**
     * Creates a new newsletter
     * @param $name string specifies the newsletter name
     */
    public function create($sName)
    {
        global $client, $lang, $auth;

        $sName  = $this->escape($sName);
        $client = cSecurity::toInteger($client);
        $lang   = cSecurity::toInteger($lang);

        // Check if the newsletter name already exists
        $this->resetQuery;
        $this->setWhere("idclient", $client);
        $this->setWhere("idlang", $lang);
        $this->setWhere("name", $sName);
        $this->query();

        if ($this->next()) {
            return $this->create($sName . "_" . substr(md5(rand()), 0, 10));
        }

        $oItem = parent::createNewItem();
        $oItem->set("idclient", $client);
        $oItem->set("idlang", $lang);
        $oItem->set("name", $sName);
        $oItem->set("created", date('Y-m-d H:i:s'), false);
        $oItem->set("author", $auth->auth["uid"]);

        $oItem->store();

        return $oItem;
    }

    /**
     * Duplicates the newsletter specified by $itemID
     * @param $itemID integer specifies the newsletter id
     */
    public function duplicate($iItemID)
    {
        global $client, $lang, $auth;

        $client = cSecurity::toInteger($client);
        $lang   = cSecurity::toInteger($lang);

        cInclude("includes", "functions.con.php");

        $oBaseItem = new Newsletter();
        $oBaseItem->loadByPrimaryKey($iItemID);

        $oItem = parent::createNewItem();
        $oItem->set("name", $oBaseItem->get("name") . "_" . substr(md5(rand()), 0, 10));

        $iIDArt = 0;
        if ($oBaseItem->get("type") == "html" && $oBaseItem->get("idart") > 0 && $oBaseItem->get("template_idart") > 0) {
            $oClientLang = new cApiClientLanguage(false, $client, $lang);

            if ($oClientLang->getProperty("newsletter", "html_newsletter") == "true") {
                $iIDArt = conCopyArticle($oBaseItem->get("idart"),
                    $oClientLang->getProperty("newsletter", "html_newsletter_idcat"),
                    sprintf(i18n("Newsletter: %s"), $oItem->get("name"))
                );
                conMakeOnline($iIDArt, $lang); // Article has to be online for sending...
            }
            unset($oClientLang);
        }
        $oItem->set("idart", $iIDArt);
        $oItem->set("template_idart", $oBaseItem->get("template_idart"));
        $oItem->set("idclient", $client);
        $oItem->set("idlang", $lang);
        $oItem->set("welcome", 0);
        $oItem->set("type", $oBaseItem->get("type"));
        $oItem->set("subject", $oBaseItem->get("subject"));
        $oItem->set("message", $oBaseItem->get("message"));
        $oItem->set("newsfrom", $oBaseItem->get("newsfrom"));
        $oItem->set("newsfromname", $oBaseItem->get("newsfromname"));
        $oItem->set("newsdate", date("Y-m-d H:i:s"), false); // But more or less deprecated
        $oItem->set("use_cronjob", $oBaseItem->get("use_cronjob"));
        $oItem->set("send_to", $oBaseItem->get("send_to"));
        $oItem->set("send_ids", $oBaseItem->get("send_ids"));
        $oItem->set("dispatch", $oBaseItem->get("dispatch"));
        $oItem->set("dispatch_count", $oBaseItem->get("dispatch_count"));
        $oItem->set("dispatch_delay", $oBaseItem->get("dispatch_delay"));
        $oItem->set("author", $auth->auth["uid"]);
        $oItem->set("created", date('Y-m-d H:i:s'), false);

        // Copy properties, runtime on-demand allocation of the properties object
        if (!is_object($this->properties)) {
            $this->properties = new cApiPropertyCollection();
        }
        $this->properties->setWhere("idclient", $client);
        $this->properties->setWhere("itemtype", $this->primaryKey);
        $this->properties->setWhere("itemid", $iItemID);
        $this->properties->query();

        while ($oPropertyItem = $this->properties->next()) {
            $oItem->setProperty($oPropertyItem->get("type"), $oPropertyItem->get("name"), $oPropertyItem->get("value"), $client);
        }

        $oItem->store();

        return $oItem;
    }
}

/**
 * Single Newsletter Item
 */
class Newsletter extends Item
{
    /**
     * @var string Error storage
     * @access private
     */
    public $_sError;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news"], "idnews");
        $this->_sError = "";
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Overriden store()-Method to set modified and modifiedby data and
     * to ensure, that there is only one welcome newsletter
     */
    public function store()
    {
        global $client, $lang, $auth;

        $client = cSecurity::toInteger($client);
        $lang     = cSecurity::toInteger($lang);

        $this->set("modified", date('Y-m-d H:i:s'), false);
        $this->set("modifiedby", $auth->auth["uid"]);

        if ($this->get("welcome") == 1) {
            $oItems = new NewsletterCollection();
            $oItems->setWhere("idclient", $client);
            $oItems->setWhere("idlang", $lang);
            $oItems->setWhere("welcome", 1);
            $oItems->setWhere("idnews", $this->get("idnews"), "<>");
            $oItems->query();

            while ($oItem = $oItems->next()) {
                $oItem->set("welcome", 0);
                $oItem->store();
            }
            unset($oItem);
            unset($oItems);
        }

        return parent::store();
    }

    /**
     * Replaces newsletter tag (e.g. MAIL_NAME) with data.
     * If code is just text using str_replace; if it is HTML by using regular expressions
     * @param string    sCode    Code, where the tags will be replaced (by reference)
     * @param bool        bIsHTML    Is code HTML?
     * @param string    sField    Field name, without MAIL_ (e.g. just "name")
     * @param string    sData    Data
     * @access private
     */
    public function _replaceTag(&$sCode, $bIsHTML, $sField, $sData)
    {
        if ($sCode && !$bIsHTML) {
            $sCode = str_replace("MAIL_".strtoupper($sField), $sData, $sCode);
        } else if ($sCode) {
            // Extract certain tag
            $sRegExp   = '/\[mail\s*([^]]+)\s*name=(?:"|&quot;)'.$sField.'(?:"|&quot;)\s*(.*?)\s*\]((?:.|\s)+?)\[\/mail\]/i';
            $aMatch    = array();
            $iMatches  = preg_match($sRegExp, $sCode, $aMatch) ;

            if ($iMatches > 0) {
                // $aMatch contains parameter info from left [1] or right [2] to name="field"
                $sParameter = $aMatch[1] . $aMatch[2];
                $sMessage   = $aMatch[3];
                $sRegExp    = '/\s*(.*?)\s*=\s*(?:"|&quot;)(.*?)(?:"|&quot;)\s*/i';
                $aMatch     = array();

                if (preg_match_all($sRegExp, $sParameter, $aMatch) > 0) {
                    // Store parameter data as assoziative array
                    $aParameter = array_combine($aMatch[1], $aMatch[2]);
                    unset($aMatch); // $aMatch not needed anymore

                    if (!array_key_exists("type", $aParameter)) {
                        $aParameter["type"] = "text";
                    }

                    switch ($aParameter["type"]) {
                        case "link":
                            # TODO: Works everything fine?
                            # The current code makes it possible to do something like
                            # [mail ...]Some text here, then the link: [MAIL_STOP] and more text[/mail]
                            #
                            # If the other lines will be used, you don't need to
                            # set [MAIL_xy] and the message between the [mail]-tags will
                            # be used as link text (instead of using the tag parameter "text")

                            $sText = $aParameter["text"];

                            if ($sText == "") {
                                $sText = $sData;
                            }
                            if ($sMessage == "") {
                                $sMessage = $sData;
                            }

                            // Remove not needed parameters from the parameters list
                            // everything else goes into the link as parameters
                            unset($aParameter["type"]);
                            unset($aParameter["text"]);

                            $sParameter = "";
                            if (count($aParameter) > 0) {
                                foreach ($aParameter as $sKey => $sValue) {
                                    $sParameter .= ' '.$sKey . '="' . $sValue . '"';
                                }
                            }
                            $sMessage    = str_replace("MAIL_".strtoupper($sField), '<a href="'.conHtmlentities($sData).'"'.$sParameter.'>'.$sText.'</a>', $sMessage);
                            #$sMessage    = '<a href="'.conHtmlentities($sData).'"'.$sParameter.'>'.$sMessage.'</a>';
                            break;
                        default:
                            $sMessage    = str_replace("MAIL_".strtoupper($sField), $sData, $sMessage);
                            #$sMessage    = $sData;
                    }

                    $sRegExp = '/\[mail[^]]+name=(?:"|&quot;)'.$sField.'(?:"|&quot;).*?\].*?\[\/mail\]/is';
                    $sCode   = preg_replace($sRegExp, $sMessage, $sCode, -1);
                    // Just to replace "text"-tags in HTML message also, just in case...
                    $sCode   = str_replace("MAIL_".strtoupper($sField), $sData, $sCode);
                }
            }
        }
    }

    /* TODO: HerrB: Remove or insert some functionality */
    protected function _getNewsletterTagData($sHTML, $sTag)
    {
        //$sRegExp = "/<newsletter[^>](.*?)>.*?<\/newsletter>/i";
        //$sRegExp = "/\[mail[^\]](.*?)>.*?\[\/mail\]/i";
        #\[mail[^\]]((name="(?P<name>.*?)")|(type="(?P<type>.*?)"))\](?P<content>.*?)\[\/mail\]
        #\[mail[^\]]((name=(?P<name>[^"]*.*?[^"]*))|(type="(?P<type>.*?)"))\](?P<content>.*?)\[\/mail\]

        /* RegExp explanation:
         * Match the character "[" literally �\[�
         * Match the characters "mail" literally �mail�
         * Match "whitespace characters" (spaces, tabs, line breaks, etc.) after "mail" �\s*�
         * Match the regular expression below and capture its match into backreference number 1 �([^]]+)�
         * Match any character that is not a "]" �[^]]+�
         *       Between one and unlimited times, as many times as possible, giving back as needed (greedy) �+�
         * Match the character "]" literally �\]�
         * Match the regular expression below and capture its match into backreference number 2 �((?:.|\s)+?)�
         *    Match the regular expression below �(?:.|\s)+?�
         *       Between one and unlimited times, as few times as possible, expanding as needed (lazy) �+?�
         *       Match either the regular expression below (attempting the next alternative only if this one fails) �.�
         *          Match any single character that is not a line break character �.�
         *       Or match regular expression number 2 below (the entire group fails if this one fails to match) �\s�
         *          Match a single character that is a "whitespace character" (spaces, tabs, line breaks, etc.) �\s�
         * Match the character "[" literally �\[�
         * Match the characters "/mail" literally �/mail�
         * Match the character "]" literally �\]�
         * Ignore case (i), . includes new lines (s)
         **/

        /*
        $sRegExp = '/\[mail\s*([^]]+)\]((?:.|\s)+?)\[\/mail\]/is';
        $aMatch = array();
        preg_match_all($sRegExp, $sHTML, $aMatch, PREG_SET_ORDER);
        print_r ($aMatch);

        // Auf bestimmten Typ matchen
        $sRegExp = '/\[mail.*?name="name".*?\]((?:.|\s)+?)\[\/mail\]/is';
        $aMatch = array();
        preg_match_all($sRegExp, $sHTML, $aMatch, PREG_SET_ORDER);
        print_r ($aMatch); */

        // Parameter auseinandernehmen (ohne PREG_SET_ORDER)
        #$sRegExp = '/\s*(.*?)\s*=\s*"(.*?)"\s*/i';
        #$aMatch = array();
        #preg_match_all($sRegExp, $sHTML, $aMatch);
        #print_r ($aMatch);
    }

    protected function _deChunkHTTPBody($sHeader, $sBody, $sEOL = "\r\n")
    {
        // Based on code from jbr at ya-right dot com, posted on http://www.php.net
        // as user comment on fsockopen documentation (2007-05-01)

        // Analyze header
        $aParts = preg_split("/\r?\n/", $sHeader, -1, PREG_SPLIT_NO_EMPTY);

        $aHeader = array();
        for ($i = 0;$i < sizeof ($aParts); $i++) {
            if ($i != 0) {
                $iPos       = strpos($aParts[$i], ':');
                $sParameter = strtolower (str_replace(' ', '', substr ($aParts[$i], 0, $iPos)));
                $sValue     = trim(substr($aParts[$i], ($iPos + 1)));
            } else {
                $sField      = 'status';
                $aParameters = explode(' ', $aParts[$i]);
                $sParameter  = $aParameters[1];
            }

            if ($sParameter == 'set-cookie') {
                $aHeader['cookies'][] = $sValue;
            } else if ($sParameter == 'content-type') {
                if (($iPos = strpos($sValue, ';')) !== false) {
                    $aHeader[$sParameter] = substr($sValue, 0, $iPos);
                } else {
                    $aHeader[$sParameter] = $sValue;
                }
            } else {
                $aHeader[$sParameter] = $sValue;
            }
        }

        // Get dechunked and decompressed body
        $iEOLLen = strlen($sEOL);

        $sBuffer = '';
        if (isset($aHeader['transfer-encoding']) && $aHeader['transfer-encoding'] == 'chunked') {
            do {
                $sBody    = ltrim ($sBody);
                $iPos     = strpos($sBody, $sEOL);
                $iDataLen = hexdec (substr($sBody, 0, $iPos));

                if (isset($aHeader['content-encoding'])) {
                    $sBuffer .= gzinflate(substr($sBody, ($iPos + $iEOLLen + 10), $iDataLen));
                } else {
                    $sBuffer .= substr($sBody, ($iPos + $iEOLLen), $iDataLen);
                }

                $sBody      = substr ($sBody, ($iPos + $iDataLen + $iEOLLen));
                $sRemaining = trim ($sBody);

            } while (!empty($sRemaining));
        } else if (isset($aHeader['content-encoding'])) {
            $sBuffer = gzinflate(substr($sBody, 10));
        } else {
            $sBuffer = $sBody; // Not chunked, not compressed
        }

        return $sBuffer;
    }

    /**
     * If newsletter is HTML newsletter and necessary data available
     * returns final HTML message
     * @return string HTML message
     */
    public function getHTMLMessage()
    {
        global $lang, $client, $contenido;
        $frontendURL = cRegistry::getFrontendUrl();

        if ($this->get("type") == "html" && $this->get("idart") > 0 && $this->htmlArticleExists()) {

            // Article ID
            $iIDArt = $this->get("idart");

            // Category ID
            $oClientLang = new cApiClientLanguage(false, $client, $lang);
            $iIDCat      = $oClientLang->getProperty("newsletter", "html_newsletter_idcat");
            unset($oClientLang);

            // Get http username and password, if frontend is protected
            $oClient = new cApiClient($client);
            $sHTTPUserName = $oClient->getProperty("newsletter", "html_username");
            $sHTTPPassword = $oClient->getProperty("newsletter", "html_password");
            unset($oClient);

            // Get HTML
            if ($iIDArt > 0 && $iIDCat > 0) {
                // Check, if newsletter is online and set temporarely online, otherwise
                $bSetOffline = false;
                $oArticles = new cApiArticleLanguageCollection;
                $oArticles->setWhere("idlang", $this->get("idlang"));
                $oArticles->setWhere("idart", $this->get("idart"));
                $oArticles->query();

                if ($oArticle = $oArticles->next()) {
                    if ($oArticle->get("online") == 0) {
                        $bSetOffline = true;
                        $oArticle->set("online", 1);
                        $oArticle->store();
                    }
                    unset($oArticle);
                }
                unset($oArticles);

                $sFile = "front_content.php?client=$client&lang=$lang&idcat=$iIDCat&idart=$iIDArt&noex=1&send=1";

                $handler = cHttpRequest::getHttpRequest($frontendURL.$sFile);
                $headers = array();

                // Maybe the website has been protected using .htaccess, then login
                if ($sHTTPUserName != "" && $sHTTPPassword != "") {
                    $headers['Authorization'] = "Basic " . base64_encode("$sHTTPUserName:$sHTTPPassword");
                }

                $headers['Referer'] = "Referer: http://".$frontendURL;
                $headers['User-Agent'] = "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";

                $handler->setHeaders($headers);

                $iErrorNo    = 0;
                $sErrorMsg   = "";
                if ($output = $handler->getRequest(true, true)) {
                    // Get the HTTP header and body separately
                    $sHTML   = strstr(strstr($output, "200"), "\r\n\r\n");
                    $sHeader = strstr($output, "\r\n\r\n", true);
                    $sHTML = $this->_deChunkHTTPBody($sHeader, $sHTML);

                    // If someone likes to use anchors in html newsletters (*sigh*)
                    // the base href tag has to be removed - that means, we have to fix
                    // all source paths manually...
                    if (getEffectiveSetting('newsletter', 'remove_base_tag', "false") == "true") {
                        // Remove base tag
                        $sHTML = preg_replace('/<base href=(.*?)>/is', '', $sHTML, 1);

                        // Fix source path
                        // TODO: Test any URL specification that may exist under the sun...
                        $sHTML = preg_replace('/[sS[rR][cC][ ]*=[ ]*"([^h][^t][^t][^p][^:].*)"/', 'rc="'.$frontendURL.'$1"', $sHTML);
                        $sHTML = preg_replace('/[hH][rR][eE][fF][ ]*=[ ]*"([^h][^t][^t][^p][^:][A-Za-z0-9#\.?\-=_&]*)"/', 'href="'.$frontendURL.'$1"', $sHTML);
                        $sHTML = preg_replace('/url\((.*)\)/', 'url('. $frontendURL.'$1)', $sHTML);

                        // Now replace anchor tags to the newsletter article itself just by the anchor
                        $sHTML = str_replace($frontendURL . "front_content.php?idart=".$iIDArt."#", "#", $sHTML);
                    }

                    $sReturn = $sHTML;
                } else {
                    if ($contenido) { // Use i18n only in backend
                        $sErrorText = i18n("There was a problem getting the newsletter article using http. Error: %s (%s)");
                    } else {
                        $sErrorText = "There was a problem getting the newsletter article using http. Error: %s (%s)";
                    }

                    $this->_sError = sprintf($sErrorText, $sErrorMsg, $iErrorNo);
                    $sReturn = false;
                }

                // Set previously offline article back to offline
                if ($bSetOffline) {
                    $oArticles = new cApiArticleLanguageCollection();
                    $oArticles->setWhere("idlang", $this->get("idlang"));
                    $oArticles->setWhere("idart", $this->get("idart"));
                    $oArticles->query();

                    if ($oArticle = $oArticles->next()) {
                        $oArticle->set("online", 0);
                        $oArticle->store();
                    }
                    unset($oArticle);
                    unset($oArticles);
                }

                return $sReturn;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Checks, if html newsletter article still exists
     * @return bool
     */
    public function htmlArticleExists()
    {
        if ($this->get("idart") > 0) {
            $oArticles = new cApiArticleLanguageCollection();
            $oArticles->setWhere("idlang", $this->get("idlang"));
            $oArticles->setWhere("idart", $this->get("idart"));
            $oArticles->query();

            if ($oArticles->count() > 0) {
                $bReturn = true;
            } else {
                $bReturn = false;
            }

            unset($oArticles);
        } else {
            $bReturn = false;
        }

        return $bReturn;
    }

    /**
     * Sends test newsletter directly to specified email address
     * @param integer  $iIDCatArt        idcatart of newsletter handler article
     * @param string   $sEMail           Recipient email address
     * @param string   $sName            Optional: Recipient name
     * @param bool     $bSimulatePlugin  If recipient plugin activated, include plugins
     *                                   and simulate values from plugins
     * @param string   $sEncoding        Message (and header) encoding, e.g. iso-8859-1
     */
    public function sendEMail($iIDCatArt, $sEMail, $sName = "", $bSimulatePlugins = true, $sEncoding = "iso-8859-1")
    {
        global $lang, $client, $cfg, $cfgClient, $contenido;

        // Initialization
        if ($sName == "") {
            $sName = $sEMail;
        }

        $oLanguage = new cApiLanguage($lang);
        $sFormatDate = $oLanguage->getProperty("dateformat", "date");
        $sFormatTime = $oLanguage->getProperty("dateformat", "time");
        unset($oLanguage);

        if ($sFormatDate == "") {
            $sFormatDate = "%d.%m.%Y";
        }
        if ($sFormatTime == "") {
            $sFormatTime = "%H:%M";
        }

        // Get newsletter data
        $sFrom            = $this->get("newsfrom");
        $sFromName        = $this->get("newsfromname");
        if ($sFromName == "") {
            $sFromName    = $sFrom;
        }
        $sSubject        = $this->get("subject");
        $sMessageText    = $this->get("message");

        $bIsHTML        = false;
        if ($this->get("type") == "html") {
            $sMessageHTML    = $this->getHTMLMessage();

            if ($sMessageHTML === false) {
                // There was a problem getting the html message (maybe article
                // deleted). Exit with error instead of sending as text message only

                if ($contenido) { // Use i18n only in backend
                    $sError = i18n("Newsletter to %s could not be sent: No html message available");
                } else {
                    $sError = "Newsletter to %s could not be sent: No html message available";
                }
                $this->_sError = $sName." (".$sEMail."): ".sprintf($sError, $sEMail);
                return false;
            } else {
                $bIsHTML = true;
            }
        }

        // Preventing double lines in mail, you may wish to disable this function on windows servers
        if (!getSystemProperty("newsletter", "disable-rn-replacement")) {
            $sMessageText = str_replace("\r\n", "\n", $sMessageText);
        }

        // Simulate key, an alphanumeric string of 30 characters
        $sKey    = str_repeat("key", 10);
        $sPath    = cRegistry::getFrontendUrl() . "front_content.php?changelang=".$lang."&idcatart=".$iIDCatArt."&";

        // Replace message tags (text message)
        $this->_replaceTag($sMessageText, false, "name", $sName);
        $this->_replaceTag($sMessageText, false, "number", 1);
        $this->_replaceTag($sMessageText, false, "date", strftime($sFormatDate));
        $this->_replaceTag($sMessageText, false, "time", strftime($sFormatTime));
        $this->_replaceTag($sMessageText, false, "unsubscribe", $sPath."unsubscribe=".$sKey);
        $this->_replaceTag($sMessageText, false, "change", $sPath."change=".$sKey);
        $this->_replaceTag($sMessageText, false, "stop", $sPath."stop=".$sKey);
        $this->_replaceTag($sMessageText, false, "goon", $sPath."goon=".$sKey);

        // Replace message tags (html message)
        if ($bIsHTML) {
            $this->_replaceTag($sMessageHTML, true, "name", $sName);
            $this->_replaceTag($sMessageHTML, true, "number", 1);
            $this->_replaceTag($sMessageHTML, true, "date", strftime($sFormatDate));
            $this->_replaceTag($sMessageHTML, true, "time", strftime($sFormatTime));
            $this->_replaceTag($sMessageHTML, true, "unsubscribe", $sPath."unsubscribe=".$sKey);
            $this->_replaceTag($sMessageHTML, true, "change", $sPath."change=".$sKey);
            $this->_replaceTag($sMessageHTML, true, "stop", $sPath."stop=".$sKey);
            $this->_replaceTag($sMessageHTML, true, "goon", $sPath."goon=".$sKey);
        }

        if ($bSimulatePlugins) {
            // Enabling plugin interface
            if (getSystemProperty("newsletter", "newsletter-recipients-plugin") == "true") {
                if (is_array($cfg['plugins']['recipients'])) {
                    foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                        plugin_include("recipients", $sPlugin."/".$sPlugin.".php");
                        if (function_exists("recipients_".$sPlugin."_wantedVariables")) {
                            $aPluginVars = array();
                            $aPluginVars = call_user_func("recipients_".$sPlugin."_wantedVariables");

                            foreach ($aPluginVars as $sPluginVar) {
                                // Replace tags in text message
                                $this->_replaceTag($sMessageText, false, $sPluginVar, ":: ".$sPlugin.": ".$sPluginVar." ::");
                                // Replace tags in html message
                                if ($bIsHTML) {
                                    $this->_replaceTag($sMessageHTML, true,     $sPluginVar, ":: ".$sPlugin.": ".$sPluginVar." ::");
                                }
                            }
                        }
                    }
                }
            } else {
                setSystemProperty("newsletter", "newsletter-recipients-plugin", "false");
            }
        }

        if (!isValidMail($sEMail) || strtolower($sEMail) == "sysadmin@ihresite.de") {
            // No valid destination mail address specified
            if ($contenido) { // Use i18n only in backend
                $sError = i18n("Newsletter to %s could not be sent: No valid e-mail address");
            } else {
                $sError = "Newsletter to %s could not be sent: No valid e-mail address";
            }
            $this->_sError = $sName." (".$sEMail."): ".sprintf($sError, $sEMail);
            return false;
        } else {
            if ($bIsHTML) {
                $body = $sMessageHTML;
            } else {
                $body = $sMessageText."\n\n";
            }
            if ($bIsHTML) {
                $contentType = 'text/html';
            } else {
                $contentType = 'text/plain';
            }

            $mailer = new cMailer();
            $message = Swift_Message::newInstance($sSubject, $body, $contentType, $sEncoding);
            $message->setFrom($sFrom, $sFromName);
            $message->setTo($sEMail);
            $result = $mailer->send($message);

            if (!$result) {
                if ($contenido) { // Use i18n only in backend
                    $sError = i18n("Newsletter to %s could not be sent");
                } else {
                    $sError = "Newsletter to %s could not be sent";
                }
                $this->_sError = $sName." (".$sEMail."): ".sprintf($sError, $sEMail);
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Sends test newsletter directly to specified recipients (single or group)
     *
     * Note: Sending in chunks not supported! Only usable for tests and only a few
     * recipients.
     *
     * @param integer  $iIDCatArt     idcatart of newsletter handler article
     * @param integer  $iIDNewsRcp    If specified, newsletter recipient id, ignored, if group specified
     * @param integer  $iIDNewsGroup  If specified, newsletter recipient group id
     * @param array    $aSendRcps     As reference: Filled with a list of succesfull recipients
     * @param string   $sEncoding     Message (and header) encoding, e.g. iso-8859-1
     */
    public function sendDirect($iIDCatArt, $iIDNewsRcp = false, $iIDNewsGroup = false, &$aSendRcps, $sEncoding = "iso-8859-1")
    {
        global $lang, $client, $cfg, $cfgClient, $contenido, $recipient;

        // Initialization
        $aMessages  = array();

        $oLanguage = new cApiLanguage($lang);
        unset($oLanguage);

        if ($sFormatDate == "") {
            $sFormatDate = "%d.%m.%Y";
        }
        if ($sFormatTime == "") {
            $sFormatTime = "%H:%M";
        }

        $sPath = cRegistry::getFrontendUrl() . "front_content.php?changelang=".$lang."&idcatart=".$iIDCatArt."&";

        // Get newsletter data
        $sFrom     = $this->get("newsfrom");
        $sFromName = $this->get("newsfromname");
        if ($sFromName == "") {
            $sFromName = $sFrom;
        }
        $sSubject     = $this->get("subject");
        $sMessageText = $this->get("message");

        $bIsHTML      = false;
        if ($this->get("type") == "html") {
            $sMessageHTML    = $this->getHTMLMessage();

            if ($sMessageHTML === false) {
                // There was a problem getting the html message (maybe article
                // deleted). Exit with error instead of sending as text message only

                if ($contenido) { // Use i18n only in backend
                    $sError = i18n("Newsletter could not be sent: No html message available");
                } else {
                    $sError = "Newsletter could not be sent: No html message available";
                }
                $this->_sError = $sError;
                return false;
            } else {
                $bIsHTML = true;
            }
        }

        // Preventing double lines in mail, you may wish to disable this function on windows servers
        if (!getSystemProperty("newsletter", "disable-rn-replacement")) {
            $sMessageText = str_replace("\r\n", "\n", $sMessageText);
        }

        // Single replacements
        // Replace message tags (text message)
        $this->_replaceTag($sMessageText, false, "date", strftime($sFormatDate));
        $this->_replaceTag($sMessageText, false, "time", strftime($sFormatTime));

        // Replace message tags (html message)
        if ($bIsHTML) {
            $this->_replaceTag($sMessageHTML, true, "date", strftime($sFormatDate));
            $this->_replaceTag($sMessageHTML, true, "time", strftime($sFormatTime));
        }

        // Enabling plugin interface
        if (getSystemProperty("newsletter", "newsletter-recipients-plugin") == "true") {
            $bPluginEnabled = true;
            $aPlugins       = array();

            if (is_array($cfg['plugins']['recipients'])) {
                foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                    plugin_include("recipients", $sPlugin."/".$sPlugin.".php");
                    if (function_exists("recipients_".$sPlugin."_wantedVariables")) {
                        $aPlugins[$sPlugin] = call_user_func("recipients_".$sPlugin."_wantedVariables");
                    }
                }
            }
        } else {
            setSystemProperty("newsletter", "newsletter-recipients-plugin", "false");
            $bPluginEnabled = false;
        }

        $aRecipients = array();
        if ($iIDNewsGroup !== false) {
            $oGroupMembers = new RecipientGroupMemberCollection;
            $aRecipients = $oGroupMembers->getRecipientsInGroup ($iIDNewsGroup, false);
        } else if ($iIDNewsRcp !== false) {
            $aRecipients[] = $iIDNewsRcp;
        }

        $iCount = count($aRecipients);
        if ($iCount > 0) {
            $this->_replaceTag($sMessageText, false, "number", $iCount);

            // Replace message tags (html message)
            if ($bIsHTML) {
                $this->_replaceTag($sMessageHTML, true,     "number", $iCount);
            }

            foreach ($aRecipients as $iID) {
                $sRcpMsgText = $sMessageText;
                $sRcpMsgHTML = $sMessageHTML;

                // Don't change name of $recipient variable as it is used in plugins!
                $recipient   = new Recipient;
                $recipient->loadByPrimaryKey($iID);

                $sEMail  = $recipient->get("email");
                $sName   = $recipient->get("name");
                if (empty ($sName)) {
                    $sName = $sEMail;
                }
                $sKey    = $recipient->get("hash");

                $bSendHTML = false;
                if ($recipient->get("news_type") == 1) {
                    $bSendHTML = true; // Recipient accepts html newsletter
                }

                $this->_replaceTag($sRcpMsgText, false, "name", $sName);
                $this->_replaceTag($sRcpMsgText, false, "unsubscribe", $sPath."unsubscribe=".$sKey);
                $this->_replaceTag($sRcpMsgText, false, "change", $sPath."change=".$sKey);
                $this->_replaceTag($sRcpMsgText, false, "stop", $sPath."stop=".$sKey);
                $this->_replaceTag($sRcpMsgText, false, "goon", $sPath."goon=".$sKey);

                // Replace message tags (html message)
                if ($bIsHTML && $bSendHTML) {
                    $this->_replaceTag($sRcpMsgHTML, true, "name", $sName);
                    $this->_replaceTag($sRcpMsgHTML, true, "unsubscribe", $sPath."unsubscribe=".$sKey);
                    $this->_replaceTag($sRcpMsgHTML, true, "change", $sPath."change=".$sKey);
                    $this->_replaceTag($sRcpMsgHTML, true, "stop", $sPath."stop=".$sKey);
                    $this->_replaceTag($sRcpMsgHTML, true, "goon", $sPath."goon=".$sKey);
                }

                if ($bPluginEnabled) {
                    foreach ($aPlugins as $sPlugin => $aPluginVar) {
                        foreach ($aPluginVar as $sPluginVar) {
                            // Replace tags in text message
                            $this->_replaceTag($sRcpMsgText, false, $sPluginVar, call_user_func("recipients_".$sPlugin."_getvalue", $sPluginVar));
                            // Replace tags in html message
                            if ($bIsHTML && $bSendHTML) {
                                $this->_replaceTag($sRcpMsgHTML, true, $sPluginVar, call_user_func("recipients_".$sPlugin."_getvalue", $sPluginVar));
                            }
                        }
                    }
                }

                if (strlen($sKey) != 30) { // Prevents sending without having a key
                    if ($contenido) { // Use i18n only in backend
                        $sError = i18n("Newsletter to %s could not be sent: Recipient has an incompatible or empty key");
                    } else {
                        $sError = "Newsletter to %s could not be sent: Recipient has an incompatible or empty key";
                    }
                    $aMessages[] = $sName." (".$sEMail."): ".sprintf($sError, $sEMail);
                } else if (!isValidMail($sEMail)) {
                    if ($contenido) { // Use i18n only in backend
                        $sError = i18n("Newsletter to %s could not be sent: No valid e-mail address specified");
                    } else {
                        $sError = "Newsletter to %s could not be sent: No valid e-mail address specified";
                    }
                    $aMessages[] = $sName." (".$sEMail."): ".sprintf($sError, $sEMail);
                } else {
                    if ($bIsHTML && $bSendHTML) {
                        $body = $sRcpMsgHTML;
                    } else {
                        $body = $sRcpMsgText."\n\n";
                    }

                    if ($bIsHTML && $bSendHTML) {
                        $contentType = 'text/html';
                    } else {
                        $contentType = 'text/plain';
                    }

                    $mailer = new cMailer();
                    $message = Swift_Message::newInstance($sSubject, $body, $contentType, $sEncoding);
                    $message->setFrom($sFrom, $sFromName);
                    $message->setTo($sEMail);
                    $result = $mailer->send($message);

                    if ($result) {
                        $aSendRcps[] = $sName." (".$sEMail.")";
                    } else {
                        if ($contenido) { // Use i18n only in backend
                            $sError = i18n("Newsletter to %s could not be sent");
                        } else {
                            $sError = "Newsletter to %s could not be sent";
                        }
                        $aMessages[] = $sName." (".$sEMail."): ".sprintf($sError, $sEMail);
                    }
                }
            }
        } else {
            if ($contenido) { // Use i18n only in backend
                $sError = i18n("No recipient with specified recipient/group id %s/%s found");
            } else {
                $sError = "No recipient with specified recpient/group id %s/%s found";
            }
            $aMessages[] = sprintf($sError, $iIDNewsRcp, $iIDNewsGroup);
        }

        if (count($aMessages) > 0) {
            $this->_sError = implode("<br>", $aMessages);
            return false;
        } else {
            return true;
        }
    }
}
