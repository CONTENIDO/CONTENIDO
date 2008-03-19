<?php
/**
 * Plugin Systemtools
 *
 * @file config.plugin.php
 * 
 * @version	1.0.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 24.08.2005
 * @modified 24.08.2005
 * @modified 22.02.2006
 */

#### define array of valid users. The users must be system administrators!
global $arrayOfValidUsers;
$arrayOfValidUsers = array('sysadmin');

#### define plugin path
define('__plugin_systemtools_path__', 'systemtools/');

?>