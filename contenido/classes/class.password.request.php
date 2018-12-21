<?php

/**
 * This file contains the the password request class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for handling passwort recovery of backend users.
 * If a user has set his e-mail address, this class generates a new password for user and submits to his e-mail address.
 * Submitting a new Password is only possible every 30 minutes. Mailsender, Mailsendername and Mailserver are set into
 * system properties. There it is also possible to deactivate this feature.
 *
 * @package    Core
 * @subpackage Backend
 */
class cPasswordRequest
{
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
     * Length of validation token, which is generated automatically
     *
     * @var int
     */
    protected $_tokenLength;

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
     * Constructor to create an instance of this class.
     *
     * @param cDb   $db
     *         CONTENIDO database object
     * @param array $cfg
     *         The CONTENIDO configuration array
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($db, $cfg)
    {
        // generate new db object, if it does not exist
        if (!is_object($db)) {
            $this->_db = cRegistry::getDb();
        } else {
            $this->_db = $db;
        }

        // init class variables
        $this->_cfg      = $cfg;
        $this->_tpl      = new cTemplate();
        $this->_username = '';
        $this->_email    = '';

        // set reload to 4 hours (60*4 minutes)
        $this->_reloadTime = 240;

        // set token length to 14 chars
        $this->_tokenLength = 14;

        // get systemproperty, which definies if password request is enabled
        // (true) or disabled (false) : default to enabled
        $sEnable = getSystemProperty('pw_request', 'enable');
        if ($sEnable == 'false') {
            $this->_isEnabled = false;
        } else {
            $this->_isEnabled = true;
        }

        // get systemproperty for senders mail and validate mail address, if not
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

        // show form if password reset is wished
        // if feature is not enabled, do nothing
        if (true === $this->_isEnabled) {
            // check if confirmation link from mail used
            if (isset($_GET['pw_reset']) && '' !== $_GET['pw_reset']) {
                // check if requests found
                $aRequests = $this->_getCurrentRequests();
                if (count($aRequests) > 0) {
                    // check if form with username and new password was filled out
                    if (false === isset($_POST['user_name'])
                        || false === isset($_POST['user_pw'])
                        || false === isset($_POST['user_pw_repeat'])
                    ) {
                        // show form to set new password
                        $this->_renderNewPwForm();
                    } else {
                        // do validation checks then set new password for user in database
                        $this->_handleResetPw();
                    }
                }
            }
        }
    }

