<?php
/**
 * This file contains the cMail class which should be used for all mail sending
 * purposes.
 *
 * @package Core
 * @subpackage Util
 * @version SVN Revision $Rev:$
 *
 * @author Rusmir Jusufovic
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// since CONTENIDO has it's own autoloader, swift_init.php is enough
// we do not need and should not use swift_required.php!
require_once 'swiftmailer/lib/swift_init.php';

/**
 * Mailer class which should be used for all mail sending purposes.
 *
 * @package Core
 * @subpackage Util
 */
class cMail extends Swift_Mailer {

    private $_isPreSendError = true;

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
    private $_mailEncryption = null;

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
     * @return void
     */
    public function __construct($transport = null) {
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
        if (in_array(strtolower(getSystemProperty('system', 'mail_encryption')), $encryptions)) {
            $this->_mailEncryption = strtolower(getSystemProperty('system', 'mail_encryption'));
        }

        // get mailserver port from system properties
        if (is_numeric(getSystemProperty('system', 'mail_port'))) {
            $this->_mailPort = (int) getSystemProperty('system', 'mail_port');
        }

        // try to use SMTP
        $transport = Swift_SmtpTransport::newInstance($this->_mailHost, $this->_mailPort, $this->_mailEncryption);
        if (!empty($this->_mailUser)) {
            $authHandler = new Swift_Transport_Esmtp_AuthHandler(array(
                        new Swift_Transport_Esmtp_Auth_PlainAuthenticator(),
                        new Swift_Transport_Esmtp_Auth_LoginAuthenticator(),
                        new Swift_Transport_Esmtp_Auth_CramMd5Authenticator()
                    ));
            $authHandler->setUsername($this->_mailUser);
            if (!empty($this->_mailPass)) {
                $authHandler->setPassword($this->_mailPass);
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
        parent::__construct($transport);
    }

    /**
     * Nice wrapper function for sending a mail.
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
     * @return int number of recipients to which the mail has been sent
     */
    public function sendMail($from, $to, $subject, $body = '', $cc = null, $bcc = null, $replyTo = null) {
        $message = Swift_Message::newInstance($subject, $body);
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
        $result = $this->send($message, $failedRecipients);
        $this->_logMail($message, $failedRecipients);

        return $result;
    }

    /**
     * Log the information about sending the email.
     *
     * @param Swift_Message $message the message which has been send
     * @param array $failedRecipients [optional] the recipient addresses that
     *        did not get the mail
     */
    private function _logMail($message, $failedRecipients = array()) {
        $mailLogCollection = new cApiMailLogCollection();
        $idmail = $mailLogCollection->create($message->getFrom(), $message->getTo(), $message->getReplyTo(), $message->getCc(), $message->getBcc(), $message->getSubject(), $message->getBody(), time());

        // do not use array_merge here since the mail addresses are array keys
        // array_merge will make problems if one recipient is e.g. in cc and bcc
        $recipientArrays = array(
            $message->getTo(),
            $message->getCc(),
            $message->getBcc()
        );
        $mailLogSuccessCollection = new cApiMailLogSuccessCollection();
        foreach ($recipientArrays as $recipients) {
            foreach ($recipients as $key => $value) {
                $success = true;
                if (in_array($key, $failedRecipients)) {
                    $success = false;
                }
                // TODO how do we get the information why message sending has
                // failed?
                $exception = '';
                $mailLogSuccessCollection->create($idmail, $key, $success, $exception);
            }
        }
    }

}
