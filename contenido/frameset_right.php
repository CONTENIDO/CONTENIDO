<?php
/**
 * This file loads the right backend frameset.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann, Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
// CONTENIDO startup process
include_once('./includes/startup.php');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
));

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

// Create CONTENIDO classes
$db  = cRegistry::getDb();
$tpl = new cTemplate();

// Build the CONTENIDO content area frameset
$tpl->reset();

if (isset($_GET['appendparameters'])) {
    $tpl->set('s', 'FRAME[3]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=3&appendparameters=" . $_GET['appendparameters'])));
    $tpl->set('s', 'FRAME[4]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=4&appendparameters=" . $_GET['appendparameters'])));
} else {
    $tpl->set('s', 'FRAME[3]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=3")));
    $tpl->set('s', 'FRAME[4]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=4")));
}

$tpl->set('s', 'VERSION', CON_VERSION);
$tpl->set('s', 'CONTENIDOPATH', cRegistry::getBackendUrl() . 'favicon.ico');

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_right']);

cRegistry::shutdown();

?>