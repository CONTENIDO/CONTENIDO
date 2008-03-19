<?php

/******************************************
* File      :   config.php
* Project   :   Contenido
* Descr     :   Defines all general
*               variables of Contenido.
*
* � four for business AG
******************************************/

global $cfg;

/* Section 1: Path settings
 * ------------------------
 *
 * Path settings which will vary along different
 * Contenido settings.
 *
 * A little note about web and server path settings:
 * - A Web Path can be imagined as web addresses. Example:
 *   http://192.168.1.1/test/
 * - A Server Path is the path on the server's hard disk. Example:
 *   /var/www/html/contenido    for Unix systems OR
 *   c:/htdocs/contenido        for Windows systems
 *
 * Note: If you want to modify the locations of subdirectories for
 *       some reason (e.g. the includes directory), see Section 8.
 */

/* The root server path to the contenido backend */
$cfg['path']['contenido']               = '{CONTENIDO_ROOT}/contenido/';

/* The web server path to the contenido backend */
$cfg['path']['contenido_fullhtml']      = '{CONTENIDO_WEB}/contenido/';

/* The root server path where all frontends reside */
$cfg['path']['frontend']                = '{CONTENIDO_ROOT}';

/* The root server path to the conlib directory */
$cfg['path']['phplib']                  = '{CONTENIDO_ROOT}/conlib/';

/* The root server path to the pear directory */
$cfg['path']['pear']                    = '{CONTENIDO_ROOT}/pear/';

/* The server path to the desired WYSIWYG-Editor */
$cfg['path']['wysiwyg']                 = '{CONTENIDO_ROOT}/contenido/external/wysiwyg/tinymce2/';

/* The web path to the desired WYSIWYG-Editor */
$cfg['path']['wysiwyg_html']            = '{CONTENIDO_WEB}/contenido/external/wysiwyg/tinymce2/';

/* The server path to all WYSIWYG-Editors */
$cfg['path']['all_wysiwyg']                 = '{CONTENIDO_ROOT}/contenido/external/wysiwyg/';

/* The web path to all WYSIWYG-Editors */
$cfg['path']['all_wysiwyg_html']            = '{CONTENIDO_WEB}/contenido/external/wysiwyg/';





/* Section 2: Database settings
 * ----------------------------
 *
 * Database settings for MySQL. Note that we don't support
 * other databases in this release.
 */

/* The prefix for all contenido system tables, usually "con" */
$cfg['sql']['sqlprefix'] = '{MYSQL_PREFIX}';

/* The host where your database runs on */
$contenido_host = '{MYSQL_HOST}';

/* The database name which you use */
$contenido_database = '{MYSQL_DB}';

/* The username to access the database */
$contenido_user = '{MYSQL_USER}';

/* The password to access the database */
$contenido_password = '{MYSQL_PASS}';

$cfg["database_extension"] = '{DB_EXTENSION}';

$cfg["nolock"] = '{NOLOCK}';

$cfg["is_start_compatible"] = {START_COMPATIBLE};


/* Security fix */
if ( isset($_REQUEST['cfg']) ) { exit; }
if ( isset($_REQUEST['cfgClient']) ) { exit; }
?>
