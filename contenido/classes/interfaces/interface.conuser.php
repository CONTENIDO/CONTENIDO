<?php
/**
 * Contenido Content Management System User Interface
 *
 * This interface describes the main methods an user class
 * has to implement.
 *
 * @package Contenido Backend Classes
 * @subpackage Backend User
 *
 * @version $Revision$
 * @author Holger Librenz
 * @copyright four for business AG
 *
 * {@internal
 *  created 2008-11-16, H. Librenz
 *  modified 2008-12-04, Timo Trautman, Added Contenido $cfg as param for getErrorString()
 *
 *  $Id$
 * }}
 */

/**
 * Interface to describe main user functionallities.
 *
 * This interface describes the main methods an user calss
 * has to implement. It will be implemented in an abstract
 * class, which will implement main features and should be
 * used if you start implementing your own user class.
 *
 * @package Contenido Backend Classes
 * @subpackage Backend User
 *
 * @version 0.0.1
 * @author Holger Librenz
 * @copyright four for business AG
 *
 */
interface iConUser {

	/**
	 * Password is ok and stored.
	 *
	 * @var int
	 * @final
	 */
	const PASS_OK = 0;

	/**
	 * Given password is to short
	 *
	 * @var int
	 * @final
	 */
	const PASS_TO_SHORT =  1;

	/**
	 * Given password is not strong enough
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_STRONG =  2;

	/**
	 * Given password is not complex enough
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_COMPLEX =  3;

	/**
	 * Password does not contain enough numbers.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_NUMBERS =  4;

	/**
	 * Password does not contain enough symbols.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_SYMBOLS =  5;

	/**
	 * Password does not contain enough mixed characters.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_MIXED_CHARS =  6;

	/**
	 * Password does not contain enough different characters.
	 *
	 * @var int
	 * @final
	 */
	const PASS_NOT_ENOUGH_DIFFERENT_CHARS =  7;

	/**
	 * Exception code, which is used if you try to add an user
	 * that already exists.
	 *
	 * @var int
	 * @final
	 */
	const EXCEPTION_USERNAME_EXISTS =  8;


	/**
	 * Exception code, which is used if an password is set to save
	 * that is not valid.
	 *
	 * @var int
	 * @final
	 */
	const EXCEPTION_PASSWORD_INVALID =  9;

    /**
     * This value will be used if no minimum length
     * for passwords are set via $cfg['password']['min_length']
     *
     */
	const MIN_PASS_LENGTH_DEFAULT = 8;

	/**
	 * This method saves the given password $sNewPassword. The password
	 * has to be checked, before it is set to the database. The resulting
	 * integer value represents the result code.
	 * Use the PASS_* constants to check what happens.
	 *
	 * @param string $sNewPassword
	 */
	public function savePassword ($sNewPassword);

	/**
	 * Checks given password $sNewPassword is complex enough.
	 *
	 * This method should check everything the user has to do to
	 * have a "valid" password. Such a check can be "User has to use
	 * symbols in password, but not as first sign."
	 *
	 * @param string $sNewPassword
	 * @return int
	 */
	public function checkPasswordMask ($sNewPassword);

	/**
	 * Checks given password $sNewPassword has a valid strength.
	 *
	 * @param string $sNewPassword
	 * @return int
	 */
	public function checkPasswordStrength ($sNewPassword);

	/**
	 * This method tries to save all information collected for an user.
	 * If everything is fine, it returns true - otherwise false. On fatal
	 * errors a ConUserException will be thrown.
	 *
	 * @return boolean
	 */
	public function save ();

	/**
	 * Loads data for user $sUserId.
	 *
	 * @param string $sUserId
	 */
	public function load ($sUserId);

	/**
	 * Simple error messages, depending on error code $iErrorCode.
	 *
	 * @param int $iErrorCode
	 * @param array $aCfg Contenido configuration array
	 * @return string
	 */
	public static function getErrorString ($iErrorCode, $aCfg);

	/**
	 * Encodes the password $sPassword. You should use one-way
	 * encodings or hash-algorithms to ensure that nobody can
	 * read simply the passwords!
	 *
	 * @param string $sPassword
	 * @return string
	 */
	public static function encodePassword ($sPassword);
}

?>