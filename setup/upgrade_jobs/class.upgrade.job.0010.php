<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Removes deleted files from con_files
 *
 * @package CONTENIDO Setup upgrade
 * @version 0.1
 * @author Mischa Holz <mischa.holz@4fb.de>
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

    public $maxVersion = "4.9.0-rc1";

    protected $filesToRemove = array(
        "functions.forms.php"
    );

    public function _execute() {
        global $db, $cfg;

        if ($this->_setupType == 'upgrade') {
            foreach($this->filesToRemove as $file) {
                $db->query("DELETE FROM " . $cfg["tab"]["file"] . " WHERE `filename`=" . $file);
            }
        }
    }
}
