<?php

/**
 * This file loads the left backend frameset.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

/**
 * @var string $belang
 * @var array $cfg
 * @var cSession $sess
 * @var string $area
 */

// CONTENIDO startup process
include_once('./includes/startup.php');

cRegistry::bootstrap([
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission',
]);

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

// Create CONTENIDO classes
$db = cRegistry::getDb();
$tpl = new cTemplate();

// Build the CONTENIDO content area frameset
$tpl->reset();

if (isset($_GET['appendparameters'])) {
    $tpl->set('s', 'FRAME[1]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=1&appendparameters=" . $_GET['appendparameters'])));
    $tpl->set('s', 'FRAME[2]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=2&appendparameters=" . $_GET['appendparameters'])));
    $tpl->set('s', 'FRAME[3]', 'templates/standard/template.deco.html');
} else {
    $tpl->set('s', 'FRAME[1]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=1")));
    $tpl->set('s', 'FRAME[2]', str_replace('&', '&amp;', $sess->url("main.php?area=$area&frame=2")));
    $tpl->set('s', 'FRAME[3]', 'templates/standard/template.deco.html');
}

$tpl->set('s', 'VERSION', CON_VERSION);
$tpl->set('s', 'CONTENIDOPATH', cRegistry::getBackendUrl() . 'favicon.ico');
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_left']);

cRegistry::shutdown();
