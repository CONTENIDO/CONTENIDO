<?php
/**
 * This file contains the Collection management class.
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
 * Collection management class.
 *
 * @package Plugin
 * @subpackage Newsletter
 */
class NewsletterJobCollection extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["news_jobs"], "idnewsjob");
        $this->_setItemClass("NewsletterJob");
    }

    /**
     * Creates a newsletter job
     *
     * @param $name string Specifies the name of the newsletter, the same name
     *            may be used more than once
     * @param $idnews integer Newsletter id
     */
    public function create($iIDNews, $iIDCatArt, $sName = "") {
        global $client, $lang, $cfg, $cfgClient, $auth;

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
                        if (is_array($cfg['plugins']['recipients'])) {
                            foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                                plugin_include("recipients", $sPlugin . "/" . $sPlugin . ".php");
                                if (function_exists("recipients_" . $sPlugin . "_wantedVariables")) {
                                    $aPluginVars = array();
                                    $aPluginVars = call_user_func("recipients_" . $sPlugin . "_wantedVariables");

                                    foreach ($aPluginVars as $sPluginVar) {
                                        $oNewsletter->_replaceTag($sMessageHTML, true, $sPluginVar, "MAIL_" . strtoupper($sPluginVar));
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // There was a problem getting html message (maybe article
                    // deleted)
                    // Cancel job generation
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
            $aSendInfo = array();
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
            $iRecipientCount = $oLogs->initializeJob($oItem->get($oItem->primaryKey), $iIDNews);
            unset($oLogs);

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
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg["tab"]["news_jobs"], "idnewsjob");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    public function runJob() {
        global $cfg, $recipient;

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
                $oLogs->setWhere("idnewsjob", $this->get($this->primaryKey));
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
            $aMessages = array();

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
                $aPlugins = array();

                if (is_array($cfg['plugins']['recipients'])) {
                    foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                        plugin_include("recipients", $sPlugin . "/" . $sPlugin . ".php");
                        if (function_exists("recipients_" . $sPlugin . "_wantedVariables")) {
                            $aPlugins[$sPlugin] = call_user_func("recipients_" . $sPlugin . "_wantedVariables");
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
            $oLogs->setWhere("idnewsjob", $this->get($this->primaryKey));
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
                $bSendHTML = false;
                if ($oLog->get("rcpnewstype") == 1) {
                    $bSendHTML = true; // Recipient accepts html newsletter
                }

                if (strlen($sKey) == 30) { // Prevents sending without having a
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
                                $sRcpMsgText = str_replace("MAIL_" . strtoupper($sPluginVar), call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar), $sRcpMsgText);

                                // Replace tags in html message
                                if ($bIsHTML && $bSendHTML) {
                                    $sRcpMsgHTML = str_replace("MAIL_" . strtoupper($sPluginVar), call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar), $sRcpMsgHTML);
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

                    $message = Swift_Message::newInstance($sSubject, $body, $contentType, $sEncoding);
                    $message->setFrom($sFrom, $sFromName);
                    $message->setTo($to);
                    $result = $mailer->send($message);

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
            } else if ($bDispatch) {
                // Check, if there are recipients remaining - stops job faster
                $oLogs->resetQuery();
                $oLogs->setWhere("idnewsjob", $this->get($this->primaryKey));
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
     * Overriden store() method to set status to finished if rcpcount is 0
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
