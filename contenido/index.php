<?php

/**
 * This file is the main entrance point of the backend.
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
 * @var cPermission $perm
 * @var string $belang
 * @var array $cfg
 * @var cSession $sess
 * @var int $changelang
 * @var int $client
 */

// CONTENIDO startup process
include_once('./includes/startup.php');

cRegistry::bootstrap([
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
]);

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'functions.includePluginConf.php');

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

$sess->register('belang');

//test
$sess->register('selectedArticleId');
//test

// create global CONTENIDO class instances
$db = cRegistry::getDb();
$tpl = new cTemplate();

// change lang
if (isset($changelang) && is_numeric($changelang)) {
    $lang = $changelang;
}

// change client
if (isset($changeclient) && is_numeric($changeclient)) {
    $client = $changeclient;
    unset($lang);
}

// preselect client and language, if defined
// only check at first login into backend
if (!$sess->isRegistered('client')) {
    $iTmpClient = getEffectiveSetting('backend', 'preferred_idclient', false);

    if ($iTmpClient && ($perm->have_perm_client('admin[' . $iTmpClient . ']') || $perm->have_perm_client('client[' . $iTmpClient . ']'))) {
        $client = $iTmpClient;
        unset($lang);
    }
    unset($iTmpClient);

    $iTmpLang = getEffectiveSetting('backend', 'preferred_idlang', false);

    if ($iTmpLang && ($perm->have_perm_client_lang($client, $iTmpLang))) {
        $lang = $iTmpLang;
    }
    unset($iTmpLang);
}

if (!is_numeric($client) || $client == '') {
    $sess->register('client');
    $oClientColl = new cApiClientCollection();
    $oClientColl->select('', '', 'idclient ASC', '1');
    $oClient = $oClientColl->next();
    if ($oClient == NULL) {
        $client = 0;
    } else {
        $client = $oClient->get('idclient');
    }
} else {
    $sess->register('client');
}

if (!is_numeric($lang) || $lang == '') {
    $sess->register('lang');

    $oClientLangColl = new cApiClientLanguageCollection();
    $aClientLanguages = $oClientLangColl->getAllLanguageIdsByClient(
        cSecurity::toInteger($client)
    );
    do {
        $lang = array_shift($aClientLanguages);
        if (!$perm->have_perm_client_lang($client, $lang)) {
            $lang = '';
        }
    } while (count($aClientLanguages) && !is_numeric($lang));
    unset($aClientLanguages);
} else {
    $sess->register('lang');
}

$perm->load_permissions();

if (isset($area)) {
    $sess_area = $area;
} else {
    $area = $sess_area ?? 'login';
}

$backendUrl = cRegistry::getBackendUrl();

$tpl->reset();

// Get backend label
$backend_label = getSystemProperty('backend', 'backend_label');
$backend_label = ' ' . $backend_label . ' ';
$tpl->set('s', 'BACKEND_LABEL', $backend_label);

// Template settings
$tpl->set('s', 'HEADER', str_replace('&', '&amp;', $sess->url('header.php?changelang=' . $lang . '&changeclient=' . $client)));
$tpl->set('s', 'CONTENT', str_replace('&', '&amp;', $sess->url('frameset.php?area=mycontenido&frame=1&menuless=1&changelang=' . $changelang . '&lang=' . $lang . '&client=' . $client)));
$tpl->set('s', 'VERSION', CON_VERSION);
$tpl->set('s', 'LOCATION', $backendUrl);
$tpl->set('s', 'CONTENIDOPATH', $backendUrl . 'favicon.ico');
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset']);

cRegistry::shutdown();
