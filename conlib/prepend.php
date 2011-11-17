<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLib startup
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Backend
 * @subpackage Startup
 * @version    1.4
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * 
 *
 * {@internal
 *   created  2000-01-01
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-10-29, Murat Purc, automatic loading of configured database driver
 *   modified 2011-03-21, Murat Purc, inclusion of ct_session.inc
 *
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

$_PHPLIB = array();
$_PHPLIB['libdir'] = str_replace ('\\', '/', dirname(__FILE__) . '/');

global $cfg;

require($_PHPLIB['libdir'] . 'db_sql_abstract.inc');

// include/require database driver
$dbDriverFileName = 'db_' . $cfg['database_extension'] . '.inc';
if (is_file($_PHPLIB['libdir'] . $dbDriverFileName)) {
    require_once($_PHPLIB['libdir'] . $dbDriverFileName);
} else {
    die('Invalid database extension: ' . $cfg['database_extension']);
}
unset($dbDriverFileName);

require_once($_PHPLIB['libdir'] . 'ct_sql.inc');    // Data storage container: database
require_once($_PHPLIB['libdir'] . 'ct_file.inc');   // Data storage container: file
require_once($_PHPLIB['libdir'] . 'ct_shm.inc');    // Data storage container: memory
require_once($_PHPLIB['libdir'] . 'ct_session.inc');// Data storage container: memory
require_once($_PHPLIB['libdir'] . 'ct_null.inc');   // Data storage container: null -
                                                    // no session container - CONTENIDO does not work

require_once($_PHPLIB['libdir'] . 'session.inc');   // Required for everything below.     
require_once($_PHPLIB['libdir'] . 'auth.inc');      // Disable this, if you are not using authentication.
require_once($_PHPLIB['libdir'] . 'perm.inc');      // Disable this, if you are not using permission checks.

// Additional require statements go before this line

require_once($_PHPLIB['libdir'] . 'local.php');     // Required, contains your local configuration.

require_once($_PHPLIB['libdir'] . 'page.inc');      // Required, contains the page management functions.
