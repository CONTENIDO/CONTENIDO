<?php

/**
 * This file contains the abstract authentication handler class.
 *
 * @package    Core
 * @subpackage Authentication
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the abstract methods for the authentication in CONTENIDO.
 *
 * @package    Core
 * @subpackage Authentication
 */
abstract class cAuthHandlerAbstract extends cAuth {

    /**
     * Handle the pre authorization.
     * Let return this method a valid user ID to be set before the login form is
     * handled, otherwise false.
     *
     * @return string|false
     */
    abstract public function preAuthorize();

    /**
     * Display the login form.
     * Let this method include a file which displays the login form.
     */
    abstract public function displayLoginForm();

    /**
     * Validate the credentials.
     * Let this method validate the users input against source and return a
     * valid user ID or false.
     *
     * @return string|false
     */
    abstract public function validateCredentials();

    /**
     * Log the successful authentication.
     * If wished, this method can be executed to log a successful login.
     */
    abstract public function logSuccessfulAuth();


    /**
     * Returns true if a user is logged in
     *
     * @return bool
     */
    abstract public function isLoggedIn();

}