    /**
     * Function displays form for password request, if
     * password is submitted this function also starts the
     * passwort reset request and sending process
     *
     * @param bool $return [optional]
     *                     Return or print template
     *
     * @return string
     *         rendered HTML code
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function renderForm($return = false)
    {
        // if feature is not enabled, do nothing
        if (!$this->_isEnabled) {
            return '';
        }

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
            $message = '';

            // by default request layer is invisible so do nothing
            $this->_tpl->set('s', 'JS_CALL', '');
        }

        // generate new form
        $form = new cHTMLForm('request_pw', 'index.php', 'post');

        // CON-2772 generate username safe to display
        $safeUsername = stripslashes($this->_username);
        $safeUsername = conHtmlentities($safeUsername);

        // generate input for username
        $inputUsername = new cHTMLTextbox('request_username', $safeUsername, '', '', 'request_username');
        $inputUsername->setStyle('width:215px;');

        // set request action and current language
        $form->setVar('action', 'request_pw');
        $form->setVar('belang', $GLOBALS['belang']);

        // generate submitbutton and fill the form
        $form->setContent(
            '<input class="password_request_input" type="image" src="images/submit.gif" alt="' . i18n('Submit')
            . '" title="' . i18n('Submit') . '">' . $inputUsername->render()
        );
        $this->_tpl->set('s', 'FORM', $form->render());
        $this->_tpl->set('s', 'MESSAGE', $message);
        $this->_tpl->set('s', 'LABEL', i18n('Please enter your login') . ':');

        // if handleNewPassword() returns a message, display it
        return $this->_tpl->generate(
            $this->_cfg['path']['contenido'] . $this->_cfg['path']['templates']
            . $this->_cfg['templates']['request_password'],
            $return
        );
    }

    /**
     * Function to display form to set new password for user.
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _renderNewPwForm()
    {
        if (isset($_POST['action']) && $_POST['action'] == 'reset_pw') {
            $this->_username = $_POST['request_username'];

            $message = $this->_handleNewPassword();
            
            // do not show password reset form
            $this->_tpl->set('s', 'JS_CALL', '');
        } else {
            // show password reset form using JavaScript
            $this->_tpl->set('s', 'JS_CALL', 'showResetLayer();');
        }

        $msg = i18n('You may now set a new password');
        $this->_tpl->set('s', 'RESET_LABEL', $msg);

        // insert form with username, password and password repeat fields
        $form =
            new cHTMLForm('reset_form', htmlentities(cRegistry::getBackendUrl()) . '?pw_reset=' . $_GET['pw_reset']);

        $userNameLbl = new cHTMLDiv(new cHTMLLabel(i18n('User name') . ': ', 'user_name'));
        $userNameBox = new cHTMLTextbox('user_name');
        $userNameBox->removeAttribute('size');
        $userNameBox = new cHTMLDiv($userNameBox);

        $userPwLbl = new cHTMLLabel(i18n('New password') . ': ', 'user_pw');
        $userPwBox = new cHTMLTextbox('user_pw');
        $userPwBox->setAttribute('type', 'password');
        $userPwBox->removeAttribute('size');
        $userPwBox = new cHTMLDiv($userPwBox);

        $userPwRepeatLbl = new cHTMLLabel(i18n('Confirm new password'), 'user_pw_repeat');
        $userPwRepeatBox = new cHTMLTextbox('user_pw_repeat');
        $userPwRepeatBox->setAttribute('type', 'password');
        $userPwRepeatBox->removeAttribute('size');

        $sendBtn = new cHTMLButton('submit');
        $sendBtn->setAttribute('type', 'image');
        $sendBtn->setAttribute('src', 'images/submit.gif');
        $sendBtn->setAttribute('alt', i18n('Submit'));
        $sendBtn->setAttribute('title', i18n('Submit'));
        $sendBtn->setAttribute('class', 'send_btn');
        $lastFormRow = new cHTMLDiv($userPwRepeatLbl . $userPwRepeatBox . $sendBtn);
        $lastFormRow->setAttribute('class', 'last_row');

        $sendBtn->removeAttribute('value');
        $form->setContent([$userNameLbl, $userNameBox, $userPwLbl, $userPwBox, $lastFormRow]);

        $this->_tpl->set('s', 'RESET_MESSAGE', '');

        $this->_tpl->set('s', 'RESET_FORM', $form->render());
    }

    /**
     * Getter function to obtain an array of all current user password reset requests
     *
     * @return array
     *
     * @throws cDbException
     * @throws cException
     */
    protected function _getCurrentRequests()
    {
        $oApiUserPasswordRequest = new cApiUserPasswordRequestCollection();

        return $oApiUserPasswordRequest->fetchCurrentRequests();
    }

