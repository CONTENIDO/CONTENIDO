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
    private static $db = null;

    /**
     * Returns the user recordset by username
     */
    public static function getUserByUsername(string $username = ''): ?stdClass
    {
        $username = !empty($username) ? $username : 'sysadmin';
        $db = self::_getDatabase();
        $sql = "SELECT * FROM `%s` WHERE `username` = '%s'";
        if (!$db->query($sql, cDb::getTableName('user'), $username)) {
            return null;
        } elseif (!$user = $db->getResultObject()) {
            return null;
        }
        return $user;
    }

    /**
     * Sets the database instance and returns it back.
     */
    private static function _getDatabase(): ?cDb
    {
        if (self::$db == null) {
            self::$db = cRegistry::getDb();
        }
        return self::$db;
    }
}
