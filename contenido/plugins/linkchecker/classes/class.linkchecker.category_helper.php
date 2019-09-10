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
     * @param int  $widcat
     * @param null $db
     *
     * @return bool
     * @throws cDbException
     */
    public static function checkPermission($widcat, $db = null)
    {
        global $cfg, $sess, $auth, $group_id, $_arrCatIDs_cCP;

        if (cString::findFirstPos($auth->auth['perm'], 'admin') !== false) {
            return true;
        }

        if (is_null($db) || !is_object($db)) {
            $db = cRegistry::getDb();
        }

        $group_ids   = self::_getGroupIDs($db);
        $group_ids[] = $db->escape($auth->auth['uid']);

        if (!is_array($_arrCatIDs_cCP)) {
            $sql_inc = " user_id='" . implode("' OR user_id='", $group_ids) . "' ";

            $sql = "SELECT idcat
                FROM " . $cfg['tab']['rights'] . "
                WHERE idarea = 6
                    AND idaction = 359
                    AND ($sql_inc)";

            $db->query($sql);

            $_arrCatIDs_cCP = [];
            while ($db->nextRecord()) {
                $_arrCatIDs_cCP[$db->f('idcat')] = '';
            }
        }

        return array_key_exists($widcat, $_arrCatIDs_cCP);
    }

    /**
     * @param $db
     *
     * @return array
     * @throws cDbException
     */
    private static function _getGroupIDs(cDb &$db)
    {
        global $cfg, $sess, $auth, $group_id, $_arrGroupIDs_gGI;

        if (is_array($_arrGroupIDs_gGI)) {
            return $_arrGroupIDs_gGI;
        }

        $sql = "SELECT group_id
            FROM " . $cfg["tab"]["groupmembers"] . "
            WHERE user_id='" . $db->escape($auth->auth["uid"]) . "'";
        $db->query($sql);

        $_arrGroupIDs_gGI = [];
        while ($db->nextRecord()) {
            $_arrGroupIDs_gGI[] = $db->f('group_id');
        }

        return $_arrGroupIDs_gGI;
    }
}
