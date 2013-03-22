<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Language Functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.2.9
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.str.php');

/**
 * Edit a language
 *
 * @param  int  $idlang
 * @param  string  $langname Name of the language
 * @param  string  $encoding
 * @param  int  $active  Flag for active state, 1 or 0
 * @param  string  $direction
 * @return  bool
 */
function langEditLanguage($idlang, $langname, $encoding, $active, $direction = 'ltr') {
    $oLang = new cApiLanguage();
    if ($oLang->loadByPrimaryKey((int) $idlang)) {
        $oLang->set('name', $oLang->escape($langname), false);
        $oLang->set('encoding', $oLang->escape($encoding), false);
        $oLang->set('active', (int) $active, false);
        $oLang->set('direction', $oLang->escape($direction), false);
        return $oLang->store();
    }
    return false;
}

/**
 * Create a new language
 *
 * @param  string  $name  Name of the language
 * @param  int  $client  Id of client
 * @return int  New language id
 */
function langNewLanguage($name, $client) {
    global $cfgClient, $notification;

    // Add new language to database
    $oLangCol = new cApiLanguageCollection();
    $oLangItem = $oLangCol->create($name, 0, 'iso-8859-1', 'ltr');
    // Add new client language to database
    $oClientLangCol = new cApiClientLanguageCollection();
    $oClientLangItem = $oClientLangCol->create($client, $oLangItem->get('idlang'));

    // Ab hyr seynd Drachen
    $destPath = $cfgClient[$client]['config']['path'];

    if (cFileHandler::exists($destPath) && cFileHandler::exists($destPath . 'config.php')) {
        $buffer = cFileHandler::read($destPath . 'config.php');
        $outbuf = str_replace('!LANG!', $oLangItem->get('idlang'), $buffer);
        cFileHandler::write($destPath . 'config.php.new', $outbuf);
        if (cFileHandler::exists($destPath . 'config.php')) {
            cFileHandler::remove($destPath . 'config.php');
        }

        cFileHandler::rename($destPath . 'config.php.new', 'config.php');
    } else {
        $notification->displayNotification('error', i18n("Could not set the language-ID in the file 'config.php'. Please set the language manually."));
    }

    return $oLangItem->get('idlang');
}

/**
 * Rename a language
 *
 * @param  int  $idlang  Id of the language
 * @param  string  $name  Name of the language
 * @return  bool
 */
function langRenameLanguage($idlang, $name) {
    $oLang = new cApiLanguage();
    if ($oLang->loadByPrimaryKey((int) $idlang)) {
        $oLang->set('name', $oLang->escape($name), false);
        return $oLang->store();
    }
    return false;
}

/**
 * Duplicate a language
 *
 * @param  int  $client  Id of the client
 * @param  int  $idlang  Id of the language
 *
 * @deprecated  [2012-03-05]  This function is not longer supported.
 */
