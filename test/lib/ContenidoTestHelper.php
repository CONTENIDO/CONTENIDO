<?php
/**
 * Contenido test helper class.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        03.04.2008
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Helper
 */
class ContenidoTestHelper
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
        $sql = "SELECT * FROM `%s` WHERE username = '%s'";
        if (!$db->query($sql, $GLOBALS['cfg']['tab']['user'], $username)) {
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
