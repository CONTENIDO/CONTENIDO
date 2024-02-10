<?php

/**
 * This file contains the upgrade job 7.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 7.
 * Copies the content of the con_plugins."path" column to the "folder" column
 * and deletes the "path" column afterwards.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0007 extends cUpgradeJobAbstract
{

    public $maxVersion = "4.9.0-beta1";

    public function _execute()
    {
        if ($this->_setupType == 'upgrade') {
            // check if the column "path" still exists
            $this->_oDb->query('SHOW COLUMNS FROM `%s`;', cRegistry::getDbTableName('plugins'));

            $columns = [];
            while ($this->_oDb->nextRecord()) {
                $columns[] = $this->_oDb->f('Field');
            }

            if (in_array('path', $columns)) {
                // copy path to folder
                $this->_oDb->query('UPDATE `%s` SET folder = path;', cRegistry::getDbTableName('plugins'));
                // drop column "path"
                $this->_oDb->query('ALTER TABLE `%s` DROP path;', cRegistry::getDbTableName('plugins'));
            }
        }
    }

}
