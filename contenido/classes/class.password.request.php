<?php

/**
 * This file contains the password request class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for handling passwort recovery of backend users.
 * If a user has set his e-mail address, this class generates a new password
 * for user and submits to his e-mail address. Submitting a new password is
 * only possible once within each defined repeat time (see $this->_reloadTime).
 * The mail settings configured in the system are used for sending the emails.
 *
 * The password request feature can be enabled/disabled via the setting:
 * pw_request > enable = 'true' or 'false'
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

        // get system-property, which defines if password request is enabled
        // (true) or disabled (false) : default to enabled
        $sEnable = getSystemProperty('pw_request', 'enable');
        $this->_isEnabled = !(($sEnable == 'false'));

        // get system-property for senders mail and validate mail address, if not
        // set use standard sender
        $sendermail = getSystemProperty('system', 'mail_sender');
        $validator = cValidatorFactory::getInstance('email');
        if ($validator->isValid($sendermail)) {
            $this->_sendermail = $sendermail;
        } else {
            $this->_sendermail = 'info@contenido.org';
        }

        // get system-property for senders name, if not set use CONTENIDO Backend
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
                        // do validation check then set new password for user in database
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

        // if form is submitted call function handleNewPassword() and set
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
        $form->setVar('belang', cRegistry::getBackendLanguage());

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
     * Returns the effective setting for the password request expiration time,
     * the setting value has to be a supported relative date format, see
     * {@link https://www.php.net/manual/en/datetime.formats.relative.php}.
     *
     * @return string  The found expiration setting, default value is '+4 hour'
     * @throws cDbException
     * @throws cException
     */
    public static function getExpirationSetting(): string
    {
        $expiration = trim(cSecurity::toString(
            cEffectiveSetting::get('pw_request', 'user_password_reset_expiration')
        ));
        if (empty($expiration)) {
            $expiration = '+4 hour';
        }

        return $expiration;
    }

    /**
     * Returns the effective setting 'outdated_threshold' for the password requests,
     * the setting value has to be a supported relative date format, see
     * {@link https://www.php.net/manual/en/datetime.formats.relative.php}.
     *
     * @return string  The found outdated threshold setting, default value is '-1 day'
     * @throws cDbException
     * @throws cException
     */
    public static function getOutdatedThresholdSetting(): string
    {
        $outdated = trim(cSecurity::toString(
            cEffectiveSetting::get('pw_request', 'outdated_threshold')
        ));
        if (empty($outdated)) {
            $outdated = '-1 day';
        }

        return $outdated;
    }


    /**
     * Returns the effective setting 'reset_threshold' for the max amount of password
     * requests a user can do.
     *
     * @return int  The found reset threshold setting, default value is 4
     * @throws cDbException
     * @throws cException
     */
    public static function getResetThresholdSetting(): int
    {
        $resetThreshold =  cSecurity::toInteger(
            cEffectiveSetting::get('pw_request', 'reset_threshold', '0')
        );
        if ($resetThreshold <= 0) {
            $resetThreshold = 4;
        }

        return $resetThreshold;
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
        $form = new cHTMLForm(
            'reset_form', htmlentities(cRegistry::getBackendUrl()) . '?pw_reset=' . $_GET['pw_reset']
        );

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
        // Notification message, which is returned to caller
        $message = '';

        // Check if requested username exists, also get email and timestamp when
        // user last requests a new password (last_pw_request)
        $oApiUserColl = new cApiUserCollection();
        $oApiUser = $oApiUserColl->fetchUserByName($this->_username, true);
        if (!is_object($oApiUser) || md5($this->_username) !== md5($oApiUser->get('username'))) {
            // Sleep a while, then return error message
            sleep(5);
            // return i18n('This user does not exist.');
            // return i18n('No matching data found. Please contact your system administrator.');
            return i18n('New password was submitted to your e-mail address.');
        }

        // Store some user information
        $this->_email = $oApiUser->getMail();
        $this->_username = stripslashes($this->_username);

        $lastPwRequest = '';

        // Check if there are more than allowed number of password requests for the user
        $isAllowed = $this->_checkPasswordRequest($oApiUser, $lastPwRequest);
        if (!$isAllowed) {
            $message = i18n(
                'Too many password reset requests. You may wait before requesting a new password.'
            );
        }

        // Check if there is a correct last request date
        if ($isAllowed) {
            $isAllowed = $this->_checkLastPasswordRequest($lastPwRequest);
            if (!$isAllowed) {
                $message = sprintf(
                    i18n('Password requests are allowed every %s minutes.'), $this->_reloadTime
                );
            }
        }

        // Check users mail address
        if ($isAllowed) {
            $isAllowed = $this->_checkUsersEmailAddress($oApiUser);
            if (!$isAllowed) {
                // $message = i18n('The requested user has no valid e-mail
                // address. Submitting new password is not possible. Please
                // contact your system- administrator for further support.');
                $message = i18n('No matching data found. Please contact your system administrator.');
            }
        }

        // If there are no errors, call function _generateToken(), else wait
        // a while, then return error message
        if ($isAllowed) {
            // generate a new token
            $token = $this->_generateToken();

            // how long should the password reset request be valid?
            // use 4 hours as expiration time
            $expiration = self::getExpirationSetting();
            $expirationDate = new DateTime($expiration, new DateTimeZone('UTC'));

            if (!$token || !$this->_safePwResetRequest($token, $expirationDate)
                || !$this->_submitMail($token)
            ) {
                $message = i18n('An unknown problem occurred. Please contact your system administrator.');
            } else {
                $message = i18n('New password was submitted to your e-mail address.');
            }
        } else {
            sleep(5);
        }

        return $message;
    }

    /**
     * Checks if the user has exceeded the amount of allowed password requests
     * within a defined time frame.
     *
     * @param cApiUser $oApiUser
     * @param string $lastPwRequestTime
     * @return bool
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _checkPasswordRequest(cApiUser $oApiUser, string &$lastPwRequestTime): bool
    {
        $oApiPasswordRequestCol = new cApiUserPasswordRequestCollection();

        // Do maintenance for all user password requests
        // @TODO Do we need to do the maintenance here? There is already a cronjob for this.
        $oApiPasswordRequestCol->deleteExpired();

        // Get users last (newest) password request and number of previous requests
        $lastPwRequestTime = $oApiPasswordRequestCol->getLastPasswordRequestTimeByUserIId($oApiUser->getUserId());
        $requestsCount = $oApiPasswordRequestCol->getPasswordRequestsCountByUserIId($oApiUser->getUserId());

        // Get amount of max allowed password reset requests
        $resetThreshold = self::getResetThresholdSetting();

        // Are there more than allowed number of password requests for the user?
        return !($requestsCount > $resetThreshold);
    }

    /**
     * Checks if the users any existing last password request time is older
     * than the defined repeat time. Only one password request within the
     * repeat time is allowed.
     *
     * @param string $lastPwRequest
     * @return bool
     */
    protected function _checkLastPasswordRequest(string $lastPwRequest): bool
    {
        // Check if there is a correct last request date
        $datePattern = '/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/';
        if (!empty($lastPwRequest) && preg_match($datePattern, $lastPwRequest, $aMatches)) {
            $lastRequest = mktime(
                $aMatches[4], $aMatches[5], $aMatches[6], $aMatches[2], $aMatches[3], $aMatches[1]
            );

            // Is the last request older than the time-limit?
            return !((time() - $lastRequest) < (60 * $this->_reloadTime));
        }

        return true;
    }

    /**
     * Checks the email address of the user, we need this, otherwise we can't
     * send the passwort reset email to the user.
     *
     * @param cApiUser $oApiUser
     * @return bool
     * @throws cInvalidArgumentException
     */
    protected function _checkUsersEmailAddress(cApiUser $oApiUser): bool
    {
        // Check if syntax of users mail address is correct and there is no
        // standard mail address like admin_kunde@IhreSite.de or
        // sysadmin@IhreSite.de
        $email = $oApiUser->getMail();
        $validator = cValidatorFactory::getInstance('email');
        if (!$validator->isValid($email)
            || $email == 'sysadmin@IhreSite.de'
            || $email == 'admin_kunde@IhreSite.de'
        ) {
            return false;
        }

        return true;
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
        $username = cSecurity::toString($_POST['user_name']);
        $pw       = cSecurity::toString($_POST['user_pw']);
        $pwRepeat = cSecurity::toString($_POST['user_pw_repeat']);
        $pwReset  = cSecurity::toString($_GET['pw_reset']);

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
        if ($pw === $pwReset) {
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
            if ($request->get('validation_token') === $pwReset) {
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
     *         whether password request could be saved successfully
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _safePwResetRequest($token, DateTime $expiration)
    {
        $oUserPwRequestCol = new cApiUserPasswordRequestCollection();
        $oUserPwRequest    = $oUserPwRequestCol->create();

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

        $length = cString::getStringLength($chars);

        // for each character of token choose one from $sChars randomly
        for ($i = 0; $i < $this->_tokenLength; $i++) {
            $password .= $chars[rand(0, $length - 1)];
        }

        return $password;
    }

}
