<?php
/**
 * This file contains the the password request class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for handling passwort recovery for backend users.
 * If a user has set his e-mail address, this class
 * generates a new Password for user and submits to his e-mail adress.
 * Submitting a new Password is
 * only possible every 30 minutes Mailsender, Mailsendername and Mailserver are
 * set into system properties.
 * There it is also possible to deactivate this feature.
 *
 * @package Core
 * @subpackage Backend
 */
class cPasswordRequest {

    /**
     * The CONTENIDO database object
     *
     * @var cDb
     */
    protected $_db;

    /**
     * The CONTENIDO configuration array
     *
     * @var array
     */
    protected $_cfg;

    /**
     * The CONTENIDO template object
     *
     * @var cTemplate
     */
    protected $_tpl;

    /**
     * Username of user which requests password
     *
     * @var string
     */
    protected $_username;

    /**
     * E-mail address of user which requests password
     *
     * @var string
     */
    protected $_email;

    /**
     * Time in minutes after which user is allowed to request a new password
     *
     * @var int
     */
    protected $_reloadTime;

    /**
     * Length of new passwort, which is generated automatically
     *
     * @var int
     */
    protected $_passLength;

    /**
     * Defines if passwort request is enabled or disabled.
     * Default: This feature is enabled
     *
     * @var bool
     */
    protected $_isEnabled;

    /**
     * E-mail address of the sender
     *
     * @var string
     */
    protected $_sendermail;

    /**
     * Name of the sender
     *
     * @var string
     */
    protected $_sendername;

    /**
     * Host of mailserver, which sends new password via mail
     *
     * @var string
     */
    protected $_mailhost;

    /**
     * Constructor of RequestPassword initializes class variables
     *
     * @param cDb $db The CONTENIDO database object
     * @param array $cfg The CONTENIDO configuration array
     */
    public function __construct($db, $cfg) {
        // generate new dbobject, if it does not exist
        if (!is_object($db)) {
            $this->_db = cRegistry::getDb();
        } else {
            $this->_db = $db;
        }

        // init class variables
        $this->_cfg = $cfg;
        $this->_tpl = new cTemplate();
        $this->_username = '';
        $this->_email = '';

        // set reload to 30 minutes
        $this->_reloadTime = 30;

        // set pass length to 14 chars
        $this->_passLength = 14;

        // get systemproperty, which definies if password request is enabled
        // (true) or disabled (false) : default to enabled
        $sEnable = getSystemProperty('pw_request', 'enable');
        if ($sEnable == 'false') {
            $this->_isEnabled = false;
        } else {
            $this->_isEnabled = true;
        }

        // get systemproperty for senders mail and validate mailadress, if not
        // set use standard sender
        $sendermail = getSystemProperty('system', 'mail_sender');
        if (preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $sendermail)) {
            $this->_sendermail = $sendermail;
        } else {
            $this->_sendermail = 'info@contenido.org';
        }

        // get systemproperty for senders name, if not set use CONTENIDO Backend
        $sendername = getSystemProperty('system', 'mail_sender_name');
        if ($sendername != '') {
            $this->_sendername = $sendername;
        } else {
            $this->_sendername = 'CONTENIDO Backend';
        }

