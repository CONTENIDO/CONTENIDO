<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for handling passwort recovery for backend users. If a user has set his e-mail address, this class
 * generates a new Password for user and submits to his e-mail adress. Submitting a new Password is
 * only possible every 30 minutes Mailsender, Mailsendername and Mailserver are set into system properties.
 * There it is also possible to deactivate this feature.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since 2008-03-20
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Description: Class for handling password recovery
 *
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 */
class cPasswordRequest {

    /**
     * The CONTENIDO database object
     *
     * @var object
     * @access private
     */
    protected $_db;

    /**
     * The CONTENIDO configuration array
     *
     * @var array
     * @access private
     */
    protected $_cfg;

    /**
     * The CONTENIDO template object
     *
     * @var object
     * @access private
     */
    protected $_tpl;

    /**
     * Username of user which requests password
     *
     * @var string
     * @access private
     */
    protected $_username;

    /**
     * E-mail address of user which requests password
     *
     * @var string
     * @access private
     */
    protected $_email;

    /**
     * Time in minutes after which user is allowed to request a new password
     *
     * @var integer
     * @access private
     */
    protected $_reloadTime;

    /**
     * Length of new passwort, which is generated automatically
     *
     * @var integer
     * @access private
     */
    protected $_passLength;

    /**
     * Defines if passwort request is enabled or disabled.
     * Default: This feature is enabled
     *
     * @var boolean
     * @access private
     */
    protected $_isEnabled;

    /**
     * E-mail address of the sender
     *
     * @var string
     * @access private
     */
    protected $_sendermail;

    /**
     * Name of the sender
     *
     * @var string
     * @access private
     */
    protected $_sendername;

    /**
     * Host of mailserver, which sends new password via mail
     *
     * @var string
     * @access private
     */
    protected $_mailhost;

    /* ################################################################ */
    /* ################################################################ */

    /**
     * Constructor of RequestPassword initializes class variables
     *
     * @param  object $db - The CONTENIDO database object
     * @param  array $cfg - The CONTENIDO configuration array
     * @access public
     */
    public function __construct($db, $cfg) {
        //generate new dbobject, if it does not exist
        if (!is_object($db)) {
            $this->_db = cRegistry::getDb();
        } else {
            $this->_db = $db;
        }

        //init class variables
        $this->_cfg = $cfg;
        $this->_tpl = new cTemplate();
        $this->_username = '';
        $this->_email = '';

        //set reload to 30 minutes
        $this->_reloadTime = 1;

        //set pass length to 14 chars
        $this->_passLength = 14;

        //get systemproperty, which definies if password request is enabled (true) or disabled (false) : default to enabled
        $sEnable = getSystemProperty('pw_request', 'enable');
        if ($sEnable == 'false') {
            $this->_isEnabled = false;
        } else {
            $this->_isEnabled = true;
        }

        //get systemproperty for senders mail and validate mailadress, if not set use standard sender
        $sSendermail = getSystemProperty('system', 'mail_sender');
        if (preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $sSendermail)) {
            $this->_sendermail = $sSendermail;
        } else {
            $this->_sendermail = 'noreply@contenido-passwordservice.de';
        }

        //get systemproperty for senders name, if not set use CONTENIDO Backend
        $sSendername = getSystemProperty('system', 'mail_sender_name');
        if ($sSendername != '') {
            $this->_sendername = $sSendername;
        } else {
            $this->_sendername = 'CONTENIDO Backend';
        }

