<?php

/**
 * This file contains the cOverflowException class.
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
 * Exception thrown when adding an element to a full container.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link OverflowException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cOverflowException extends cRuntimeException {
}
