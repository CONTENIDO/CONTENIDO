<?php

/**
 * This file contains the upgrade job 11.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 11.
 *
 * Add column 'uri' in table con_pifa_form so that buttons of type image can
 * store an URI to their image.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0011 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.3";

    public function _execute() {
        global $db, $cfg;

        if ($_SESSION['setuptype'] == 'upgrade' && $cfg['tab']['pifa_form'] != "") {
            $db->query('
                ALTER TABLE
                    `' . $cfg['tab']['pifa_form'] . '`
                ADD
                    `uri`
                    VARCHAR(1023)
                    DEFAULT NULL
                    COMMENT \'URI for image buttons\';');
        }
    }

    private function _addColumnToPifa() {

    }

    private function _generatePluginRelations() {

    }
}
