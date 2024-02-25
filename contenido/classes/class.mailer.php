<?php

/**
 * This file contains the cMailer class for all mail sending purposes.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Rusmir Jusufovic
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Since CONTENIDO has its own autoloader, swift_init.php is enough.
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
 * $mailer->sendMail(['sender@contenido.org' => 'sender name'], 'recipient@contenido.org', 'subject');
 * </code>
 *
 * <strong>Logging mails</strong>
 * @todo explain logging of mails via logMail()
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
 * to override the before mentioned behaviour.
 *
 * <strong>User defined transport example</strong>
 * <code>
 * @todo add example
 * </code>
 *
 * <strong>User defined character set</strong>
 * @todo explain setCharset()
 *
 * @package    Core
 * @subpackage Backend
 */
class cMailer extends Swift_Mailer
{

    /**
     * SMTP encryption.
     * - ssl (SMTPS = SMTP over TLS)
     * - tls (SMTP with STARTTLS)
     */
    const SMTP_ENCRYPTION = [
        'tls',
        'ssl'
    ];

    /**
     * Mail address of the default mail sender.
     * This will be read from system property system/mail_sender.
     * Can be overridden by giving a sender when sending a mail.
     *
     * @var string
     */
    private $_mailSender = 'noreply@contenido.org';

    /**
     * Name of the default mail sender.
     * This will be read from system property system/mail_sender_name.
     * Can be overridden by giving a sender when sending a mail.
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
     * Logger for mails, used to log information when sending of a mail fails.
     *
     * @var Swift_Plugins_Logger|null
     */
    private $_logger = null;

