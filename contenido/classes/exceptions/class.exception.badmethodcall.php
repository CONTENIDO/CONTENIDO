<?php

/**
 * This file contains the cBadMethodCallException class.
 *
 * @package Core
 * @subpackage Exception
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Exception thrown if a callback refers to an undefined method or if some
 * arguments are missing.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link BadMethodCallException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cBadMethodCallException extends cBadFunctionCallException {
}
