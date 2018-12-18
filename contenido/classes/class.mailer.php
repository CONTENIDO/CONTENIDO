<?php

/**
 * This file contains the cMailer class for all mail sending purposes.
 *
 * @package Core
 * @subpackage Backend
 * @author Rusmir Jusufovic
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Since CONTENIDO has it's own autoloader, swift_init.php is enough.
// We do not need and should not use swift_required.php!
require_once 'swiftmailer/lib/swift_init.php';

/**
 * Mailer class for all mail sending purposes.
 *
 * The class cMailer is a facade for the SwiftMailer library that
 * simplifies the process of sending mails by providing some
 * convenience methods.
 *
 * <strong>Simple example</strong>
 * <code>
 * $mailer = new cMailer();
 * $mailer->sendMail(null, 'recipient@contenido.org', 'subject', 'body');
 * </code>
 *
 * <strong>Default sender of mails</strong>
 * When sending a mail using the sendMail() method of the cMailer class
 * you can give a mail sender as first parameter. This can either be an
 * email address as string or an array with the email address as key and
 * the senders name as value. If you pass an empty value instead the
 * default mail sender is used. This default mail sender can be
 * configured with the system properties system/mail_sender and
 * system/mail_sender_name. If no default mail sender is configured it
 * defaults to "noreply@contenido.org" and "CONTENIDO Backend".
 *
 * <strong>User defined mail sender example</strong>
 * <code>
 * $mailer->sendMail('sender@contenido.org', 'recipient@contenido.org', 'subject');
 * $mailer->sendMail(array('sender@contenido.org' => 'sender name'), 'recipient@contenido.org', 'subject');
 * </code>
 *
 * <strong>Logging mails</strong>
 * @todo explain logging of mails via _logMail()
 *
 * <strong>Resending mails</strong>
 * @todo explain resending of mails via resendMail()
 *
 * <strong>Sending user created messages</strong>
 * Creating your own message is e.g. necessary in order to send mails
 * with attachments as the simplified interface the cMailer class offers
 * does not yet provide means to do so.
 * @todo explain sending of user created messages via send()
 *
 * <strong>Default transport</strong>
 * By default the cMailer tries to use an SMTP transport with optional
 * authentication. If starting the SMTP transport fails, a simple MAIL
 * transport will be used (using PHP's mail() function).
 *
 * <strong>User defined transport</strong>
 * When creating a cMailer instance an arbitrary transport can be given
 * to override the afore mentioned behaviour.
 *
 * <strong>User defined transport example</strong>
 * <code>
 * @todo add example
 * </code>
 *
 * <strong>User defined character set</strong>
 * @todo explain setCharset()
 *
 * @package Core
 * @subpackage Backend
 */
class cMailer extends Swift_Mailer {

    /**
     * Mail address of the default mail sender.
     * This will be read from system property system/mail_sender.
     * Can be overriden by giving a sender when sending a mail.
     *
     * @var string
     */
    private $_mailSender = 'noreply@contenido.org';

    /**
     * Name of the default mail sender.
     * This will be read from system property system/mail_sender_name.
     * Can be overriden by giving a sender when sending a mail.
     *
     * @var string
     */
    private $_mailSenderName = 'CONTENIDO Backend';

    /**
     * Name of the mail host.
     * This will be read from system property system/mail_host.
     *
     * @var string
     */
    private $_mailHost = 'localhost';

    /**
     * Port of the mail host.
     * This will be read from system property system/mail_port.
     *
     * @var int
     */
    private $_mailPort = 25;

    /**
     * The mail encryption method (ssl/tls).
     * This will be read from system property system/mail_encryption.
     *
     * @var string
     */
    private $_mailEncryption = NULL;

    /**
     * Name of the mail host user.
     * This will be read from system property system/mail_user.
     * Used for authentication at the mail host.
     *
     * @var string
     */
    private $_mailUser = '';

    /**
     * Password of the mail host user.
     * This will be read from system property system/mail_pass.
     * Used for authentication at the mail host.
     *
     * @var string
     */
    private $_mailPass = '';

