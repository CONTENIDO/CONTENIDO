<?php
/**
 * This file contains the cache configuration of the client.
 *
 * @package          Core
 * @subpackage       Frontend_ConfigFile
 * @author           System
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// Uncomment following line 4 debugging any occurred errors and warnings
#error_reporting(E_ALL);

$auth = cRegistry::getAuth();
$cfgClient = cRegistry::getClientConfig();
$client = cSecurity::toInteger(cRegistry::getClientId());

// Configuration array of frontend caching
global $cfgConCache;

$cfgConCache = [];

// (bool) Don't cache output, if we have a CONTENIDO variable, e.g. on calling
//        frontend preview from backend.
$cfgConCache['excludecontenido'] = true;

// (bool) Enable caching of frontend output
$cfgConCache['enable'] = true;

// (bool) Compose debugging information (hit/miss and execution time of caching)
$cfgConCache['debug'] = false;

// (string) Debug information template
$cfgConCache['infotemplate'] = '<div id="debug">%s</div>';

// (bool) Add a html comment including several debug messages to output
$cfgConCache['htmlcomment'] = true;

// (int) Lifetime in seconds to cache output
$cfgConCache['lifetime'] = 3600;

// (string) Directory where cached content is to store.
$cfgConCache['cachedir'] = $cfgClient[$client]['cache']['path'];

// (string) Cache group, will be a subdirectory inside the cache directory
$cfgConCache['cachegroup'] = 'content';

// (string) Prefix to use for the cache filenames
$cfgConCache['cacheprefix'] = 'cache_';

/**
 * (array) Array of several variables 2 create a unique id, if the output
 *     depends on them. Default variables are $_SERVER['REQUEST_URI'],
 *     $_POST and $_GET. It's also possible to add the auth object, if
 *     output differs on authenticated user.
 */
$cfgConCache['idoptions'] = [
    'uri'  => &$_SERVER['REQUEST_URI'],
    'post' => &$_POST,
    'get'  => &$_GET,
    'auth' => &$auth->auth['perm']
];

/**
 * (array) Array of event-handler, being raised on some events.
 *     We have actually two events:
 *     - 'beforeoutput': code to execute before doing the output
 *     - 'afteroutput'   code to execute after output
 *
 *     You can define any php-code to be executed on raising an event.
 *     Be aware to define a correct php-code block including finishing
 *     semicolon ';'.
 *
 *     Example:
 *     <pre>
 *     $cfgConCache['raiseonevent']['beforeoutput'] = [
 *         'functionCall_One();',
 *         'functionCall_Two();',
 *         'functionCall_Three();'
 *     ];
 *     </pre>
 *     On raising a beforeoutput event, the code 'functionCall_One();',
 *     'functionCall_Two();' and 'functionCall_Three();' will be executed
 *     one after another.
 *
 *     Another example with output:
 *     <pre>
 *     $cfgConCache['raiseonevent'] = [
 *         'beforeoutput' => ['echo("<pre>beforeoutput</pre>");'],
 *         'afteroutput'  => ['echo("<pre>afteroutput</pre>");'],
 *     ];
 *     </pre>
 */
$cfgConCache['raiseonevent'] = [
    'beforeoutput' => ['/* some code here */'],
    'afteroutput'  => [
        // Define code to update CONTENIDO statistics.
        // This will be executed on 'afteroutput' event of cache object.
        '
        // Don\'t track page hit if tracking off
        if (getSystemProperty(\'stats\', \'tracking\') != \'disabled\' && cRegistry::isTrackingAllowed()) {
            // Track page hit for statistics
            global $idcatart;
            $client = cSecurity::toInteger(cRegistry::getClientId());
            $lang = cSecurity::toInteger(cRegistry::getLanguageId());
            $idcatart = cSecurity::toInteger($idcatart);
            $oStatColl = new cApiStatCollection();
            $oStatColl->trackVisit($idcatart, $lang, $client);
        }
        ',
        'cRegistry::shutdown();',
    ],
];
