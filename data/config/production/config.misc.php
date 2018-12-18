<?php
/**
 * This file contains the miscellaneous configuration variables.
 *
 * @package          Core
 * @subpackage       Backend_ConfigFile
 * @version          SVN Revision $Rev:$
 *
 * @author           Holger Librenz
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

/* IMPORTANT! Put your modifications into the file 'config.local.php'
   to prevent that your changes are overwritten during a system update. */


/* Misc settings
 * -----------------------------------------------------------------------------
 * Actually no variables, but important settings.
 */

// (string) Current CONTENIDO Version. You shouldn't change this value unless
//          you know what you are doing.
$cfg['version'] = defined('CON_VERSION') ? CON_VERSION : CON_SETUP_VERSION;

// (int) Backend timeout
$cfg['backend']['timeout'] = 60;

// (int) Frontend timeout
$cfg['frontend']['timeout'] = 15;

// (bool) Enforce HTTPS for cookies
$cfg['secure'] = false;

// (bool) Use Pseudo-Cron?
$cfg['use_pseudocron'] = true;

// (bool) Whether all cExceptions should be logged. If disabled most exceptions will not be logged.
$cfg['debug']['log_exceptions'] = false;

// (bool) Whether all cErrorExceptions should be logged. If disabled exceptions of type error will not be logged.
$cfg['debug']['log_error_exceptions'] = true;

// (bool) If you want to measure function timing set this to true
$cfg['debug']['functiontiming'] = false;

// (bool) If you want to measure backend page rendering times, set this to true
$cfg['debug']['rendering'] = false;

// (bool) To output the code when editing and browsing the frontend, set this to true
$cfg['debug']['codeoutput'] = false;

// (bool) Whether the chain system should be disabled.
$cfg['debug']['disable_chains'] = false;

// (bool) Whether the plugin system should be disabled. If disabled, plugins are neither scanned nor included.
$cfg['debug']['disable_plugins'] = false;

// (bool) Whether deprecations should be logged. If disabled, there are no information on usage of outdated code.
$cfg['debug']['log_deprecations'] = true;

// (bool) Whether stacktraces should be logged. If disabled, the stacktrace is not logged with the corresponding error in log.
$cfg['debug']['log_stacktraces'] = true;

// (bool) If true, use the field 'urlname' for resolving. 'name' otherwise
$cfg['urlpathresolve'] = false;

// (array) The available charsets
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

// (bool) Flag to use native i18n.
//        Note: Enabling this could create unwanted side effects, because of
//        native gettext() behavior.
$cfg['native_i18n'] = false;

// (bool) Help system, currently not used
$cfg['help'] = false;

// (string) Configure page if CONTENIDO is unable to run (e.g. no database connection)
//          It is wise to create a maintenance HTML page for redirection, so you won't
//          confuse your customers.
//          Note: The URL should be absolute with http:// in front of it.
$cfg['contenido']['errorpage'] = '';

// (string) Configure an email address to alert when CONTENIDO is unable to run
$cfg['contenido']['notifyonerror'] = '';

// (int) Configure how often the notification email is sent, in minutes
$cfg['contenido']['notifyinterval'] = 20;

// (int) octal value (with a leading zero!) for use in chmod
$cfg['default_perms']['directory'] = 0775;

// (int) octal value (with a leading zero!) for use in chmod
$cfg['default_perms']['file'] = 0664;

// (bool) Use heap table to accelerate statitics (off by default)
$cfg['statistics_heap_table'] = false;


/*
 * PHP settings
 * -----------------------------------------------------------------------------
 * Configuration of different PHP settings. It is possible to configure each
 * available PHP setting, which can be set by ini_set() method.
 * All defined settings will be applied in contenido/includes/startup.php
 */

// (bool) Display PHP errors
$cfg['php_settings']['display_errors'] = false;

// (bool) Enable logging of PHP errors
$cfg['php_settings']['log_errors'] = true;

// (string) Path to log file
$cfg['php_settings']['error_log'] = $cfg['path']['contenido_logs'] . 'errorlog.txt';

