<?php

/**
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Mario Diaz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cLinkcheckerCategoryHelper
 */
class cLinkcheckerCategoryHelper
{
    /**
     * List of group ids.
     *
     * @var array|null
     */
    private static $_groupIds = null;

    /**
     * List of category ids.
     *
     * @var array|null
     */
    private static $_categoryIds = null;

    /**
     * @param int  $widcat
     * @param null $db
     *
     * @return bool
     * @throws cDbException
     */
    public static function checkPermission($widcat, $db = null) {
        $auth = cRegistry::getAuth();

        if (cString::findFirstPos($auth->auth['perm'], 'admin') !== false) {
            return true;
        }

        if (!is_object($db)) {
            $db = cRegistry::getDb();
        }

        $group_ids   = self::_getGroupIDs($db);
        $group_ids[] = $db->escape($auth->auth['uid']);

        if (!is_array(self::$_categoryIds)) {
            $sqlInc = " `user_id` = '" . implode("' OR `user_id` = '", $group_ids) . "' ";
            $sql = "SELECT `idcat` FROM `%s` WHERE `idarea` = 6 AND `idaction` = 359 AND ($sqlInc)";
            $db->query($sql, cRegistry::getDbTableName('rights'));

            self::$_categoryIds = [];
            while ($db->nextRecord()) {
                self::$_categoryIds[$db->f('idcat')] = '';
            }
        }

        return array_key_exists($widcat, self::$_categoryIds);
    }

    /**
     * @param cDb $db
     *
     * @return array
     * @throws cDbException
     */
    private static function _getGroupIDs($db) {
        if (is_array(self::$_groupIds)) {
            return self::$_groupIds;
        }

        $auth = cRegistry::getAuth();

        $sql = "SELECT `group_id` FROM `%s` WHERE `user_id` = '%s'";
        $db->query($sql, cRegistry::getDbTableName('groupmembers'), $auth->auth['uid']);

        self::$_groupIds = [];
        while ($db->nextRecord()) {
            self::$_groupIds[] = $db->f('group_id');
        }

        return self::$_groupIds;
    }
}
