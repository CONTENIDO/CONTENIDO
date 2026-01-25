<?php

/**
 * This file contains the frontend authentication handler class.
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
 * This class is the frontend authentication handler for CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
class cAuthHandlerFrontend extends cAuth
{
    use cAuthBackendUserDetailsTrait;

    /**
     *
     * @var bool
     */
    protected $_defaultNobody = true;

    /**
     * Constructor to create an instance of this class.
     *
     * Automatically sets the lifetime of the authentication to the
     * configured value.
     */
    public function __construct()
    {
        $cfg = cRegistry::getConfig();
        $this->_lifetime = (int)$cfg['frontend']['timeout'];
        if ($this->_lifetime == 0) {
            $this->_lifetime = 15;
        }
    }

    /**
     * @inheritdoc
     * @throws cDbException|cException
     */
    public function preAuthenticate()
    {
        $password = $_POST['password'] ?? '';

        if ($password == '') {
            // Stay as nobody when an empty password is passed
            $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;

            return false;
        }

        return $this->validateCredentials();
    }

    /**
     * @deprecated [2023-02-05] Since CONTENIDO 4.10.2, use {@see cAuthHandlerFrontend::preAuthenticate} instead
     */
    public function preAuthorize()
    {
        return $this->preAuthenticate();
    }

    /**
     * Includes a file which displays the frontend login form.
     * @inheritdoc
     */
    public function displayLoginForm()
    {
        include(cRegistry::getFrontendPath() . 'front_crcloginform.inc.php');
    }

    /**
     * @inheritdoc
     * @throws cDbException|cException
     */
    public function validateCredentials()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($password == '') {
            return false;
        }

        if ($username !== '') {
            $this->auth['uname'] = $username;
        } elseif ($this->_defaultNobody) {
            return $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
        }

        $userDetails = $this->createUserDetailsObject();
        $this->loadFrontendUserDetails($userDetails, $username);
        if (!$userDetails->userId) {
            $this->loadBackendUserDetails($userDetails, $username);
        }

        $result = $this->postProcessValidateCredentials($userDetails, $password);

        return $result ? $userDetails->userId : false;
    }

    /**
     * Frontend logins won't be logged.
     *
     * @inheritdoc
     */
    public function logSuccessfulAuth()
    {
    }

    /**
     * @inheritdoc
     */
    public function isLoggedIn(): bool
    {
        $userId = $this->getUserId();
        if (!empty($userId)) {
            $user = new cApiUser($userId);
            $frontendUser = new cApiFrontendUser($userId);

            return $user->get('user_id') != '' || $frontendUser->get('idfrontenduser') != '';
        } else {
            return false;
        }
    }

    /**
     * Populates the given user details object with frontend user information based on the provided username.
     *
     * @param stdClass $userDetails The object where the user details will be loaded.
     * @param string $username The username of the frontend user.
     * @since CONTENIDO 4.10.2
     */
    private function loadFrontendUserDetails(stdClass $userDetails, string $username)
    {
        try {
            $frontendUser = (new cApiFrontendUserCollection())
                ->fetchUserForLoginAttempt($username, cRegistry::getClientId());
            if ($frontendUser) {
                $userDetails->userId = $frontendUser->getId();
                $userDetails->perm = 'frontend';
                $userDetails->password = $frontendUser->get('password');
                $userDetails->salt = $frontendUser->get('salt');
            }
        } catch (cDbException|cException $e) {
            $e->log();
        }
    }

}
