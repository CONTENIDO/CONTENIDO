<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Misc Configurations
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.4.12
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2004-02-24
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-04, Dominik Ziegler, fixed bug CON-174
 *   modified 2008-11-10 Rudi Bieller Commented out display_errors as this should be handled as defined in php.ini by default
 *   modified 2008-11-18, Murat Purc, add UrlBuilder configuration
 *   modified 2008-12-04, Bilal Arslan, added for config-password examples.
 *   modified 2010-05-20, Murat Purc, documented settings for UrlBuilder and caching.
 *   modified 2011-03-13  Murat Purc, added configuration for GenericDB caching.
 *   modified 2011-08-24, Dominik Ziegler, removed CVS datetag configuration entry
 *   modified 2011-11-10  Murat Purc, added configuration for properties (user, group, system) caching.
 *   modified 2011-11-18  Murat Purc, added configuration for validators.
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file 'config.local.php'
   to prevent that your changes are overwritten during a system update. */


/* Misc settings
 * -----------------------------------------------------------------------------
 * Actually no variables, but important settings for error handling and logging.
 */

/* Current CONTENIDO Version. You shouldn't change this value unless you know what 
   you are doing. */
$cfg['version'] = '4.9.0-alpha1';

/* Backend timeout */
$cfg['backend']['timeout'] = 60;

/* Use Pseudo-Cron? */
$cfg['use_pseudocron'] = true;

/* If you want to measure function timing set this to true */
$cfg['debug']['functiontiming'] = false;

/* If you want to measure backend page rendering times, set this to true */
$cfg['debug']['rendering'] = false;

/* To output the code when editing and browsing the frontend, set this to true */
$cfg['debug']['codeoutput'] = false;

/* If true, use the field 'urlname' for resolving. 'name' otherwise */
$cfg['urlpathresolve'] = false;

/* The available charsets */
$cfg['AvailableCharsets'] = array(
    'iso-8859-1',
    'iso-8859-2',
    'iso-8859-3',
    'iso-8859-4',
    'iso-8859-5',
    'iso-8859-6',
    'iso-8859-7',
    'iso-8859-8',
    'iso-8859-8-i',
    'iso-8859-9',
    'iso-8859-10',
    'iso-8859-11',
    'iso-8859-12',
    'iso-8859-13',
    'iso-8859-14',
    'iso-8859-15',
    'iso-8859-16',
    'windows-1250',
    'windows-1251',
    'windows-1252',
    'windows-1253',
    'windows-1254',
    'windows-1255',
    'windows-1256',
    'windows-1257',
    'windows-1258',
    'koi8-r',
    'big5',
    'gb2312',
    'utf-8',
    'utf-7',
    'x-user-defined',
    'euc-jp',
    'ks_c_5601-1987',
    'tis-620',
    'SHIFT_JIS'
);


//native i18n 
$cfg['native_i18n'] = false;
/* Error handling settings
 * -----------------------------------------------------------------------------
 * Actually no variables, but important settings for error handling and logging.
 */

/* Don't display errors */
//@ini_set('display_errors', true);

/* Log errors to a file */
@ini_set('log_errors', true);

/* Report all errors except warnings */
error_reporting(E_ALL ^E_NOTICE);


/* Session data storage container (PHPLIB)
 * -----------------------------------------------------------------------------
 * Different session data storage containers are available.
 * file = session data will be stored in a file on the file system
 * sql  = session data will be stored in a database table - as it is
 */

/* default container is sql */
$cfg['session_container'] = 'sql';

/* Use heap table to accelerate statitics (off by default) */
$cfg['statistics_heap_table'] = false;


/* HTTP parameter check
 * -----------------------------------------------------------------------------
 * This feature checks GET and POST parameters against a whitelist defined in
 * $cfg['http_params_check']['config']. Depending on mode administrated in the
 * same config as the whitelist CONTENIDO will stop processing in case of unknown
 * or invalid GET parameter.
 *
 * For further informations and initial discussion see  http://contenido.org/forum/viewtopic.php?p=113492!
 *
 * Special thx to kummer!
 */
// turns parameter checking on or off
$cfg['http_params_check']['enabled'] = false;

// configuration file (whitelist and mode)
$cfg['http_params_check']['config'] = $cfg['path']['contenido'] . $cfg['path']['includes'] . '/config.http_check.php';

/* max file size for one session file */
$cfg['session_line_length'] = 99999;


/* Global cache control flag
 * -----------------------------------------------------------------------------
 * This flag is for globally activating the caching feature in all frontends.
 * NOTE: You can control the caching behaviour of each client by confiugring it
 * separately in its specific configuration file located in cms/includes/concache.php.
 *
 * So, if you want to enable frontend caching, set $cfg['cache']['disable'] to false and configure
 * the rest in cms/includes/concache.php!
 *
 * @TODO: Need a caching solution with better integration in CONTENIDO core
 */

// (bool) enable/disable caching
$cfg['cache']['disable'] = true;


/* GenericDB settings
 * -----------------------------------------------------------------------------
 */
// (string) The GenericDB driver to use, at the moment only 'mysql' is supported
$cfg['sql']['gdb_driver'] = 'mysql';

// (int) Number of GenericDB items per table to cache
$cfg['sql']['cache']['max_items_to_cache'] = 10;