    /**
     * Function checks password request for errors and sends a mail using
     * _submitMail() in case of valid requests
     *
     * @return string
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _handleNewPassword()
    {
        // notification message, which is returned to caller
        $message = '';

        // check if requested username exists, also get email and timestamp when
        // user last requests a new password (last_pw_request)
        $sql = "SELECT
                    username, email
                FROM
                    " . $this->_cfg['tab']['user'] . "
                WHERE
                    username = '" . $this->_db->escape($this->_username) . "'
                    AND (valid_from <= NOW() OR valid_from = '0000-00-00 00:00:00' OR valid_from IS NULL)
                    AND (valid_to >= NOW() OR valid_to = '0000-00-00 00:00:00' OR valid_to IS NULL)";
        $this->_db->query($sql);

        if ($this->_db->nextRecord() && md5($this->_username) == md5($this->_db->f('username'))) {
            // by default user is allowed to request new password
            $isAllowed = true;

            // we need latest password request for timelimit comparison
            $lastPwRequest = '0000-00-00 00:00:00';

            // check if user has already used max amount of reset requests
            $oApiUser = new cApiUser();
            // try to load user by name
            // this should always work because username in database already confirmed
            if (false === $oApiUser->loadBy('username', $this->_username)) {
                $isAllowed = false;
                $message   = i18n('New password was submitted to your e-mail address.');
            } else {
                $oApiPasswordRequestCol = new cApiUserPasswordRequestCollection();
                $requests               = $oApiPasswordRequestCol->fetchAvailableRequests();

                // do maintainance for all user password requests
                foreach ($requests as $oApiUserPasswordRequest) {
                    // get time of password reset request
                    $reqTime = $oApiUserPasswordRequest->get('request');

                    // if $reqTime is newer than $lastPwRequest then use this as new last password request time
                    if (strtotime($lastPwRequest) < strtotime($reqTime)
                        && $this->_db->f($oApiUser->getPrimaryKeyName()) === $oApiUser->get(
                            $oApiUser->getPrimaryKeyName()
                        )
                    ) {
                        $lastPwRequest = $reqTime;
                    }

                    // check if password request is too old and considered outdated
                    // by default 1 day old requests are outdated
                    if (false === ($outdatedStr = getEffectiveSetting('pw_request', 'outdated_threshold', false))
                        || '' === $outdatedStr
                    ) {
                        $outdatedStr = '-1 day';
                    }
                    // convert times to DateTime objects for comparison
                    // force all data to be compared using UTC timezone
                    $outdated = new DateTime('now', new DateTimeZone('UTC'));
                    $outdated->modify($outdatedStr);
                    $expiration = new DateTime($oApiUserPasswordRequest->get('expiration'), new DateTimeZone('UTC'));
                    if (false === $oApiUserPasswordRequest->get('expiration')
                        || '' === $oApiUserPasswordRequest->get('expiration')
                        || $expiration < $outdated
                    ) {
                        // delete password request as it is considered outdated
                        $oApiPasswordRequestCol->delete(
                            $oApiUserPasswordRequest->get($oApiUserPasswordRequest->getPrimaryKeyName())
                        );
                    }
                }

                // get all password reset requests related to entered username in form
                $uid      = $oApiUser->get($oApiUser->getPrimaryKeyName());
                $requests = $oApiPasswordRequestCol->fetchAvailableRequests($uid);

                // get amount of max password reset requests
                if (false === ($resetThreshold = getEffectiveSetting('pw_request', 'reset_threshold', false))
                    || '' === $resetThreshold
                ) {
                    // use 4 as default value
                    $resetThreshold = 4;
                }

                // check if there are more than allowed number of password requests for user
                if (count($requests) > $resetThreshold) {
                    $isAllowed = false;
                    $message   =
                        i18n('Too many password reset requests. You may wait before requesting a new password.');
                }
                unset($requests);
            }

            // store users mail address to class variable
            $this->_email = $this->_db->f('email');

            // check if there is a correct last request date
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $lastPwRequest, $aMatches)) {
                $lastRequest =
                    mktime($aMatches[4], $aMatches[5], $aMatches[6], $aMatches[2], $aMatches[3], $aMatches[1]);

                // check if this last request is longer ago than timelimit.
                if ((time() - $lastRequest) < (60 * $this->_reloadTime)) {
                    // user is not allowed to request new password, he has to wait
                    $isAllowed = false;
                    $message   = sprintf(i18n('Password requests are allowed every %s minutes.'), $this->_reloadTime);
                }
            }

            $this->_username = stripslashes($this->_username);

            // check if syntax of users mail address is correct and there is no
            // standard mail address like admin_kunde@IhreSite.de or
            // sysadmin@IhreSite.de
            if ((!preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $this->_email)
                    || $this->_email == 'sysadmin@IhreSite.de'
                    || $this->_email == 'admin_kunde@IhreSite.de')
                && $isAllowed
            ) {
                $isAllowed = false;
                // $sMessage = i18n('The requested user has no valid e-mail
                // address. Submitting new password is not possible. Please
                // contact your system- administrator for further support.');
                $message = i18n('No matching data found. Please contact your system administrator.');
            }

            // if there are no errors, call function _generateToken(), else wait
            // a while, then return error message
            if ($isAllowed) {
                // generate a new token
                $token = $this->_generateToken();

                // how long should the password reset request be valid?
                // use 4 hours as expiration time
                $expiration = new DateTime('+4 hour', new DateTimeZone('UTC'));

                if (false == $token || false === $this->_safePwResetRequest($token, $expiration)
                    || false === $this->_submitMail($token)
                ) {
                    $message = i18n('An unknown problem occurred. Please contact your system administrator.');
                } else {
                    $message = i18n('New password was submitted to your e-mail address.');
                }
            } else {
                sleep(5);
            }
        } else {
            // sleep a while, then return error message
            // $sMessage = i18n('This user does not exist.');
            $message = i18n('No matching data found. Please contact your system administrator.');
            sleep(5);
        }

        return $message;
    }

    /**
     * Function checks password reset request for errors and sets a new password in case there is no error
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _handleResetPw()
    {
        $this->_tpl->set('s', 'JS_CALL', 'showResetLayer();');
        $username = (string)$_POST['user_name'];
        $pw       = (string)$_POST['user_pw'];
        $pwRepeat = (string)$_POST['user_pw_repeat'];

        if (0 === cString::getStringLength($username)) {
            $this->_tpl->set('s', 'RESET_MESSAGE', i18n('Username can\'t be empty'));
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_renderNewPwForm();

            return;
        }
        if ($pw !== $pwRepeat) {
            $this->_tpl->set('s', 'RESET_MESSAGE', i18n('Passwords don\'t match'));
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_renderNewPwForm();

            return;
        }
        if ((string)$_POST['user_pw'] === (string)$_GET['pw_reset']) {
            $this->_tpl->set('s', 'RESET_MESSAGE', i18n('You may not use the confirmation token as password'));
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_renderNewPwForm();

            return;
        }

        // pass data to cApiUser class
        $oApiUser = new cApiUser();
        $oApiUser->loadUserByUsername($username);
        // check if user exists
        if (false === $oApiUser->isLoaded()) {
            // present same message as if it worked
            // so we do not give information whether a user exists
            $this->_tpl->set('s', 'RESET_MESSAGE', i18n('New password has been set.'));
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_tpl->set('s', 'RESET_FORM', '');

            return;
        }

        $oPasswordRequest = new cApiUserPasswordRequestCollection();
        // check if username matches validation token
        // user alice must not be able to set password for a different user bob

        // get available requests for all users
        if (null === ($requests = $this->_getCurrentRequests())) {
            // no password requests found but do not tell user
            $this->_tpl->set('s', 'RESET_MESSAGE', i18n('New password has been set.'));
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_tpl->set('s', 'RESET_FORM', '');

            return;
        }

        // check if passed get parameter matches request for one user
        $validUser = false;
        foreach ($requests as $request) {
            // match validation token against database password reset entry
            if ($request->get('validation_token') === $_GET['pw_reset']) {
                // we found the used token
                if ($oApiUser->get($oApiUser->getPrimaryKeyName()) === $request->get($oApiUser->getPrimaryKeyName())) {
                    // user entered in form matches user related to validation token
                    $validUser = true;
                }
            }
        }
        if (false === $validUser) {
            // no password requests found for this user
            // but let the user think it could set password for different user
            $this->_tpl->set('s', 'RESET_MESSAGE', i18n('New password has been set.'));
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_tpl->set('s', 'RESET_FORM', '');

            return;
        }

        // try to set password
        $res = $oApiUser->setPassword($pw);

        // check if password was accepted by cApiUser class
        if (cApiUser::PASS_OK !== $res) {
            // password not accepted, present error message from cApiUser class to end user
            $msg = cApiUser::getErrorString($res);
            $this->_tpl->set('s', 'RESET_MESSAGE', $msg);
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_renderNewPwForm();

            return;
        }

        // check if new password can be saved for user
        if (false !== $oApiUser->store()) {
            $this->_tpl->set('s', 'RESET_LABEL', '');
            $this->_tpl->set('s', 'RESET_FORM', '');
            // remove all password requests for this user from database
            $oPasswordRequest->deleteByUserId($oApiUser->get($oApiUser->getPrimaryKeyName()));
            $msg = i18n('New password has been set.');
        } else {
            // password could not be saved
            $msg = i18n('An unknown problem occurred. Please contact your system administrator.');
        }

        // display message in form
        $this->_tpl->set('s', 'RESET_MESSAGE', $msg);
    }

    /**
     * Save request into db for future validity check
     *
     * @param string   $token
     *         Token used to check for validity at user confirmation part
     * @param DateTime $expiration
     *
     * @return bool
     *         whether password request could be safed successfully
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _safePwResetRequest($token, DateTime $expiration)
    {
        $oUserPwRequestCol = new cApiUserPasswordRequestCollection();
        $oUserPwRequest    = $oUserPwRequestCol->createNewItem();

        // set request data
        $requestTime = new DateTime('now', new DateTimeZone('UTC'));
        $oApiUser    = new cApiUser();
        $oApiUser->loadBy('username', $this->_username);

        $oUserPwRequest->set($oApiUser->getPrimaryKeyName(), $oApiUser->get($oApiUser->getPrimaryKeyName()));
        $oUserPwRequest->set('request', $requestTime->format('Y-m-d H:i:s'));
        $oUserPwRequest->set('expiration', $expiration->format('Y-m-d H:i:s'));
        $oUserPwRequest->set('validation_token', $token);

        // save request data
        return $oUserPwRequest->store();
    }

    /**
     * Function submits new password to users mail address
     *
     * @param string $token
     *         The token used to authorise password change
     *
     * @return bool true if successful
     * @throws cException
     */
    protected function _submitMail($token)
    {
        $cfg = cRegistry::getConfig();

        $token = (string)$token;

        // get translation for mailbody and insert username and new password
        $msg      = i18n(
            "Dear CONTENIDO-User %s,\n\nA request to change your password for Content Management System CONTENIDO was made. "
        );
        $msg      .= i18n("Use the following URL to confirm the password change:\n\n");
        $msg      .= cRegistry::getBackendUrl() . '?pw_reset=';
        $msg      .= i18n("%s\n\nBest regards\n\nYour CONTENIDO sysadmin");
        $mailBody = sprintf($msg, $this->_username, $token);

        $from = [$this->_sendermail => $this->_sendername];

        // Decoding and encoding for charsets (without UTF-8)
        if ($cfg['php_settings']['default_charset'] != 'UTF-8') {
            $subject = utf8_encode(
                conHtmlEntityDecode(
                    stripslashes(i18n('Your new password for CONTENIDO Backend')),
                    '',
                    $cfg['php_settings']['default_charset']
                )
            );
            $body    = utf8_encode(conHtmlEntityDecode($mailBody, '', $cfg['php_settings']['default_charset']));
        } else {
            $subject = conHtmlEntityDecode(stripslashes(i18n('Your new password for CONTENIDO Backend')));
            $body    = conHtmlEntityDecode($mailBody);
        }

        try {
            $mailer = new cMailer();
            $mailer->sendMail($from, $this->_email, $subject, $body);

            return true;
        } catch (cDbException $e) {
            return false;
        } catch (cInvalidArgumentException $e) {
            return false;
        } catch (cException $e) {
            return false;
        }
    }

    /**
     * Function generates new token
     *
     * @return string
     *         The new token
     */
    protected function _generateToken()
    {
        // possible chars which were used in password
        $chars = "ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghjkmnopqrstuvwxyz123456789";

        $password = "";

        // for each character of token choose one from $sChars randomly
        for ($i = 0; $i < $this->_tokenLength; $i++) {
            $password .= $chars[rand(0, cString::getStringLength($chars))];
        }

        return $password;
    }
}
