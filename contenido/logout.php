<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Logout function
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend
 * @version    1.1.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-05-20
 *   modified 2008-07-02, Frederic Schneider, new code-header and include security_class
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
    'sess' => 'Contenido_Session',
    'auth' => 'Contenido_Challenge_Crypt_Auth',
    'perm' => 'Contenido_Perm'
));

i18nInit($cfg['path']['contenido'] . $cfg['path']['locale'], $belang);

cInclude('includes', 'cfg_actions.inc.php');
cInclude('includes', 'functions.forms.php');

$db = new DB_Contenido();

$iUserId = $auth->auth['uid'];
$oActiveUser = new cApiOnlineUserCollection();
$oActiveUser->deleteUser($iUserId);

$auth->logout();
cRegistry::shutdown();
$sess->delete();

header('Location:index.php');

?>