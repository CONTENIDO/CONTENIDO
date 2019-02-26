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

// uncomment following line 4 debugging any occured errors and warnings
#error_reporting(E_ALL);


/**
 * configuration array of frontend caching
 * @var array  $cfgConCache
 */
global $cfgConCache;
global $auth;

$cfgConCache = [];

/**
 * don't cache output, if we have a CONTENIDO variable, e. g. on calling frontend preview from backend
 * @var bool  $cfgConCache['excludecontenido']
 */
$cfgConCache['excludecontenido'] = true;

/**
 * activate caching of frontend output
 * @var bool  $cfgConCache['enable']
 */
$cfgConCache['enable'] = true;

/**
 * compose debuginfo (hit/miss and execution time of caching)
 * @var bool  $cfgConCache['debug']
 */
$cfgConCache['debug'] = false;

/**
 * debug information template
 * @var string  $cfgConCache['infotemplate']
 */
$cfgConCache['infotemplate'] = '<div id="debug">%s</div>';

/**
 * add a html comment including several debug messages to output
 * @var bool  $cfgConCache['htmlcomment']
 */
$cfgConCache['htmlcomment'] = true;

/**
 * lifetime in seconds 2 cache output
 * @var int  $cfgConCache['lifetime']
 */
$cfgConCache['lifetime'] = 3600;

/**
 * directory where cached content is 2 store.
 * @var string  $cfgConCache['cachedir']
 */
$cfgConCache['cachedir'] = $cfgClient[$client]['cache']['path'];

/**
 * cache group, will be a subdirectory inside cachedir
 * @var string  $cfgConCache['cachegroup']
 */
$cfgConCache['cachegroup'] = 'content';

/**
 * add prefix 2 stored filenames
 * @var string  $cfgConCache['cacheprefix']
 */
$cfgConCache['cacheprefix'] = 'cache_';

/**
 * array of several variables 2 create a unique id, if the output depends on them.
 * default variables are $_SERVER['REQUEST_URI'], $_POST and $_GET. its also possible to add the
 * auth object, if output differs on authentificated user.
 * @var array  $cfgConCache['idoptions']
 */
$cfgConCache['idoptions'] = array(
    'uri'  => &$_SERVER['REQUEST_URI'],
    'post' => &$_POST,
    'get'  => &$_GET,
    'auth' => &$auth->auth['perm']
);

/**
 * array of eventhandler, beeing raised on some events.
 * we have actually two events:
 * - 'beforeoutput': code to execute before doing the output
 * - 'afteroutput'   code to execute after output
 * you can define any php-code beeing 2 excute on raising a event.
 * be aware to define a correct php-code block including finishing semicolon ';'
 * example:
 * [code]
 *   $cfgConCache['raiseonevent']['beforeoutput'] = array(
 *      'functionCall_One();',
 *      'functionCall_Two();',
 *      'functionCall_Three();'
 * [/code]
 * on raising a beforeoutput event the code 'functionCall_One();',
 * 'functionCall_Two();' and 'functionCall_Three();' will be executes
 * one after another.
 *
 * [code]
 * $cfgConCache['raiseonevent'] = array(
 *     'beforeoutput' => array('echo("<pre>beforeoutput</pre>");'),
 *     'afteroutput'  => array('echo("<pre>afteroutput</pre>");')
 * );
 * [/code]
 * another example with output
 */

// define code 2 update CONTENIDO statistics
// this will be excuted on 'afteroutput' event of cache object

// set Security fix
$sStatCode = '
    global $client, $idcatart, $lang;
    $cApiClient = new cApiClient($client);
    // Dont track page hit if tracking off
    if ($cApiClient->getProperty(\'stats\', \'tracking\') != \'off\') {
        // Statistic, track page hit
        $oStatColl = new cApiStatCollection();
        $oStat = $oStatColl->trackVisit($idcatart, $lang, $client);
    }
';

$cfgConCache['raiseonevent'] = array(
    'beforeoutput' => array('/* some code here */'),
    'afteroutput'  => array($sStatCode, 'cRegistry::shutdown();')
);

?>