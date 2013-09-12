<?php
/**
 * This file contains the upgrade job 3.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 3.
 * Runs the upgrade job for convert date time and urldecode.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0003 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    public function _execute() {
        global $cfg;

        convertToDatetime($this->_oDb, $cfg);
        if($_SESSION['setuptype'] == 'upgrade') { // we don't want this to happen during the setup since it would decode the example client which is already decoded
            urlDecodeTables($this->_oDb);
        }
    }

}
