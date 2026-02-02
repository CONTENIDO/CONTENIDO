<?php

/**
 * This file contains the Collection management class.
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
 * Collection management class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @extends ItemCollection<NewsletterJob>
 */
class NewsletterJobCollection extends ItemCollection
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
        parent::__construct(cDb::getTableName('news_jobs'), 'idnewsjob');
        $this->_setItemClass('NewsletterJob');
    }

    /**
     * Creates a newsletter job
     *
     * @param int $newsId
     * @param int $categoryArticleId
     * @param string $name
     * @return NewsletterJob|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($newsId, $categoryArticleId, $name = '')
    {
        $cfg = cRegistry::getConfig();
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();
        $categoryArticleId = cSecurity::toInteger($categoryArticleId);
        $auth = cRegistry::getAuth();

        $oNewsletter = new Newsletter();
        if ($oNewsletter->loadByPrimaryKey($newsId)) {
            $newsId = cSecurity::toInteger($newsId);
            $name = $this->escape($name);

            $oItem = $this->createNewItem();

            $oItem->set('idnews', $newsId);
            $oItem->set('idclient', $clientId);
            $oItem->set('idlang', $languageId);

            if ($name == '') {
                $oItem->set('name', $oNewsletter->get('name'));
            } else {
                $oItem->set('name', $name);
            }
            $oItem->set('type', $oNewsletter->get('type'));
            $oItem->set('use_cronjob', $oNewsletter->get('use_cronjob'));

            $oLang = new cApiLanguage($languageId);
            $oItem->set('encoding', $oLang->get('encoding'));
            unset($oLang);
            $oItem->set('idart', $oNewsletter->get('idart'));
            $oItem->set('subject', $oNewsletter->get('subject'));

            // Precompile messages
            $path = cRegistry::getFrontendUrl() . "front_content.php?changelang=$languageId&idcatart=$categoryArticleId&";

            $messageText = $oNewsletter->get('message') ?? '';

            // Preventing double lines in mail, you may wish to disable this
            // function on windows servers
            if (!getSystemProperty('newsletter', 'disable-rn-replacement')) {
                $messageText = str_replace("\r\n", "\n", $messageText);
            }

            $oNewsletter->_replaceTag($messageText, false, "unsubscribe", $path . "unsubscribe={KEY}");
            $oNewsletter->_replaceTag($messageText, false, "change", $path . "change={KEY}");
            $oNewsletter->_replaceTag($messageText, false, "stop", $path . "stop={KEY}");
            $oNewsletter->_replaceTag($messageText, false, "goon", $path . "goon={KEY}");

            $oItem->set('message_text', $messageText);

            if ($oNewsletter->get('type') == "text") {
                // Text newsletter, no html message
                $messageHTML = '';
            } else {
                // HTML newsletter, get article content
                $messageHTML = $oNewsletter->getHTMLMessage();

                if ($messageHTML) {
                    $oNewsletter->_replaceTag($messageHTML, true, "name", "MAIL_NAME");
                    $oNewsletter->_replaceTag($messageHTML, true, "number", "MAIL_NUMBER");
                    $oNewsletter->_replaceTag($messageHTML, true, "date", "MAIL_DATE");
                    $oNewsletter->_replaceTag($messageHTML, true, "time", "MAIL_TIME");

                    $oNewsletter->_replaceTag($messageHTML, true, "unsubscribe", $path . "unsubscribe={KEY}");
                    $oNewsletter->_replaceTag($messageHTML, true, "change", $path . "change={KEY}");
                    $oNewsletter->_replaceTag($messageHTML, true, "stop", $path . "stop={KEY}");
                    $oNewsletter->_replaceTag($messageHTML, true, "goon", $path . "goon={KEY}");

                    // Replace plugin tags by simple MAIL_ tags
                    if (getSystemProperty('newsletter', 'newsletter-recipients-plugin') == 'true') {
                        if (cHasPlugins('recipients')) {
                            cIncludePlugins('recipients');
                            foreach ($cfg['plugins']['recipients'] as $sPlugin) {
                                if (function_exists('recipients_' . $sPlugin . '_wantedVariables')) {
                                    $wantVariables = call_user_func('recipients_' . $sPlugin . '_wantedVariables');
                                    if (is_array($wantVariables)) {
                                        foreach ($wantVariables as $sPluginVar) {
                                            $oNewsletter->_replaceTag($messageHTML, true, $sPluginVar, "MAIL_" . cString::toUpperCase($sPluginVar));
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

            $oItem->set('message_html', $messageHTML);

            $oItem->set('newsfrom', $oNewsletter->get('newsfrom'));
            if ($oNewsletter->get('newsfromname') == '') {
                $oItem->set('newsfromname', $oNewsletter->get('newsfrom'));
            } else {
                $oItem->set('newsfromname', $oNewsletter->get('newsfromname'));
            }
            $oItem->set('newsdate', date('Y-m-d H:i:s'), false); // $oNewsletter->get('newsdate'));
            $oItem->set('dispatch', $oNewsletter->get('dispatch'));
            $oItem->set('dispatch_count', $oNewsletter->get('dispatch_count'));
            $oItem->set('dispatch_delay', $oNewsletter->get('dispatch_delay'));

            // Store "send to" info in serialized array (just info)
            $sendInfo = [
                $oNewsletter->get('send_to')
            ];

            switch ($oNewsletter->get('send_to')) {
                case "selection":
                    $oGroups = new NewsletterRecipientGroupCollection();
                    $oGroups->setWhere('idnewsgroup', unserialize($oNewsletter->get('send_ids')), "IN");
                    $oGroups->setOrder("groupname");
                    $oGroups->query();
                    // oGroups->select("idnewsgroup IN ('" . implode("','",
                    // unserialize($oNewsletter->get('send_ids'))) . "')", "",
                    // "groupname");

                    while ($oGroup = $oGroups->next()) {
                        $sendInfo[] = $oGroup->get('groupname');
                    }

                    unset($oGroup);
                    unset($oGroups);
                    break;
                case "single":
                    if (is_numeric($oNewsletter->get('send_ids'))) {
                        $oRcp = new NewsletterRecipient($oNewsletter->get('send_ids'));

                        if ($oRcp->get('name') == '') {
                            $sendInfo[] = $oRcp->get('email');
                        } else {
                            $sendInfo[] = $oRcp->get('name');
                        }
                        $sendInfo[] = $oRcp->get('email');

                        unset($oRcp);
                    }
                    break;
                default:
            }
            $oItem->set('send_to', serialize($sendInfo), false);

            $oItem->set('created', date('Y-m-d H:i:s'), false);
            $oItem->set('author', $auth->getUserId());
            $oItem->set('authorname', $auth->getUsername());
            unset($oNewsletter); // Not needed anymore

            // Adds log items for all recipients and returns recipient count
            $oLogs = new NewsletterLogCollection();
            $iRecipientCount = $oLogs->initializeJob($oItem->get($oItem->getPrimaryKeyName()), $newsId);
            unset($oLogs);

            // fallback. there's no need to create a newsletter job if no user is selected
            if ($iRecipientCount == 0 || !is_int($iRecipientCount)) {
                return false;
            }

            $oItem->set('rcpcount', $iRecipientCount);
            $oItem->set('sendcount', 0);
            $oItem->set('status', 1); // Waiting for sending; note, that status
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
     * logs table before deleting newsletter job
     *
     * @inheritDoc
     * @param int $id The newsletter job id.
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function delete($id)
    {
        (new NewsletterLogCollection())->delete($id);

        return parent::delete($id);
    }

}

/**
 * Single NewsletterJob Item
 */
