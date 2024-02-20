<?php

/**
 * This file loads the header of the backend frameset.
 *
 * @package    Core
 * @subpackage Backend
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

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

$db = cRegistry::getDb();

if (isset($killperms)) {
    $sess->unregister('right_list');
    $sess->unregister('area_rights');
    $sess->unregister('item_rights');
}

$sess->register('sess_area');

if (isset($area)) {
    $sess_area = $area;
} else {
    $area = (isset($sess_area)) ? $sess_area : 'login';
}

if (isset($changelang) && is_numeric($changelang)) {
    unset($area_rights);
    unset($item_rights);

    $sess->register('lang');
    $lang = $changelang;
}

if (!cSecurity::isPositiveInteger($client ?? 0)
    || !cApiClientCollection::isClientAccessible(cSecurity::toInteger($client))) {
    // use first client which is accessible
    $sess->register('client');
    $oClientColl = new cApiClientCollection();
    if ($oClient = $oClientColl->getFirstAccessibleClient()) {
        unset($lang);
        $client = $oClient->get('idclient');
    }
} else {
    $sess->register('client');
}

if (!cSecurity::isPositiveInteger($lang ?? 0)) {
    $sess->register('lang');
    // Search for the first language of this client
    $oClientLangColl = new cApiClientLanguageCollection();
    $lang = (int)$oClientLangColl->getFirstLanguageIdByClient($client);
} else {
    $sess->register('lang');
}

// call http encoding header sending function
sendEncodingHeader($db, $cfg, $lang);

$perm->load_permissions();

$tpl = new cTemplate();
$nav = new cGuiNavigation();

$nav->buildHeader($lang);

cRegistry::shutdown();
