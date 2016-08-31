<?php
/**
 * This file contains the upgrade job 7.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 7.
 * Copies the content of the con_plugins."path" column to the "folder" column
 * and deletes the "path" column afterwards.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0007 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    public function _execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            // check if the column "path" still exists
            $db->query('SHOW COLUMNS FROM `%s`;', $cfg['tab']['plugins']);

            $columns = array();
            while ($db->nextRecord()) {
                $columns[] = $db->f('Field');
            }

            if (in_array('path', $columns)) {
                // copy path to folder
                $db->query('UPDATE `%s` SET folder = path;', $cfg['tab']['plugins']);
                // drop column "path"
                $db->query('ALTER TABLE `%s` DROP path;', $cfg['tab']['plugins']);
            }
        }
    }

}
