<?php

/**
 * This file contains the cOutOfBoundsException class.
 *
 * @package    Core
 * @subpackage Exception
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * Exception thrown if a value is not a valid key.
 * This represents errors that cannot be detected at compile time.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link OutOfBoundsException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cOutOfBoundsException  extends cRuntimeException {
}
