<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Generates the configuration file and saves it into CONTENIDO folder or
 * outputs the for download (depending on selected option during setup)
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2.6
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


global $cfg, $client, $db;

$db = getSetupMySQLDBConnection(false);
if (checkMySQLDatabaseCreation($db, $_SESSION['dbname'])) {
    $db = getSetupMySQLDBConnection();
}

$done = false;
$sSql = "SHOW FIELDS FROM ".$cfg['tab']['upl'];
$db->query($sSql);
while ($db->next_record()) {
    if ($db->f("Field") == 'description') {
        $done = true;
    }
}
if ($done) {
    updateUpl2Meta();
}

list($rootPath, $rootHttpPath) = getSystemDirectories();

$tpl = new Template();
$tpl->set('s', 'CONTENIDO_ROOT', $rootPath);
$tpl->set('s', 'CONTENIDO_WEB', $rootHttpPath);
$tpl->set('s', 'MYSQL_HOST', $cfg['db']['connection']['host']);
$tpl->set('s', 'MYSQL_DB', $cfg['db']['connection']['database']);
$tpl->set('s', 'MYSQL_USER', $cfg['db']['connection']['user']);
$tpl->set('s', 'MYSQL_PASS', $cfg['db']['connection']['password']);
$tpl->set('s', 'MYSQL_PREFIX', $cfg['sql']['sqlprefix']);

if (hasMySQLiExtension() && !hasMySQLExtension()) {
    $tpl->set('s', 'DB_EXTENSION', 'mysqli');
} else {
    $tpl->set('s', 'DB_EXTENSION', 'mysql');
}

$tpl->set('s', 'NOLOCK', $_SESSION['nolock']);

if ($_SESSION['configmode'] == 'save') {
    @unlink($cfg['path']['contenido_config'] . 'config.php');

    cFileHandler::create($cfg['path']['contenido_config'] . 'config.php', $tpl->generate('templates/config.php.tpl', true, false));

    if (!cFileHandler::exists($cfg['path']['contenido_config'] . 'config.php')) {
        $_SESSION['configsavefailed'] = true;
    } else {
        unset($_SESSION['configsavefailed']);
    }
} else {
    header('Content-Type: application/octet-stream');
    header('Etag: ' . md5(mt_rand()));
    header('Content-Disposition: attachment;filename=config.php');
    $tpl->generate('templates/config.php.tpl', false, false);
}
//update description from con_upl to con_upl_meta
function updateUpl2Meta() {
	global $cfg, $client, $db;
    $client = 1;
    //get
    $aUpl = array();
    $sSql = "SELECT * FROM " . $cfg['tab']['upl'] . " WHERE idclient = ". $client." AND `description` != '' ORDER BY idupl ASC";
    $db->query($sSql);
    while ($db->next_record()) {
        $aUpl[$db->f('idupl')]['description'] = $db->f('description');
        $aUpl[$db->f('idupl')]['author'] = $db->f('author');
        $aUpl[$db->f('idupl')]['created'] = $db->f('created');
        $aUpl[$db->f('idupl')]['lastmodified'] = $db->f('lastmodified');
        $aUpl[$db->f('idupl')]['modifiedby'] = $db->f('modifiedby');
    }
    $aLang = array();
    $sSql = "SELECT idlang FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient = ". $client." ORDER BY idlang ASC";
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
                $sSql = "SELECT * FROM " . $cfg['tab']['upl_meta'] . " WHERE idlang = ".$idlang."  AND idupl = ".$idupl." ORDER BY idupl ASC";
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
                        description = '" . $elem['description'] ."',
                        keywords = '',
                        internal_notice = '',
                        author = '" . $elem['author'] ."',
                        created = '" . $elem['created'] ."',
                        modified = '" . $elem['lastmodified'] ."',
                        modifiedby = '" . $elem['modifiedby'] ."',
                        copyright = ''";
                } elseif (count($aUplMeta) == 1 && $aUplMeta[0]['description'] == '') {
                    //there is already an entry and the field "description" is empty
                    $sSql = "UPDATE " . $cfg['tab']['upl_meta'] . " SET
                        description = '" . $elem['description'] ."'
                        WHERE id_uplmeta = " . $aUplMeta[0]['id_uplmeta'];
                } else {
                    //there is already an entry with an exising content in "description"
                    //do nothing;
                }
                $db->query($sSql);
                if ($db->Error !=0) {
                    $bError = false;
                    echo "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>";
                }
            }
        }
        $j++;
    }
    //At the end remove all values of con_upl.description and drop the field from table
    if ($bError && $j == count($aUpl)) {
        $sSql = "ALTER TABLE `".$cfg['tab']['upl']."` DROP `description`";
        $db->query($sSql);
        if ($db->Error !=0) {
            echo "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>";
        }
    } else {
        echo "<pre>error on updateUpl2Meta();".$j.'=='.count($aUpl)."</pre>";
    }
}

?>