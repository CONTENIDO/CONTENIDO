<?php

/**
 * This file contains the test helper class.
 *
 * @package    Testing
 * @subpackage Helper
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * Contenido test helper class.
 * @package    Testing
 * @subpackage Helper
 */
class cTestingTestHelper
{
    /**
     * Database instance
     * @var  cDb
     */
    private static $_db = null;


    /**
     * Returns the user recordset by username
     * @param string $username
     * @return  stdClass|null
     */
    public static function getUserByUsername($username = '')
    {
        $username = (!empty($username)) ? $username : 'sysadmin';
        $db = self::_getDatabase();
        $cfg = cRegistry::getConfig();
        $sql = "SELECT * FROM `%s` WHERE username = '%s'";
        if (!$db->query($sql, $cfg['tab']['user'], $username)) {
            return null;
        } elseif (!$user = $db->getResultObject()) {
            return null;
        }
        return $user;
    }


    /**
     * Sets the database instance and returns it back.
     *
     * @return  cDb
     */
    private static function _getDatabase()
    {
        if (self::$_db == null) {
            self::$_db = cRegistry::getDb();
        }
        return self::$_db;
    }
}
