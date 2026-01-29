<?php

declare(strict_types=1);

/**
 * AMR database utility class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      Advanced Mod Rewrite 2.1.0
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * AMR database utility class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteDatabaseUtil
{
    /**
     * Database query helper. Used to execute a select statement and to return the
     * result of the first recordset.
     *
     * Minimizes the following code:
     * <code>
     * // default way
     * $db = cRegistry::getDb();
     * $db->query("SELECT * FROM `foo` WHERE `bar` = 'foobar'");
     * $db->nextRecord();
     * $data = $db->getRecord();
     *
     * // new way
     * $sql = "SELECT * FROM foo WHERE bar='foobar'";
     * $data = PiModRewriteDatabaseUtil::queryAndNextRecord($sql);
     * </code>
     *
     * @param string $query Query to execute
     * @return array|false|null Associative array including recordset or `null`.
     */
    public static function queryAndNextRecord(string $query)
    {
        try {
            /** @var ?cDb $db */
            $db = cRegistry::getAppVar('pluginModRewriteDbUtilQueryAndNextRecordDb');
            if (!isset($db)) {
                $db = cRegistry::getDb();
                cRegistry::setAppVar('pluginModRewriteDbUtilQueryAndNextRecordDb', $db);
            }
            if (!$db->query($query)) {
                return null;
            }
            return $db->nextRecord() ? $db->getRecord() : null;
        } catch (cDbException $e) {
            cLogError($e->getMessage());
            return null;
        }
    }
}
