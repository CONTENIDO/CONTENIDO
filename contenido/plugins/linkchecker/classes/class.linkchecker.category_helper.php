<?php

/**
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Mario Diaz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cLinkcheckerCategoryHelper
 */
class cLinkcheckerCategoryHelper
{
    /**
     * List of group ids.
     */
    private static ?array $groupIds = null;

    /**
     * List of category ids.
     */
    private static ?array $categoryIds = null;

    /**
     * @throws cDbException
     */
    public static function checkPermission(int $categoryId, ?cDb $db = null): bool
    {
        $auth = cRegistry::getAuth();

        if (cString::findFirstPos($auth->getPerms(), 'admin') !== false) {
            return true;
        }

        if (!is_object($db)) {
            $db = cRegistry::getDb();
        }

        $groupIds = self::getGroupIDs($db);
        $groupIds[] = $db->escape($auth->getUserId());

        if (!is_array(self::$categoryIds)) {
            $sqlInc = " `user_id` = '" . implode("' OR `user_id` = '", $groupIds) . "' ";
            $sql = "SELECT `idcat` FROM `%s` WHERE `idarea` = 6 AND `idaction` = 359 AND ($sqlInc)";
            $db->query($sql, cDb::getTableName('rights'));

            self::$categoryIds = [];
            while ($db->nextRecord()) {
                self::$categoryIds[$db->f('idcat')] = '';
            }
        }

        return array_key_exists($categoryId, self::$categoryIds);
    }

    /**
     * @throws cDbException
     */
    private static function getGroupIDs(cDB $db): array
    {
        if (is_array(self::$groupIds)) {
            return self::$groupIds;
        }

        $auth = cRegistry::getAuth();

        $sql = "SELECT `group_id` FROM `%s` WHERE `user_id` = '%s'";
        $db->query($sql, cDb::getTableName('groupmembers'), $auth->getUserId());

        self::$groupIds = [];
        while ($db->nextRecord()) {
            self::$groupIds[] = cSecurity::toInteger($db->f('group_id'));
        }

        return self::$groupIds;
    }
}
