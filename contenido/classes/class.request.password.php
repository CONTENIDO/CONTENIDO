<?php
/**
 * Project:
 * Contenido Content Management System
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
 * @package    Contenido Backend classes
 * @version    1.1.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since 2008-03-20
 *
 * {@internal
 *   created 2008-03-20
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2010-05-27, Oliver Lohkemper, check if user activ in handleNewPassword()
 *   modified 2011-02-26, Ortwin Pinke, added temporary pw request behaviour, so user may login with old and/or requested pw
 *
 *   $Id: class.request.password.php 1309 2011-02-26 14:32:42Z oldperl $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 *
 * Description: Class for handling passwort recovery
 *
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 *
 */
class RequestPassword {
    /**
      * The contenido database object
      *
      * @var object
      * @access private
      */
    var $oDb;

    /**
      * The contenido configuration array
      *
      * @var array
      * @access private
      */
    var $aCfg;

    /**
      * The contenido template object
      *
      * @var object
      * @access private
      */
    var $oTpl;

    /*################################################################*/

    /**
      * Username of user which requests password
      *
      * @var string
      * @access private
      */
    var $sUsername;

    /**
      * E-mail address of user which requests password
      *
      * @var string
      * @access private
      */
    var $sEmail;

    /*################################################################*/

    /**
      * Time in minutes after which user is allowed to request a new password
      *
      * @var integer
      * @access private
      */
    var $iReloadTime;

    /**
      * Length of new passwort, which is generated automatically
      *
      * @var integer
      * @access private
      */
    var $iPassLength;

    /*################################################################*/

    /**
      * Definies if passwort request is enabled or disabled.
      * Default: This feature is enabled
      *
      * @var boolean
      * @access private
      */
    var $bIsEnabled;

    /**
      * E-mail address of the sender
      *
      * @var string
      * @access private
      */
    var $sSendermail;

    /**
      * Name of the sender
      *
      * @var string
      * @access private
      */
    var $sSendername;

    /**
      * Host of mailserver, which sends new password via mail
      *
      * @var string
      * @access private
      */
    var $sMailhost;

    /*################################################################*/
    /*################################################################*/

    /**
      * Constructor of RequestPassword initializes class variables
      *
      * @param  object $oDb - The contenido database object
      * @param  array $aCfg - The contenido configuration array
      * @access public
      */
    function RequestPassword ($oDb, $aCfg) {
        //generate new dbobject, if it does not exist
        if (!is_object($oDb)) {
            $this->oDb = new DB_contenido();
        } else {
            $this->oDb = $oDb;
        }

        //init class variables
        $this->aCfg = $aCfg;
        $this->oTpl = new Template();
        $this->sUsername = '';
        $this->sEmail = '';

        //set reload to 30 minutes
        $this->iReloadTime = 30;

        //set pass length to 14 chars
        $this->iPassLength = 14;

        //get systemproperty, which definies if password request is enabled (true) or disabled (false) : default to enabled
        $sEnable = getSystemProperty('pw_request', 'enable');
        if ($sEnable == 'false') {
            $this->bIsEnabled = false;
        } else {
            $this->bIsEnabled = true;
        }

        //get systemproperty for senders mail and validate mailadress, if not set use standard sender
        $sSendermail = getSystemProperty('system', 'mail_sender');
        if (preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $sSendermail)) {
            $this->sSendermail = $sSendermail;
        } else {
            $this->sSendermail = 'noreply@contenido-passwordservice.de';
        }

        //get systemproperty for senders name, if not set use Contenido Backend
        $sSendername = getSystemProperty('system', 'mail_sender_name');
        if ($sSendername != '') {
            $this->sSendername = $sSendername;
        } else {
            $this->sSendername = 'Contenido Backend';
        }

