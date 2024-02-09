<?php

/**
 * This file contains the cLengthException class.
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
 * Exception thrown if a length is invalid.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link LengthException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cLengthException extends cLogicException
{
}
