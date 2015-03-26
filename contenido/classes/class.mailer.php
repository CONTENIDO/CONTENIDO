<?php
/**
 * This file contains the cMail class which should be used for all mail sending
 * purposes.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Rusmir Jusufovic, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// since CONTENIDO has it's own autoloader, swift_init.php is enough
// we do not need and should not use swift_required.php!
require_once 'swiftmailer/lib/swift_init.php';

/**
 * Mailer class which should be used for all mail sending purposes.
 *
 * @package Core
 * @subpackage Backend
 */
class cMailer extends Swift_Mailer {

    /**
     * The mail host name
     *
     * @var string
     */
    private $_mailHost = 'localhost';

    /**
     * The username for authentication at the host
     *
     * @var string
     */
    private $_mailUser = '';

    /**
     * The password for authentication at the host
     *
     * @var string
     */
    private $_mailPass = '';

    /**
     * The encryption method (ssl/tls).
     *
     * @var string
     */
    private $_mailEncryption = NULL;

    /**
     * The port to use at the host
     *
     * @var int
     */
    private $_mailPort = 25;

    /**
     * The mail address of the sender
     *
     * @var string
     */
    private $_mailSender = 'noreply@contenido.org';

    /**
     * The name of the sender
     *
     * @var string
     */
    private $_mailSenderName = 'CONTENIDO Backend';

    /**
     * Constructor
     *
     * @param Swift_Transport $transport [optional] the transport type
     */
    public function __construct($transport = NULL) {
        // get sender mail from system properties
        $mailSender = getSystemProperty('system', 'mail_sender');
        if (Swift_Validate::email($mailSender)) {
            $this->_mailSender = $mailSender;
        }

        // get sender name from system properties
        $mailSenderName = getSystemProperty('system', 'mail_sender_name');
        if (!empty($mailSenderName)) {
            $this->_mailSenderName = $mailSenderName;
        }

        // if a transport object has been given, use it
        if (!is_null($transport)) {
            parent::__construct($transport);
            return;
        }

        // if no transport object has been given, read system setting and create
        // one
        // get mailserver host from system properties
        $mailHost = getSystemProperty('system', 'mail_host');
        if (!empty($mailHost)) {
            $this->_mailHost = $mailHost;
        }

        // get mailserver user and pass from system properties
        $this->_mailUser = (getSystemProperty('system', 'mail_user')) ? getSystemProperty('system', 'mail_user') : '';
        $this->_mailPass = (getSystemProperty('system', 'mail_pass')) ? getSystemProperty('system', 'mail_pass') : '';

        // get mailserver encryption from system properties
        $encryptions = array(
            'tls',
            'ssl'
        );
        $mail_encryption = strtolower(getSystemProperty('system', 'mail_encryption'));
        if (in_array($mail_encryption, $encryptions)) {
            $this->_mailEncryption = $mail_encryption;
        } elseif ('1' == $mail_encryption) {
            $this->_mailEncryption = 'ssl';
        } else {
            $this->_mailEncryption = 'tls';
        }

        // get mailserver port from system properties
        if (is_numeric(getSystemProperty('system', 'mail_port'))) {
            $this->_mailPort = (int) getSystemProperty('system', 'mail_port');
        }

        // try to use SMTP
        $transport = self::constructTransport($this->_mailHost, $this->_mailPort, $this->_mailEncryption, $this->_mailUser, $this->_mailPass);
        parent::__construct($transport);
    }

