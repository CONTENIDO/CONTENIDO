<?php

/**
 * This file contains the backend authentication handler class.
 *
 * @package    Core
 * @subpackage Authentication
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class is the backend authentication handler for CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
class cAuthHandlerBackend extends cAuth
{
    use cAuthBackendUserDetailsTrait;

    /**
     * Constructor to create an instance of this class.
     *
     * Automatically sets the lifetime of the authentication to the
     * configured value.
     */
    public function __construct()
    {
        $cfg = cRegistry::getConfig();
        $this->_lifetime = cSecurity::toInteger($cfg['backend']['timeout']);
        if ($this->_lifetime == 0) {
            $this->_lifetime = 15;
        }
    }

    /**
     * There is no pre-authentication in the backend.
     *
     * @inheritdoc
     */
    public function preAuthenticate()
    {
        return false;
    }

    /**
     * @deprecated [2023-02-05] Since CONTENIDO 4.10.2, use {@see cAuthHandlerBackend::preAuthenticate} instead
     */
    public function preAuthorize()
    {
        return $this->preAuthenticate();
    }

    /**
     * Includes a file which displays the backend login form.
     * @inheritdoc
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function displayLoginForm()
    {
        // @TODO  We need a better solution for this.
        //        One idea could be to set the request/response type in
        //        global $cfg array instead of checking $_REQUEST['ajax']
        //        everywhere...
        if (!empty($_REQUEST['ajax'] ?? '')) {
            $oAjax = new cAjaxRequest();
            $sReturn = $oAjax->handle('authentication_fail');
            echo $sReturn;
        } else {
            include(cRegistry::getBackendPath() . 'main.loginform.php');
        }
    }

    /**
     * @inheritdoc
     * @throws cDbException|cException
     */
    public function validateCredentials()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $formTmestamp = $_POST['formtimestamp'] ?? '';

        // add slashes if they are not automatically added
        if (cRegistry::getConfigValue('simulate_magic_quotes') !== true) {
            // backward compatibility of passwords
            $password = addslashes($password);
        }

        if ($password == '') {
            return false;
        }

        if (($formTmestamp + (60 * 15)) < time()) {
            return false;
        }

        if ($username !== '') {
            $this->auth['uname'] = $username;
        } elseif ($this->_defaultNobody) {
            return $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
        }

        $userDetails = $this->createUserDetailsObject();
        $this->loadBackendUserDetails($userDetails, $username);

        $result = $this->postProcessValidateCredentials($userDetails, $password);

        return $result ? $userDetails->userId : false;
    }

    /**
     * Log the successful authentication.
     *
     * Switches the globals $client & $lang to the first client/language for which the current user has permissions.
     * If a client/language combination is found, the action "login" is added to the actionlog.
     * Eventually the global $saveLoginTime is set to true which will trigger the update of the user properties
     * "currentlogintime" and "lastlogintime" in mycontenido.
     *
     * @inheritdoc
     *
     * @throws cDbException|cException
     */
    public function logSuccessfulAuth()
    {
        // NOTE: Use globals here
        global $client, $lang, $saveLoginTime;

        $perm = new cPermission();

        $saveLoginTime = false;

        // Find the first accessible client and language for the user
        $clientLangColl = new cApiClientLanguageCollection();
        $clientLangColl->select();

        $bFound = false;
        while ($bFound == false) {
            if (($item = $clientLangColl->next()) === false) {
                break;
            }

            $iTmpClient = $item->get('idclient');
            $iTmpLang = $item->get('idlang');

            if ($perm->have_perm_client_lang($iTmpClient, $iTmpLang)) {
                $client = $iTmpClient;
                $lang = $iTmpLang;
                $bFound = true;
            }
        }

        if (!isset($client) || !is_numeric($client) || !isset($lang) || !is_numeric($lang)) {
            return;
        }

        $idaction = $perm->getIdForAction('login');
        $userId = $this->getUserId();

        // create a actionlog entry
        $actionLogCol = new cApiActionlogCollection();
        $actionLogCol->create($userId, $client, $lang, $idaction, 0);

        $sess = cRegistry::getSession();
        $sess->register('saveLoginTime');
        $saveLoginTime = true;
    }

    /**
     * @inheritdoc
     */
    public function isLoggedIn(): bool
    {
        $userId = $this->getUserId();
        if (!empty($userId)) {
            $user = new cApiUser($userId);

            return $user->get('user_id') != '';
        } else {
            return false;
        }
    }

}