function langDuplicateFromFirstLanguage($client, $idlang) {
    cDeprecated("This function is not longer supported.");
    global $db, $sess, $cfg, $auth;

    $client = (int) $client;
    $idlang = (int) $idlang;

    $sql = "SELECT * FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient=" . $client . " ORDER BY idlang ASC";

    $db->query($sql);
    if ($db->nextRecord()) {
        // if there is already a language copy from it

        $db2 = cRegistry::getDb();
        $db3 = cRegistry::getDb();

        $firstlang = (int) $db->f('idlang');

        // duplicate entries in 'art_lang' table
        $sql = "SELECT * FROM " . $cfg['tab']['art_lang'] . " AS A, " . $cfg['tab']['art'] . " AS B "
                . "WHERE A.idart=B.idart AND B.idclient=" . $client . " AND idlang!=0 AND idlang=" . $firstlang;
        $db->query($sql);

        // Array storing the article->templatecfg allocations for later reallocation
        $aCfgArt = array();

        while ($db->nextRecord()) {
            // Store the idartlang->idplcfg allocation for later reallocation
            $aCfgArt[] = array('idartlang' => $db->f('idartlang'), 'idtplcfg' => $db->f('idtplcfg'));

            //$iIdartLangNew = (int) $db2->nextid($cfg['tab']['art_lang']);
            $aRs = $db->toArray();
            $iIdartLangOld = $aRs['idartlang'];
            //$aRs['idartlang'] = $iIdartLangNew;
            $aRs['idlang'] = $idlang;
            $aRs['created'] = date('Y-m-d H:i:s');
            $aRs['lastmodified'] = '0000-00-00 00:00:00';
            $aRs['online'] = 0;
            $aRs['author'] = $auth->auth['uname'];
            unset($aRs['idclient']);
            $db2->insert($cfg['tab']['art_lang'], $aRs);
            $iIdartLangNew = $db2->getLastInsertedId($cfg['tab']['art_lang']);

            // duplicate entries in 'content' table
            $sql = "SELECT * FROM " . $cfg['tab']['content'] . " WHERE idartlang=" . (int) $iIdartLangOld;
            $db2->query($sql);
            while ($db2->nextRecord()) {
                $aRs = $db2->toArray();
                //$aRs['idcontent'] = (int) $db3->nextid($cfg['tab']['content']);
                $aRs['idartlang'] = $iIdartLangNew;
                $db3->insert($cfg['tab']['content'], $aRs);
            }
        }

        fakeheader(time());

        // duplicate all entries in the 'cat_lang' table
        $sql = "SELECT * FROM " . $cfg['tab']['cat_lang'] . " AS A, " . $cfg['tab']['cat'] . " AS B "
                . "WHERE A.idcat=B.idcat AND B.idclient=" . $client . " AND idlang=" . $firstlang;
        $db->query($sql);

        // Array storing the category->template allocations fot later reallocation
        $aCfgCat = array();

        while ($db->nextRecord()) {
            //$nextid = (int) $db2->nextid($cfg['tab']['cat_lang']);
            $aRs = $db->toArray();
            $aRs['visible'] = 0;
            $aRs['author'] = $auth->auth['uname'];
            unset($aRs['idclient'], $aRs['parentid'], $aRs['preid'], $aRs['postid']);
            $db2->insert($cfg['tab']['cat_lang'], $aRs);

            $aCfgCat[] = array('idcatlang' => $db2->getLastInsertedId($cfg['tab']['cat_lang']), 'idtplcfg' => (int) $db->f('idtplcfg'));
        }

        // duplicate all entries in the 'stat' table
        $sql = "SELECT * FROM " . $cfg['tab']['stat'] . " WHERE idclient=" . $client . " AND idlang=" . $firstlang;
        $db->query($sql);
        while ($db->nextRecord()) {
            $aRs = $db->toArray();
            //$aRs['idstat'] = (int) $db2->nextid($cfg['tab']['stat']);
            $aRs['idlang'] = $idlang;
            $aRs['visited'] = 0;
            $db2->insert($cfg['tab']['stat'], $aRs);
        }

        fakeheader(time());

        // duplicate all entries in the 'tpl_conf' table
        $sql = "SELECT * FROM " . $cfg['tab']['tpl_conf'];
        $db->query($sql);

        // Array storing the category->template allocations fot later reallocation
        $aCfgOldNew = array();

        while ($db->nextRecord()) {
            // $nextid = (int) $db2->nextid($cfg['tab']['tpl_conf']);
            $aRs = $db->toArray();
            $db2->insert($cfg['tab']['tpl_conf'], $aRs);

            $aCfgOldNew[] = array('oldidtplcfg' => (int) $db->f('idtplcfg'), 'newidtplcfg' => $db2->getLastInsertedId($cfg['tab']['tpl_conf']));
        }


        // Copy the template configuration data
        foreach ($aCfgOldNew as $data) {
            $oldidtplcfg = $data['oldidtplcfg'];
            $newidtplcfg = $data['newidtplcfg'];

            $sql = "SELECT number, container FROM " . $cfg['tab']['container_conf']
                    . " WHERE idtplcfg=" . $oldidtplcfg . " ORDER BY number ASC";
            $db->query($sql);

            while ($db->nextRecord()) {
                $aRs = array(
                    'idtplcfg' => $newidtplcfg,
                    'number' => (int) $db->f('number'),
                    'container' => $db->f('container')
                );
                $db2->insert($cfg['tab']['container_conf'], $aRs);
            }
        }

        // Reallocate the category -> templatecfg allocations
        foreach ($aCfgCat as $data) {
            if ($data['idtplcfg'] != 0) {
                // Category has a configuration
                foreach ($aCfgOldNew as $arr) {
                    if ($data['idtplcfg'] == $arr['oldidtplcfg']) {
                        $aFields = array('idtplcfg' => (int) $arr['newidtplcfg']);
                        $aWhere = array('idcatlang' => (int) $data['idcatlang']);
                        $db->update($cfg['tab']['cat_lang'], $aFields, $aWhere);
                    }
                }
            }
        }

        // Reallocate the article -> templatecfg allocations
        foreach ($aCfgArt as $data) {
            if ($data['idtplcfg'] != 0) {
                // Category has a configuration
                foreach ($aCfgOldNew as $arr) {
                    if ($data['idtplcfg'] == $arr['oldidtplcfg']) {
                        // We have a match :)
                        $aFields = array('idtplcfg' => (int) $arr['newidtplcfg']);
                        $aWhere = array('idartlang' => (int) $data['idartlang']);
                        $db->update($cfg['tab']['art_lang'], $aFields, $aWhere);
                    }
                }
            }
        }
    }

    // Update code
    conGenerateCodeForAllarts();
}