    /**
     * Tries to establish an SMTP connection with the given settings.
     * If this is possible, a Swift_SmtpTransport object is returned. Otherwise
     * a simple Swift_MailTransport object is returned.
     *
     * @param string $mailHost the mail host
     * @param string $mailPort the mail port
     * @param string $mailEncryption [optional] the mail encryption
     * @param string $mailUser [optional] the mail user
     * @param string $mailPass [optional] the mail password
     * @return Swift_SmtpTransport Swift_MailTransport the transport object
     */
    public static function constructTransport($mailHost, $mailPort, $mailEncryption = NULL, $mailUser = NULL, $mailPass = NULL) {
        // try to use SMTP
        $transport = Swift_SmtpTransport::newInstance($mailHost, $mailPort, $mailEncryption);
        if (!empty($mailUser)) {
            $authHandler = new Swift_Transport_Esmtp_AuthHandler(array(
                new Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
                new Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                new Swift_Transport_Esmtp_Auth_CramMd5Authenticator()
            ));
            $authHandler->setUsername($mailUser);
            if (!empty($mailPass)) {
                $authHandler->setPassword($mailPass);
            }
            $transport->setExtensionHandlers(array(
                $authHandler
            ));
        }

        // check if SMTP usage is possible
        try {
            $transport->start();
        } catch (Swift_TransportException $e) {
            // if SMTP is not possible, simply use PHP's mail() functionality
            $transport = Swift_MailTransport::newInstance();
        }

        return $transport;
    }

    /**
     * Sets the charset of the messages which are sent with this mailer object.
     * If you want to use UTF-8, you do not need to call this method.
     *
     * @param string $charset the character encoding
     */
    public function setCharset($charset) {
        Swift_Preferences::getInstance()->setCharset($charset);
    }

    /**
     * Wrapper function for sending a mail.
     * All parameters which accept mail addresses also accept an array where
     * the key is the email address and the value is the name.
     *
     * @param string|array $from the sender of the mail, if something "empty" is
     *        given, default address from CONTENIDO system settings is used
     * @param string|array $to one or more recipient addresses
     * @param string $subject the subject of the mail
     * @param string $body [optional] the body of the mail
     * @param string|array $cc [optional] one or more recipient addresses which
     *        should get a normal copy
     * @param string|array $bcc [optional] one or more recipient addresses which
     *        should get a blind copy
     * @param string|array $replyTo [optional] address to which replies should
     *        be sent
     * @param bool $resend [optional] whether the mail is resent
     * @param string $contentType
     * @return int number of recipients to which the mail has been sent
     */
    public function sendMail($from, $to, $subject, $body = '', $cc = NULL, $bcc = NULL, $replyTo = NULL, $resend = false, $contentType = 'text/plain') {
        $message = Swift_Message::newInstance($subject, $body, $contentType);
        if (empty($from) || is_array($from) && count($from) > 1) {
            $message->setFrom(array(
                $this->_mailSender => $this->_mailSenderName
            ));
        } else {
            $message->setFrom($from);
        }
        $message->setTo($to);
        $message->setCc($cc);
        $message->setBcc($bcc);
        $message->setReplyTo($replyTo);
        $failedRecipients = array();

        return $this->send($message, $failedRecipients, $resend);
    }

    /**
     * Sends the given Swift_Mime_Message and logs it if $resend is false.
     *
     * @see Swift_Mailer::send()
     * @param Swift_Mime_Message $message
     * @param array &$failedRecipients, optional
     * @param bool $resend, optional
     * @return int
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = NULL, $resend = false) {
        if (!is_array($failedRecipients)) {
            $failedRecipients = array();
        }
        $result = parent::send($message, $failedRecipients);

        // log the mail only if it is a new one
        if (!$resend) {
            $this->_logMail($message, $failedRecipients);
        }

        return $result;
    }

    /**
     * Resends the mail with the given idmailsuccess.
     *
     * @param int $idmailsuccess the ID of the mail which should be resend
     * @throws cInvalidArgumentException if the mail has already been sent
     *         successfully or does not exist
     */
    public function resendMail($idmailsuccess) {
        $mailLogSuccess = new cApiMailLogSuccess($idmailsuccess);
        if (!$mailLogSuccess->isLoaded() || $mailLogSuccess->get('success') == 1) {
            throw new cInvalidArgumentException('The mail which should be resent has already been sent successfully or does not exist.');
        }

        // get all fields, json-decode address fields
        $idmail = $mailLogSuccess->get('idmail');
        $mailLog = new cApiMailLog($idmail);
        $from = json_decode($mailLog->get('from'), true);
        $to = json_decode($mailLog->get('to'), true);
        $replyTo = json_decode($mailLog->get('reply_to'), true);
        $cc = json_decode($mailLog->get('cc'), true);
        $bcc = json_decode($mailLog->get('bcc'), true);
        $subject = $mailLog->get('subject');
        $body = $mailLog->get('body');
        $contentType = $mailLog->get('content_type');
        $this->setCharset($mailLog->get('charset'));

        // decode all fields
        $charset = $mailLog->get('charset');
        $from = $this->decodeField($from, $charset);
        $to = $this->decodeField($to, $charset);
        $replyTo = $this->decodeField($replyTo, $charset);
        $cc = $this->decodeField($cc, $charset);
        $bcc = $this->decodeField($bcc, $charset);
        $subject = $this->decodeField($subject, $charset);
        $body = $this->decodeField($body, $charset);

        $success = $this->sendMail($from, $to, $subject, $body, $cc, $bcc, $replyTo, true, $contentType);

        if ($success) {
            $mailLogSuccess->set('success', 1);
            $mailLogSuccess->store();
        }
    }

