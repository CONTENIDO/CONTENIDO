<?php

/**
 * This file contains the cDomainException class.
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
 * Exception thrown if a value does not adhere to a defined valid data domain.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link DomainException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cDomainException extends cLogicException {
}