        //get systemproperty for location of mailserver, if not set use localhost
        $sMailhost = getSystemProperty('system', 'mail_host');
        if ($sMailhost != '') {
            $this->sMailhost = $sMailhost;
        } else {
            $this->sMailhost = 'localhost';
        }
    }

    /**
      * Function displays form for password request and sets new password, if password is submitted this function
      * also starts the passwort change an sending process
      *
      * @access public
      */
    function renderForm () {
        //if feature is not enabled, do nothing
        if (!$this->bIsEnabled) {
            return;
        }

        $sMessage = '';

        //if form is sumbitted call function handleNewPassword() and set submitted username to class variable $sUsername
        if (isset($_POST['action']) && $_POST['action'] == 'request_pw') {
            //avoid SQL-Injection, first check if submitted vars are escaped automatically
            $this->sUsername = $_POST['request_username'];

            $sMessage = $this->handleNewPassword();
            //if form is submitted, show corresponding password request layer
            $this->oTpl->set('s', 'JS_CALL', 'showRequestLayer();');
        } else {
            //by default request layer is invisible so da nothing
            $this->oTpl->set('s', 'JS_CALL', '');
        }

        //generate new form
        $oForm = new UI_Form('request_pw', 'index.php', 'post');

        //generate input for username
        $oInputUsername = new cHTMLTextbox('request_username', stripslashes($_POST['request_username']), '', '', 'request_username');
        $oInputUsername->setStyle('width:215px;');

        //set request action and current language
        $oForm->setVar('action', 'request_pw');
        $oForm->setVar('belang', $GLOBALS['belang']);

        //generate submitbutton and fill the form
        $oForm->add('submit', '<input type="image" src="images/submit.gif" alt="'.i18n("Submit").'" title="'.i18n("Submit").'" style="vertical-align:top; margin-top:2px; float:right; margin-right:6px;">');
        $oForm->add('request_username', $oInputUsername->render());
        $this->oTpl->set('s', 'FORM', $oForm->render());
        $this->oTpl->set('s', 'MESSAGE', $sMessage);
        $this->oTpl->set('s', 'LABEL', i18n("Please enter your login").':');

        //if handleNewPassword() returns a message, display it
        $this->oTpl->generate($this->aCfg['path']['contenido'].$this->aCfg['path']['templates'].$this->aCfg['templates']['request_password']);
    }

    /**
      * Function checks password request for errors an delegate request to setNewPassword() if there is no error
      *
      * @access private
      * @param string - contains message for displaying (errors or success message)
      */
    function handleNewPassword () {
        //notification message, which is returned to caller
        $sMessage = '';
        $this->sUsername = stripslashes($this->sUsername);

        //check if requested username exists, also get email and  timestamp when user last requests a new password (last_pw_request)
        $sSql = "SELECT username, last_pw_request, email FROM ".$this->aCfg["tab"]["phplib_auth_user_md5"]."
			     WHERE username = '".$this->oDb->escape($this->sUsername)."'
				 AND ( valid_from <= NOW() OR valid_from = '0000-00-00')
                 AND ( valid_to >= NOW() OR valid_to = '0000-00-00' )";

    	$this->oDb->query($sSql);
    	if ($this->oDb->next_record() && md5($this->sUsername) == md5($this->oDb->f('username'))) {
            //by default user is allowed to request new password
            $bIsAllowed = true;
            $sLast_pw_request = $this->oDb->f('last_pw_request');
            //store users mail adress to class variable
            $this->sEmail = $this->oDb->f('email');

            //check if there is a correct last request date
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $sLast_pw_request, $aMatches)) {
                $iLastRequest = mktime($aMatches[4], $aMatches[5], $aMatches[6], $aMatches[2], $aMatches[3], $aMatches[1]);
                $iNow = time();

                //check if this last request is longer ago then timelimit.
                if ($iNow-$iLastRequest < (60*$this->iReloadTime)) {
                    //user is not allowed to request new password, he has to wait
                    $bIsAllowed = false;
                    $sMessage = sprintf(i18n("Password requests are allowed every %s minutes."), $this->iReloadTime);
                }
            }

            //check if syntax of users mail adress is correct and there is no standard mailadress like admin_kunde@IhreSite.de or sysadmin@IhreSite.de
            if ((!preg_match("/^.+@.+\.([A-Za-z0-9\-_]{1,20})$/", $this->sEmail) || $this->sEmail == 'sysadmin@IhreSite.de' || $this->sEmail == 'admin_kunde@IhreSite.de') && $bIsAllowed) {
                $bIsAllowed = false;
                //$sMessage = i18n("The requested user has no valid e-mail address. Submitting new password is not possible. Please contact your system- administrator for further support.");
                $sMessage = i18n("No matching data found. Please contact your systemadministrator.");
            }

            //if there are no errors, call function setNewPassword(), else wait a while, then return error message
            if ($bIsAllowed) {
                $this->setNewPassword();
                $sMessage = i18n("New password was submitted to your e-mail address.");
            } else {
                sleep(5);
            }
        } else {
            //slepp a while, then return error message
            //$sMessage = i18n("This user does not exist.");
            $sMessage = i18n("No matching data found. Please contact your systemadministrator.");
            sleep(5);
        }
        return $sMessage;
    }

    /**
      * Function sets new password for user and sets last request time to now
      *
      * @access private
      */
    function setNewPassword () {
        //generate new password, using generatePassword()
        $sPassword = $this->generatePassword();

        //update database entry, set new password and last_pw_request time
        $sSql = "UPDATE ".$this->aCfg["tab"]["phplib_auth_user_md5"]."
                         SET last_pw_request = '".date('Y-m-d H:i:s')."',
                             tmp_pw_request = '".md5($sPassword)."'
                         WHERE username = '".$this->sUsername."'";
        $this->oDb->query($sSql);

        //call function submitMail(), which sends new password to user
        $this->submitMail($sPassword);
    }

   /**
      * Function submits new password to users mail adress
      *
      * @access private
      * @param string $sPassword - the new password
      */
    function submitMail ($sPassword) {
        $sPassword = (string) $sPassword;

        //get translation for mailbody and insert username and new password
        $sMailBody = sprintf(i18n("Dear Contenidouser %s,\n\nYour password to log in Content Management System Contenido is: %s\n\nBest regards\n\nYour Contenido sysadmin"), $this->sUsername, $sPassword);
        //use php mailer class for submitting mail
        $oMail = new PHPMailer;
        //set host of mailserver
        $oMail->Host = $this->sMailhost;
        //it is not a html mail
        $oMail->IsHTML(0);
        //set senders e-mail adress
        $oMail->From = $this->sSendermail;
        //set senders name
        $oMail->FromName = $this->sSendername;
        //set users e mail adress as recipient
        $oMail->AddAddress($this->sEmail, "");
        //set mail subject
        $oMail->Subject = stripslashes (i18n("Your new password for Contenido Backend"));
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
    function generatePassword () {
       //possible chars which were used in password
	   $sChars = "ABCDEFGHJKLMNOPQRSTUVWXYZabcdefghjkmnopqrstuvwxyz123456789";

		$sPw = "";

        //for each character of password choose one from $sChars randomly
		for ($i = 0; $i < $this->iPassLength; $i++) {
			$sPw.= $sChars[rand(0, strlen($sChars))];
		}

		return $sPw;
    }
}

?>