    /**
     * Constructor to create an instance of this class.
     *
     * System properties to define the default mail sender are read and
     * aggregated.
     *
     * An arbitrary transport instance of class Swift_Transport can be
     * given. If no transport is given, system properties to build a
     * transport are read and aggregated and eventually a transport is
     * created using constructTransport().
     *
     * @todo add type hinting!
     *
     * @param Swift_Transport $transport [optional]
     *                                   a transport instance
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     * @throws Swift_DependencyException
     * @throws Swift_RfcComplianceException
     */
    public function __construct($transport = NULL) {

        // get address of default mail sender
        $mailSender = getSystemProperty('system', 'mail_sender');
        if (Swift_Validate::email($mailSender)) {
            $this->_mailSender = $mailSender;
        }

        // get name of default mail sender
        $mailSenderName = getSystemProperty('system', 'mail_sender_name');
        if (!empty($mailSenderName)) {
            $this->_mailSenderName = $mailSenderName;
        }

        // if a transport object has been given, use it and skip the rest
        if (!is_null($transport)) {
            parent::__construct($transport);
            return;
        }
        // if no transport object has been given, read system setting and create one

        // get name of mail host
        $mailHost = getSystemProperty('system', 'mail_host');
        if (!empty($mailHost)) {
            $this->_mailHost = $mailHost;
        }

        // get port of mail host
        if (is_numeric(getSystemProperty('system', 'mail_port'))) {
            $this->_mailPort = (int) getSystemProperty('system', 'mail_port');
        }

        // get mail encryption
        $encryptions = array(
            'tls',
            'ssl'
        );

        $mail_type = cString::toLowerCase(getSystemProperty('system', 'mail_transport'));

        if ($mail_type == 'smtp') {

            $mail_encryption = cString::toLowerCase(getSystemProperty('system', 'mail_encryption'));
            if (in_array($mail_encryption, $encryptions)) {
                $this->_mailEncryption = $mail_encryption;
            } elseif ('1' == $mail_encryption) {
                $this->_mailEncryption = 'ssl';
            } else {
                $this->_mailEncryption = NULL;
            }

            // get name and password of mail host user
            $this->_mailUser = (getSystemProperty('system', 'mail_user')) ? getSystemProperty('system', 'mail_user') : '';
            $this->_mailPass = (getSystemProperty('system', 'mail_pass')) ? getSystemProperty('system', 'mail_pass') : '';

            // build transport
            $transport = self::constructTransport($this->_mailHost, $this->_mailPort, $this->_mailEncryption, $this->_mailUser, $this->_mailPass);

        } else {
            $transport = Swift_MailTransport::newInstance();
        }

        // CON-2530
        if ($transport === false) {
            throw new cInvalidArgumentException('Can not connect to the mail server. Please check your mail server configuration at CONTENIDO backend.');
        }

        parent::__construct($transport);
    }

    /**
     * This factory method tries to establish an SMTP connection to the
     * given mail host. If an optional mail host user is given it is
     * used to authenticate at the mail host. On success a SMTP transport
     * instance is returned. On failure a simple MAIL transport instance
     * is created and returned which will use PHP's mail() function to
     * send mails.
     *
     * @todo making this a static method and passing all the params is
     *         not that smart!
     * @param string $mailHost
     *         the mail host
     * @param string $mailPort
     *         the mail port
     * @param string $mailEncryption [optional]
     *         the mail encryption, none by default
     * @param string $mailUser [optional]
     *         the mail user, none by default
     * @param string $mailPass [optional]
     *         the mail password, none by default
     * @return Swift_Transport
     *         the transport object
     */
    public static function constructTransport($mailHost, $mailPort, $mailEncryption = NULL, $mailUser = NULL, $mailPass = NULL) {

        // use SMTP by default
        $transport = Swift_SmtpTransport::newInstance($mailHost, $mailPort, $mailEncryption);

        // use optional mail user to authenticate at mail host
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
            // CON-2530
            // fallback in constructTransport deleted
            // parent::send() can't handle it, therefore return false before
            return false;
        }

