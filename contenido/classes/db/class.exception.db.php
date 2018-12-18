<?php

/**
 * This file contains the database exception class.
 *
 * @package Core
 * @subpackage Database
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Exception thrown if there is some fault in database connection.
 * This exception type is logged to data/logs/exception.txt.
 *
 * @package Core
 * @subpackage Database
 */
class cDbException extends cException {
}
