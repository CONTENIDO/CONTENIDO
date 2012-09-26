<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Changes the datatype of the field con_groups.group_id to varchar(32).
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
class cUpgradeJob_0009 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            $sql = 'ALTER TABLE `' . $cfg['tab']['groups'] . '` CHANGE `group_id` `group_id` VARCHAR(32) NOT NULL';
            $db->query($sql);
        }
    }

}
