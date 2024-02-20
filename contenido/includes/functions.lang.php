<?php

/**
 * This file contains the CONTENIDO language functions.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.str.php');

/**
 * Edit a language
 *
 * @param int $idlang
 * @param string $langname
 *         Name of the language
 * @param string $encoding
 * @param int $active
 *         Flag for active state, 1 or 0
 * @param string $direction
 *
 * @return bool
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function langEditLanguage($idlang, $langname, $encoding, $active, $direction = 'ltr')
{
    $oLang = new cApiLanguage();
    if ($oLang->loadByPrimaryKey((int)$idlang)) {
        if ('' === $langname) {
            $langname = "-- " . i18n("New language") . " --";
        }
        $oLang->set('name', $langname, false);
        $oLang->set('encoding', $encoding, false);
        $oLang->set('active', $active, false);
        $oLang->set('direction', $direction, false);
        return $oLang->store();
    }
    return false;
}

/**
 * Create a new language
 *
 * @param string $name
 *         Name of the language
 * @param int $client
 *         Id of client
 *
 * @return int
 *         New language id
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function langNewLanguage($name, $client)
{
    global $cfgClient, $notification;

    // Add new language to database
    $oLangCol = new cApiLanguageCollection();
    $oLangItem = $oLangCol->create($name, 0, 'utf-8', 'ltr');
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
 * @param int $idlang
 *         Id of the language
 * @param string $name
 *         Name of the language
 *
 * @return bool
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function langRenameLanguage($idlang, $name)
{
    $oLang = new cApiLanguage();
    if ($oLang->loadByPrimaryKey(cSecurity::toInteger($idlang))) {
        $oLang->set('name', $name, false);
        return $oLang->store();
    }
    return false;
}

/**
 * Delete a language
 *
 * @param int $iIdLang
 *         Id of the language
 * @param int $iIdClient
 *         Id of the client, uses global client id by default
 *
 * @return void|string
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function langDeleteLanguage($iIdLang, $iIdClient = 0)
{
    global $db, $sess, $client, $cfg, $notification, $cfgClient;

    $deleteok = 1;
    $iIdLang = (int)$iIdLang;
    $iIdClient = (int)$iIdClient;

    // Bugfix: New idclient parameter introduced, as Administration -> Languages
    // is used for different clients to delete the language
    // Use global client id, if idclient not specified (former behaviour)
    // Note, that this check also have been added for the action in the database
    // - just to be equal to langNewLanguage
    if ($iIdClient == 0) {
        $iIdClient = $client;
    }

    // ************ check if there are still arts online
    $sql = "SELECT * FROM " . $cfg['tab']['art_lang'] . " AS A, " . $cfg['tab']['art'] . " AS B " . "WHERE A.idart=B.idart AND B.idclient=" . $iIdClient . " AND A.idlang=" . $iIdLang;
    $db->query($sql);
    if ($db->nextRecord()) {
        conDeleteArt($db->f('idart'));
    }

    // ************ check if there are visible categories
    $sql = "SELECT * FROM " . $cfg['tab']['cat_lang'] . " AS A, " . $cfg['tab']['cat'] . " AS B " . "WHERE A.idcat=B.idcat AND B.idclient=" . $iIdClient . " AND A.idlang=" . $iIdLang;
    $db->query($sql);
    if ($db->nextRecord()) {
        strDeleteCategory($db->f('idcat'));
    }

    $aIdArtLang = [];
    $aIdArt = [];
    $aIdCatLang = [];
    $aIdCat = [];
    $aIdTplCfg = [];

    if ($deleteok == 1) {
        // ********* check if this is the clients last language to be deleted,
        // if yes delete from art, cat, and cat_art as well
        $lastlanguage = 0;
        $sql = "SELECT COUNT(*) FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient=" . $iIdClient;
        $db->query($sql);
        $db->nextRecord();
        if ($db->f(0) == 1) {
            $lastlanguage = 1;
        }

        // ********** delete from 'art_lang'-table
        $sql = "SELECT A.idtplcfg AS idtplcfg, idartlang, A.idart FROM " . $cfg['tab']['art_lang'] . " AS A, " . $cfg['tab']['art'] . " AS B WHERE A.idart=B.idart AND B.idclient=" . $iIdClient . "
                AND idlang!=0 AND idlang=" . $iIdLang;
        $db->query($sql);
        while ($db->nextRecord()) {
            $aIdArtLang[] = $db->f('idartlang');
            $aIdArt[] = $db->f('idart');
            $aIdTplCfg[] = $db->f('idtplcfg');
        }
        foreach ($aIdArtLang as $value) {
            $value = cSecurity::toInteger($value);
            $sql = "DELETE FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang=" . $value;
            $db->query($sql);
            $sql = "DELETE FROM " . $cfg['tab']['content'] . " WHERE idartlang=" . $value;
            $db->query($sql);
        }

        if ($lastlanguage == 1) {
            foreach ($aIdArt as $value) {
                $value = cSecurity::toInteger($value);
                $sql = "DELETE FROM " . $cfg['tab']['art'] . " WHERE idart=" . $value;
                $db->query($sql);
                $sql = "DELETE FROM " . $cfg['tab']['cat_art'] . " WHERE idart=" . $value;
                $db->query($sql);
            }
        }

        // ********** delete from 'cat_lang'-table
        $sql = "SELECT A.idtplcfg AS idtplcfg, idcatlang, A.idcat FROM " . $cfg['tab']['cat_lang'] . " AS A, " . $cfg['tab']['cat'] . " AS B WHERE A.idcat=B.idcat AND B.idclient=" . $iIdClient . "
                AND idlang!=0 AND idlang=" . $iIdLang;
        $db->query($sql);
        while ($db->nextRecord()) {
            $aIdCatLang[] = $db->f('idcatlang');
            $aIdCat[] = $db->f('idcat');
            $aIdTplCfg[] = $db->f('idtplcfg'); // added
        }
        foreach ($aIdCatLang as $value) {
            $sql = "DELETE FROM " . $cfg['tab']['cat_lang'] . " WHERE idcatlang=" . (int)$value;
            $db->query($sql);
        }
        if ($lastlanguage == 1) {
            foreach ($aIdCat as $value) {
                $value = cSecurity::toInteger($value);
                $sql = "DELETE FROM " . $cfg['tab']['cat'] . " WHERE idcat=" . $value;
                $db->query($sql);
                $sql = "DELETE FROM " . $cfg['tab']["cat_tree"] . " WHERE idcat=" . $value;
                $db->query($sql);
            }
        }

        // ********** delete from 'stat'-table
        $sql = "DELETE FROM " . $cfg['tab']['stat'] . " WHERE idlang=" . $iIdLang . " AND idclient=" . $iIdClient;
        $db->query($sql);

        // ********** delete from 'code'-cache
        if (cFileHandler::exists($cfgClient[$iIdClient]['code']['path'])) {
            /* @var $file SplFileInfo */
            foreach (new DirectoryIterator($cfgClient[$iIdClient]['code']['path']) as $file) {
                if ($file->isFile() === false) {
                    continue;
                }

                $extension = cString::getPartOfString($file, cString::findLastPos($file->getBasename(), '.') + 1);
                if ($extension != 'php') {
                    continue;
                }

                if (preg_match('/' . $iIdClient . '.' . $iIdLang . '.[0-9*]/s', $file->getBasename())) {
                    cFileHandler::remove($cfgClient[$iIdClient]['code']['path'] . '/' . $file->getFilename());
                }
            }
        }

        foreach ($aIdTplCfg as $tplcfg) {
            $tplcfg = (int)$tplcfg;
            if ($tplcfg != 0) {
                // ********** delete from 'tpl_conf'-table
                $sql = "DELETE FROM " . $cfg['tab']['tpl_conf'] . " WHERE idtplcfg=" . $tplcfg;
                $db->query($sql);
                // ********** delete from 'container_conf'-table
                $sql = "DELETE FROM " . $cfg['tab']['container_conf'] . " WHERE idtplcfg=" . $tplcfg;
                $db->query($sql);
            }
        }

        // *********** delete from 'clients_lang'-table
        $sql = "DELETE FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient=" . $iIdClient . " AND idlang=" . $iIdLang;
        $db->query($sql);

        // *********** delete from 'lang'-table
        $sql = "DELETE FROM " . $cfg['tab']['lang'] . " WHERE idlang=" . $iIdLang;
        $db->query($sql);

        // *********** delete from 'properties'-table
        $oPropertyColl = new cApiPropertyCollection($iIdClient);
        $oPropertyColl->deleteProperties('idlang', $iIdLang);
    } else {
        return $notification->returnMessageBox('error', i18n("Could not delete language"), 0);
    }
}

/**
 * Deactivate a language
 *
 * @param int $idlang
 * @param int $active
 *
 * @return bool
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function langActivateDeactivateLanguage($idlang, $active)
{
    $oLang = new cApiLanguage();
    if ($oLang->loadByPrimaryKey((int)$idlang)) {
        $oLang->set('active', (int)$active, false);
        return $oLang->store();
    }
    return false;
}

/**
 * Returns the base direction of text (ltr = left to right, rtl = right to left)
 * by language id
 *
 * @param int $idlang
 * @param cDb $db
 *         Is not in use
 *
 * @return string
 *         'ltr' or 'rtl'
 *
 * @throws cDbException
 * @throws cException
 */
function langGetTextDirection($idlang, $db = NULL)
{
    static $oLang;
    if (!isset($oLang)) {
        $oLang = new cApiLanguage();
    }
    $direction = '';
    if ($oLang->loadByPrimaryKey((int)$idlang)) {
        $direction = $oLang->get('direction');
    }
    if ($direction != 'ltr' && $direction != 'rtl') {
        $direction = 'ltr';
    }
    return $direction;
}
