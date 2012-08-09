<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the main upgrade job
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Setup upgrade
 * @version    0.1
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 */


if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


class cUpgradeJobMain extends cUpgradeJobAbstract {

    /**
     * Main function to execute
     */
    public function execute() {
        $this->_executeInitialJobs();

        $upgradeJobs = $this->_getUpgradeJobFiles();
        $this->_processUpgradeJobs($upgradeJobs);
    }

    /**
     * Initial update jobs
     */
    protected function _executeInitialJobs() {
        global $cfg;

        updateContenidoVersion($this->_oDb, $cfg['tab']['system_prop'], C_SETUP_VERSION);

        if (isset($_SESSION['sysadminpass']) && $_SESSION['sysadminpass'] != '') {
            updateSysadminPassword($this->_oDb, $cfg['tab']['phplib_auth_user_md5'], 'sysadmin');
        }

        // Empty code table and set code creation (on update) flag
        $this->_oDb->query('DELETE FROM %s', $cfg['tab']['code']);
        $this->_oDb->query('UPDATE %s SET createcode = 1', $cfg['tab']['cat_art']);

        if ($this->_setupType == 'migration') {
            $aClients = listClients($this->_oDb, $cfg['tab']['clients']);
            foreach ($aClients as $iIdClient => $aInfo) {
                updateClientPath($this->_oDb, $cfg['tab']['clients'], $iIdClient, $_SESSION['frontendpath'][$iIdClient], $_SESSION['htmlpath'][$iIdClient]);
            }
        }

        // @fixme What job is this???
        if ($this->_setupType == 'upgrade') {
            $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " WHERE is_start = 1";
            $this->_oDb->query($sql);

            $db2 = getSetupMySQLDBConnection();

            while ($this->_oDb->next_record()) {
                $startidart = (int) $this->_oDb->f("idart");
                $idcat = (int) $this->_oDb->f("idcat");

                foreach (self::$_languages as $vlang => $oLang) {
                    $vlang = (int) $vlang;
                    $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = " . $startidart . " AND idlang = " . $vlang;
                    $db2->query($sql);
                    if ($db2->next_record()) {
                        $idartlang = (int) $db2->f("idartlang");
                        $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET startidartlang = " . $idartlang . " WHERE idcat = " . $idcat . " AND idlang= " . $vlang;
                        $db2->query($sql);
                    }
                }
            }

            $sql = "UPDATE " . $cfg["tab"]["cat_art"] . " SET is_start = 0";
            $this->_oDb->query($sql);
        }

        // Update Keys
        $aNothing = array();
        injectSQL($this->_oDb, $cfg['sql']['sqlprefix'], 'data/indexes.sql', array(), $aNothing);

        // Update to autoincrement
        addAutoIncrementToTables($this->_oDb, $cfg);

        // Insert or update default system properties
        updateSystemProperties($this->_oDb, $cfg['tab']['system_prop']);
    }

    /**
     * Get all upgrade job files
     * @return  array
     */
    protected function _getUpgradeJobFiles() {
        $files = array();
        $dir = C_SETUP_PATH . '/upgrade_jobs/';
        if ($hDir = opendir($dir)) {
            while (false !== ($file = readdir($hDir))) {
                if ($file != '.' && $file != '..' && is_file($dir . $file)) {
                    if (preg_match('/^class\.upgrade\.job\.(\d{4})\.php$/', $file, $match)) {
                        $files[$match[1]] = $file;
                    }
                }
            }
            closedir($hDir);
        }
        ksort($files, SORT_NUMERIC);

        return $files;
    }

    /**
     * Execute passed upgrade job files
     * @param  array  $upgradeJobs
     */
    protected function _processUpgradeJobs(array $upgradeJobs) {
        foreach ($upgradeJobs as $index => $file) {
            require_once(C_SETUP_PATH . '/upgrade_jobs/' . $file);
            $className = 'cUpgradeJob_' . $index;
            if (!class_exists($className)) {
                continue;;
            }
            /* @var $obj cUpgradeJobAbstract */
            $obj = new $className($this->_oDb, $this->_aCfg, $this->_aCfgClient);
            $obj->execute();
        }
    }

}