// (string) valid PHP timezone http://php.net/manual/en/timezones.php
$cfg['php_settings']['date.timezone'] = '';

// (string) valid PHP default charset
$cfg['php_settings']['default_charset'] = 'UTF-8';

// (int) PHP error reporting setting
$cfg['php_error_reporting'] = E_ALL & ~(E_STRICT | E_NOTICE);


/* Global cache control flag
 * -----------------------------------------------------------------------------
 * This flag is for globally activating the caching feature in all frontends.
 * NOTE: You can control the caching behaviour of each client by configuring it
 * separately in its specific configuration file located in cms/includes/concache.php.
 *
 * So, if you want to enable frontend caching, set $cfg['cache']['disable'] to false and configure
 * the rest in cms/includes/concache.php!
 */

// (bool) Enable/disable caching
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


/* UriBuilder settings
 * -----------------------------------------------------------------------------
 * Configuration of UriBuilder to use.
 *
 * Example setting for UriBuilder 'front_content' (generates URLs like '/cms/front_content.php?idcat=2&lang=1'):
 * $cfg['url_builder']['name']   = 'front_content';
 * $cfg['url_builder']['config'] = array();
 *
 * Example setting for UriBuilder 'custom_path' (generates URLs like '/cms/Was-ist-Contenido/rocknroll,a,2.4fb'):
 * $cfg['url_builder']['name']   = 'custom_path';
 * $cfg['url_builder']['config'] = array('prefix' => 'rocknroll', 'suffix' => '.4fb', 'separator' => ',');
 *
 * See also http://forum.contenido.org/viewtopic.php?f=64&t=23280
 */

// (string)  Name of UriBuilder to use.
//           Feasible values are 'front_content', 'custom', 'custom_path' or a user defined name.
//           Check out cUriBuilderFactory::getUriBuilder() in
//           contenido/classes/uri/class.uriBuilder.factory.php for more details
//           about this setting.
$cfg['url_builder']['name']   = 'front_content';

// (array)  Default UriBuilder configuration.
//          An associative configuration array which will be passed to the UriBuilder instance.
//          Values depend on used UriBuilder.
$cfg['url_builder']['config'] = array();


/* Password Settings
 * -----------------------------------------------------------------------------
 * For more comments please look in class.user.php file
 */

// (bool) Enable or disable checking password
$cfg['password']['check_password_mask'] = true;

// (int) Minimum length of password (num characters). Default is 6.
$cfg['password']['min_length'] = 6;

// (int) If set to a value greater than 0 so many lower and upper case character
//       must appear in the password.
//       (e.g.: if set to 2, 2 upper and 2 lower case characters must appear)
$cfg['password']['mixed_case_mandatory'] = 0;

// (int) If 'symbols_mandatory' set to a value greater than 0, at least so many
//       symbols has to appear in given password.
$cfg['password']['symbols_mandatory'] = 0;

// (int) If set to a value greater than 0, at least $cfg['password']['numbers_mandatory']
//       numbers must be in password
$cfg['password']['numbers_mandatory'] = 2;


/* Content Type Settings
 * -----------------------------------------------------------------------------
 */

// (array) Define here all content types which includes special module translations
//         (dont forget the prefix 'CMS_'!)
$cfg['translatable_content_types'] = array('CMS_TEASER', 'CMS_FILELIST');

// (array) Content type CMS_LINKEDIT settings
$cfg['content_types']['CMS_LINKEDIT'] = array(
    'document_filetypes' => array('pdf', 'doc', 'ppt', 'xls', 'rtf', 'dot', 'docx', 'xlsx', 'pptx'),
    'image_filetypes' => array('png', 'gif', 'tif', 'jpg', 'jpeg', 'psd', 'pdd', 'iff', 'bmp', 'rle', 'eps', 'fpx', 'pcx', 'jpe', 'pct', 'pic', 'pxr', 'tga'),
    'archive_filetypes' => array('zip', 'arj', 'lha', 'lhx', 'tar', 'tgz', 'rar', 'gz'),
    'media_filetypes' => array('mp3', 'mp2', 'avi', 'mpg', 'mpeg', 'mid', 'wav', 'mov', 'wmv'),
);