        // get systemproperty for location of mailserver, if not set use
        // localhost
        $mailhost = getSystemProperty('system', 'mail_host');
        if ($mailhost != '') {
            $this->_mailhost = $mailhost;
        } else {
            $this->_mailhost = 'localhost';
        }
    }

    /**
     * Function displays form for password request and sets new password, if
     * password is submitted this function also starts the passwort change an
     * sending process
     *
     * @param bool $return Return or print template
     * @return string rendered HTML code
     */
    public function renderForm($return = 0) {
        // if feature is not enabled, do nothing
        if (!$this->_isEnabled) {
            return '';
        }

        $message = '';

        // if form is sumbitted call function handleNewPassword() and set
        // submitted username to class variable $sUsername
        if (isset($_POST['action']) && $_POST['action'] == 'request_pw') {
            // avoid SQL-Injection, first check if submitted vars are escaped
            // automatically
            $this->_username = $_POST['request_username'];

            $message = $this->_handleNewPassword();
            // if form is submitted, show corresponding password request layer
            $this->_tpl->set('s', 'JS_CALL', 'showRequestLayer();');
        } else {
            // by default request layer is invisible so da nothing
            $this->_tpl->set('s', 'JS_CALL', '');
        }

        // generate new form
        $form = new cHTMLForm('request_pw', 'index.php', 'post');

        // generate input for username
        $inputUsername = new cHTMLTextbox('request_username', stripslashes($_POST['request_username']), '', '', 'request_username');
        $inputUsername->setStyle('width:215px;');

        // set request action and current language
        $form->setVar('action', 'request_pw');
        $form->setVar('belang', $GLOBALS['belang']);

        // generate submitbutton and fill the form
        $form->setContent('<input class="password_request_input" type="image" src="images/submit.gif" alt="' . i18n('Submit') . '" title="' . i18n('Submit') . '">' . $inputUsername->render());
        $this->_tpl->set('s', 'FORM', $form->render());
        $this->_tpl->set('s', 'MESSAGE', $message);
        $this->_tpl->set('s', 'LABEL', i18n('Please enter your login') . ':');

        // if handleNewPassword() returns a message, display it
        if ($return) {
            return $this->_tpl->generate($this->_cfg['path']['contenido'] . $this->_cfg['path']['templates'] . $this->_cfg['templates']['request_password'], 1);
        } else {
            return $this->_tpl->generate($this->_cfg['path']['contenido'] . $this->_cfg['path']['templates'] . $this->_cfg['templates']['request_password']);
        }
    }

    /**
     * Function checks password request for errors an delegate request to
     * setNewPassword() if there is no error
     *
     * @return string
     */
    protected function _handleNewPassword() {
        // notification message, which is returned to caller
        $message = '';
        $this->_username = stripslashes($this->_username);

        // check if requested username exists, also get email and timestamp when
        // user last requests a new password (last_pw_request)
        $sql = "SELECT username, last_pw_request, email FROM " . $this->_cfg['tab']['user'] . "
                 WHERE username = '" . $this->_db->escape($this->_username) . "'
                 AND (valid_from <= NOW() OR valid_from = '0000-00-00' OR valid_from IS NULL)
                 AND (valid_to >= NOW() OR valid_to = '0000-00-00' OR valid_to IS NULL)";

        $this->_db->query($sql);
        if ($this->_db->nextRecord() && md5($this->_username) == md5($this->_db->f('username'))) {
            // by default user is allowed to request new password
            $isAllowed = true;
            $lastPwRequest = $this->_db->f('last_pw_request');
            // store users mail adress to class variable
            $this->_email = $this->_db->f('email');

            // check if there is a correct last request date
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $lastPwRequest, $aMatches)) {
                $lastRequest = mktime($aMatches[4], $aMatches[5], $aMatches[6], $aMatches[2], $aMatches[3], $aMatches[1]);

                // check if this last request is longer ago then timelimit.
                if ((time() - $lastRequest) < (60 * $this->_reloadTime)) {
                    // user is not allowed to request new password, he has to
                    // wait
                    $isAllowed = false;
                    $message = sprintf(i18n('Password requests are allowed every %s minutes.'), $this->_reloadTime);
                }
            }

            // check if syntax of users mail adress is correct and there is no
            // standard mailadress like admin_kunde@IhreSite.de or
            // sysadmin@IhreSite.de
            if ((!preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $this->_email) || $this->_email == 'sysadmin@IhreSite.de' || $this->_email == 'admin_kunde@IhreSite.de') && $isAllowed) {
                $isAllowed = false;
                // $sMessage = i18n('The requested user has no valid e-mail
                // address. Submitting new password is not possible. Please
                // contact your system- administrator for further support.');
                $message = i18n('No matching data found. Please contact your systemadministrator.');
            }

            // if there are no errors, call function setNewPassword(), else wait
            // a while, then return error message
            if ($isAllowed) {
                $this->_setNewPassword();
                $message = i18n('New password was submitted to your e-mail address.');
            } else {
                sleep(5);
            }
        } else {
            // slepp a while, then return error message
            // $sMessage = i18n('This user does not exist.');
            $message = i18n('No matching data found. Please contact your systemadministrator.');
            sleep(5);
        }
        return $message;
    }

    /**
     * Function sets new password for user and sets last request time to now
     */
    protected function _setNewPassword() {
        // generate new password, using generatePassword()
        $password = $this->_generatePassword();

        // get salt
        $sql = "SELECT salt FROM " . $this->_cfg['tab']['user'] . " WHERE username = '" . $this->_username . "'";
        $this->_db->query($sql);
        $this->_db->nextRecord();

        // hash password
        $password_hash = hash("sha256", md5($password) . $this->_db->f("salt"));

        // update database entry, set new password and last_pw_request time
        $sql = "UPDATE " . $this->_cfg['tab']['user'] . "
                SET last_pw_request = '" . date('Y-m-d H:i:s') . "',
                tmp_pw_request = '" . $password_hash . "',
                password = '" . $password_hash . "'
                WHERE username = '" . $this->_username . "'";
        $this->_db->query($sql);

        // call function submitMail(), which sends new password to user
        $this->_submitMail($password);
    }

    /**
     * Function submits new password to users mail adress
     *
     * @param string $password The new password
     */
    protected function _submitMail($password) {
    	$cfg = cRegistry::getConfig();

        $password = (string) $password;

        // get translation for mailbody and insert username and new password
        $mailBody = sprintf(i18n("Dear CONTENIDO-User %s,\n\nYour password to log in Content Management System CONTENIDO is: %s\n\nBest regards\n\nYour CONTENIDO sysadmin"), $this->_username, $password);

        $mailer = new cMailer();
        $from = array(
            $this->_sendermail => $this->_sendername
        );

        // Decoding and encoding for charsets (without UTF-8)
        if($cfg['php_settings']['default_charset'] != 'UTF-8') {
	        $subject = utf8_encode(conHtmlEntityDecode(stripslashes(i18n('Your new password for CONTENIDO Backend')), '', $cfg['php_settings']['default_charset']));
	        $body = utf8_encode(conHtmlEntityDecode($mailBody, '', $cfg['php_settings']['default_charset']));
        } else {
        	$subject = conHtmlEntityDecode(stripslashes(i18n('Your new password for CONTENIDO Backend')));
        	$body = conHtmlEntityDecode($mailBody);
        }

        $mailer->sendMail($from, $this->_email, $subject, $body);
    }

    /**
     * Function generates new password
     *
     * @return string The new password
     */
    protected function _generatePassword() {
        // possible chars which were used in password
        $chars = "ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghjkmnopqrstuvwxyz123456789";

        $password = "";

        // for each character of password choose one from $sChars randomly
        for ($i = 0; $i < $this->_passLength; $i++) {
            $password .= $chars[rand(0, strlen($chars))];
        }

        return $password;
    }
}
