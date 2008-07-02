<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido main file
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2.1
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-20#
 *   modified 2008-06-25, Timo Trautmann, Contenido Framework Constand added
 *   modified 2008-07-02, Frederic Schneider, new code-header and include security_class
 *
 *   $Id$:
 * }}
 * 
 */

define("CON_FRAMEWORK", true);

// include security class and check request variables
include_once ('./classes/class.security.php');
Contenido_Security::checkRequests();

include_once ('./includes/startup.php');
include_once ($cfg["path"]["classes"] . 'class.template.php');

page_open(
    array('sess' => 'Contenido_Session',
          'auth' => 'Contenido_Challenge_Crypt_Auth',
          'perm' => 'Contenido_Perm'));

cInclude ("includes", 'functions.i18n.php');

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

cInclude ("includes", 'cfg_sql.inc.php');
cInclude ("includes", 'cfg_language_de.inc.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.forms.php');

# Create Contenido classes
$db  = new DB_Contenido;
$tpl = new Template;

# Build the Contenido
# Content area frameset
$tpl->reset();

if (isset($_GET["appendparameters"]))
{
	$tpl->set('s', 'FRAME[3]', $sess->url("main.php?area=$area&frame=3&appendparameters=".$_GET["appendparameters"]));
	$tpl->set('s', 'FRAME[4]', $sess->url("main.php?area=$area&frame=4&appendparameters=".$_GET["appendparameters"]));
} else {
	$tpl->set('s', 'FRAME[3]', $sess->url("main.php?area=$area&frame=3"));
	$tpl->set('s', 'FRAME[4]', $sess->url("main.php?area=$area&frame=4"));
}

$tpl->set('s', 'VERSION', $cfg['version']);
$tpl->set('s', 'CONTENIDOPATH', $cfg["path"]["contenido_fullhtml"]."favicon.ico");

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset_right']);

page_close();

?>