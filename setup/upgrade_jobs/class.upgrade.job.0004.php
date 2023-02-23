<?php
/**
 * This file contains the upgrade job 4.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 4.
 * Runs the upgrade job to takeover some properties from upload to upload_meta
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0004 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    public function _execute() {
        global $cfg;

        if ($this->_setupType != 'setup') {
            $done = false;
            $sSql = "SHOW COLUMNS FROM " . $cfg['tab']['upl'];
            $this->_oDb->query($sSql);
            while ($this->_oDb->nextRecord()) {
                if ($this->_oDb->f("Field") == 'description') {
                    $done = true;
                }
            }
            if ($done) {
                $this->_updateUpl2Meta();
            }
        }
    }

    //update description from con_upl to con_upl_meta
    protected function _updateUpl2Meta() {
        global $cfg;

        $db = $this->_oDb;
        $sSql = "SELECT * FROM " . $cfg['tab']['upl'] . " WHERE `description` != '' ORDER BY idupl ASC";
        $db->query($sSql);

        $aUploads = [];
        while ($db->nextRecord()) {
            $uploadId = $db->f('idupl');
            $aUploads[$uploadId]['description'] = $db->f('description');
            $aUploads[$uploadId]['author'] = $db->f('author');
            $aUploads[$uploadId]['created'] = $db->f('created');
            $aUploads[$uploadId]['lastmodified'] = $db->f('lastmodified');
            $aUploads[$uploadId]['modifiedby'] = $db->f('modifiedby');
            $aUploads[$uploadId]['idclient'] = $db->f('idclient');
        }

        $sSql = "SELECT idclient, idlang FROM " . $cfg['tab']['clients_lang'] . " ORDER BY idclient ASC";
        $db->query($sSql);
        $aClientLanguages = [];
        while ($db->nextRecord()) {
            $clientId = $db->f('idclient');
            $aClientLanguages[$clientId][] = $db->f('idlang');
        }

        $bError = false;
        $j = 0;

        foreach ($aUploads as $idupl => $elem) {
            if ($elem['description'] == '') {
                continue;
            }

            $clientId = $elem['idclient'];
            if (isset($aClientLanguages[$clientId]) === false) {
                continue;
            }

            foreach ($aClientLanguages[$clientId] as $idlang) {
                $sSql = "SELECT * FROM " . $cfg['tab']['upl_meta'] . " WHERE idlang = " . $idlang . "  AND idupl = " . $idupl . " ORDER BY id_uplmeta ASC";
                $db->query($sSql);
                $i = 0;
                $aUplMeta = [];
                while ($db->nextRecord()) {
                    $aUplMeta[$i]['description'] = $db->f('description');
                    $aUplMeta[$i]['id_uplmeta'] = $db->f('id_uplmeta');
                    $i++;
                }

                if (count($aUplMeta) < 1) {
                    //there is no entry in con_upl_meta for this upload
                    $sSql = "INSERT INTO " . $cfg['tab']['upl_meta'] . " SET
                            idupl = $idupl,
                            idlang = $idlang,
                            medianame = '',
                            description = '" . $elem['description'] . "',
                            keywords = '',
                            internal_notice = '',
                            author = '" . $elem['author'] . "',
                            created = '" . $elem['created'] . "',
                            modified = '" . $elem['lastmodified'] . "',
                            modifiedby = '" . $elem['modifiedby'] . "',
                            copyright = ''";
                } elseif (count($aUplMeta) == 1 && $aUplMeta[0]['description'] == '') {
                    //there is already an entry and the field "description" is empty
                    $sSql = "UPDATE " . $cfg['tab']['upl_meta'] . " SET
                            description = '" . $elem['description'] . "'
                            WHERE id_uplmeta = " . $aUplMeta[0]['id_uplmeta'];
                } else {
                    //there is already an entry with an exising content in "description"
                    //do nothing;
                }

                $db->query($sSql);
                if ($db->getErrorNumber() != 0) {
                    $bError = true;
                    $this->_logError($sSql . "\nMysql Error:" . $db->getErrorMessage() . "(" . $db->getErrorNumber() . ")");
                }
            }

            $j++;
        }

        // At the end remove all values of con_upl.description and drop the field from table
        if ($bError === false && $j == count($aUploads)) {
            $sSql = "ALTER TABLE `" . $cfg['tab']['upl'] . "` DROP `description`";
            $db->query($sSql);
            if ($db->getErrorNumber() != 0) {
                $this->_logError($sSql . "\nMysql Error:" . $db->getErrorMessage() . "(" . $db->getErrorNumber() . ")");
            }
        } else {
            $this->_logError("error on _updateUpl2Meta();" . $j . '==' . count($aUploads));
        }
    }

}