/* DBFS (Database file system) Settings
 * -----------------------------------------------------------------------------
 */

// (array) List of mimetypes where the output of the Content-Disposition header
//         should be skipped
$cfg['dbfs']['skip_content_disposition_header_for_mimetypes'] = array('application/x-shockwave-flash');


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


/* Images settings
 * -----------------------------------------------------------------------------
 */

// (bool) Flag to use ImageMagick, if available.
//        If disabled, the image functions will try to use PHP GD library.
$cfg['images']['image_magick']['use'] = true;

// (string) Optional, path to ImageMagick binary directory, with ending slash
//          e. g. C:/Program Files/ImageMagick/
//          IMPORTANT: use slashes - not backslashes!
// NOTE: You should set this on a windows os, otherwise the system could execute
//       the "convert.exe" from system32 folder. This executable does not belongs
//       to ImageMagick.
$cfg['images']['image_magick']['path'] = '';


// (int) configuration of the compression rate used by the cApiImgScale functions
$cfg['images']['image_quality']['compression_rate'] = 75;


/* Code generator settings
 * -----------------------------------------------------------------------------
 */

// (string) Name of code generator to use (e. g. 'Standard' to use class cCodeGeneratorStandard)
$cfg['code_generator']['name'] = 'Standard';


/* Inuse settings
 * -----------------------------------------------------------------------------
 */

// (int) Livetime in seconds
$cfg['inuse']['lifetime'] = 3600;


/* Backend template settings
 * -----------------------------------------------------------------------------
 */

// (array)  List of default link tags for CSS files to render in backend pages
//          The wildcard {basePath} will be replaced dynamically
$cfg['backend_template']['css_files'] = array(
    '{basePath}styles/jquery/jquery-ui.css',
    '{basePath}styles/contenido.css',
    '{basePath}styles/jquery/plugins/atooltip.css'
);

// (array)  List of default script tags for JS files to render in backend pages
//          The wildcard {basePath} will be replaced dynamically
//          The item '_CONFIG_' is a marker to inject the configuration at this place!
$cfg['backend_template']['js_files'] = array(
    '{basePath}scripts/jquery/jquery.js',
    '{basePath}scripts/jquery/jquery-ui.js',
    '{basePath}scripts/contenido.js',
    '{basePath}scripts/general.js',
    '_CONFIG_',
    '{basePath}scripts/startup.js',
    '{basePath}scripts/jquery/plugins/atooltip.jquery.js'
);


/* Client template settings
 * -----------------------------------------------------------------------------
 */

// (CSV) allowed extensions of template files in the client template folder
// only files with these extensions will be shown in "Style | Module templates"
$cfg['client_template']['allowed_extensions'] = 'html,tpl';

// (string) default extensions of template files in the client template folder
// if no extension is defined for new files this default will be assumed
$cfg['client_template']['default_extension'] = 'html';


/* System log display settings
 * -----------------------------------------------------------------------------
 */

// Number of lines
$cfg['system_log']['number_of_lines'] = 100;

// Allowed log file names
$cfg['system_log']['allowed_filenames'] = array('deprecatedlog.txt', 'errorlog.txt', 'exception.txt', 'security.txt', 'setuplog.txt');

// Default memory limit in bytes in case of not determining it via the PHP setting memory_limit
$cfg['system_log']['default_memory_limit'] = 67108864; // 67108864 = 64 MB

/* Search index settings
 * -----------------------------------------------------------------------------
 */

// Excluded content types
$cfg['search_index']['excluded_content_types'] = array(
	'linktarget',
	'link',
	'img',
	'date',
	'teaser',
	'filelist',
	'imgeditor',
	'linkeditor'
);

/* WYSIWYG editor classes
 * -----------------------------------------------------------------------------
 */

/* The name of WYSIWYG editor classes */
$cfg['wysiwyg']['tinymce3_editorclass'] = 'cTinyMCEEditor';
$cfg['wysiwyg']['tinymce4_editorclass'] = 'cTinyMCE4Editor';