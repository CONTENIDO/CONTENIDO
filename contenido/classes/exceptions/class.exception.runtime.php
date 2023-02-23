<?php
/**
 * This file contains the cRuntimeException class.
 *
 * @package Core
 * @subpackage Exception
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

/**
 * Exception thrown if an error which can only be found on runtime occurs.
 * This kind of exceptions should directly lead to a fix in your code.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link RuntimeException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cRuntimeException extends cException {

}
