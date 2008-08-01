<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Help system
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend scripts
 * @version    1.3.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-16, H. Librenz, Hotfix: Added check for invalid calls
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-07-02, Frederic Schneider, include security_class
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// include security class and check request variables
include_once ('../classes/class.security.php');
Contenido_Security::checkRequests();

include_once ('../includes/startup.php');

include_once ($cfg["path"]["contenido"].$cfg["path"]["includes"] . 'functions.i18n.php');

header("Content-Type: text/javascript");

page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
page_close();

$baseurl = $cfg["help_url"] . "front_content.php?version=".$cfg['version']."&help=";
?>

function callHelp (path)
{
	f1 = window.open('<?php echo $baseurl; ?>' + path, 'contenido_help', 'height=500,width=600,resizable=yes,scrollbars=yes,location=no,menubar=no,status=no,toolbar=no');
	f1.focus();
}
