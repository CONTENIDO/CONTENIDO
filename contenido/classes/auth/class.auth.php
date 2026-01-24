<?php

/**
 * This file contains the global authentication class.
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
 * This class contains functions for global authentication in CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
abstract class cAuth
{

    /**
     * Authentication user ID for nobody.
     *
     * @var string
     */
    public const AUTH_UID_NOBODY = 'nobody';

    /**
     * Authentication user ID for calling a login form.
     *
     * @var string
     */
    public const AUTH_UID_FORM = 'form';

    /**
     * The global auth information array.
     *
     * This array has these keys:
     * - uid = user_id for backend users and idfrontenduser for frontendusers
     * - uname = username as part of the credentials to login
     * - perm = user and group permissions as CSV
     * - exp = expiration date as Unix timestamp
     *
     * @var array
     */
    public $auth = [];

    /**
     * Lifetime for authenticated users in minutes.
     * After that time the authentication expires.
     *
     * @var int
     */
    protected $_lifetime = 15;

    /**
     * Automatic authentication as nobody.
     *
     * @var bool
     */
    protected $_defaultNobody = false;

    /**
     * The "in flag".
     * Nobody knows for which reason it exists.
     *
     * @var bool
     */
    private $_in = false;

    /**
     * Property used for session persistency, by cSession.
     * This property needs to be public since cSession has to set it!
     *
     * @var array
     */
    public $persistent_slots = ['auth'];

    /**
     * Handle the pre-authorization.
     *
     * When implementing this method, let it return a valid user ID to be
     * set before the login form is handled, otherwise false.
     *
     * @return string|false
     */
    abstract public function preAuthenticate();

    /**
     * @deprecated [2023-02-05] Since CONTENIDO 4.10.2, use {@see cAuthHandlerAbstract::preAuthenticate} instead
     */
    abstract public function preAuthorize();

    /**
     * When implementing this method, let this method render the login form.
     */
    abstract public function displayLoginForm();

    /**
     * Validate the credentials.
     *
     * When implementing this method, let this method validate the users'
     * input against the source and return a valid user ID (`int` or `string`) or `false`.
     *
     * @return int|string|false
     */
    abstract public function validateCredentials();

    /**
     * Log a successful authentication.
     * This method can be executed to log a successful login.
     */
    abstract public function logSuccessfulAuth();

    /**
     * Returns true if a user is logged in
     *
     * @return bool
     */
    abstract public function isLoggedIn(): bool;

    /**
     * Magic getter function for outdated variable names.
     *
     * @param string $name Name of the variable
     * @return int|string|null
     */
    public function __get(string $name)
    {
        if ($name === 'lifetime') {
            return $this->_lifetime;
        } elseif ($name === 'classname') {
            return get_class($this);
        }

        return null;
    }

    /**
     * Starts the authentication process.
     */
    public function start()
    {
        $sess = cRegistry::getSession();
        if (!$this->_in) {
            $sess->register('auth');
            $this->_in = true;
        }

        if ($this->isAuthenticated()) {
            $userId = $this->getUserId();
            if ($userId === self::AUTH_UID_FORM) {
                $userId = $this->validateCredentials();
                if ($userId !== false) {
                    $this->_setAuthInfo($userId);
                    $this->logSuccessfulAuth();
                } else {
                    $this->_fetchLoginForm();
                }
            } elseif ($userId !== self::AUTH_UID_NOBODY) {
                $this->_setExpiration();
            }
        } else {
            $this->resetAuthInfo();

            $userId = $this->preAuthenticate();
            if ($userId !== false) {
                $this->_setAuthInfo($userId);

                return;
            }

            if ($this->_defaultNobody) {
                $this->_setAuthInfo(self::AUTH_UID_NOBODY, 0x7fffffff);
            } else {
                $this->_fetchLoginForm();
            }
        }
    }

    /**
     * Restarts the authentication process.
     */
    public function restart()
    {
        $this->resetAuthInfo();
        $this->_defaultNobody = false;
        $this->start();
    }

    /**
     * Resets the global authentication information.
     *
     * @param bool $nobody If the flag set to true, the default authentication is
     *      switched to nobody. (optional, default: false)
     */
    public function resetAuthInfo(bool $nobody = false)
    {
        $this->auth['uid'] = $nobody ? self::AUTH_UID_NOBODY : '';
        $this->auth['uname'] = $nobody ? self::AUTH_UID_NOBODY : '';
        $this->auth['perm'] = '';
        // TODO 0x7fffffff is the timestamp 2147483647. This means it won't work after the date 19.01.2038!
        $this->_setExpiration($nobody ? 0x7fffffff : 0);
    }

    /**
     * Logs out the current user, resets the auth information and freezes the session.
     *
     * @param bool $nobody If the flag set to true, nobody is recreated as a user.
     */
    public function logout(bool $nobody = false): bool
    {
        $sess = cRegistry::getSession();

        $sess->unregister('auth');
        unset($this->auth['uname']);

        $this->resetAuthInfo($nobody ?: $this->_defaultNobody);
        $sess->freeze();

        return true;
    }

    /**
     * Getter for the auth information.
     */
    public function getAuthInfo(): array
    {
        return $this->auth;
    }

    /**
     * Checks if the user is authenticated (NOT logged in!).
     *
     * @return bool|string The userid if the user is authenticated, otherwise false.
     */
    public function isAuthenticated()
    {
        $authInfo = $this->getAuthInfo();
        $userId = $this->getUserId();

        if (!empty($userId) && (($this->_lifetime <= 0) || (time() < $authInfo['exp']))) {
            return $userId;
        } else {
            return false;
        }
    }

    /**
     * Checks if the user is currently in login form mode.
     */
    public function isLoginForm(): bool
    {
        return $this->getUserId() === self::AUTH_UID_FORM;
    }

    /**
     * Returns the user id of the currently authenticated user
     */
    public function getUserId(): string
    {
        $authInfo = $this->getAuthInfo();

        return $authInfo['uid'] ?? '';
    }

    /**
     * Returns the username of the currently authenticated user
     */
    public function getUsername(): string
    {
        $authInfo = $this->getAuthInfo();

        return $authInfo['uname'] ?? '';
    }

    /**
     * Returns the permission string of the currently authenticated user
     */
    public function getPerms(): string
    {
        $authInfo = $this->getAuthInfo();

        return $authInfo['perm'] ?? '';
    }


    /**
     * Returns the permission of the currently authenticated user as array.
     *
     * @since CONTENIDO 4.10.2
     */
    public function getPermsArray(): array
    {
        return cPermission::permissionToArray($this->getPerms());
    }

    /**
     * Sets or refreshes the expiration of the authentication.
     *
     * @param ?int $expiration New expiration (optional, default: NULL = current time plus lifetime minutes)
     */
    protected function _setExpiration(?int $expiration = NULL)
    {
        if ($expiration === NULL) {
            $expiration = time() + (60 * $this->_lifetime);
        }

        $this->auth['exp'] = $expiration;
    }

    /**
     * Fetches the login form.
     */
    protected function _fetchLoginForm()
    {
        $sess = cRegistry::getSession();
        $this->_setAuthInfo(self::AUTH_UID_FORM, 0x7fffffff);
        $this->displayLoginForm();
        $sess->freeze();
        exit();
    }

    /**
     * Sets the authentication info for a user.
     *
     * @param string|int $userId User ID to set
     * @param int $expiration [optional] Expiration (optional, default: NULL)
     */
    protected function _setAuthInfo($userId, $expiration = NULL)
    {
        $this->auth['uid'] = $userId;
        $this->_setExpiration($expiration);
    }

    /**
     * Validates the provided credentials and processes permissions for the user.
     *
     * @param stdClass $userDetails The user details, see {@see cAuth::createUserDetailsObject()}.
     * @param string $password The plaintext password provided by the user.
     * @return bool Returns true if the credentials are valid; false otherwise.
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    protected function postProcessValidateCredentials(stdClass $userDetails, string $password): bool
    {
        if (!$userDetails->userId || cApiUser::hashPassword($password, $userDetails->salt) != $userDetails->password) {
            sleep(2);

            return false;
        }

        $groupPerm = [];
        if ($userDetails->perm != '') {
            $groupPerm[] = $userDetails->perm;
        }

        $groupColl = new cApiGroupCollection();
        $this->auth['perm'] = cPermission::permissionToString(
            array_merge($groupPerm, $groupColl->getPermissionsByUserId($userDetails->userId))
        );

        return true;
    }

    /**
     * Creates and returns a new user details object with default properties set to null.
     * The returned object is used during the process of the validation details.
     *
     * @return stdClass{
     *      userId: string|int|null,
     *      perm: ?string,
     *      password: ?string,
     *      salt: ?string
     *  } A new instance of stdClass representing the user details object.
     * @since CONTENIDO 4.10.2
     */
    protected function createUserDetailsObject(): stdClass
    {
        $obj = new stdClass();
        $obj->userId = null;
        $obj->perm = null;
        $obj->password = null;
        $obj->salt = null;

        return $obj;
    }
}
