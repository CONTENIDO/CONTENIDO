<?php
/**
 * This file contains the upgrade job 16.
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
 * Upgrade job 16.
 * Add menus for the new search statistics
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0016 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.3";

    public function _execute() {
        global $db, $cfg;

        if($_SESSION['setuptype'] == 'upgrade') {
            // new area stat_search
            $db->query("INSERT INTO " . $cfg['tab']['area'] . " VALUES('200', '0', 'stat_search', '1', '1', '0');");
            
            // 2 new files and use the left_top from the other statistic view
            $db->query("INSERT INTO " . $cfg['tab']['files'] . " VALUES ('300', '200', 'include.stat_search_menu.php', 'main');");
            $db->query("INSERT INTO " . $cfg['tab']['files'] . " VALUES ('301', '200', 'include.stat_left_top.php', 'main');");
            $db->query("INSERT INTO " . $cfg['tab']['files'] . " VALUES ('302', '200', 'include.stat_search_overview.php', 'main');");
            
            // Link the files to frames
            $db->query("INSERT INTO " . $cfg['tab']['frame_files'] . " VALUES('300', '200', '2', '300');");
            $db->query("INSERT INTO " . $cfg['tab']['frame_files'] . " VALUES('301', '200', '1', '301');");
            $db->query("INSERT INTO " . $cfg['tab']['frame_files'] . " VALUES('302', '200', '4', '302');");
            
            // new sub navigation
            $db->query("INSERT INTO " . $cfg['tab']['nav_sub'] . " VALUES('200', '4', '200', '0', 'navigation/statistic/searches', '1');");
        }
    }

}
