<?php
/**
 * This file contains the session class for unit tests.
 *
 * @package     Testing
 * @subpackage  Helper
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     https://www.contenido.org/license/LIZENZ.txt
 * @link        https://www.4fb.de
 * @link        https://www.contenido.org
 */

/**
 * Session class for unit tests, creates no session and no cookie.
 * Prevents running unit tests from creating errorlog.txt entries like`:
 * `PHP Warning:  ini_set(): A session is active.`
 *
 * Still provides the feature to set and retrieve values, but does not
 * persist them, so the values are not available across multiple
 * "request lifecycles".
 *
 * @package          Testing
 * @subpackage       Helper
 */
class cUnitTestSession extends cSession
{

    public function __construct($prefix = 'unittest')
    {
    }

    protected function _rSerialize($var, &$str)
    {
    }

    public function freeze()
    {
    }

    public function thaw()
    {
    }

    public function delete()
    {
    }

}
