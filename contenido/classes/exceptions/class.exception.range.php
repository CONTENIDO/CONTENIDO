<?php
/**
 * This file contains the cRangeException class.
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
 * Exception thrown to indicate range errors during program execution.
 * Normally this means there was an arithmetic error other than under/overflow.
 * This is the runtime version of {@link cDomainException}.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link RangeException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cRangeException extends cRuntimeException {

}
