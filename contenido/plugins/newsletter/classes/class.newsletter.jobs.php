<?php
/**
 * This file contains the Collection management class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Collection management class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @method NewsletterJob createNewItem
 * @method NewsletterJob|bool next
 */
class NewsletterJobCollection extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["news_jobs"], "idnewsjob");
        $this->_setItemClass("NewsletterJob");
    }

    /**
     * Creates a newsletter job
     *
     * @param        $iIDNews
     * @param        $iIDCatArt
     * @param string $sName
     *
     * @return bool|Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($iIDNews, $iIDCatArt, $sName = "") {
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $auth = cRegistry::getAuth();

        $oNewsletter = new Newsletter();
        if ($oNewsletter->loadByPrimaryKey($iIDNews)) {
            $iIDNews = cSecurity::toInteger($iIDNews);
            $iIDCatArt = cSecurity::toInteger($iIDCatArt);
            $lang = cSecurity::toInteger($lang);
            $client = cSecurity::toInteger($client);
            $sName = $this->escape($sName);

            $oItem = $this->createNewItem();

            $oItem->set("idnews", $iIDNews);
            $oItem->set("idclient", $client);
            $oItem->set("idlang", $lang);

            if ($sName == "") {
                $oItem->set("name", $oNewsletter->get("name"));
            } else {
                $oItem->set("name", $sName);
            }
            $oItem->set("type", $oNewsletter->get("type"));
            $oItem->set("use_cronjob", $oNewsletter->get("use_cronjob"));

            $oLang = new cApiLanguage($lang);
            $oItem->set("encoding", $oLang->get("encoding"));
            unset($oLang);
            $oItem->set("idart", $oNewsletter->get("idart"));
            $oItem->set("subject", $oNewsletter->get("subject"));

            // Precompile messages
            $sPath = cRegistry::getFrontendUrl() . "front_content.php?changelang=" . $lang . "&idcatart=" . $iIDCatArt . "&";

            $sMessageText = $oNewsletter->get("message");

            // Preventing double lines in mail, you may wish to disable this
            // function on windows servers
            if (!getSystemProperty("newsletter", "disable-rn-replacement")) {
                $sMessageText = str_replace("\r\n", "\n", $sMessageText);
            }

            $oNewsletter->_replaceTag($sMessageText, false, "unsubscribe", $sPath . "unsubscribe={KEY}");
            $oNewsletter->_replaceTag($sMessageText, false, "change", $sPath . "change={KEY}");
            $oNewsletter->_replaceTag($sMessageText, false, "stop", $sPath . "stop={KEY}");
            $oNewsletter->_replaceTag($sMessageText, false, "goon", $sPath . "goon={KEY}");

            $oItem->set("message_text", $sMessageText);

            if ($oNewsletter->get("type") == "text") {
                // Text newsletter, no html message
                $sMessageHTML = "";
            } else {
                // HTML newsletter, get article content
                $sMessageHTML = $oNewsletter->getHTMLMessage();

                if ($sMessageHTML) {
                    $oNewsletter->_replaceTag($sMessageHTML, true, "name", "MAIL_NAME");
                    $oNewsletter->_replaceTag($sMessageHTML, true, "number", "MAIL_NUMBER");
                    $oNewsletter->_replaceTag($sMessageHTML, true, "date", "MAIL_DATE");
                    $oNewsletter->_replaceTag($sMessageHTML, true, "time", "MAIL_TIME");

                    $oNewsletter->_replaceTag($sMessageHTML, true, "unsubscribe", $sPath . "unsubscribe={KEY}");
                    $oNewsletter->_replaceTag($sMessageHTML, true, "change", $sPath . "change={KEY}");
                    $oNewsletter->_replaceTag($sMessageHTML, true, "stop", $sPath . "stop={KEY}");
                    $oNewsletter->_replaceTag($sMessageHTML, true, "goon", $sPath . "goon={KEY}");

                    // Replace plugin tags by simple MAIL_ tags
                    if (getSystemProperty("newsletter", "newsletter-recipients-plugin") == "true") {
                        if (cHasPlugins('recipients')) {
                            cIncludePlugins('recipients');
                            foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                                if (function_exists('recipients_' . $sPlugin . '_wantedVariables')) {
                                    $wantVariables = call_user_func('recipients_' . $sPlugin . '_wantedVariables');
                                    if (is_array($wantVariables)) {
                                        foreach ($wantVariables as $sPluginVar) {
                                            $oNewsletter->_replaceTag($sMessageHTML, true, $sPluginVar, "MAIL_" . cString::toUpperCase($sPluginVar));
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // There was a problem getting html message (maybe article
                    // deleted)
                    // Cancel job generation
                    $this->delete($oItem->get($oItem->getPrimaryKeyName()));
                    return false;
                }
            }

            $oItem->set("message_html", $sMessageHTML);

            $oItem->set("newsfrom", $oNewsletter->get("newsfrom"));
            if ($oNewsletter->get("newsfromname") == "") {
                $oItem->set("newsfromname", $oNewsletter->get("newsfrom"));
            } else {
                $oItem->set("newsfromname", $oNewsletter->get("newsfromname"));
            }
            $oItem->set("newsdate", date("Y-m-d H:i:s"), false); // $oNewsletter->get("newsdate"));
            $oItem->set("dispatch", $oNewsletter->get("dispatch"));
            $oItem->set("dispatch_count", $oNewsletter->get("dispatch_count"));
            $oItem->set("dispatch_delay", $oNewsletter->get("dispatch_delay"));

            // Store "send to" info in serialized array (just info)
            $aSendInfo = [];
            $aSendInfo[] = $oNewsletter->get("send_to");

            switch ($oNewsletter->get("send_to")) {
                case "selection":
                    $oGroups = new NewsletterRecipientGroupCollection();
                    $oGroups->setWhere("idnewsgroup", unserialize($oNewsletter->get("send_ids")), "IN");
                    $oGroups->setOrder("groupname");
                    $oGroups->query();
                    // oGroups->select("idnewsgroup IN ('" . implode("','",
                    // unserialize($oNewsletter->get("send_ids"))) . "')", "",
                    // "groupname");

                    while (($oGroup = $oGroups->next()) !== false) {
                        $aSendInfo[] = $oGroup->get("groupname");
                    }

                    unset($oGroup);
                    unset($oGroups);
                    break;
                case "single":
                    if (is_numeric($oNewsletter->get("send_ids"))) {
                        $oRcp = new NewsletterRecipient($oNewsletter->get("send_ids"));

                        if ($oRcp->get("name") == "") {
                            $aSendInfo[] = $oRcp->get("email");
                        } else {
                            $aSendInfo[] = $oRcp->get("name");
                        }
                        $aSendInfo[] = $oRcp->get("email");

                        unset($oRcp);
                    }
                    break;
                default:
            }
            $oItem->set("send_to", serialize($aSendInfo), false);

            $oItem->set("created", date('Y-m-d H:i:s'), false);
            $oItem->set("author", $auth->auth["uid"]);
            $oItem->set("authorname", $auth->auth["uname"]);
            unset($oNewsletter); // Not needed anymore

            // Adds log items for all recipients and returns recipient count
            $oLogs = new NewsletterLogCollection();
            $iRecipientCount = $oLogs->initializeJob($oItem->get($oItem->getPrimaryKeyName()), $iIDNews);
            unset($oLogs);

            // fallback. there's no need to create a newsletter job if no user is selected
            if ($iRecipientCount == 0 || !is_int($iRecipientCount)) {
                return false;
            }

            $oItem->set("rcpcount", $iRecipientCount);
            $oItem->set("sendcount", 0);
            $oItem->set("status", 1); // Waiting for sending; note, that status
                                      // will be set to 9, if $iRecipientCount =
                                      // 0 in store() method

            $oItem->store();

            return $oItem;
        } else {
            return false;
        }
    }

    /**
     * Overridden delete method to remove job details (logs) from newsletter
     * logs table
     * before deleting newsletter job
     *
     * @param $iItemID int specifies the frontend user group
     */
    public function delete($iItemID) {
        $oLogs = new NewsletterLogCollection();
        $oLogs->delete($iItemID);

        parent::delete($iItemID);
    }

}

