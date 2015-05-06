<?php

/**
 * This file contains the cOutOfRangeException class.
 *
 * @package Core
 * @subpackage Exception
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Exception thrown when an illegal index was requested.
 * This represents errors that should be detected at compile time.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link OutOfRangeException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cOutOfRangeException extends cLogicException {
}
