<?php

/**
 * This file contains the upgrade job 15.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 15.
 * Change the upload root folder from "/" to ""
 *
 * @package    Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0015 extends cUpgradeJobAbstract
{

    public $maxVersion = "4.9.4";

    public function _execute()
    {
        if ($_SESSION['setuptype'] == 'upgrade') {
            $this->_oDb->query('UPDATE ' . cRegistry::getDbTableName('upl') . ' SET dirname="" WHERE dirname="/"');
        }
    }

}
