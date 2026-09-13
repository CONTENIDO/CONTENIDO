<?php

/**
 * This file contains the cFileNotFoundException class.
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
 * Exception thrown if a file could not be found.
 * This exception type is logged to data/logs/$cfg['log_file_names']['exception_log'].
 */
class cFileNotFoundException extends cRuntimeException
{
}
