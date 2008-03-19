<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: prepend.php,v 1.3 2007/07/20 22:18:31 holger.librenz Exp $
 *
 */

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