        return $transport;
    }

    /**
     * Sets the charset of the messages which are sent by this mailer.
     * If you want to use UTF-8, you do not need to call this method.
     *
     * @param string $charset
     *         the character encoding
     */
    public function setCharset($charset) {
        Swift_Preferences::getInstance()->setCharset($charset);
    }

    /**
     * Wrapper function for sending a mail.
     *
     * All parameters which accept mail addresses also accept an array
     * where the key is the email address and the value is the name.
     *
     * @param string|array $from
     *                                  the sender of the mail, if something "empty" is given,
     *                                  default address from CONTENIDO system settings is used
     * @param string|array $to
     *                                  one or more recipient addresses
     * @param string       $subject
     *                                  the subject of the mail
     * @param string       $body        [optional]
     *                                  the body of the mail
     * @param string|array $cc          [optional]
     *                                  one or more recipient addresses which should get a normal copy
     * @param string|array $bcc         [optional]
     *                                  one or more recipient addresses which should get a blind copy
     * @param string|array $replyTo     [optional]
     *                                  address to which replies should be sent
     * @param bool         $resend      [optional]
     *                                  whether the mail is resent
     * @param string       $contentType [optional]
     *                                  MIME type to use for mail, defaults to 'text/plain'
     *
     * @return int
     *         number of recipients to which the mail has been sent
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
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
     *
     * @param Swift_Mime_Message $message
     *                                              the message to send
     * @param array              &$failedRecipients [optional]
     *                                              list of recipients for which the sending has failed
     * @param bool               $resend            [optional]
     *                                              if this mail is send via resend
     *                                              when resending a mail it is not logged again
     *
     * @return int
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = NULL, $resend = false) {
        if (!is_array($failedRecipients)) {
            $failedRecipients = array();
        }

        // CON-2540
        // fallback in constructTransport deleted
        // parent::send() can't handle it, therefore return null before
        if($this->getTransport() == null) {
            return null;
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
     * @param int $idmailsuccess
     *         ID of the mail which should be resend
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException if the mail has already been sent successfully or does not exist
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
     * Encodes the given value / array of values using conHtmlEntities().
     *
     * @todo check why conHtmlEntities() is called w/ 4 params
     * @param string|array $value
     *         the value to encode
     * @param string $charset
     *         the charset to use
     * @return string|array
     *         encoded value
     */
    private function encodeField($value, $charset) {
        if (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) {
                if (!empty($value[$i])) {
                    $value[$i] = conHtmlentities($value[$i], ENT_COMPAT, $charset, false);
                }
            }
            return $value;
        } else if (is_string($value)) {
            return conHtmlentities($value, ENT_COMPAT, $charset, false);
        } else {
            return $value;
        }
    }

    /**
     * Decodes the given value / array of values using conHtmlEntityDecode().
     *
     * @todo check why conHtmlEntityDecode() is called w/ 4 params
     * @param string|array $value
     *         the value to decode
     * @param string $charset
     *         the charset to use
     * @return string|array
     *         decoded value
     */
    private function decodeField($value, $charset) {
        if (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) {
                if (!empty($value[$i])) {
                    $value[$i] = conHtmlEntityDecode($value[$i], ENT_COMPAT | ENT_HTML401, $charset, false);
                }
            }
            return $value;
        } else if (is_string($value)) {
            return conHtmlEntityDecode($value, ENT_COMPAT | ENT_HTML401, $charset);
        } else {
            return $value;
        }
    }

    /**
     * Log the information about sending the email.
     *
     * @param Swift_Mime_Message $message
     *                                             the message which has been send
     * @param array              $failedRecipients [optional]
     *                                             the recipient addresses that did not get the mail
     *
     * @return string|bool
     *         the idmail of the inserted table row in con_mail_log|bool
     *         false if mail_log option is inactive
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _logMail(Swift_Mime_Message $message, array $failedRecipients = array()) {

        // Log only if mail_log is active otherwise return false
        $mail_log = getSystemProperty('system', 'mail_log');
        if ($mail_log == 'false') {
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
                // has failed?
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
