<?php

/**
 * This file contains the Newsletter recipient class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Newsletter recipient class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @extends ItemCollection<Newsletter>
 */
class NewsletterCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdAndLanguageIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('news'), 'idnews');
        $this->_setItemClass('Newsletter');
    }

    /**
     * Creates a new newsletter
     *
     * @param $name string specifies the newsletter name
     * @return Newsletter
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($name)
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();
        $auth = cRegistry::getAuth();

        // Check if the newsletter name already exists
        $this->resetQuery();
        $this->setWhere('idclient', $clientId);
        $this->setWhere('idlang', $languageId);
        $this->setWhere('name', $name);
        $this->query();

        if ($this->next()) {
            return $this->create($name . "_" . cString::getPartOfString(md5(rand()), 0, 10));
        }

        $oItem = $this->createNewItem();
        $oItem->set('idclient', $clientId);
        $oItem->set('idlang', $languageId);
        $oItem->set('name', $name);
        $oItem->set('created', date('Y-m-d H:i:s'), false);
        $oItem->set('author', $auth->getUserId());

        $oItem->store();

        return $oItem;
    }

    /**
     * Duplicates the newsletter specified by $itemID
     *
     * @param int $itemId specifies the newsletter id
     * @return Newsletter
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function duplicate($itemId): Newsletter
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        cInclude('includes', 'functions.con.php');

        $oBaseItem = new Newsletter();
        $oBaseItem->loadByPrimaryKey($itemId);

        $oItem = $this->createNewItem();
        $newName = $oBaseItem->get('name') . '_' . cString::getPartOfString(md5(rand()), 0, 10);
        $oItem->set('name', $newName);

        $articleId = 0;
        if (
            $oBaseItem->get('type') === 'html'
            && $oBaseItem->get('idart') > 0
            && $oBaseItem->get('template_idart') > 0
        ) {
            $oClientLang = new cApiClientLanguage(false, $clientId, $languageId);
            if ($oClientLang->getProperty('newsletter', 'html_newsletter') == 'true') {
                $articleId = conCopyArticle($oBaseItem->get('idart'),
                    $oClientLang->getProperty('newsletter', 'html_newsletter_idcat'),
                    sprintf(i18n("Newsletter: %s", "newsletter"), $oItem->get('name'))
                );
                conMakeOnline($articleId, $languageId); // Article has to be online for sending...
            }
        }

        $oItem->set('idart', $articleId);
        $oItem->set('template_idart', $oBaseItem->get('template_idart'));
        $oItem->set('idclient', $clientId);
        $oItem->set('idlang', $languageId);
        $oItem->set('welcome', 0);
        $oItem->set('type', $oBaseItem->get('type'));
        $oItem->set('subject', $oBaseItem->get('subject'));
        $oItem->set('message', $oBaseItem->get('message'));
        $oItem->set('newsfrom', $oBaseItem->get('newsfrom'));
        $oItem->set('newsfromname', $oBaseItem->get('newsfromname'));
        $oItem->set('newsdate', date('Y-m-d H:i:s'), false); // But more or less deprecated
        $oItem->set('use_cronjob', $oBaseItem->get('use_cronjob'));
        $oItem->set('send_to', $oBaseItem->get('send_to'));
        $oItem->set('send_ids', $oBaseItem->get('send_ids'));
        $oItem->set('dispatch', $oBaseItem->get('dispatch'));
        $oItem->set('dispatch_count', $oBaseItem->get('dispatch_count'));
        $oItem->set('dispatch_delay', $oBaseItem->get('dispatch_delay'));
        $oItem->set('author', cRegistry::getAuth()->getUserId());
        $oItem->set('created', date('Y-m-d H:i:s'), false);

        // Copy properties, runtime on-demand allocation of the properties object
        if (!is_object($this->properties)) {
            $this->properties = new cApiPropertyCollection();
        }
        $this->properties->setWhere('idclient', $clientId);
        $this->properties->setWhere('itemtype', $this->getPrimaryKeyName());
        $this->properties->setWhere('itemid', $itemId);
        $this->properties->query();

        while ($oPropertyItem = $this->properties->next()) {
            $oItem->setProperty(
                $oPropertyItem->get('type'),
                $oPropertyItem->get('name'),
                $oPropertyItem->get('value'),
                $clientId
            );
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
     */
    public $_sError;

    /**
     * Constructor Function
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('news'), 'idnews');
        $this->_sError = '';
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Overridden store()-Method to set modified and modifiedby data and
     * to ensure that there is only one welcome newsletter
     *
     * @inheritDoc
     * @throws cException
     */
    public function store()
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', cRegistry::getAuth()->getUserId());

        if ($this->get('welcome') == 1) {
            $oItems = new NewsletterCollection();
            $oItems->setWhere('idclient', $clientId);
            $oItems->setWhere('idlang', $languageId);
            $oItems->setWhere('welcome', 1);
            $oItems->setWhere('idnews', $this->get('idnews'), "<>");
            $oItems->query();

            while ($oItem = $oItems->next()) {
                $oItem->set('welcome', 0);
                $oItem->store();
            }
            unset($oItem);
            unset($oItems);
        }

        return parent::store();
    }

    /**
     * User-defined setter for newsletter fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idnews':
            case 'idlang':
            case 'idclient':
            case 'idart':
            case 'template_idart':
            case 'welcome':
            case 'use_cronjob':
            case 'dispatch':
            case 'dispatch_count':
            case 'dispatch_delay':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * @inheritDoc
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'idnews':
            case 'idlang':
            case 'idclient':
            case 'idart':
            case 'template_idart':
            case 'welcome':
            case 'use_cronjob':
            case 'dispatch':
            case 'dispatch_count':
            case 'dispatch_delay':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

    /**
     * Replaces newsletter tag (e.g. MAIL_NAME) with value.
     * If code is just text using str_replace; if it is HTML by using regular expressions
     * @param string $code Code, where the found tags are to be replaced (by reference)
     * @param bool $isHTML Is code HTML?
     * @param string $field Field name, without MAIL_ (e.g. "name" for "MAIL_NAME")
     * @param string $value The value to set
     */
    public function _replaceTag(&$code, $isHTML, $field, $value)
    {
        $tag = 'MAIL_' . cString::toUpperCase($field);

        // Nothing to replace
        if (empty($code)) {
            return;
        }

        // Do replacement in text e-mail
        if (!$isHTML) {
            $code = str_replace($tag, $value, $code);
            return;
        }

        // Do replacement in HTML e-mail

        // Extract certain tag
        $sRegExp = '/\[mail\s*([^]]+)\s*name=(?:"|&quot;)' . $field . '(?:"|&quot;)\s*(.*?)\s*\]((?:.|\s)+?)\[\/mail\]/i';
        $aMatch = [];
        if (!preg_match($sRegExp, $code, $aMatch)) {
            return;
        }

        // $aMatch contains parameter info from left [1] or right [2] to name="field"
        $sParameter = $aMatch[1] . $aMatch[2];
        $sMessage = $aMatch[3];
        $sRegExp = '/\s*(.*?)\s*=\s*(?:"|&quot;)(.*?)(?:"|&quot;)\s*/i';
        $aMatch = [];
        if (!preg_match_all($sRegExp, $sParameter, $aMatch)) {
            return;
        }

        // Store parameter data as associative array
        $aParameter = array_combine($aMatch[1], $aMatch[2]);
        unset($aMatch); // $aMatch not needed anymore

        if (!array_key_exists("type", $aParameter)) {
            $aParameter['type'] = "text";
        }

        switch ($aParameter['type']) {
            case "link":
                # TODO: Works everything fine?
                # The current code makes it possible to do something like
                # [mail ...]Some text here, then the link: [MAIL_STOP] and more text[/mail]
                #
                # If the other lines will be used, you don't need to
                # set [MAIL_xy] and the message between the [mail]-tags will
                # be used as link text (instead of using the tag parameter "text")

                $sText = $aParameter['text'];

                if ($sText == '') {
                    $sText = $value;
                }
                if ($sMessage == '') {
                    $sMessage = $value;
                }

                // Remove not needed parameters from the parameters list
                // everything else goes into the link as parameters
                unset($aParameter['type']);
                unset($aParameter['text']);

                $sParameter = "";
                if (count($aParameter) > 0) {
                    foreach ($aParameter as $sKey => $sValue) {
                        $sParameter .= ' ' . $sKey . '="' . $sValue . '"';
                    }
                }
                $sMessage = str_replace($tag, '<a href="' . conHtmlentities($value) . '"' . $sParameter . '>' . $sText . '</a>', $sMessage);
                #$sMessage    = '<a href="'.conHtmlentities($value).'"'.$sParameter.'>'.$sMessage.'</a>';
                break;
            default:
                $sMessage = str_replace($tag, $value, $sMessage);
            #$sMessage    = $value;
        }

        $sRegExp = '/\[mail[^]]+name=(?:"|&quot;)' . $field . '(?:"|&quot;).*?\].*?\[\/mail\]/is';
        $code = preg_replace($sRegExp, $sMessage, $code, -1);
        // Just to replace "text"-tags in HTML message also, just in case...
        $code = str_replace($tag, $value, $code);
    }

    /**
     * @param $sHTML
     * @param $sTag
     * @todo HerrB: Remove or insert some functionality
     */
    protected function _getNewsletterTagData($sHTML, $sTag)
    {
        //$sRegExp = "/<newsletter[^>](.*?)>.*?<\/newsletter>/i";
        //$sRegExp = "/\[mail[^\]](.*?)>.*?\[\/mail\]/i";
        //\[mail[^\]]((name="(?P<name>.*?)")|(type="(?P<type>.*?)"))\](?P<content>.*?)\[\/mail\]
        //\[mail[^\]]((name=(?P<name>[^"]*.*?[^"]*))|(type="(?P<type>.*?)"))\](?P<content>.*?)\[\/mail\]

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
         */

        /*
        $sRegExp = '/\[mail\s*([^]]+)\]((?:.|\s)+?)\[\/mail\]/is';
        $aMatch = [];
        preg_match_all($sRegExp, $sHTML, $aMatch, PREG_SET_ORDER);
        print_r ($aMatch);

        // Auf bestimmten Typ matchen
        $sRegExp = '/\[mail.*?name="name".*?\]((?:.|\s)+?)\[\/mail\]/is';
        $aMatch = [];
        preg_match_all($sRegExp, $sHTML, $aMatch, PREG_SET_ORDER);
        print_r ($aMatch); */

        // Parameter auseinandernehmen (ohne PREG_SET_ORDER)
        //$sRegExp = '/\s*(.*?)\s*=\s*"(.*?)"\s*/i';
        //$aMatch = [];
        //preg_match_all($sRegExp, $sHTML, $aMatch);
        //print_r ($aMatch);
    }

    /**
     * @param $sHeader
     * @param $sBody
     * @param string $sEOL
     *
     * @return string
     */
    protected function _deChunkHTTPBody($sHeader, $sBody, $sEOL = "\r\n")
    {
        // Based on code from jbr at ya-right dot com, posted on http://www.php.net
        // as user comment on fsockopen documentation (2007-05-01)

        // Analyze header
        $aParts = preg_split("/\r?\n/", $sHeader, -1, PREG_SPLIT_NO_EMPTY);

        $aHeader = [];
        for ($i = 0; $i < count($aParts); $i++) {
            if ($i != 0) {
                $iPos = cString::findFirstPos($aParts[$i], ':');
                $sParameter = cString::toLowerCase(str_replace(' ', '', cString::getPartOfString($aParts[$i], 0, $iPos)));
                $sValue = trim(cString::getPartOfString($aParts[$i], ($iPos + 1)));
            } else {
                $sParameter = 'status';
                $aParameters = explode(' ', $aParts[$i]);
                $sValue = $aParameters[1];
            }

            if ($sParameter == 'set-cookie') {
                $aHeader['cookies'][] = $sValue;
            } elseif ($sParameter == 'content-type') {
                if (($iPos = cString::findFirstPos($sValue, ';')) !== false) {
                    $aHeader[$sParameter] = cString::getPartOfString($sValue, 0, $iPos);
                } else {
                    $aHeader[$sParameter] = $sValue;
                }
            } else {
                $aHeader[$sParameter] = $sValue;
            }
        }

        // Get dechunked and decompressed body
        $iEOLLen = cString::getStringLength($sEOL);

        $sBuffer = '';

        // workaround:
        // others and i don't understand this part, thats why i made a workaround
        // seems as it is chunked, but reveived data isn't, thats why hexdec() produces an error
        if (isset($aHeader['transfer-encoding']) && $aHeader['transfer-encoding'] == 'chunked') {
            $isHex = true;

            do {
                $sBody = ltrim($sBody);
                $iPos = cString::findFirstPos($sBody, $sEOL);
                $nextChunkLength = cString::getPartOfString($sBody, 0, (int)$iPos);

                // workaround begin
                preg_match('/^[0-9A-F]$/', $nextChunkLength, $isHex);
                if (empty($isHex)) {
                    $isHex = false;
                    break;
                }
                // workarround end

                $iDataLen = hexdec($nextChunkLength);

                if (isset($aHeader['content-encoding'])) {
                    $sBuffer .= gzinflate(cString::getPartOfString($sBody, ((int)$iPos + (int)$iEOLLen + 10), (int)$iDataLen));
                } else {
                    $sBuffer .= cString::getPartOfString($sBody, ((int)$iPos + (int)$iEOLLen), (int)$iDataLen);
                }

                $sBody = cString::getPartOfString($sBody, ((int)$iPos + (int)$iDataLen + (int)$iEOLLen));

                $sRemaining = trim($sBody);
            } while ($sRemaining != '');

            // workarround begin
            if ($isHex === false) {
                if (isset($aHeader['content-encoding'])) {
                    $sBuffer = gzinflate(cString::getPartOfString($sBody, 10));
                } else {
                    $sBuffer = $sBody; // Not chunked, not compressed
                }
            }
            // workarround end

        } elseif (isset($aHeader['content-encoding'])) {
            $sBuffer = gzinflate(cString::getPartOfString($sBody, 10));
        } else {
            $sBuffer = $sBody; // Not chunked, not compressed
        }

        return $sBuffer;
    }

    /**
     * If newsletter is HTML newsletter and necessary data available
     * returns final HTML message
     *
     * @return string HTML message
     * @throws cDbException|cException
     */
    public function getHTMLMessage()
    {
        $frontendURL = cRegistry::getFrontendUrl();
        if ($this->get('type') == "html" && $this->get('idart') > 0 && $this->htmlArticleExists()) {
            $clientId = cRegistry::getClientId();
            $languageId = cRegistry::getLanguageId();

            // Article ID
            $articleId = $this->get('idart');

            // Category ID
            $oClientLang = new cApiClientLanguage(false, $clientId, $languageId);
            $iIDCat = $oClientLang->getProperty('newsletter', 'html_newsletter_idcat');
            unset($oClientLang);

            // Get http username and password, if frontend is protected
            $oClient = new cApiClient($clientId);
            $sHTTPUserName = $oClient->getProperty('newsletter', 'html_username');
            $sHTTPPassword = $oClient->getProperty('newsletter', 'html_password');
            unset($oClient);
            // Get HTML
            if ($articleId > 0 && $iIDCat > 0) {
                // Check, if newsletter is online and set temporarely online, otherwise
                $bSetOffline = false;
                $oArticles = new cApiArticleLanguageCollection;
                $oArticles->setWhere('idlang', $this->get('idlang'));
                $oArticles->setWhere('idart', $this->get('idart'));
                $oArticles->query();

                if ($oArticle = $oArticles->next()) {
                    if ($oArticle->get('online') == 0) {
                        $bSetOffline = true;
                        $oArticle->set('online', 1);
                        $oArticle->store();
                    }
                    unset($oArticle);
                }
                unset($oArticles);

                $sFile = "front_content.php?client=$clientId&lang=$languageId&idcat=$iIDCat&idart=$articleId&noex=1&send=1";

                $handler = cHttpRequest::getHttpRequest($frontendURL . $sFile);
                $headers = [];

                // Maybe the website has been protected using .htaccess, then login
                if ($sHTTPUserName != '' && $sHTTPPassword != '') {
                    $headers['Authorization'] = "Basic " . base64_encode("$sHTTPUserName:$sHTTPPassword");
                }

                $protocol = cIsHttpsRequest() ? 'https://' : 'http://';

                $headers['Referer'] = "Referer: " . $protocol . $frontendURL;
                $headers['User-Agent'] = "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";

                $handler->setHeaders($headers);

                $iErrorNo = 0;
                $sErrorMsg = "";
                if ($output = $handler->getRequest(true, true)) {
                    // Get the HTTP header and body separately
                    $sHTML = strstr(strstr($output, "200"), "\r\n\r\n");
                    $sHeader = strstr($output, "\r\n\r\n", true);
                    $sHTML = $this->_deChunkHTTPBody($sHeader, $sHTML);

                    // If someone likes to use anchors in html newsletters (*sigh*)
                    // the base href tag has to be removed - that means, we have to fix
                    // all source paths manually...
                    if (getEffectiveSetting('newsletter', 'remove_base_tag', "false") == 'true') {
                        // Remove base tag
                        $sHTML = preg_replace('/<base href=(.*?)>/is', '', $sHTML, 1);

                        // Fix source path
                        // TODO: Test any URL specification that may exist under the sun...
                        $sHTML = preg_replace('/[sS[rR][cC][ ]*=[ ]*"([^h][^t][^t][^p][^:].*)"/', 'rc="' . $frontendURL . '$1"', $sHTML);
                        $sHTML = preg_replace('/[hH][rR][eE][fF][ ]*=[ ]*"([^h][^t][^t][^p][^:][A-Za-z0-9#\.?\-=_&]*)"/', 'href="' . $frontendURL . '$1"', $sHTML);
                        $sHTML = preg_replace('/url\((.*)\)/', 'url(' . $frontendURL . '$1)', $sHTML);

                        // Now replace anchor tags to the newsletter article itself just by the anchor
                        $sHTML = str_replace($frontendURL . "front_content.php?idart=" . $articleId . "#", "#", $sHTML);
                    }

                    $sReturn = $sHTML;
                } else {
                    if (cRegistry::getBackendSessionId()) { // Use i18n only in backend
                        $sErrorText = i18n("There was a problem getting the newsletter article using http. Error: %s (%s)", "newsletter");
                    } else {
                        $sErrorText = "There was a problem getting the newsletter article using http. Error: %s (%s)";
                    }

                    $this->_sError = sprintf($sErrorText, $sErrorMsg, $iErrorNo);
                    $sReturn = false;
                }

                // Set previously offline article back to offline
                if ($bSetOffline) {
                    $oArticles = new cApiArticleLanguageCollection();
                    $oArticles->setWhere('idlang', $this->get('idlang'));
                    $oArticles->setWhere('idart', $this->get('idart'));
                    $oArticles->query();

                    if ($oArticle = $oArticles->next()) {
                        $oArticle->set('online', 0);
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
     * Checks if the html newsletter article still exists
     *
     * @throws cException
     */
    public function htmlArticleExists(): bool
    {
        if ($this->get('idart') > 0) {
            $oArticles = new cApiArticleLanguageCollection();
            $oArticles->setWhere('idlang', $this->get('idlang'));
            $oArticles->setWhere('idart', $this->get('idart'));
            $oArticles->query();

            return $oArticles->count() > 0;
        }

        return false;
    }

    /**
     * Sends test newsletter directly to specified email address
     *
     * @param int $categoryArticleId idcatart of newsletter handler article
     * @param string $sEMail Recipient email address
     * @param string $name Optional: Recipient name
     * @param bool $bSimulatePlugins If recipient plugin activated, include plugins
     *      and simulate values from plugins
     * @param string $sEncoding Message (and header) encoding, e.g. iso-8859-1
     * @return bool
     * @throws cDbException|cException
     */
    public function sendEMail($categoryArticleId, $sEMail, $name = '', $bSimulatePlugins = true, $sEncoding = 'iso-8859-1')
    {
        $languageId = cRegistry::getLanguageId();

        // Initialization
        if ($name == '') {
            $name = $sEMail;
        }

        /** @var PiNewsletter $plugin */
        $plugin = cRegistry::getAppVar('pluginNewsletter');
        $sFormatDate = $plugin->getDateFormat($this->get('idlang'));
        $sFormatTime = $plugin->getTimeFormat($this->get('idlang'));

        // Get newsletter data
        $sFrom = $this->get('newsfrom');
        $sFromName = $this->get('newsfromname');
        if ($sFromName == '') {
            $sFromName = $sFrom;
        }
        $sSubject = $this->get('subject');
        $sMessageText = $this->get('message');

        $isHTML = false;
        if ($this->get('type') == "html") {
            $sMessageHTML = $this->getHTMLMessage();

            if ($sMessageHTML === false) {
                // There was a problem getting the html message (maybe article
                // deleted). Exit with error instead of sending as text message only

                if (cRegistry::getBackendSessionId()) { // Use i18n only in backend
                    $sError = i18n("Newsletter to %s could not be sent: No html message available", "newsletter");
                } else {
                    $sError = "Newsletter to %s could not be sent: No html message available";
                }
                $this->_sError = $name . " (" . $sEMail . "): " . sprintf($sError, $sEMail);
                return false;
            } else {
                $isHTML = true;
            }
        }

        // Preventing double lines in mail, you may wish to disable this function on windows servers
        if (!empty($sMessageText) && !getSystemProperty('newsletter', 'disable-rn-replacement')) {
            $sMessageText = str_replace("\r\n", "\n", $sMessageText);
        }

        // Simulate key, an alphanumeric string of 30 characters
        $sKey = str_repeat("key", 10);
        $sPath = cRegistry::getFrontendUrl() . "front_content.php?changelang=" . $languageId . "&idcatart=" . $categoryArticleId . "&";

        // Replace message tags (text message)
        $this->_replaceTag($sMessageText, false, "name", $name);
        $this->_replaceTag($sMessageText, false, "number", 1);
        $this->_replaceTag($sMessageText, false, "date", cDate::formatToDate($sFormatDate));
        $this->_replaceTag($sMessageText, false, "time", cDate::formatToDate($sFormatTime));
        $this->_replaceTag($sMessageText, false, "unsubscribe", $sPath . "unsubscribe=" . $sKey);
        $this->_replaceTag($sMessageText, false, "change", $sPath . "change=" . $sKey);
        $this->_replaceTag($sMessageText, false, "stop", $sPath . "stop=" . $sKey);
        $this->_replaceTag($sMessageText, false, "goon", $sPath . "goon=" . $sKey);

        // Replace message tags (html message)
        if ($isHTML) {
            $this->_replaceTag($sMessageHTML, true, "name", $name);
            $this->_replaceTag($sMessageHTML, true, "number", 1);
            $this->_replaceTag($sMessageHTML, true, "date", cDate::formatToDate($sFormatDate));
            $this->_replaceTag($sMessageHTML, true, "time", cDate::formatToDate($sFormatTime));
            $this->_replaceTag($sMessageHTML, true, "unsubscribe", $sPath . "unsubscribe=" . $sKey);
            $this->_replaceTag($sMessageHTML, true, "change", $sPath . "change=" . $sKey);
            $this->_replaceTag($sMessageHTML, true, "stop", $sPath . "stop=" . $sKey);
            $this->_replaceTag($sMessageHTML, true, "goon", $sPath . "goon=" . $sKey);
        }

        if ($bSimulatePlugins) {
            // Enabling plugin interface
            if (getSystemProperty('newsletter', 'newsletter-recipients-plugin') == 'true') {
                if (cHasPlugins('recipients')) {
                    cIncludePlugins('recipients');
                    $cfg = cRegistry::getConfig();
                    foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                        if (function_exists('recipients_' . $sPlugin . '_wantedVariables')) {
                            $aPluginVars = [];
                            $aPluginVars = call_user_func('recipients_' . $sPlugin . '_wantedVariables');

                            foreach ($aPluginVars as $sPluginVar) {
                                // Replace tags in text message
                                $this->_replaceTag($sMessageText, false, $sPluginVar, ":: " . $sPlugin . ": " . $sPluginVar . " ::");
                                // Replace tags in html message
                                if ($isHTML) {
                                    $this->_replaceTag($sMessageHTML, true, $sPluginVar, ":: " . $sPlugin . ": " . $sPluginVar . " ::");
                                }
                            }
                        }
                    }
                }
            } else {
                setSystemProperty('newsletter', 'newsletter-recipients-plugin', 'false');
            }
        }

        if (!isValidMail($sEMail) || cString::toLowerCase($sEMail) == 'sysadmin@ihresite.de') {
            // No valid destination mail address specified
            if (cRegistry::getBackendSessionId()) { // Use i18n only in backend
                $sError = i18n("Newsletter to %s could not be sent: No valid e-mail address", "newsletter");
            } else {
                $sError = "Newsletter to %s could not be sent: No valid e-mail address";
            }
            $this->_sError = $name . " (" . $sEMail . "): " . sprintf($sError, $sEMail);
            return false;
        } else {
            if ($isHTML) {
                $body = $sMessageHTML;
            } else {
                $body = $sMessageText . "\n\n";
            }
            if ($isHTML) {
                $contentType = 'text/html';
            } else {
                $contentType = 'text/plain';
            }

            try {
                $mailer = new cMailer();
            } catch (cInvalidArgumentException $e) {
                $this->_sError = $e->getMessage();
                return false;
            }

            $message = Swift_Message::newInstance($sSubject, $body, $contentType, $sEncoding);
            $message->setFrom($sFrom, $sFromName);
            $message->setTo($sEMail);
            $result = $mailer->send($message);

            if (!$result) {
                // Use i18n only in backend
                if (cRegistry::getBackendSessionId()) {
                    $sError = i18n("Newsletter to %s could not be sent", "newsletter");
                } else {
                    $sError = "Newsletter to %s could not be sent";
                }
                $this->_sError = $name . " (" . $sEMail . "): " . sprintf($sError, $sEMail);
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
     * @param int $categoryArticleId idcatart of newsletter handler article
     * @param bool $recipientId If specified, newsletter recipient id, ignored, if group specified
     * @param bool $recipientGroupId If specified, newsletter recipient group id
     * @param array $aSendRcps As reference: Filled with a list of succesfull recipients
     * @param string $sEncoding Message (and header) encoding, e.g. iso-8859-1
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function sendDirect(
        $categoryArticleId,
        $recipientId = false,
        $recipientGroupId = false,
        &$aSendRcps = [],
        $sEncoding = 'iso-8859-1'
    ): bool {
        global $recipient;

        $languageId = cRegistry::getLanguageId();

        // Initialization
        $aMessages = [];

        /** @var PiNewsletter $plugin */
        $plugin = cRegistry::getAppVar('pluginNewsletter');
        $sFormatDate = $plugin->getDateFormat($this->get('idlang'));
        $sFormatTime = $plugin->getTimeFormat($this->get('idlang'));

        $sPath = cRegistry::getFrontendUrl() . "front_content.php?changelang=" . $languageId . "&idcatart=" . $categoryArticleId . "&";

        // Get newsletter data
        $sFrom = $this->get('newsfrom');
        $sFromName = $this->get('newsfromname');
        if ($sFromName == '') {
            $sFromName = $sFrom;
        }
        $sSubject = $this->get('subject');
        $sMessageText = $this->get('message');

        $isHTML = false;
        if ($this->get('type') == "html") {
            $sMessageHTML = $this->getHTMLMessage();

            if ($sMessageHTML === false) {
                // There was a problem getting the html message (maybe article
                // deleted). Exit with error instead of sending as text message only

                if (cRegistry::getBackendSessionId()) { // Use i18n only in backend
                    $sError = i18n("Newsletter could not be sent: No html message available", "newsletter");
                } else {
                    $sError = "Newsletter could not be sent: No html message available";
                }
                $this->_sError = $sError;
                return false;
            } else {
                $isHTML = true;
            }
        }

        // Preventing double lines in mail, you may wish to disable this function on windows servers
        if (!getSystemProperty('newsletter', 'disable-rn-replacement')) {
            $sMessageText = str_replace("\r\n", "\n", $sMessageText);
        }

        // Single replacements
        // Replace message tags (text message)
        $this->_replaceTag($sMessageText, false, "date", cDate::formatToDate($sFormatDate));
        $this->_replaceTag($sMessageText, false, "time", cDate::formatToDate($sFormatTime));

        // Replace message tags (html message)
        if ($isHTML) {
            $this->_replaceTag($sMessageHTML, true, "date", cDate::formatToDate($sFormatDate));
            $this->_replaceTag($sMessageHTML, true, "time", cDate::formatToDate($sFormatTime));
        }

        // Enabling plugin interface
        if (getSystemProperty('newsletter', 'newsletter-recipients-plugin') == 'true') {
            $bPluginEnabled = true;
            $aPlugins = [];

            if (cHasPlugins('recipients')) {
                cIncludePlugins('recipients');
                $cfg = cRegistry::getConfig();
                foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                    if (function_exists('recipients_' . $sPlugin . '_wantedVariables')) {
                        $aPlugins[$sPlugin] = call_user_func('recipients_' . $sPlugin . '_wantedVariables');
                    }
                }
            }
        } else {
            setSystemProperty('newsletter', 'newsletter-recipients-plugin', 'false');
            $bPluginEnabled = false;
        }

        $aRecipients = [];
        if ($recipientGroupId !== false) {
            $oGroupMembers = new NewsletterRecipientGroupMemberCollection;
            $aRecipients = $oGroupMembers->getRecipientsInGroup($recipientGroupId, false);
        } elseif ($recipientId !== false) {
            $aRecipients[] = $recipientId;
        }

        $contenido = cRegistry::getBackendSessionId();

        $iCount = count($aRecipients);
        if ($iCount > 0) {
            $this->_replaceTag($sMessageText, false, "number", $iCount);

            // Replace message tags (html message)
            if ($isHTML) {
                $this->_replaceTag($sMessageHTML, true, "number", $iCount);
            }

            foreach ($aRecipients as $iID) {
                $sRcpMsgText = $sMessageText;
                $sRcpMsgHTML = $sMessageHTML;

                // Don't change name of $recipient variable as it is used in plugins!
                $recipient = new NewsletterRecipient;
                $recipient->loadByPrimaryKey($iID);

                $sEMail = $recipient->get('email');
                $name = $recipient->get('name');
                if (empty ($name)) {
                    $name = $sEMail;
                }
                $sKey = $recipient->get('hash');

                $bSendHTML = false;
                if ($recipient->get('news_type') == 1) {
                    $bSendHTML = true; // Recipient accepts html newsletter
                }

                $this->_replaceTag($sRcpMsgText, false, "name", $name);
                $this->_replaceTag($sRcpMsgText, false, "unsubscribe", $sPath . "unsubscribe=" . $sKey);
                $this->_replaceTag($sRcpMsgText, false, "change", $sPath . "change=" . $sKey);
                $this->_replaceTag($sRcpMsgText, false, "stop", $sPath . "stop=" . $sKey);
                $this->_replaceTag($sRcpMsgText, false, "goon", $sPath . "goon=" . $sKey);

                // Replace message tags (html message)
                if ($isHTML && $bSendHTML) {
                    $this->_replaceTag($sRcpMsgHTML, true, "name", $name);
                    $this->_replaceTag($sRcpMsgHTML, true, "unsubscribe", $sPath . "unsubscribe=" . $sKey);
                    $this->_replaceTag($sRcpMsgHTML, true, "change", $sPath . "change=" . $sKey);
                    $this->_replaceTag($sRcpMsgHTML, true, "stop", $sPath . "stop=" . $sKey);
                    $this->_replaceTag($sRcpMsgHTML, true, "goon", $sPath . "goon=" . $sKey);
                }

                if ($bPluginEnabled) {
                    foreach ($aPlugins as $sPlugin => $aPluginVar) {
                        foreach ($aPluginVar as $sPluginVar) {
                            // Replace tags in text message
                            $this->_replaceTag($sRcpMsgText, false, $sPluginVar, call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar));
                            // Replace tags in html message
                            if ($isHTML && $bSendHTML) {
                                $this->_replaceTag($sRcpMsgHTML, true, $sPluginVar, call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar));
                            }
                        }
                    }
                }

                if (cString::getStringLength($sKey) != 30) { // Prevents sending without having a key
                    if ($contenido) { // Use i18n only in backend
                        $sError = i18n("Newsletter to %s could not be sent: Recipient has an incompatible or empty key", "newsletter");
                    } else {
                        $sError = "Newsletter to %s could not be sent: Recipient has an incompatible or empty key";
                    }
                    $aMessages[] = $name . " (" . $sEMail . "): " . sprintf($sError, $sEMail);
                } elseif (!isValidMail($sEMail)) {
                    if ($contenido) { // Use i18n only in backend
                        $sError = i18n("Newsletter to %s could not be sent: No valid e-mail address specified", "newsletter");
                    } else {
                        $sError = "Newsletter to %s could not be sent: No valid e-mail address specified";
                    }
                    $aMessages[] = $name . " (" . $sEMail . "): " . sprintf($sError, $sEMail);
                } else {
                    if ($isHTML && $bSendHTML) {
                        $body = $sRcpMsgHTML;
                    } else {
                        $body = $sRcpMsgText . "\n\n";
                    }

                    if ($isHTML && $bSendHTML) {
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
                        $aSendRcps[] = $name . " (" . $sEMail . ")";
                    } else {
                        if ($contenido) { // Use i18n only in backend
                            $sError = i18n("Newsletter to %s could not be sent", "newsletter");
                        } else {
                            $sError = "Newsletter to %s could not be sent";
                        }
                        $aMessages[] = $name . " (" . $sEMail . "): " . sprintf($sError, $sEMail);
                    }
                }
            }
        } else {
            if ($contenido) { // Use i18n only in backend
                $sError = i18n("No recipient with specified recipient/group id %s/%s found", "newsletter");
            } else {
                $sError = "No recipient with specified recipient/group id %s/%s found";
            }
            $aMessages[] = sprintf($sError, $recipientId, $recipientGroupId);
        }

        if (count($aMessages) > 0) {
            $this->_sError = implode("<br>", $aMessages);
            return false;
        } else {
            return true;
        }
    }
}
