<?php

/**
 * This file contains the cUnderflowException class.
 *
 * @package    Core
 * @subpackage Exception
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Exception thrown when performing an invalid operation on an empty container,
 * such as removing an element.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link UnderflowException}.
 * This exception type is logged to data/logs/$cfg['log_file_names']['exception_log'].
 */
class cUnderflowException extends cRuntimeException
{

}
