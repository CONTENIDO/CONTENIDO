<?php
/**
 * This file contains the upgrade job 9.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 9.
 * Convert newsletter plugin tables
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0009 extends cUpgradeJobAbstract {

    private $_tableMapping = array('%s_news' => '%s_pi_news',
                                   '%s_news_groupmembers' => '%s_pi_news_groupmembers',
                                   '%s_news_groups' => '%s_pi_news_groups',
                                   '%s_news_jobs' => '%s_pi_news_jobs',
                                   '%s_news_log' => '%s_pi_news_log',
                                   '%s_news_rcp' => '%s_pi_news_rcp');

    public function _execute() {
        global $cfg;

        if ($this->_setupType != 'upgrade') {
            return;
        }

        $updateDB = getSetupMySQLDBConnection(false);
        foreach ($this->_tableMapping as $oldName => $newName) {
             $this->_oDb->query('SHOW TABLES LIKE "%s"', sprintf($oldName, $cfg['sql']['sqlprefix']));
             $oldTable = $this->_oDb->nextRecord();

             $this->_oDb->query('SHOW TABLES LIKE "%s"', sprintf($newName, $cfg['sql']['sqlprefix']));
             $newTable = $this->_oDb->nextRecord();

             if ($newTable === false && $oldTable === true) {
                $this->_oDb->query('RENAME TABLE ' . sprintf($oldName, $cfg['sql']['sqlprefix']) . ' TO ' . sprintf($newName, $cfg['sql']['sqlprefix']));

                alterTableHandling(sprintf($newName, $cfg['sql']['sqlprefix']));
                urlDecodeTable($updateDB, sprintf($newName, $cfg['sql']['sqlprefix']));
             }
        }
    }

}

?>