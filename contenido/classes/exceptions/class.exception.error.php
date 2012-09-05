<?php
/**
 * This file contains the cErrorException class.
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
 * An Error Exception.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link ErrorException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cErrorException extends cException {

}