// (bool) Enable GenericDB item cache
$cfg['sql']['cache']['enable'] = true;

// (bool) Enable mode to select all fields in GenericDB item collections.
$cfg['sql']['select_all_mode'] = true;



/* Help system, currently not used */
$cfg['help'] = false;

/* Configure page if CONTENIDO is unable to run (e.g. no database connection)
 * It is wise to create a maintenance HTML page for redirection, so you won't
 * confuse your customers.
 *
 * Note: The URL should be absolute with http:// in front of it.
 */
$cfg['contenido']['errorpage'] = '';

/* Configure an email address to alert when CONTENIDO is unable to run. */
$cfg['contenido']['notifyonerror'] = '';

/* Configure how often the notification email is sent, in minutes */
$cfg['contenido']['notifyinterval'] = 20;


/* UrlBuilder settings
 * -----------------------------------------------------------------------------
 * Configuration of UrlBuilder to use.
 *
 * Example setting for UrlBuilder 'front_content' (generates URLs like '/cms/front_content.php?idcat=2&lang=1'):
 * $cfg['url_builder']['name']   = 'front_content';
 * $cfg['url_builder']['config'] = array();
 *
 * Example setting for UrlBuilder 'custom_path' (generates URLs like '/cms/Was-ist-Contenido/rocknroll,a,2.4fb'):
 * $cfg['url_builder']['name']   = 'custom_path';
 * $cfg['url_builder']['config'] = array('prefix' => 'rocknroll', 'suffix' => '.4fb', 'separator' => ',');
 *
 * See also http://forum.contenido.org/viewtopic.php?f=64&t=23280
 */
// (string)  Name of UrlBuilder to use.
//           Feasible values are 'front_content', 'custom', 'custom_path' or a user defined name.
//           Check out Contenido_UrlBuilderFactory::getUrlBuilder() in
//           contenido/classes/UrlBuilder/Contenido_UrlBuilderFactory.class.php for more details
//           about this setting.
$cfg['url_builder']['name']   = 'front_content';

// (array)  Default UrlBuilder configuration.
//          An associative configuration array which will be passed to the UrlBuilder instance.
//          Values depend on used UrlBuilder.
$cfg['url_builder']['config'] = array();


/* Password Settings
 * -----------------------------------------------------------------------------
 * For more comments please look in class.conuser.php file
 */
// Enable or disable checking password (true or false)
$cfg['password']['check_password_mask'] = false;

// Minimum length of password (num characters). Default is 8.
$cfg['password']['min_length'] = 6;

// If set to a value greater than 0 so many lower and upper case character must appear in the password.
// (e.g.: if set to 2, 2 upper and 2 lower case characters must appear)
$cfg['password']['mixed_case_mandatory'] = 3;

// If 'symbols_mandatory' set to a value greater than 0, at least so many symbols has to appear in given password.
$cfg['password']['symbols_mandatory'] = 3;

// If set to a value greater than 0, at least $cfg['password']['numbers_mandatory'] numbers must be in password
$cfg['password']['numbers_mandatory'] = 3;


/* Content Type Settings
 * -----------------------------------------------------------------------------
 */
// Define here all content types which includes special module translations (dont forget the prefix 'CMS_'!)
$cfg['translatable_content_types'] = array('CMS_TEASER', 'CMS_FILELIST');


/* Properties settings
 * -----------------------------------------------------------------------------
 * Here you can configure the behavior of properties (user, group, system and 
 * general properties).
 * Enabling caching for a specific properties will preload all related entries 
 * which enhances the performance during application lifecycle.
 */
// (bool) Enable caching of user properties
$cfg['properties']['user_prop']['enable_cache'] = true;

// (bool) Enable caching of group properties
$cfg['properties']['group_prop']['enable_cache'] = true;

// (int) Max groups to cache. Is helpfull if a user is in several groups. It's 
//       recommended to have a lower number, e. g. 3
$cfg['properties']['group_prop']['max_groups'] = 3;

// (bool) Enable caching of system properties
$cfg['properties']['system_prop']['enable_cache'] = true;

// (bool) Enable caching of general properties (for current client)
$cfg['properties']['properties']['enable_cache'] = true;

// (array) Configuration of itemtypes and itemids which should be cached.
//         Itemids are represented with wild-cards and will be replaced as follows:
//         - %client% against current client id
//         - %lang% against current language id
$cfg['properties']['properties']['itemtypes'] = array(
    'clientsetting' => '%client%',
    'idclientslang' => '%lang%',
    'idlang' => '%lang%',
);


/* Validators settings
 * -----------------------------------------------------------------------------
 * Configuration of CONTENIDO validators.
 * Each validator can be configured thru CONTENIDO $cfg configuration variable.
 */

// E-Mail validator settings
// (array) Optional, list of top level domains to disallow
//         Validation of E-Mail addresses having configured top level domains will fail!
$cfg['validator']['email']['disallow_tld'] = array('.test', '.example', '.invalid', '.localhost');

// (array) Optional, list of hosts to disallow
//         Validation of E-Mail addresses having configured hosts will fail!
$cfg['validator']['email']['disallow_host'] = array('example.com', 'example.org', 'example.net');

// (bool) Optional, flag to check DNS records for MX type
$cfg['validator']['email']['mx_check'] = false;


?>