/**
 * Delete a language
 *
 * @param  int  $iIdLang  Id of the language
 * @param  int  $iIdClient  Id of the client, uses global client id by default
 */
function langDeleteLanguage($iIdLang, $iIdClient = 0) {
    global $db, $sess, $client, $cfg, $notification, $cfgClient;

    $deleteok = 1;
    $iIdLang = (int) $iIdLang;
    $iIdClient = (int) $iIdClient;

    // Bugfix: New idclient parameter introduced, as Administration -> Languages
    // is used for different clients to delete the language
    // Use global client id, if idclient not specified (former behaviour)
    // Note, that this check also have been added for the action in the database
    // - just to be equal to langNewLanguage
    if ($iIdClient == 0) {
        $iIdClient = $client;
    }

    //************ check if there are still arts online
    $sql = "SELECT * FROM " . $cfg['tab']['art_lang'] . " AS A, " . $cfg['tab']['art'] . " AS B "
            . "WHERE A.idart=B.idart AND B.idclient=" . $iIdClient . " AND A.idlang=" . $iIdLang;
    $db->query($sql);
    if ($db->nextRecord()) {
        conDeleteArt($db->f('idart'));
    }

    //************ check if there are visible categories
    $sql = "SELECT * FROM " . $cfg['tab']['cat_lang'] . " AS A, " . $cfg['tab']['cat'] . " AS B "
            . "WHERE A.idcat=B.idcat AND B.idclient=" . $iIdClient . " AND A.idlang=" . $iIdLang;
    $db->query($sql);
    if ($db->nextRecord()) {
        strDeleteCategory($db->f('idcat'));
    }

    $aIdArtLang = array();
    $aIdArt = array();
    $aIdCatLang = array();
    $aIdCat = array();
    $aIdTplCfg = array();

    if ($deleteok == 1) {
        //********* check if this is the clients last language to be deleted, if yes delete from art, cat, and cat_art as well
        $lastlanguage = 0;
        $sql = "SELECT COUNT(*) FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient=" . $iIdClient;
        $db->query($sql);
        $db->nextRecord();
        if ($db->f(0) == 1) {
            $lastlanguage = 1;
        }

        //********** delete from 'art_lang'-table
        $sql = "SELECT A.idtplcfg AS idtplcfg, idartlang, A.idart FROM " . $cfg['tab']['art_lang'] . " AS A, " . $cfg['tab']['art'] . " AS B WHERE A.idart=B.idart AND B.idclient=" . $iIdClient . "
                AND idlang!=0 AND idlang=" . $iIdLang;
        $db->query($sql);
        while ($db->nextRecord()) {
            $aIdArtLang[] = $db->f('idartlang');
            $aIdArt[] = $db->f('idart');
            $aIdTplCfg[] = $db->f('idtplcfg');
        }
        foreach ($aIdArtLang as $value) {
            $value = (int) $value;
            $sql = "DELETE FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang=" . $value;
            $db->query($sql);
            $sql = "DELETE FROM " . $cfg['tab']['content'] . " WHERE idartlang=" . $value;
            $db->query($sql);
        }

        if ($lastlanguage == 1) {
            foreach ($aIdArt as $value) {
                $value = (int) $value;
                $sql = "DELETE FROM " . $cfg['tab']['art'] . " WHERE idart=" . $value;
                $db->query($sql);
                $sql = "DELETE FROM " . $cfg['tab']['cat_art'] . " WHERE idart=" . $value;
                $db->query($sql);
            }
        }

        //********** delete from 'cat_lang'-table
        $sql = "SELECT A.idtplcfg AS idtplcfg, idcatlang, A.idcat FROM " . $cfg['tab']['cat_lang'] . " AS A, " . $cfg['tab']['cat'] . " AS B WHERE A.idcat=B.idcat AND B.idclient=" . $iIdClient . "
                AND idlang!=0 AND idlang=" . $iIdLang;
        $db->query($sql);
        while ($db->nextRecord()) {
            $aIdCatLang[] = $db->f('idcatlang');
            $aIdCat[] = $db->f('idcat');
            $aIdTplCfg[] = $db->f('idtplcfg'); // added
        }
        foreach ($aIdCatLang as $value) {
            $sql = "DELETE FROM " . $cfg['tab']['cat_lang'] . " WHERE idcatlang=" . (int) $value;
            $db->query($sql);
        }
        if ($lastlanguage == 1) {
            foreach ($aIdCat as $value) {
                $value = (int) $value;
                $sql = "DELETE FROM " . $cfg['tab']['cat'] . " WHERE idcat=" . $value;
                $db->query($sql);
                $sql = "DELETE FROM " . $cfg['tab']["cat_tree"] . " WHERE idcat=" . $value;
                $db->query($sql);
            }
        }

        //********** delete from 'stat'-table
        $sql = "DELETE FROM " . $cfg['tab']['stat'] . " WHERE idlang=" . $iIdLang . " AND idclient=" . $iIdClient;
        $db->query($sql);

        //********** delete from 'code'-cache
        foreach (new DirectoryIterator($cfgClient[$client]['code']['path']) as $file) {
            if ($file->getFilename() == $iIdClient . "." . $iIdLang && $file->getExtension() == "php") {
                unlink($cfgClient[$client]['code']['path'] . $iIdClient . "." . $iIdLang . 'php');
            }
        }

        foreach ($aIdTplCfg as $tplcfg) {
            $tplcfg = (int) $tplcfg;
            if ($tplcfg != 0) {
                //********** delete from 'tpl_conf'-table
                $sql = "DELETE FROM " . $cfg['tab']['tpl_conf'] . " WHERE idtplcfg=" . $tplcfg;
                $db->query($sql);
                //********** delete from 'container_conf'-table
                $sql = "DELETE FROM " . $cfg['tab']['container_conf'] . " WHERE idtplcfg=" . $tplcfg;
                $db->query($sql);
            }
        }

        //*********** delete from 'clients_lang'-table
        $sql = "DELETE FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient=" . $iIdClient . " AND idlang=" . $iIdLang;
        $db->query($sql);

        //*********** delete from 'lang'-table
        $sql = "DELETE FROM " . $cfg['tab']['lang'] . " WHERE idlang=" . $iIdLang;
        $db->query($sql);

        //*********** delete from 'properties'-table
        $oPropertyColl = new cApiPropertyCollection($iIdClient);
        $oPropertyColl->deleteProperties('idlang', $iIdLang);
    } else {
        return $notification->returnMessageBox('error', i18n("Could not delete language"), 0);
    }
}

/**
 * Deactivate a language
 *
 * @param  int  $idlang
 * @param  int  $active
 * @return  bool
 */
function langActivateDeactivateLanguage($idlang, $active) {
    $oLang = new cApiLanguage();
    if ($oLang->loadByPrimaryKey((int) $idlang)) {
        $oLang->set('active', (int) $active, false);
        return $oLang->store();
    }
    return false;
}

/**
 * Returns the base direction of text (ltr = left to right, rtl = right to left) by language id
 *
 * @param  int  $idlang
 * @param  cDb  Is not in use
 * @return string  'ltr' or 'rtl'
 */
function langGetTextDirection($idlang, $db = null) {
    static $oLang;
    if (!isset($oLang)) {
        $oLang = new cApiLanguage();
    }
    $direction = '';
    if ($oLang->loadByPrimaryKey((int) $idlang)) {
        $direction = $oLang->get('direction');
    }
    if ($direction != 'ltr' && $direction != 'rtl') {
        $direction = 'ltr';
    }
    return $direction;
}

?>