class NewsletterJob extends Item
{

    /**
     * Constructor Function
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('news_jobs'), 'idnewsjob');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * @return int
     * @throws cDbException|cException
     */
    public function runJob(): int
    {
        global $recipient;

        $cfg = cRegistry::getConfig();

        $oLogs = null;
        $iCount = 0;
        if ($this->get('status') == 2) {
            // Job is currently running, check start time and restart if
            // started 5 minutes ago
            $dStart = strtotime($this->get('started'));
            $dNow = time();

            if (($dNow - $dStart) > (5 * 60)) {
                $this->set('status', 1);
                $this->set('started', '0000-00-00 00:00:00', false);

                $oLogs = new NewsletterLogCollection();
                $oLogs->setWhere('idnewsjob', $this->get($this->getPrimaryKeyName()));
                $oLogs->setWhere('status', 'sending');
                $oLogs->query();

                while ($oLog = $oLogs->next()) {
                    $oLog->set('status', "error (sending)");
                    $oLog->store();
                }
            }
        }

        if ($this->get('status') == 1) {
            // Job waiting for sending
            $this->set('status', 2);
            $this->set('started', date('Y-m-d H:i:s'), false);
            $this->store();

            /** @var PiNewsletter $plugin */
            $plugin = cRegistry::getAppVar('pluginNewsletter');
            $sFormatDate = $plugin->getDateFormat($this->get('idlang'));
            $sFormatTime = $plugin->getTimeFormat($this->get('idlang'));

            // Get newsletter data
            $sFrom = $this->get('newsfrom');
            $sFromName = $this->get('newsfromname');
            $sSubject = $this->get('subject');
            $messageText = $this->get('message_text');
            $messageHTML = $this->get('message_html');
            $dNewsDate = strtotime($this->get('newsdate'));
            $sEncoding = $this->get('encoding');
            $bIsHTML = false;
            if ($this->get('type') == "html" && $messageHTML != '') {
                $bIsHTML = true;
            }

            $bDispatch = false;
            if ($this->get('dispatch') == 1) {
                $bDispatch = true;
            }

            // Single replacements
            // Replace message tags (text message)
            $messageText = str_replace("MAIL_DATE", cDate::formatToDate($sFormatDate, $dNewsDate), $messageText);
            $messageText = str_replace("MAIL_TIME", cDate::formatToDate($sFormatTime, $dNewsDate), $messageText);
            $messageText = str_replace("MAIL_NUMBER", $this->get('rcpcount'), $messageText);

            // Replace message tags (html message)
            if ($bIsHTML) {
                $messageHTML = str_replace("MAIL_DATE", cDate::formatToDate($sFormatDate, $dNewsDate), $messageHTML);
                $messageHTML = str_replace("MAIL_TIME", cDate::formatToDate($sFormatTime, $dNewsDate), $messageHTML);
                $messageHTML = str_replace("MAIL_NUMBER", $this->get('rcpcount'), $messageHTML);
            }

            // Plugin interface
            $aPlugins = [];
            if (getSystemProperty('newsletter', 'newsletter-recipients-plugin') == 'true') {
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
            $oLogs->setWhere('idnewsjob', $this->get($this->getPrimaryKeyName()));
            $oLogs->setWhere('status', 'pending');

            if ($bDispatch) {
                $oLogs->setLimit(0, $this->get('dispatch_count'));
            }

            $oLogs->query();
            while ($oLog = $oLogs->next()) {
                $iCount++;
                $oLog->set('status', "sending");
                $oLog->store();

                $rcpMsgText = $messageText;
                $rcpMsgHTML = $messageHTML;

                $sKey = $oLog->get('rcphash');
                $sEMail = $oLog->get('rcpemail');

                // do not try to send a message to an invalid email address
                if (!isValidMail($sEMail)) {
                    $oLog->set('status', "error (invalid email)");
                    $oLog->store();
                    continue;
                }

                $bSendHTML = false;
                if ($oLog->get('rcpnewstype') == 1) {
                    $bSendHTML = true; // Recipient accepts html newsletter
                }

                if (cString::getStringLength($sKey) == 30) { // Prevents sending without having a
                    // key
                    $rcpMsgText = str_replace("{KEY}", $sKey, $rcpMsgText);
                    $rcpMsgText = str_replace("MAIL_MAIL", $sEMail, $rcpMsgText);
                    $rcpMsgText = str_replace("MAIL_NAME", $oLog->get('rcpname'), $rcpMsgText);

                    // Replace message tags (html message)
                    if ($bIsHTML && $bSendHTML) {
                        $rcpMsgHTML = str_replace("{KEY}", $sKey, $rcpMsgHTML);
                        $rcpMsgHTML = str_replace("MAIL_MAIL", $sEMail, $rcpMsgHTML);
                        $rcpMsgHTML = str_replace("MAIL_NAME", $oLog->get('rcpname'), $rcpMsgHTML);
                    }

                    if (count($aPlugins)) {
                        // Don't change name of $recipient variable as it is used in plugins!
                        $recipient = new NewsletterRecipient();
                        $recipient->loadByPrimaryKey($oLog->get('idnewsrcp'));

                        foreach ($aPlugins as $sPlugin => $aPluginVar) {
                            foreach ($aPluginVar as $sPluginVar) {
                                // Replace tags in text message
                                $rcpMsgText = str_replace("MAIL_" . cString::toUpperCase($sPluginVar), call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar), $rcpMsgText);

                                // Replace tags in html message
                                if ($bIsHTML && $bSendHTML) {
                                    $rcpMsgHTML = str_replace("MAIL_" . cString::toUpperCase($sPluginVar), call_user_func("recipients_" . $sPlugin . "_getvalue", $sPluginVar), $rcpMsgHTML);
                                }
                            }
                        }
                        unset($recipient);
                    }

                    $mailer = new cMailer();
                    $mailer->setCharset($sEncoding);

                    $to = $sEMail;
                    if ($bIsHTML && $bSendHTML) {
                        $body = $rcpMsgHTML;
                    } else {
                        $body = $rcpMsgText . "\n\n";
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
                        $oLog->set('status', "successful");
                        $oLog->set('sent', date('Y-m-d H:i:s'), false);
                    } else {
                        $oLog->set('status', "error (sending)");
                    }
                } else {
                    $oLog->set('status', "error (key)");
                }
                $oLog->store();
            }

            $this->set('sendcount', $this->get('sendcount') + $iCount);

            if ($iCount == 0 || !$bDispatch) {
                // No recipients remaining, job finished
                $this->set('status', 9);
                $this->set('finished', date('Y-m-d H:i:s'), false);
            } elseif ($bDispatch) {
                // Check, if there are recipients remaining - stops job faster
                $oLogs->resetQuery();
                $oLogs->setWhere('idnewsjob', $this->get($this->getPrimaryKeyName()));
                $oLogs->setWhere('status', 'pending');
                $oLogs->setLimit(0, $this->get('dispatch_count'));
                $oLogs->query();

                if ($oLogs->next()) {
                    // Remaining recipients found, set job back to pending
                    $this->set('status', 1);
                    $this->set('started', '0000-00-00 00:00:00', false);
                } else {
                    // No remaining recipients, job finished
                    $this->set('status', 9);
                    $this->set('finished', date('Y-m-d H:i:s'), false);
                }
            } else {
                // Set job back to pending
                $this->set('status', 1);
                $this->set('started', '0000-00-00 00:00:00', false);
            }
            $this->store();
        }

        return $iCount;
    }

    /**
     * Overridden store() method to set status to finished if rcpcount is 0.
     *
     * @inheritDoc
     */
    public function store(): bool
    {
        if ($this->get('rcpcount') == 0) {
            // No recipients, job finished
            $this->set('status', 9);
            if ($this->get('started') == '0000-00-00 00:00:00') {
                $this->set('started', date('Y-m-d H:i:s'), false);
            }
            $this->set('finished', date('Y-m-d H:i:s'), false);
        }

        return parent::store();
    }

    /**
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idnewsjob':
            case 'idclient':
            case 'idlang':
            case 'idnews':
            case 'status':
            case 'use_cronjob':
            case 'idart':
            case 'dispatch':
            case 'dispatch_count':
            case 'dispatch_delay':
            case 'rcpcount':
            case 'sendcount':
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
            case 'idnewsjob':
            case 'idclient':
            case 'idlang':
            case 'idnews':
            case 'status':
            case 'use_cronjob':
            case 'idart':
            case 'dispatch':
            case 'dispatch_count':
            case 'dispatch_delay':
            case 'rcpcount':
            case 'sendcount':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

}