    /**
     * Encodes the given value / the given array values with htmlentities.
     *
     * @param string|array $value the value to encode
     * @param string $charset the charset to use in htmlentities
     * @return string array encoded value
     */
    private function encodeField($value, $charset) {
        if (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) {
                if (!empty($value[$i])) {
                    $value[$i] = conHtmlEntities($value[$i], ENT_COMPAT, $charset, false);
                }
            }
            return $value;
        } else if (is_string($value)) {
            return conHtmlentities($value, ENT_COMPAT, $charset, false);
        }
        return $value;
    }

    /**
     * Decodes the given value / the given array values with html_entity_decode.
     *
     * @param string|array $value the value to decode
     * @param string $charset the charset to use in htmlentities
     * @return string array decoded value
     */
    private function decodeField($value, $charset) {
        if (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) {
                if (!empty($value[$i])) {
                    $value[$i] = conHtmlEntityDecode($value[$i], ENT_COMPAT | ENT_HTML401, $charset, false);
                }
            }
        } else if (is_string($value)) {
            return conHtmlEntityDecode($value, ENT_COMPAT | ENT_HTML401, $charset);
        }
        return $value;
    }

    /**
     * Log the information about sending the email.
     *
     * @param Swift_Message $message the message which has been send
     * @param array $failedRecipients [optional] the recipient addresses that
     *        did not get the mail
     * @return string the idmail of the inserted table row in con_mail_log|boolean false if mail_log option is inactive
     */
    private function _logMail(Swift_Mime_Message $message, array $failedRecipients = array()) {

    	// Log only if mail_log is active otherwise return false
    	$mail_log = getSystemProperty('system', 'mail_log');
    	if (false === $mail_log) {
    		return false;
    	}

        $mailLogCollection = new cApiMailLogCollection();

        // encode all fields
        $charset = $message->getCharset();
        $from = $this->encodeField($message->getFrom(), $charset);
        $to = $this->encodeField($message->getTo(), $charset);
        $replyTo = $this->encodeField($message->getReplyTo(), $charset);
        $cc = $this->encodeField($message->getCc(), $charset);
        $bcc = $this->encodeField($message->getBcc(), $charset);
        $subject = $this->encodeField($message->getSubject(), $charset);
        $body = $this->encodeField($message->getBody(), $charset);
        $contentType = $message->getContentType();
        $mailItem = $mailLogCollection->create($from, $to, $replyTo, $cc, $bcc, $subject, $body, time(), $charset, $contentType);

        // get idmail variable
        $idmail = $mailItem->get('idmail');

        // do not use array_merge here since the mail addresses are array keys
        // array_merge will make problems if one recipient is e.g. in cc and bcc
        $recipientArrays = array(
            $message->getTo(),
            $message->getCc(),
            $message->getBcc()
        );
        $mailLogSuccessCollection = new cApiMailLogSuccessCollection();
        foreach ($recipientArrays as $recipients) {
            if (!is_array($recipients)) {
                continue;
            }
            foreach ($recipients as $key => $value) {
                $recipient = array(
                    $key => $value
                );
                $success = true;
                // TODO how do we get the information why message sending
                // has
                // failed?
                $exception = '';
                if (in_array($key, $failedRecipients)) {
                    $success = false;
                }
                $mailLogSuccessCollection->create($idmail, $recipient, $success, $exception);
            }
        }

        return $idmail;
    }

}