        //get systemproperty for location of mailserver, if not set use localhost
        $sMailhost = getSystemProperty('system', 'mail_host');
        if ($sMailhost != '') {
            $this->_mailhost = $sMailhost;
        } else {
            $this->_mailhost = 'localhost';
        }
    }

    /**
     * Function displays form for password request and sets new password, if password is submitted this function
     * also starts the passwort change an sending process
     *
     * @access public
     * @param  bool    $return    Return or print template
     */
    public function renderForm($return = 0) {
        //if feature is not enabled, do nothing
        if (!$this->_isEnabled) {
            return;
        }

        $sMessage = '';

        //if form is sumbitted call function handleNewPassword() and set submitted username to class variable $sUsername
        if (isset($_POST['action']) && $_POST['action'] == 'request_pw') {
            //avoid SQL-Injection, first check if submitted vars are escaped automatically
            $this->_username = $_POST['request_username'];

            $sMessage = $this->_handleNewPassword();
            //if form is submitted, show corresponding password request layer
            $this->_tpl->set('s', 'JS_CALL', 'showRequestLayer();');
        } else {
            //by default request layer is invisible so da nothing
            $this->_tpl->set('s', 'JS_CALL', '');
        }

        //generate new form
        $oForm = new cHTMLForm('request_pw', 'index.php', 'post');

        //generate input for username
        $oInputUsername = new cHTMLTextbox('request_username', stripslashes($_POST['request_username']), '', '', 'request_username');
        $oInputUsername->setStyle('width:215px;');

        //set request action and current language
        $oForm->setVar('action', 'request_pw');
        $oForm->setVar('belang', $GLOBALS['belang']);

        //generate submitbutton and fill the form
        $oForm->setContent('<input type="image" src="images/submit.gif" alt="' . i18n('Submit') . '" title="' . i18n('Submit') . '" style="vertical-align:top; margin-top:2px; float:right; margin-right:6px;">' . $oInputUsername->render());
        $this->_tpl->set('s', 'FORM', $oForm->render());
        $this->_tpl->set('s', 'MESSAGE', $sMessage);
        $this->_tpl->set('s', 'LABEL', i18n('Please enter your login') . ':');

        //if handleNewPassword() returns a message, display it
        if ($return) {
            return $this->_tpl->generate($this->_cfg['path']['contenido'] . $this->_cfg['path']['templates'] . $this->_cfg['templates']['request_password'], 1);
        } else {
            return $this->_tpl->generate($this->_cfg['path']['contenido'] . $this->_cfg['path']['templates'] . $this->_cfg['templates']['request_password']);
        }
    }

    /**
     * Function checks password request for errors an delegate request to setNewPassword() if there is no error
     *
     * @access private
     * @param string - contains message for displaying (errors or success message)
     */
    protected function _handleNewPassword() {
        //notification message, which is returned to caller
        $sMessage = '';
        $this->_username = stripslashes($this->_username);

        //check if requested username exists, also get email and  timestamp when user last requests a new password (last_pw_request)
        $sSql = "SELECT username, last_pw_request, email FROM " . $this->_cfg["tab"]["phplib_auth_user_md5"] . "
                 WHERE username = '" . $this->_db->escape($this->_username) . "'
                 AND ( valid_from <= NOW() OR valid_from = '0000-00-00')
                 AND ( valid_to >= NOW() OR valid_to = '0000-00-00' )";

        $this->_db->query($sSql);
        if ($this->_db->next_record() && md5($this->_username) == md5($this->_db->f('username'))) {
            //by default user is allowed to request new password
            $bIsAllowed = true;
            $sLast_pw_request = $this->_db->f('last_pw_request');
            //store users mail adress to class variable
            $this->_email = $this->_db->f('email');

            //check if there is a correct last request date
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $sLast_pw_request, $aMatches)) {
                $iLastRequest = mktime($aMatches[4], $aMatches[5], $aMatches[6], $aMatches[2], $aMatches[3], $aMatches[1]);
                $iNow = time();

                //check if this last request is longer ago then timelimit.
                if ($iNow - $iLastRequest < (60 * $this->_reloadTime)) {
                    //user is not allowed to request new password, he has to wait
                    $bIsAllowed = false;
                    $sMessage = sprintf(i18n('Password requests are allowed every %s minutes.'), $this->_reloadTime);
                }
            }

            //check if syntax of users mail adress is correct and there is no standard mailadress like admin_kunde@IhreSite.de or sysadmin@IhreSite.de
            if ((!preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $this->_email) || $this->_email == 'sysadmin@IhreSite.de' || $this->_email == 'admin_kunde@IhreSite.de') && $bIsAllowed) {
                $bIsAllowed = false;
                //$sMessage = i18n('The requested user has no valid e-mail address. Submitting new password is not possible. Please contact your system- administrator for further support.');
                $sMessage = i18n('No matching data found. Please contact your systemadministrator.');
            }

            //if there are no errors, call function setNewPassword(), else wait a while, then return error message
            if ($bIsAllowed) {
                $this->_setNewPassword();
                $sMessage = i18n('New password was submitted to your e-mail address.');
            } else {
                sleep(5);
            }
        } else {
            //slepp a while, then return error message
            //$sMessage = i18n('This user does not exist.');
            $sMessage = i18n('No matching data found. Please contact your systemadministrator.');
            sleep(5);
        }
        return $sMessage;
    }

    /**
     * Function sets new password for user and sets last request time to now
     *
     * @access private
     */
    protected function _setNewPassword() {
        //generate new password, using generatePassword()
        $sPassword = $this->_generatePassword();

        //update database entry, set new password and last_pw_request time
        $sSql = "UPDATE " . $this->_cfg["tab"]["phplib_auth_user_md5"] . "
                         SET last_pw_request = '" . date('Y-m-d H:i:s') . "',
                             tmp_pw_request = '" . md5($sPassword) . "',
                             password = '" . md5($sPassword) . "'
                         WHERE username = '" . $this->_username . "'";
        echo $sSql;
        $this->_db->query($sSql);

        //call function submitMail(), which sends new password to user
        $this->_submitMail($sPassword);
    }

    /**
     * Function submits new password to users mail adress
     *
     * @access private
     * @param string $sPassword - the new password
     */
    protected function _submitMail($sPassword) {
        $sPassword = (string) $sPassword;

        //get translation for mailbody and insert username and new password
        $sMailBody = sprintf(i18n("Dear CONTENIDO-User %s,\n\nYour password to log in Content Management System CONTENIDO is: %s\n\nBest regards\n\nYour CONTENIDO sysadmin"), $this->_username, $sPassword);

        //use php mailer class for submitting mail
        $oMail = new PHPMailer();
        //set host of mailserver
        $oMail->Host = $this->_mailhost;
        //it is not a html mail
        $oMail->IsHTML(0);
        //set senders e-mail adress
        $oMail->From = $this->_sendermail;
        //set senders name
        $oMail->FromName = $this->_sendername;
        //set users e mail adress as recipient
        $oMail->AddAddress($this->_email, "");
        //set mail subject
        $oMail->Subject = stripslashes(i18n('Your new password for CONTENIDO Backend'));
        //append mail body
        $oMail->Body = $sMailBody;
        //wrap after 1000 chars
        $oMail->WordWrap = 1000;
        //activate mail and send it
        $oMail->IsMail();
        $oMail->Send();

    }

    /**
     * Function generates new password
     *
     * @access private
     * @return string - the new password
     */
    protected function _generatePassword() {
        //possible chars which were used in password
        $sChars = "ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghjkmnopqrstuvwxyz123456789";

        $sPw = "";

        //for each character of password choose one from $sChars randomly
        for ($i = 0; $i < $this->_passLength; $i++) {
            $sPw.= $sChars[rand(0, strlen($sChars))];
        }

        return $sPw;
    }

}
