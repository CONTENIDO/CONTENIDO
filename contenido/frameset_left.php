<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO main file
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend
 * @version    1.2.2
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-01-20
 *   modified 2008-06-16, Holger Librenz, Hotfix: added check for illegal calling
 *   modified 2008-06-25, Timo Trautmann, CONTENIDO Framework Constand added
 *   modified 2008-07-02, Frederic Schneider, new code-header and include security_class
 *   modified 2009-10-16, Ortwin Pinke, added rewrite of ampersand in frameset url
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('./includes/startup.php');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'Contenido_Challenge_Crypt_Auth',
    'perm' => 'cPermission'
));

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');
cInclude('includes', 'functions.forms.php');

// Create CONTENIDO classes
$db  = cRegistry::getDb();
$tpl = new cTemplate();

// Build the CONTENIDO content area frameset
$tpl->reset();

if (isset($_GET['appendparameters'])) {
    $tpl->set('s', 'FRAME[1]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=1&appendparameters=".$_GET['appendparameters'])));
    $tpl->set('s', 'FRAME[2]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=2&appendparameters=".$_GET['appendparameters'])));
    $tpl->set('s', 'FRAME[3]', 'templates/standard/template.deco.html');
} else {
    $tpl->set('s', 'FRAME[1]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=1")));
    $tpl->set('s', 'FRAME[2]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=2")));
    $tpl->set('s', 'FRAME[3]', 'templates/standard/template.deco.html');
}

$tpl->set('s', 'VERSION', $cfg['version']);
$tpl->set('s', 'CONTENIDOPATH', $cfg['path']['contenido_fullhtml'] . 'favicon.ico');
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_left']);

cRegistry::shutdown();

?>