    /**
     * Constructor to create an instance of this class.
     *
     * System properties to define the default mail sender are read and
     * aggregated.
     *
     * An arbitrary transport instance of class Swift_Transport can be
     * given. If no transport is given, system properties to build a
     * transport are read and aggregated and eventually transport is
     * created using constructTransport().
     *
     * @param Swift_Transport|null $transport
     *        A transport instance. If omitted, the transport will be created
     *        with configured system settings for mail.
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct(Swift_Transport $transport = NULL)
    {
        // If a transport object has been given, use it and skip the rest
        if (!is_null($transport)) {
            parent::__construct($transport);
            return;
        }

        // Is logging of errors enabled?
        $logErrors = getSystemProperty('system', 'mail_log_error') === 'true';

        // Get address of default mail sender
        $mailSender = getSystemProperty('system', 'mail_sender');
        if (Swift_Validate::email($mailSender)) {
            $this->_mailSender = $mailSender;
        }

        // Get name of default mail sender
        $mailSenderName = getSystemProperty('system', 'mail_sender_name');
        if (!empty($mailSenderName)) {
            $this->_mailSenderName = $mailSenderName;
        }

        // Get name of mail host
        $mailHost = getSystemProperty('system', 'mail_host');
        if (!empty($mailHost)) {
            $this->_mailHost = $mailHost;
        }

        // Get port of mail host
        $mailPort = getSystemProperty('system', 'mail_port');
        if (is_numeric($mailPort)) {
            $this->_mailPort = (int)$mailPort;
        }

        $transport = $this->createTransport();
        if (!$transport) {
            $errorMessage = 'Can not connect to the mail server. Please check your mail server configuration at CONTENIDO backend.';
            if ($logErrors) {
                // Log the error, the exception below may be caught somewhere!
                cWarning($errorMessage);
            }
            throw new cInvalidArgumentException($errorMessage);
        }

        parent::__construct($transport);

        // NOTE: We can register the plug-in after calling parents constructor!
        if ($logErrors) {
            $this->_logger = new Swift_Plugins_Loggers_ArrayLogger();
            $this->registerPlugin(new Swift_Plugins_LoggerPlugin($this->_logger));
        }
    }

    /**
     * This factory method tries to establish an SMTP connection to the
     * given mail host. If an optional mail host user is given it is
     * used to authenticate at the mail host. On success a SMTP transport
     * instance is returned. On failure a simple MAIL transport instance
     * is created and returned which will use PHP's mail() function to
     * send mails.
     *
     * @param string $mailHost
     *        The mail host
     * @param int $mailPort
     *        The mail port
     * @param string $mailEncryption
     *        The mail encryption, none by default
     * @param string $mailUser
     *        The mail user, none by default
     * @param string $mailPass
     *        The mail password, none by default
     * @return Swift_Transport|false
     *         The transport object or false
     * @todo Making this a static method and passing all the params is
     *       not that smart! Return value should be either the transport
     *       instance or null, not false!
     */
    public static function constructTransport(
        string $mailHost, int $mailPort, string $mailEncryption = '',
        string $mailUser = '', string $mailPass = ''
    )
    {
        // use SMTP by default
        $transport = Swift_SmtpTransport::newInstance(
            $mailHost, $mailPort, $mailEncryption
        );

        // use optional mail user to authenticate at mail host
        if (!empty($mailUser)) {
            $authHandler = new Swift_Transport_Esmtp_AuthHandler([
                new Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
                new Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                new Swift_Transport_Esmtp_Auth_CramMd5Authenticator()
            ]);
            $authHandler->setUsername($mailUser);
            if (!empty($mailPass)) {
                $authHandler->setPassword($mailPass);
            }
            $transport->setExtensionHandlers([
                $authHandler
            ]);
        }

        // check if SMTP usage is possible
        try {
            $transport->start();
        } catch (Throwable $e) {
            // Fallback in constructTransport deleted
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
     *        The character encoding
     */
    public function setCharset(string $charset)
    {
        Swift_Preferences::getInstance()->setCharset($charset);
    }

    /**
     * Wrapper function for sending a mail.
     *
     * All parameters which accept mail addresses also accept an array
     * where the key is the email address and the value is the name.
     *
     * @param string|array $from
     *        The sender of the mail, if something "empty" is given,
     *        default address from CONTENIDO system settings is used
     * @param string|array $to
     *        One or more recipient addresses
     * @param string $subject
     *        The subject of the mail
     * @param string $body
     *        The body of the mail
     * @param string|array $cc
     *        One or more recipient addresses which should get a normal copy
     * @param string|array $bcc
     *        One or more recipient addresses which should get a blind copy
     * @param string|array $replyTo
     *        Address to which replies should be sent
     * @param bool $resend
     *        Whether the mail is resent
     * @param string $contentType
     *        MIME type to use for mail, defaults to 'text/plain'
     * @return int|null
     *         Number of recipients to which the mail has been sent
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function sendMail(
        $from, $to, string $subject, string $body = '', $cc = NULL, $bcc = NULL,
        $replyTo = NULL, bool $resend = false, string $contentType = 'text/plain'
    ): ?int
    {
        $message = Swift_Message::newInstance($subject, $body, $contentType);
        if (empty($from) || is_array($from) && count($from) > 1) {
            $message->setFrom([
                $this->_mailSender => $this->_mailSenderName
            ]);
        } else {
            $message->setFrom($from);
        }
        $message->setTo($to);
        $message->setCc($cc);
        $message->setBcc($bcc);
        $message->setReplyTo($replyTo);

        $failedRecipients = [];
        return $this->send($message, $failedRecipients, $resend);
    }

    /**
     * Sends the given Swift_Mime_Message and logs it if $resend is false.
     *
     * @param Swift_Mime_Message $message
     *        The message to send
     * @param array|null $failedRecipients
     *        List of recipients for which the sending has failed
     * @param bool $resend
     *        If this mail is send via resend
     *        when resending a mail it is not logged again
     * @return int|null
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     * @see Swift_Mailer::send()
     */
    public function send(
        Swift_Mime_Message $message, &$failedRecipients = NULL, bool $resend = false
    )
    {
        if (!is_array($failedRecipients)) {
            $failedRecipients = [];
        }

        // CON-2540
        // fallback in constructTransport deleted
        // parent::send() can't handle it, therefore return null before
        if ($this->getTransport() == null) {
            return null;
        }
        $result = parent::send($message, $failedRecipients);

        if (!$result && is_object($this->_logger)) {
            cWarning("Could not send email. Logger message:" . $this->_logger->dump());
        }

        // log the mail only if it is a new one
        if (!$resend) {
            $this->logMail($message, $failedRecipients);
        }

        return $result;
    }

    /**
     * Resends the mail with the given idmailsuccess.
     *
     * @param int $idmailsuccess
     *        ID of the mail which should be resent
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException if the mail has already been sent successfully or does not exist
     */
    public function resendMail(int $idmailsuccess)
    {
        $mailLogSuccess = new cApiMailLogSuccess($idmailsuccess);
        if (!$mailLogSuccess->isLoaded() || $mailLogSuccess->get('success') == 1) {
            throw new cInvalidArgumentException(
                'The mail which should be resent has already been sent successfully or does not exist.'
            );
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

        $success = $this->sendMail(
            $from, $to, $subject, $body, $cc, $bcc, $replyTo, true, $contentType
        );

        if ($success) {
            $mailLogSuccess->set('success', 1);
            $mailLogSuccess->store();
        }
    }

    /**
     * Encodes the given value / array of values using conHtmlEntities().
     *
     * @param string|array $value
     *        The value to encode
     * @param string $charset
     *        The charset to use
     * @return string|array
     *        Encoded value
     */
    private function encodeField($value, string $charset)
    {
        if (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) {
                if (!empty($value[$i])) {
                    $value[$i] = conHtmlentities(
                        cSecurity::toString($value[$i]), ENT_COMPAT, $charset
                    );
                }
            }
            return $value;
        } elseif (is_string($value)) {
            return conHtmlentities($value, ENT_COMPAT, $charset);
        } else {
            return $value;
        }
    }

    /**
     * Decodes the given value / array of values using conHtmlEntityDecode().
     *
     * @param string|array $value
     *        The value to decode
     * @param string $charset
     *        The charset to use
     * @return string|array
     *         Decoded value
     */
    private function decodeField($value, string $charset)
    {
        if (is_array($value)) {
            for ($i = 0; $i < count($value); $i++) {
                if (!empty($value[$i])) {
                    $value[$i] = conHtmlEntityDecode(
                        cSecurity::toString($value[$i]), ENT_COMPAT | ENT_HTML401, $charset
                    );
                }
            }
            return $value;
        } elseif (is_string($value)) {
            return conHtmlEntityDecode($value, ENT_COMPAT | ENT_HTML401, $charset);
        } else {
            return $value;
        }
    }

    /**
     * Creates a mailer transport instance (smtp or mail) depending on system settings.
     *
     * @return false|Swift_MailTransport|Swift_Transport
     * @throws cDbException|cException
     */
    protected function createTransport()
    {
        $mail_type = cString::toLowerCase(getSystemProperty('system', 'mail_transport'));

        if ($mail_type == 'smtp') {
            $mail_encryption = cString::toLowerCase(
                getSystemProperty('system', 'mail_encryption')
            );
            if (in_array($mail_encryption, self::SMTP_ENCRYPTION)) {
                $this->_mailEncryption = $mail_encryption;
            } elseif ('1' == $mail_encryption) {
                $this->_mailEncryption = 'ssl';
            } else {
                $this->_mailEncryption = NULL;
            }

            // get name and password of mail host user
            $this->_mailUser = cSecurity::toString(getSystemProperty('system', 'mail_user'));
            $this->_mailPass = cSecurity::toString(getSystemProperty('system', 'mail_pass'));

            // build transport
            $transport = self::constructTransport(
                $this->_mailHost, $this->_mailPort, $this->_mailEncryption, $this->_mailUser,
                $this->_mailPass
            );
        } else {
            $transport = Swift_MailTransport::newInstance();
        }

        return $transport;
    }


    /**
     * Log the information about sending the email.
     *
     * @param Swift_Mime_Message $message
     *        The message which has been sent
     * @param array $failedRecipients
     *        The recipient addresses that did not get the mail
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function logMail(Swift_Mime_Message $message, array $failedRecipients = [])
    {
        // Log only if mail_log is active otherwise return false
        $mail_log = getSystemProperty('system', 'mail_log');
        if ($mail_log == 'false') {
            return;
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
        $mailItem = $mailLogCollection->create(
            $from, $to, $replyTo, $cc, $bcc, $subject, $body, time(), $charset, $contentType
        );

        // get idmail variable
        $idmail = $mailItem->get('idmail');

        // do not use array_merge here since the mail addresses are array keys
        // array_merge will make problems if one recipient is e.g. in cc and bcc
        $recipientArrays = [
            $message->getTo(),
            $message->getCc(),
            $message->getBcc()
        ];
        $mailLogSuccessCollection = new cApiMailLogSuccessCollection();
        foreach ($recipientArrays as $recipients) {
            if (!is_array($recipients)) {
                continue;
            }
            foreach ($recipients as $key => $value) {
                $recipient = [
                    $key => $value
                ];
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
    }

}
