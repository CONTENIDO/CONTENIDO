<?php
/**
 * This file contains the upgrade job 9.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 9.
 * Changes the datatype of the field con_groups.group_id to varchar(32).
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0009 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    public function _execute() {
        global $cfg, $db;

       // if ($this->_setupType == 'upgrade') {
            //$sql = 'ALTER TABLE `' . $cfg['tab']['groups'] . '` CHANGE `group_id` `group_id` VARCHAR(32) NOT NULL';
            //$db->query($sql);
            //
       // }
    }

}
