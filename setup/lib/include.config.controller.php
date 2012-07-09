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

?>