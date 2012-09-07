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
     * @var  DB_Contenido
     */
    private static $_db = null;


    /**
     * Returns the user recordset by username
     * @return  stdClass|null
     */
    public static function getUserByUsername()
    {
        $db = self::_getDatabase();
        $sql = 'SELECT * FROM ' . $GLOBALS['cfg']['tab']['phplib_auth_user_md5'] . ' WHERE username="admin"';
        if (!$db->query($sql)) {
            return null;
        } elseif (!$user = $db->getResultObject()) {
            return null;
        }
        return $user;
    }


    /**
     * Sets the database instance and returns it back.
     *
     * @return  DB_Contenido
     */
    private static function _getDatabase()
    {
        if (self::$_db == null) {
            self::$_db = new DB_Contenido();
        }
        return self::$_db;
    }
}
