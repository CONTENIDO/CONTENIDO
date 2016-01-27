<?php
/**
 * This file loads the header of the backend frameset.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('./includes/startup.php');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
));

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

if (is_numeric($changelang)) {
    unset($area_rights);
    unset($item_rights);

    $sess->register('lang');
    $lang = $changelang;
}

if (!is_numeric($client) ||
    (!$perm->have_perm_client('client['.$client.']') &&
    !$perm->have_perm_client('admin['.$client.']')))
{
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

if (!is_numeric($lang)) { // use first language found
    $sess->register("lang");
    $sql = "SELECT * FROM ".$cfg["tab"]["lang"]." AS A, ".$cfg["tab"]["clients_lang"]." AS B WHERE A.idlang=B.idlang AND idclient='".cSecurity::toInteger($client)."' ORDER BY A.idlang ASC";
    $db->query($sql);
    $db->nextRecord();
    $lang = $db->f('idlang');
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

?>