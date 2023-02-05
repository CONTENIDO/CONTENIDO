<?php
/**
 * This file handles the logout from backend.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
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

cRegistry::bootstrap(
    [
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission',
    ]
);

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

$db = cRegistry::getDb();

$iUserId = $auth->auth['uid'];

$oInUse = new cApiInUseCollection();
$oInUse->removeUserMarks($iUserId);

$oActiveUser = new cApiOnlineUserCollection();
$oActiveUser->deleteUser($iUserId);

$auth->logout();
cRegistry::shutdown();
$sess->delete();

header('Location:index.php');

?>