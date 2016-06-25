<?php
/**
 * This file contains the main upgrade job class.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Main upgrade job class.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJobMain extends cUpgradeJobAbstract {

    /**
     * Main function to execute
     */
    public function _execute() {
        global $cfg;

        $this->_version = getContenidoVersion($this->_oDb, $cfg['tab']['system_prop']);
        $this->_executeInitialJobs();

        $upgradeJobs = $this->_getUpgradeJobFiles();
        $this->_processUpgradeJobs($upgradeJobs);
    }

    /**
     * Initial update jobs.
     *
     * NOTE: Don't spam this function with additional upgrade tasks.
     * Create a new upgrated job file and implement the execute() method!
     */
    protected function _executeInitialJobs() {
        global $cfg;

        updateContenidoVersion($this->_oDb, $cfg['tab']['system_prop'], CON_SETUP_VERSION);
        if ($this->_setupType == 'setup') {
            updateSysadminPassword($this->_oDb, $cfg['sql']['sqlprefix'] . '_user', $_SESSION['adminpass'], $_SESSION['adminmail']);
        }

        // Set code creation (on update) flag
        $this->_oDb->query('UPDATE %s SET createcode = 1', $cfg['tab']['cat_art']);

        // Convert old category start articles to new format, we don't support
        $this->_jobConvertOldStartArticlesToNewOne();

        // Update Keys
        injectSQL($this->_oDb, $cfg['sql']['sqlprefix'], 'data/indexes.sql', array());

        // Update to autoincrement
        addAutoIncrementToTables($this->_oDb, $cfg);

        // Insert or update default system properties
        updateSystemProperties($this->_oDb, $cfg['tab']['system_prop']);

        // Renames table 'phplib_auth_user_md5' to 'user'
        $this->_renameOldUserTableToNewOne();
    }

    /**
     * Function to convert old start article configuration to new style.
     *
     * In former CONTENIDO versions (4.6 or earlier) start articles were
     * stored in table con_cat_art.is_start.
     * Since 4.6 start articles are stored con_cat_lang.startidartlang.
     *
     * This function takes the start articles from con_cat_art.is_start and
     * sets them in con_cat_lang.startidartlang for all available languages.
     */
    protected function _jobConvertOldStartArticlesToNewOne() {
        global $cfg;

        // Convert old category start articles to new format, we don't support
        // the configuration '$cfg["is_start_compatible"] = true;'
        if ($this->_setupType == 'upgrade') {
            $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " WHERE is_start = 1";
            $this->_oDb->query($sql);

            $db2 = getSetupMySQLDBConnection();

            while ($this->_oDb->nextRecord()) {
                $startidart = (int) $this->_oDb->f("idart");
                $idcat = (int) $this->_oDb->f("idcat");

                foreach (self::$_languages as $vlang => $oLang) {
                    $vlang = (int) $vlang;
                    $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = " . $startidart . " AND idlang = " . $vlang;
                    $db2->query($sql);
                    if ($db2->nextRecord()) {
                        $idartlang = (int) $db2->f("idartlang");
                        $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET startidartlang = " . $idartlang . " WHERE idcat = " . $idcat . " AND idlang= " . $vlang;
                        $db2->query($sql);
                    }
                }
            }

            $sql = "UPDATE " . $cfg["tab"]["cat_art"] . " SET is_start = 0";
            $this->_oDb->query($sql);
        }
    }

    /**
     * Renames table 'phplib_auth_user_md5' to 'user'
     */
    protected function _renameOldUserTableToNewOne() {
        global $cfg;

        $this->_oDb->query('SHOW TABLES LIKE "%s"', $cfg['sql']['sqlprefix'] . '_phplib_auth_user_md5');
        $oldTable = $this->_oDb->nextRecord();

        $this->_oDb->query('SHOW TABLES LIKE "%s"', $cfg['sql']['sqlprefix'] . '_user');
        $newTable = $this->_oDb->nextRecord();

        if ($oldTable === true) {
            if ($newTable === false) {
                // Only the old table exists. Rename it.
                $this->_oDb->query('RENAME TABLE ' . $cfg['sql']['sqlprefix'] . '_phplib_auth_user_md5 TO ' . $cfg['sql']['sqlprefix'] . '_user');
            } else {
                // The new and the old table exists. We trust the old table more
                // since the new one should've been deleted by the setup. Drop
                // the new one and rename the old one
                $this->_oDb->query('DROP TABLE ' . $cfg['sql']['sqlprefix'] . '_user');
                $this->_oDb->query('RENAME TABLE ' . $cfg['sql']['sqlprefix'] . '_phplib_auth_user_md5 TO ' . $cfg['sql']['sqlprefix'] . '_user');
            }
        }

        // Convert passwords to salted ones
        addSalts($this->_oDb);
    }

    /**
     * Get all upgrade job files
     *
     * @return array
     */
    protected function _getUpgradeJobFiles() {
        $files = array();
        $dir = CON_SETUP_PATH . '/upgrade_jobs/';
        if (is_dir($dir)) {
            if (false !== ($handle = cDirHandler::read($dir))) {
                foreach ($handle as $file) {
                    if (false === cFileHandler::fileNameIsDot($file) && is_file($dir . $file)) {
                        if (preg_match('/^class\.upgrade\.job\.(\d{4})\.php$/', $file, $match)) {
                            $files[$match[1]] = $file;
                        }
                    }
                }
            }
            ksort($files, SORT_NUMERIC);
        }

        return $files;
    }

    /**
     * Execute passed upgrade job files
     *
     * @param array $upgradeJobs
     */
    protected function _processUpgradeJobs(array $upgradeJobs) {
        foreach ($upgradeJobs as $index => $file) {
            require_once (CON_SETUP_PATH . '/upgrade_jobs/' . $file);
            $className = 'cUpgradeJob_' . $index;
            if (!class_exists($className)) {
                continue;
            }

            /* @var $obj cUpgradeJobAbstract */
            $obj = new $className($this->_oDb, $this->_aCfg, $this->_aCfgClient, $this->_version);
            $obj->execute();
        }
    }

}