/**
 * Single NewsletterJob Item
 */
class NewsletterJob extends Item {
    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["news_jobs"], "idnewsjob");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * @return int
     * @throws cDbException
     * @throws cException
     */
    public function runJob() {
        global $recipient;

        $cfg = cRegistry::getConfig();

        $iCount = 0;
        if ($this->get("status") == 2) {
            // Job is currently running, check start time and restart if
            // started 5 minutes ago
            $dStart = strtotime($this->get("started"));
            $dNow = time();

            if (($dNow - $dStart) > (5 * 60)) {
                $this->set("status", 1);
                $this->set("started", "0000-00-00 00:00:00", false);

                $oLogs = new NewsletterLogCollection();
                $oLogs->setWhere("idnewsjob", $this->get($this->getPrimaryKeyName()));
                $oLogs->setWhere("status", "sending");
                $oLogs->query();

                while (($oLog = $oLogs->next()) !== false) {
                    $oLog->set("status", "error (sending)");
                    $oLog->store();
                }
            }
        }

        if ($this->get("status") == 1) {
            // Job waiting for sending
            $this->set("status", 2);
            $this->set("started", date("Y-m-d H:i:s"), false);
            $this->store();

            // Initialization
            $aMessages = [];

            $oLanguage = new cApiLanguage($this->get("idlang"));
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
            $sFrom = $this->get("newsfrom");
            $sFromName = $this->get("newsfromname");
            $sSubject = $this->get("subject");
            $sMessageText = $this->get("message_text");
            $sMessageHTML = $this->get("message_html");
            $dNewsDate = strtotime($this->get("newsdate"));
            $sEncoding = $this->get("encoding");
            $bIsHTML = false;
            if ($this->get("type") == "html" && $sMessageHTML != "") {
                $bIsHTML = true;
            }

            $bDispatch = false;
            if ($this->get("dispatch") == 1) {
                $bDispatch = true;
            }

            // Single replacements
            // Replace message tags (text message)
            $sMessageText = str_replace("MAIL_DATE", strftime($sFormatDate, $dNewsDate), $sMessageText);
            $sMessageText = str_replace("MAIL_TIME", strftime($sFormatTime, $dNewsDate), $sMessageText);
            $sMessageText = str_replace("MAIL_NUMBER", $this->get("rcpcount"), $sMessageText);

            // Replace message tags (html message)
            if ($bIsHTML) {
                $sMessageHTML = str_replace("MAIL_DATE", strftime($sFormatDate, $dNewsDate), $sMessageHTML);
                $sMessageHTML = str_replace("MAIL_TIME", strftime($sFormatTime, $dNewsDate), $sMessageHTML);
                $sMessageHTML = str_replace("MAIL_NUMBER", $this->get("rcpcount"), $sMessageHTML);
            }

            // Enabling plugin interface
            $bPluginEnabled = false;
            if (getSystemProperty("newsletter", "newsletter-recipients-plugin") == "true") {
                $bPluginEnabled = true;
                $aPlugins = [];

                if (cHasPlugins('recipients')) {
                    cIncludePlugins('recipients');
                    foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                        if (function_exists('recipients_' . $sPlugin . '_wantedVariables')) {
                            $aPlugins[$sPlugin] = call_user_func('recipients_' . $sPlugin . '_wantedVariables');
                        }
                    }
                }
            }

            // Get recipients (from log table)
            if (!is_object($oLogs)) {
                $oLogs = new NewsletterLogCollection();
            } else {
                $oLogs->resetQuery();
            }
            $oLogs->setWhere("idnewsjob", $this->get($this->getPrimaryKeyName()));
            $oLogs->setWhere("status", "pending");

            if ($bDispatch) {
                $oLogs->setLimit(0, $this->get("dispatch_count"));
            }

            $oLogs->query();
            while (($oLog = $oLogs->next()) !== false) {
                $iCount++;
                $oLog->set("status", "sending");
                $oLog->store();

                $sRcpMsgText = $sMessageText;
                $sRcpMsgHTML = $sMessageHTML;

                $sKey = $oLog->get("rcphash");
                $sEMail = $oLog->get("rcpemail");

                // do not try to send a message to an invalid email address
                if (false === isValidMail($sEMail)) {
                    $oLog->set("status", "error (invalid email)");
                    $oLog->store();
                    continue;
                }

                $bSendHTML = false;
                if ($oLog->get("rcpnewstype") == 1) {
                    $bSendHTML = true; // Recipient accepts html newsletter
                }

                if (cString::getStringLength($sKey) == 30) { // Prevents sending without having a
                                           // key
                    $sRcpMsgText = str_replace("{KEY}", $sKey, $sRcpMsgText);
                    $sRcpMsgText = str_replace("MAIL_MAIL", $sEMail, $sRcpMsgText);
                    $sRcpMsgText = str_replace("MAIL_NAME", $oLog->get("rcpname"), $sRcpMsgText);

                    // Replace message tags (html message)
                    if ($bIsHTML && $bSendHTML) {
                        $sRcpMsgHTML = str_replace("{KEY}", $sKey, $sRcpMsgHTML);
                        $sRcpMsgHTML = str_replace("MAIL_MAIL", $sEMail, $sRcpMsgHTML);
                        $sRcpMsgHTML = str_replace("MAIL_NAME", $oLog->get("rcpname"), $sRcpMsgHTML);
                    }

                    if ($bPluginEnabled) {
                        // Don't change name of $recipient variable as it is
                        // used in plugins!
                        $recipient = new NewsletterRecipient();
                        $recipient->loadByPrimaryKey($oLog->get("idnewsrcp"));

                        foreach ($aPlugins as $sPlugin => $aPluginVar) {
                            foreach ($aPluginVar as $sPluginVar) {
                                // Replace tags in text message
                                $sRcpMsgText = str_replace("MAIL_" . cString::toUpperCase($sPluginVar), call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar), $sRcpMsgText);

                                // Replace tags in html message
                                if ($bIsHTML && $bSendHTML) {
                                    $sRcpMsgHTML = str_replace("MAIL_" . cString::toUpperCase($sPluginVar), call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar), $sRcpMsgHTML);
                                }
                            }
                        }
                        unset($recipient);
                    }

                    $mailer = new cMailer();
                    $mailer->setCharset($sEncoding);

                    $to = $sEMail;
                    if ($bIsHTML && $bSendHTML) {
                        $body = $sRcpMsgHTML;
                    } else {
                        $body = $sRcpMsgText . "\n\n";
                    }
                    $contentType = 'text/plain';
                    if ($bIsHTML && $bSendHTML) {
                        $contentType = 'text/html';
                    }

                    try {
                        // this code can throw exceptions like Swift_RfcComplianceException
                        $message = Swift_Message::newInstance($sSubject, $body, $contentType, $sEncoding);
                        $message->setFrom($sFrom, $sFromName);
                        $message->setTo($to);

                        // send the email
                        $result = $mailer->send($message);
                    } catch (Exception $e) {
                        $result = false;
                    }

                    if ($result) {
                        $oLog->set("status", "successful");
                        $oLog->set("sent", date("Y-m-d H:i:s"), false);
                    } else {
                        $oLog->set("status", "error (sending)");
                    }
                } else {
                    $oLog->set("status", "error (key)");
                }
                $oLog->store();
            }

            $this->set("sendcount", $this->get("sendcount") + $iCount);

            if ($iCount == 0 || !$bDispatch) {
                // No recipients remaining, job finished
                $this->set("status", 9);
                $this->set("finished", date("Y-m-d H:i:s"), false);
            } elseif ($bDispatch) {
                // Check, if there are recipients remaining - stops job faster
                $oLogs->resetQuery();
                $oLogs->setWhere("idnewsjob", $this->get($this->getPrimaryKeyName()));
                $oLogs->setWhere("status", "pending");
                $oLogs->setLimit(0, $this->get("dispatch_count"));
                $oLogs->query();

                If ($oLogs->next()) {
                    // Remaining recipients found, set job back to pending
                    $this->set("status", 1);
                    $this->set("started", "0000-00-00 00:00:00", false);
                } else {
                    // No remaining recipients, job finished
                    $this->set("status", 9);
                    $this->set("finished", date("Y-m-d H:i:s"), false);
                }
            } else {
                // Set job back to pending
                $this->set("status", 1);
                $this->set("started", "0000-00-00 00:00:00", false);
            }
            $this->store();
        }

        return $iCount;
    }

    /**
     * Overridden store() method to set status to finished if rcpcount is 0
     */
    public function store() {
        if ($this->get("rcpcount") == 0) {
            // No recipients, job finished
            $this->set("status", 9);
            if ($this->get("started") == "0000-00-00 00:00:00") {
                $this->set("started", date("Y-m-d H:i:s"), false);
            }
            $this->set("finished", date("Y-m-d H:i:s"), false);
        }

        return parent::store();
    }

}
