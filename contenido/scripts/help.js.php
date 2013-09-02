<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Help system
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend scripts
 * @version    1.3.4
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../includes/startup.php');

header('Content-Type: text/javascript');

cRegistry::bootstrap(array('sess' => 'cSession',
                'auth' => 'cAuthHandlerBackend',
                'perm' => 'cPermission'));

i18nInit($cfg['path']['contenido_locale'], $belang);
// do not call cRegistry::shutdown(); here because
// it will print <script> tags which result in errors

$baseurl = $cfg['help_url'] . 'front_content.php?version='.CON_VERSION.'&help=';
?>

function callHelp (path)
{
    f1 = window.open('<?php echo $baseurl; ?>' + path, 'contenido_help', 'height=500,width=600,resizable=yes,scrollbars=yes,location=no,menubar=no,status=no,toolbar=no');
    f1.focus();
}
