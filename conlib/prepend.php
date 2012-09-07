<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Session Management for PHP3
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    1.3
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2000-01-01
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id: prepend.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

$_PHPLIB = array();
$_PHPLIB["libdir"] = str_replace ('\\', '/', dirname(__FILE__) . '/');

global $cfg;

if ($cfg["database_extension"] !== "mysqli")
{
	require($_PHPLIB["libdir"] . "db_mysql.inc"); 
} else {
	require($_PHPLIB["libdir"] . "db_mysqli.inc");
}

require($_PHPLIB["libdir"] . "ct_sql.inc");    /* Data storage container: database */
require($_PHPLIB["libdir"] . "ct_file.inc");    /* Data storage container: file */
require($_PHPLIB["libdir"] . "ct_shm.inc");    /* Data storage container: memory */
require($_PHPLIB["libdir"] . "ct_null.inc");    /* Data storage container: null -
													no session container - Contenido does not work */

require($_PHPLIB["libdir"] . "session.inc");   /* Required for everything below.      */
require($_PHPLIB["libdir"] . "auth.inc");      /* Disable this, if you are not using authentication. */
require($_PHPLIB["libdir"] . "perm.inc");      /* Disable this, if you are not using permission checks. */

/* Additional require statements go before this line */

require($_PHPLIB["libdir"] . "local.php");     /* Required, contains your local configuration. */

require($_PHPLIB["libdir"] . "page.inc");      /* Required, contains the page management functions. */

?>