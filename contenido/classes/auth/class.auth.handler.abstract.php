<?php
/**
 * This file contains the abstract authentication handler class.
 *
 * @package			Core
 * @subpackage		Authentication
 * @version			1.0
 *
 * @author			Dominik Ziegler
 * @copyright		four for business AG <www.4fb.de>
 * @license			http://www.contenido.org/license/LIZENZ.txt
 * @link			http://www.4fb.de
 * @link			http://www.contenido.org
 */

/**
 * @package			Core
 * @subpackage		Authentication
 *
 * This class contains the abstract methods for the authentication in CONTENIDO.
 */
abstract class cAuthHandlerAbstract extends cAuth {
	/**
	 * Handle the pre authorization. 
	 * Let return this method a valid user ID to set before the login form is handled, otherwise false.
	 *
	 * @return	string|false
	 */
	abstract public function preAuthorize();
	
	/** 
	 * Display the login form.
	 * Let this method include a file which displays the login form.
	 *
	 * @return	void
	 */
	abstract public function displayLoginForm();
	
	/**
	 * Validate the credentials.
	 * Let this method validate the users input against source and return a valid user ID or false.
	 *
	 * @return	string|false
	 */
	abstract public function validateCredentials();
	
	/**
	 * Log the successful authentication.
	 * If wished, this method can be executed for logging an successful authentication.
	 * @return	void
	 */
	abstract public function logSuccessfulAuth();
}