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
 * {@internal
 *   created  2000-01-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$_PHPLIB = array();
$_PHPLIB['libdir'] = str_replace('\\', '/', dirname(__FILE__) . '/');

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
require_once($_PHPLIB['libdir'] . 'ct_session.inc'); // Data storage container: session

require_once($_PHPLIB['libdir'] . 'session.inc');   // Session management
require_once($_PHPLIB['libdir'] . 'auth.inc');      // Authorization management
// Additional require statements go before this line
require_once($_PHPLIB['libdir'] . 'local.php');     // Required, contains your local configuration.
require_once($_PHPLIB['libdir'] . 'page.inc');      // Required, contains the page management functions.
