<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Copies the content of the con_plugins."path" column to the "folder" column
 * and deletes the "path" column afterwards.
 *
 * @package CONTENIDO Setup upgrade
 * @version 0.1
 * @author Simon Sprankel <simon.sprankel@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cUpgradeJob_0008 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            // check if the column "path" still exists
            $columns = array();
            $sql = 'SHOW COLUMNS FROM ' . $cfg['tab']['plugins'];
            $db->query($sql);
            while ($db->next_record()) {
                $columns[] = $db->f('Field');
            }
            if (in_array('path', $columns)) {
                $db2 = clone $db;
                // iterate over all con_plugin entries and copy the contents
                // from column "path" to column "folder"
                $sql = 'SELECT `idplugin`, `path` FROM `' . $cfg['tab']['plugins'] . '`';
                $db->query($sql);
                while ($db->next_record()) {
                    $sql2 = 'UPDATE `' . $cfg['tab']['plugins'] . "` SET `folder`='" . $db->f('path') . "' WHERE `idplugin`=" . $db->f('idplugin');
                    $db2->query($sql2);
                }
                // drop column "path"
                $sql = 'ALTER TABLE `' . $cfg['tab']['plugins'] . '` DROP `path`';
                $db->query($sql);
            }
        }
    }

}
