<?php
/**
 * @deprecated [2013-11-05] Use help.js instead
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

// @deprecated [2013-11-05] Use help.js instead
function callHelp (path)
{
    f1 = window.open('<?php echo $baseurl; ?>' + path, 'contenido_help', 'height=500,width=600,resizable=yes,scrollbars=yes,location=no,menubar=no,status=no,toolbar=no');
    f1.focus();
}
