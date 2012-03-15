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

page_open(array(
    'sess' => 'Contenido_Session',
    'auth' => 'Contenido_Challenge_Crypt_Auth',
    'perm' => 'Contenido_Perm'
));

i18nInit($cfg['path']['contenido'] . $cfg['path']['locale'], $belang);

cInclude('includes', 'cfg_language_de.inc.php');
cInclude('includes', 'functions.forms.php');

$db = new DB_Contenido();

$oActiveUser = new ActiveUsers($db, $cfg, $auth);
$iUserId = $auth->auth['uid'];
$oActiveUser->deleteUser($iUserId);

$auth->logout();
page_close();
$sess->delete();

header('Location:index.php');

?>