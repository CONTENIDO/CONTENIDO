<?php
/**
 * This file contains the upgrade job 10.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 10.
 * Removes deleted files from con_files.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0010 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-rc1";

    protected $filesToRemove = array(
        "functions.forms.php"
    );

    public function _execute() {
        global $db, $cfg;

        if ($this->_setupType == 'upgrade') {
            foreach ($this->filesToRemove as $file) {
                $db->query("DELETE FROM " . $cfg['tab']['files'] . " WHERE filename = '" . $file . "'");
            }
        }
    }

}
