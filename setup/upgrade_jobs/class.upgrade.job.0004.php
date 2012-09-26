<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the upgrade job to takeover some properties from upload to upload_meta
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


class cUpgradeJob_0004 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg;

        if ($this->_setupType != 'setup') {
            $done = false;
            $sSql = "SHOW COLUMNS FROM " . $cfg['tab']['upl'];
            $this->_oDb->query($sSql);
            while ($this->_oDb->next_record()) {
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
        global $cfg, $client;

        // @fixme  Update works only for default client...
        $client = 1;

        $db = $this->_oDb;
        $aUpl = array();
        $sSql = "SELECT * FROM " . $cfg['tab']['upl'] . " WHERE idclient = " . $client . " AND `description` != '' ORDER BY idupl ASC";
        $db->query($sSql);
        while ($db->next_record()) {
            $aUpl[$db->f('idupl')]['description'] = $db->f('description');
            $aUpl[$db->f('idupl')]['author'] = $db->f('author');
            $aUpl[$db->f('idupl')]['created'] = $db->f('created');
            $aUpl[$db->f('idupl')]['lastmodified'] = $db->f('lastmodified');
            $aUpl[$db->f('idupl')]['modifiedby'] = $db->f('modifiedby');
        }
        $aLang = array();
        $sSql = "SELECT idlang FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient = " . $client . " ORDER BY idlang ASC";
        $db->query($sSql);
        while ($db->next_record()) {
            $aLang[] = $db->f('idlang');
        }

        $bError = true;
        $j = 0;
        foreach ($aUpl as $idupl => $elem) {
            if ($elem['description'] != '') {
                foreach ($aLang as $idlang) {
                    $aUplMeta = array();
                    $sSql = "SELECT * FROM " . $cfg['tab']['upl_meta'] . " WHERE idlang = " . $idlang . "  AND idupl = " . $idupl . " ORDER BY idupl ASC";
                    $db->query($sSql);
                    $i = 0;
                    while ($db->next_record()) {
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
                    if ($db->Error != 0) {
                        $bError = false;
                        $this->_logError($sSql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")");
                    }
                }
            }
            $j++;
        }
        // At the end remove all values of con_upl.description and drop the field from table
        if ($bError && $j == count($aUpl)) {
            $sSql = "ALTER TABLE `" . $cfg['tab']['upl'] . "` DROP `description`";
            $db->query($sSql);
            if ($db->Error != 0) {
                $this->_logError($sSql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")");
            }
        } else {
            $this->_logError("error on _updateUpl2Meta();" . $j . '==' . count($aUpl));
        }
    }

}
