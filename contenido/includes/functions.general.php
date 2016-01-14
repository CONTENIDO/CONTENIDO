<?php

/**
 * Defines the general CONTENIDO functions
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.file.php');

/**
 * Extracts the available content-types from the database
 *
 * Creates an array $a_content[type][number] = content string
 * f.e. $a_content['CMS_HTML'][1] = content string
 * Same for array $a_description
 *
 * @param int $idartlang
 *         Language specific ID of the arcticle
 */
function getAvailableContentTypes($idartlang) {
    global $db, $cfg, $a_content, $a_description;

    $sql = "SELECT
                *
            FROM
                " . $cfg["tab"]["content"] . " AS a,
                " . $cfg["tab"]["art_lang"] . " AS b,
                " . $cfg["tab"]["type"] . " AS c
            WHERE
                a.idtype    = c.idtype AND
                a.idartlang = b.idartlang AND
                b.idartlang = " . (int) $idartlang;

    $db->query($sql);

    while ($db->nextRecord()) {
        $a_content[$db->f('type')][$db->f('typeid')] = $db->f('value');
        $a_description[$db->f('type')][$db->f('typeid')] = i18n($db->f('description'));
    }
}

/**
 * Checks if an article is assigned to multiple categories
 *
 * @param int $idart
 *         Article-Id
 * @return bool
 *         Article assigned to multiple categories
 */
function isArtInMultipleUse($idart) {
    global $cfg;

    $db = cRegistry::getDb();
    $sql = "SELECT idart FROM " . $cfg["tab"]["cat_art"] . " WHERE idart = " . (int) $idart;
    $db->query($sql);

    return ($db->affectedRows() > 1);
}

/**
 * Checks if a value is alphanumeric
 *
 * @deprecated [2015-05-21]
 *         use cString::isAlphanumeric
 * @param mixed $test
 *         Value to test
 * @param bool $umlauts [optional]
 *         Use german umlauts
 * @return bool
 *         Value is alphanumeric
 */
function isAlphanumeric($test, $umlauts = true) {
    return cString::isAlphanumeric($test, $umlauts);
}

/**
 * Returns whether a string is UTF-8 encoded or not
 *
 * @deprecated [2015-05-21]
 *         use cString::isUtf8
 * @param string $input
 * @return bool
 */
function isUtf8($input) {
    return cString::isUtf8($input);
}

/**
 * Returns multi-language month name (canonical) by its numeric value
 *
 * @param int $month
 * @return string
 */
function getCanonicalMonth($month) {
    switch ($month) {
        case 1:
            return (i18n("January"));
            break;
        case 2:
            return (i18n("February"));
            break;
        case 3:
            return (i18n("March"));
            break;
        case 4:
            return (i18n("April"));
            break;
        case 5:
            return (i18n("May"));
            break;
        case 6:
            return (i18n("June"));
            break;
        case 7:
            return (i18n("July"));
            break;
        case 8:
            return (i18n("August"));
            break;
        case 9:
            return (i18n("September"));
            break;
        case 10:
            return (i18n("October"));
            break;
        case 11:
            return (i18n("November"));
            break;
        case 12:
            return (i18n("December"));
            break;
    }
}

/**
 * Get multi-language day
 *
 * @param int $iDay
 *         The day number of date(w)
 * @return string
 *         Dayname of current language
 */
function getCanonicalDay($iDay) {
    switch ($iDay) {
        case 1:
            return (i18n("Monday"));
            break;
        case 2:
            return (i18n("Tuesday"));
            break;
        case 3:
            return (i18n("Wednesday"));
            break;
        case 4:
            return (i18n("Thursday"));
            break;
        case 5:
            return (i18n("Friday"));
            break;
        case 6:
            return (i18n("Saturday"));
            break;
        case 0:
            return (i18n("Sunday"));
            break;
        default:
            break;
    }
}

/**
 * Returns a formatted date and/or timestring according to the current settings
 *
 * @param mixed $timestamp
 *         a timestamp. If no value is given the current time will be used.
 * @param bool $date
 *         if true the date will be included in the string
 * @param bool $time
 *         if true the time will be included in the string
 * @return string
 *         the formatted time string.
 */
function displayDatetime($timestamp = "", $date = false, $time = false) {
    if ($timestamp == "") {
        $timestamp = time();
    } else {
        $timestamp = strtotime($timestamp);
    }

    $ret = "";

    if ($date && !$time) {
        $ret = date(getEffectiveSetting("dateformat", "date", "Y-m-d"), $timestamp);
    } else if ($time && !$date) {
        $ret = date(getEffectiveSetting("dateformat", "time", "H:i:s"), $timestamp);
    } else {
        $ret = date(getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s"), $timestamp);
    }
    return $ret;
}

/**
 * Returns the id of passed area
 *
 * @param int|string $area
 *         Area name or id
 * @return int|string
 */
function getIDForArea($area) {
    if (!is_numeric($area)) {
        $oArea = new cApiArea();
        if ($oArea->loadBy('name', $area)) {
            $area = $oArea->get('idarea');
        }
    }

    return $area;
}

/**
 * Returns the parent id of passed area
 *
 * @param mixed $area
 * @return int
 */
function getParentAreaId($area) {
    $oAreaColl = new cApiAreaCollection();
    return $oAreaColl->getParentAreaID($area);
}

/**
 * Write JavaScript to mark submenu item.
 *
 * @param int $menuitem
 *         Which menuitem to mark
 * @param bool $return
 *         Return or echo script
 * @return string
 */
function markSubMenuItem($menuitem, $return = false) {
    global $changeview;

    if (!isset($changeview) || 'prev' !== $changeview) {
        // CONTENIDO backend but not in preview mode
        $str = <<<JS
<script type="text/javascript">
var id = 'c_{$menuitem}';
if ('undefined' !== typeof(Con)) {
    Con.markSubmenuItem(id);
} else {
    // Contenido backend but with frozen article
    // Check if submenuItem is existing and mark it
    if (parent.parent.frames.right.frames.right_top.document.getElementById(id)) {
        menuItem = parent.parent.frames.right.frames.right_top.document.getElementById(id).getElementsByTagName('a')[0];
        // load the new tab now
        parent.parent.frames.right.frames.right_top.Con.Subnav.clicked(menuItem, true);
    }
}
</script>
JS;
    } else {
        // CONTENIDO backend and article preview mode. We don't have the JavaScript object Con here!
        $str = <<<JS
<script type="text/javascript">
(function(id) {
    var menuItem;
    try {
        // Check if we are in a dual-frame or a quad-frame
        if (parent.parent.frames[0].name == 'header') {
            if (parent.frames.right_top.document.getElementById(id)) {
                menuItem = parent.frames.right_top.document.getElementById(id).getElementsByTagName('a')[0];
                parent.frames.right_top.Con.Subnav.clicked(menuItem);
            }
        } else {
            // Check if submenuItem is existing and mark it
            if (parent.parent.frames.right.frames.right_top.document.getElementById(id)) {
                menuItem = parent.parent.frames.right.frames.right_top.document.getElementById(id).getElementsByTagName('a')[0];
                parent.parent.frames.right.frames.right_top.Con.Subnav.clicked(menuItem);
            }
        }
    } catch (e) {}
})('c_{$menuitem}');
</script>
JS;
    }

    if ($return) {
        return $str;
    } else {
        echo $str;
    }
}

/**
 * Creates a inline script wrapped with a self executing function
 *
 * @param string $content
 *         to wrap
 * @return string
 */
function conMakeInlineScript($content) {
    $script = <<<JS
<script type="text/javascript">
(function(Con, $) {
{$content}
})(Con, Con.$);
</script>
JS;
    return $script;
}

/**
 * Redirect to main area
 *
 * @param bool $send
 *         Redirect Yes/No
 */
function backToMainArea($send) {
    if ($send) {
        // Global vars
        global $area, $sess, $idart, $idcat, $idartlang, $idcatart, $frame;

        // Get main area
        $oAreaColl = new cApiAreaCollection();
        $parent = $oAreaColl->getParentAreaID($area);

        // Create url string
        $url_str = 'main.php?' . 'area=' . $parent . '&' . 'idcat=' . $idcat . '&' . 'idart=' . $idart . '&' . 'idartlang=' . $idartlang . '&' . 'idcatart=' . $idcatart . '&' . 'force=1&' . 'frame=' . $frame;
        $url = $sess->url($url_str);

        // Redirect
        header("location: $url");
    }
}

/**
 * Returns list of languages (language ids) by passed client.
 *
 * @param int $client
 * @return array
 */
function getLanguagesByClient($client) {
    $oClientLangColl = new cApiClientLanguageCollection();
    return $oClientLangColl->getLanguagesByClient($client);
}

/**
 * Returns all languages (language ids and names) of an client
 *
 * @param int $client
 * @return array
 *         List of languages where the key is the language id
 *         and value the language name
 */
function getLanguageNamesByClient($client) {
    $oClientLangColl = new cApiClientLanguageCollection();
    return $oClientLangColl->getLanguageNamesByClient($client);
}

/**
 * Adds slashes to passed string if PHP setting for magic quotes is disabled
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 * @param string $code
 *         String by reference
 */
function set_magic_quotes_gpc(&$code) {
    cDeprecated('This method is deprecated and is not needed any longer');

    global $cfg;
    if (!$cfg['simulate_magic_quotes']) {
        if (get_magic_quotes_gpc() == 0) {
            $code = addslashes($code);
        }
    }
}

/**
 * Returns a list with all clients and languages.
 *
 * @return array
 *         Indexed array where the value is an assoziative array as follows:
 *         <pre>
 *         - $arr[0]['idlang']
 *         - $arr[0]['langname']
 *         - $arr[0]['idclient']
 *         - $arr[0]['clientname']
 *         </pre>
 */
function getAllClientsAndLanguages() {
    global $db, $cfg;

    $sql = "SELECT
                a.idlang as idlang,
                a.name as langname,
                b.name as clientname,
                b.idclient as idclient
             FROM
                " . $cfg["tab"]["lang"] . " as a,
                " . $cfg["tab"]["clients_lang"] . " as c,
                " . $cfg["tab"]["clients"] . " as b
             WHERE
                a.idlang = c.idlang AND
                c.idclient = b.idclient";
    $db->query($sql);

    $aRs = array();
    while ($db->nextRecord()) {
        $aRs[] = array(
            'idlang' => $db->f('idlang'),
            'langname' => $db->f('langname'),
            'idclient' => $db->f('idclient'),
            'clientname' => $db->f('clientname')
        );
    }
    return $aRs;
}

/**
 *
 * @return number
 */
function getmicrotime() {
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}

/**
 *
 * @param unknown_type $uid
 * @return boolean
 */
function isGroup($uid) {
    $user = new cApiUser();
    if ($user->loadByPrimaryKey($uid) === false) {
        return true;
    } else {
        return false;
    }
}

/**
 *
 * @param int $uid
 * @return string|bool
 */
function getGroupOrUserName($uid) {
    $user = new cApiUser();
    if ($user->loadByPrimaryKey($uid) === false) {
        $group = new cApiGroup();
        // Yes, it's a group. Let's try to load the group members!
        if ($group->loadByPrimaryKey($uid) === false) {
            return false;
        } else {
            return $group->getGroupName(true);
        }
    } else {
        return $user->getField('realname');
    }
}

/**
 * Checks if passed email address is valid or not
 *
 * @param string $email
 * @param bool $strict
 *         No more used!
 * @return boolean
 */
function isValidMail($email, $strict = false) {
    $validator = cValidatorFactory::getInstance('email');
    return $validator->isValid($email);
}

/**
 *
 * @param unknown_type $string
 * @return string
 */
function htmldecode($string) {
    $trans_tbl = conGetHtmlTranslationTable(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    $ret = strtr($string, $trans_tbl);
    return $ret;
}

/**
 * Loads the client information from the database and stores it in
 * config.client.php.
 * Reinitializes the $cfgClient array and fills it wih updated information if
 * provided.
 *
 * @param int $idclient
 *         client id which will be updated
 * @param string $htmlpath
 *         new HTML path. Starting with "http://"
 * @param string $frontendpath
 *         path the to the frontend
 * @return array
 *         client configuration
 */
function updateClientCache($idclient = 0, $htmlpath = '', $frontendpath = '') {

    global $cfg, $cfgClient, $errsite_idcat, $errsite_idart;

    if (!is_array($cfgClient)) {
        $cfgClient = array();
    }

    if ($idclient != 0 && $htmlpath != '' && $frontendpath != '') {
        $cfgClient[$idclient]['path']['frontend'] = cSecurity::escapeString($frontendpath);
        $cfgClient[$idclient]['path']['htmlpath'] = cSecurity::escapeString($htmlpath);
    }

    // remember paths as these will be lost otherwise
    $htmlpaths = array();
    $frontendpaths = array();
    foreach ($cfgClient as $id => $aclient) {
        if (is_array($aclient)) {
            $htmlpaths[$id] = $aclient["path"]["htmlpath"];
            $frontendpaths[$id] = $aclient["path"]["frontend"];
        }
    }
    unset($cfgClient);
    $cfgClient = array();

    // don't do that as the set of clients may have changed!
    // paths will be set in subsequent foreach instead.
    // foreach ($htmlpaths as $id => $path) {
    //     $cfgClient[$id]["path"]["htmlpath"] = $htmlpaths[$id];
    //     $cfgClient[$id]["path"]["frontend"] = $frontendpaths[$id];
    // }

    // get clients from database
    $db = cRegistry::getDb();
    $db->query('
        SELECT idclient
            , name
            , errsite_cat
            , errsite_art
        FROM ' . $cfg['tab']['clients']);

    while ($db->nextRecord()) {
        $iClient = $db->f('idclient');
        $cfgClient['set'] = 'set';

        // set original paths
        if (isset($htmlpaths[$iClient])) {
            $cfgClient[$iClient]["path"]["htmlpath"] = $htmlpaths[$iClient];
        }
        if (isset($frontendpaths[$iClient])) {
            $cfgClient[$iClient]["path"]["frontend"] = $frontendpaths[$iClient];
        }

        $cfgClient[$iClient]['name'] = conHtmlSpecialChars(str_replace(array(
            '*/',
            '/*',
            '//'
        ), '', $db->f('name')));

        $errsite_idcat[$iClient] = $db->f('errsite_cat');
        $errsite_idart[$iClient] = $db->f('errsite_art');
        $cfgClient[$iClient]["errsite"]["idcat"] = $errsite_idcat[$iClient];
        $cfgClient[$iClient]["errsite"]["idart"] = $errsite_idart[$iClient];

        $cfgClient[$iClient]['images'] = $cfgClient[$iClient]['path']['htmlpath'] . 'images/';
        $cfgClient[$iClient]['upload'] = 'upload/';

        $cfgClient[$iClient]['htmlpath']['frontend'] = $cfgClient[$iClient]['path']['htmlpath'];

        $cfgClient[$iClient]['upl']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'upload/';
        $cfgClient[$iClient]['upl']['htmlpath'] = $cfgClient[$iClient]['htmlpath']['frontend'] . 'upload/';
        $cfgClient[$iClient]['upl']['frontendpath'] = 'upload/';

        $cfgClient[$iClient]['css']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'css/';

        $cfgClient[$iClient]['js']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'js/';

        $cfgClient[$iClient]['tpl']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'templates/';

        $cfgClient[$iClient]['cache']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'cache/';
        $cfgClient[$iClient]['cache']['frontendpath'] = 'cache/';

        $cfgClient[$iClient]['code']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'cache/code/';
        $cfgClient[$iClient]['code']['frontendpath'] = 'cache/code/';

        $cfgClient[$iClient]['xml']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'xml/';
        $cfgClient[$iClient]['xml']['frontendpath'] = 'xml/';

        $cfgClient[$iClient]['template']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'templates/';
        $cfgClient[$iClient]['template']['frontendpath'] = 'templates/';

        $cfgClient[$iClient]['data']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'data/';

        $cfgClient[$iClient]['module']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'data/modules/';
        $cfgClient[$iClient]['module']['frontendpath'] = 'data/modules/';

        $cfgClient[$iClient]['config']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'data/config/' . CON_ENVIRONMENT . '/';
        $cfgClient[$iClient]['config']['frontendpath'] = 'data/config/';

        $cfgClient[$iClient]['layout']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'data/layouts/';
        $cfgClient[$iClient]['layout']['frontendpath'] = 'data/layouts/';

        $cfgClient[$iClient]['log']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'data/logs/';
        $cfgClient[$iClient]['log']['frontendpath'] = 'data/logs/';

        $cfgClient[$iClient]['version']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'data/version/';
        $cfgClient[$iClient]['version']['frontendpath'] = 'data/version/';
    }

    $aConfigFileContent = array();
    $aConfigFileContent[] = '<?php';
    $aConfigFileContent[] = 'global $cfgClient;';
    $aConfigFileContent[] = '';

    foreach ($cfgClient as $iIdClient => $aClient) {
        if ((int) $iIdClient > 0 && is_array($aClient)) {

            $aConfigFileContent[] = '/* ' . $aClient['name'] . ' */';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["name"] = "' . $aClient['name'] . '";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["errsite"]["idcat"] = "' . $aClient["errsite"]["idcat"] . '";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["errsite"]["idart"] = "' . $aClient["errsite"]["idart"] . '";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["images"] = "' . $aClient["path"]["htmlpath"] . 'images/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["upload"] = "upload/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["path"]["frontend"] = "' . $aClient["path"]["frontend"] . '";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["htmlpath"]["frontend"] = "' . $aClient["path"]["htmlpath"] . '";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["upl"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "upload/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["upl"]["htmlpath"] = "' . $aClient["htmlpath"]["frontend"] . 'upload/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["upl"]["frontendpath"] = "upload/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["css"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "css/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["js"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "js/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["tpl"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "templates/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["cache"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "cache/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["cache"]["frontendpath"] = "cache/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["code"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "cache/code/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["code"]["frontendpath"] = "cache/code/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["xml"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "xml/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["xml"]["frontendpath"] = "xml/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["template"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "templates/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["template"]["frontendpath"] = "templates/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["data"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "data/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["module"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "data/modules/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["module"]["frontendpath"] = "data/modules/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["config"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "data/config/' . CON_ENVIRONMENT . '/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["config"]["frontendpath"] = "data/config/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["layout"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "data/layouts/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["layout"]["frontendpath"] = "data/layouts/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["log"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "data/logs/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["log"]["frontendpath"] = "data/logs/";';

            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["version"]["path"] = $cfgClient[' . $iIdClient . ']["path"]["frontend"] . "data/version/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["version"]["frontendpath"] = "data/version/";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["path"]["htmlpath"] = "' . $aClient['path']['htmlpath'] . '";';
            $aConfigFileContent[] = '';
        }
    }
    $aConfigFileContent[] = '$cfgClient["set"] = "set";';
    $aConfigFileContent[] = '?>';

    cFileHandler::write($cfg['path']['contenido_config'] . 'config.clients.php', implode(PHP_EOL, $aConfigFileContent));

    return $cfgClient;
}

/**
 * Sets a system property entry
 *
 * @modified Timo Trautmann 22.02.2008 Support for editing name and type
 *
 * @param string $type
 *         The type of the item
 * @param string $name
 *         The name of the item
 * @param string $value
 *         The value of the item
 * @param int $idsystemprop
 *         The sysprop id, use optional.
 *         If set it allows to modify type name and value
 * @return void|boolean
 */
function setSystemProperty($type, $name, $value, $idsystemprop = 0) {
    if ($type == '' || $name == '') {
        return false;
    }

    $idsystemprop = (int) $idsystemprop;

    $systemPropColl = new cApiSystemPropertyCollection();

    if ($idsystemprop == 0) {
        $prop = $systemPropColl->setValueByTypeName($type, $name, $value);
    } else {
        $prop = $systemPropColl->setTypeNameValueById($type, $name, $value, $idsystemprop);
    }
}

/**
 * Remove a system property entry
 *
 * @param string $type
 *         The type of the item
 * @param string $name
 *         The name of the item
 * @return bool
 */
function deleteSystemProperty($type, $name) {
    $systemPropColl = new cApiSystemPropertyCollection();
    $systemPropColl->deleteByTypeName($type, $name);
}

/**
 * Retrieves all available system properties.
 * Array format:
 *
 * $array[$type][$name] = $value;
 *
 * @modified Timo Trautmann 22.02.2008 Support for editing name and type editing
 * by primaray key idsystemprop
 * if bGetPropId is set:
 * $array[$type][$name][value] = $value;
 * $array[$type][$name][idsystemprop] = $idsystemprop;
 *
 * @param bool $bGetPropId
 *         If true special mode is activated which generates for each property
 *         a third array, which also contains idsystemprop value
 * @return array
 */
function getSystemProperties($bGetPropId = false) {
    $return = array();

    $systemPropColl = new cApiSystemPropertyCollection();
    $props = $systemPropColl->fetchAll('type ASC, name ASC, value ASC');
    foreach ($props as $prop) {
        $item = $prop->toArray();

        if ($bGetPropId) {
            $return[$item['type']][$item['name']]['value'] = $item['value'];
            $return[$item['type']][$item['name']]['idsystemprop'] = $item['idsystemprop'];
        } else {
            $return[$item['type']][$item['name']] = $item['value'];
        }
    }

    return $return;
}

/**
 * Gets a system property entry
 *
 * @param string $type
 *         The type of the item
 * @param string $name
 *         The name of the item
 * @return string|bool
 *         property value or false if nothing was found
 */
function getSystemProperty($type, $name) {
    $systemPropColl = new cApiSystemPropertyCollection();
    $prop = $systemPropColl->fetchByTypeName($type, $name);
    return ($prop) ? $prop->get('value') : false;
}

/**
 * Gets system property entries
 *
 * @param string $type
 *         The type of the properties
 * @return array
 *         Assoziative array like
 *         - $arr[name] = value
 */
function getSystemPropertiesByType($type) {
    $return = array();

    $systemPropColl = new cApiSystemPropertyCollection();
    $props = $systemPropColl->fetchByType($type);
    foreach ($props as $prop) {
        $return[$prop->get('name')] = $prop->get('value');
    }
    if (count($return) > 1) {
        ksort($return);
    }
    return $return;
}

/**
 * Returns effective setting for a property.
 *
 * The order is: System => Client => Client (language) => Group => User
 *
 * System properties can be overridden by the group, and group properties
 * can be overridden by the user.
 *
 * NOTE: If you provide a default value (other than empty string), then it will be returned back
 *       in case of not existing or empty setting.
 *
 * @param string $type
 *         The type of the item
 * @param string $name
 *         The name of the item
 * @param string $default
 *         Optional default value
 * @return bool|string
 *         Setting value or false
 */
function getEffectiveSetting($type, $name, $default = '') {
    return cEffectiveSetting::get($type, $name, $default);
}

/**
 * Returns the current effective settings for a type of properties.
 *
 * The order is: System => Client => Group => User
 *
 * System properties can be overridden by the group, and group
 * properties can be overridden by the user.
 *
 * @param string $type
 *         The type of the item
 * @return array Value
 */
function getEffectiveSettingsByType($type) {
    return cEffectiveSetting::getByType($type);
}

/**
 * Retrieve list of article specifications for current client and language
 *
 * @return array
 *         list of article specifications
 */
function getArtspec() {
    global $db, $cfg, $lang, $client;
    $sql = "SELECT artspec, idartspec, online, artspecdefault FROM " . $cfg['tab']['art_spec'] . "
            WHERE client = " . (int) $client . " AND lang = " . (int) $lang . " ORDER BY artspec ASC";
    $db->query($sql);

    $artspec = array();

    while ($db->nextRecord()) {
        $artspec[$db->f("idartspec")]['artspec'] = $db->f("artspec");
        $artspec[$db->f("idartspec")]['online'] = $db->f("online");
        $artspec[$db->f("idartspec")]['default'] = $db->f("artspecdefault");
    }
    return $artspec;
}

/**
 * Add new article specification
 *
 * @param string $artspectext
 *         specification text
 * @param int $online
 *         Online status (1 or 0)
 */
function addArtspec($artspectext, $online) {
    global $db, $cfg, $lang, $client;

    if (isset($_POST['idartspec'])) { // update
        $fields = array(
            'artspec' => $artspectext,
            'online' => (int) $online
        );
        $where = array(
            'idartspec' => (int) $_POST['idartspec']
        );
        $sql = $db->buildUpdate($cfg['tab']['art_spec'], $fields, $where);
    } else {
        $fields = array(
            'client' => (int) $client,
            'lang' => (int) $lang,
            'artspec' => $artspectext,
            'online' => 0,
            'artspecdefault' => 0
        );
        $sql = $db->buildInsert($cfg['tab']['art_spec'], $fields);
    }
    $db->query($sql);
}

/**
 * Delete specified article specification
 *
 * @param int $idartspec
 *         article specification id
 */
function deleteArtspec($idartspec) {
    global $db, $cfg;
    $sql = "DELETE FROM " . $cfg['tab']['art_spec'] . " WHERE idartspec = " . (int) $idartspec;
    $db->query($sql);

    $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET artspec = 0 WHERE artspec = " . (int) $idartspec;
    $db->query($sql);
}

/**
 * Set article specifications online
 *
 * Flag to switch if an article specification should be shown the frontend or
 * not
 *
 * @param int $idartspec
 *         article specification id
 * @param int $online
 *         0/1 switch the status between on an offline
 */
function setArtspecOnline($idartspec, $online) {
    global $db, $cfg;
    $sql = "UPDATE " . $cfg['tab']['art_spec'] . " SET online = " . (int) $online . " WHERE idartspec = " . (int) $idartspec;
    $db->query($sql);
}

/**
 * Set a default article specification
 *
 * While creating a new article this defined article specification will be
 * default setting
 *
 * @param int $idartspec
 *         Article specification id
 */
function setArtspecDefault($idartspec) {
    global $db, $cfg, $lang, $client;
    $sql = "UPDATE " . $cfg['tab']['art_spec'] . " SET artspecdefault=0 WHERE client = " . (int) $client . " AND lang = " . (int) $lang;
    $db->query($sql);

    $sql = "UPDATE " . $cfg['tab']['art_spec'] . " SET artspecdefault = 1 WHERE idartspec = " . (int) $idartspec;
    $db->query($sql);
}

/**
 * Build a Article select Box
 *
 * @param string $sName
 *         Name of the SelectBox
 * @param string $iIdCat
 *         Category id
 * @param string $sValue
 *         Value of the SelectBox
 * @return string
 *         HTML
 */
function buildArticleSelect($sName, $iIdCat, $sValue) {
    global $cfg, $lang;

    $db = cRegistry::getDb();

    $selectElem = new cHTMLSelectElement($sName, "", $sName);
    $selectElem->appendOptionElement(new cHTMLOptionElement(i18n("Please choose"), ""));

    $sql = "SELECT b.title, b.idart FROM
               " . $cfg["tab"]["art"] . " AS a, " . $cfg["tab"]["art_lang"] . " AS b, " . $cfg["tab"]["cat_art"] . " AS c
               WHERE c.idcat = " . (int) $iIdCat . "
               AND b.idlang = " . (int) $lang . " AND b.idart = a.idart and b.idart = c.idart
               ORDER BY b.title";

    $db->query($sql);

    while ($db->nextRecord()) {
        if ($sValue != $db->f('idart')) {
            $selectElem->appendOptionElement(new cHTMLOptionElement($db->f('title'), $db->f('idart')));
        } else {
            $selectElem->appendOptionElement(new cHTMLOptionElement($db->f('title'), $db->f('idart'), true));
        }
    }

    return $selectElem->toHTML();
}

/**
 * Build a Category / Article select Box
 *
 * @param string $sName
 *         Name of the SelectBox
 * @param string $sValue
 *         Value of the SelectBox
 * @param int $sLevel
 *         Value of highest level that should be shown
 * @param string $sClass
 *         Optional css class for select
 * @return string
 *         HTML
 */
function buildCategorySelect($sName, $sValue, $sLevel = 0, $sClass = '') {
    global $cfg, $client, $lang;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $selectElem = new cHTMLSelectElement($sName, "", $sName);
    $selectElem->setClass($sClass);
    $selectElem->appendOptionElement(new cHTMLOptionElement(i18n("Please choose"), ""));

    if ($sLevel > 0) {
        $addString = "AND c.level < " . (int) $sLevel;
    }

    $sql = "SELECT a.idcat AS idcat, b.name AS name, c.level FROM
           " . $cfg["tab"]["cat"] . " AS a, " . $cfg["tab"]["cat_lang"] . " AS b,
           " . $cfg["tab"]["cat_tree"] . " AS c WHERE a.idclient = " . (int) $client . "
           AND b.idlang = " . (int) $lang . " AND b.idcat = a.idcat AND c.idcat = a.idcat " . $addString . "
           ORDER BY c.idtree";

    $db->query($sql);

    $categories = array();

    while ($db->nextRecord()) {
        $categories[$db->f("idcat")]["name"] = $db->f("name");

        $sql2 = "SELECT level FROM " . $cfg["tab"]["cat_tree"] . " WHERE idcat = " . (int) $db->f("idcat");
        $db2->query($sql2);

        if ($db2->nextRecord()) {
            $categories[$db->f("idcat")]["level"] = $db2->f("level");
        }

        $sql2 = "SELECT a.title AS title, b.idcatart AS idcatart FROM
                " . $cfg["tab"]["art_lang"] . " AS a,  " . $cfg["tab"]["cat_art"] . " AS b
                WHERE b.idcat = '" . $db->f("idcat") . "' AND a.idart = b.idart AND
                a.idlang = " . (int) $lang;

        $db2->query($sql2);

        while ($db2->nextRecord()) {
            $categories[$db->f("idcat")]["articles"][$db2->f("idcatart")] = $db2->f("title");
        }
    }

    foreach ($categories as $tmpidcat => $props) {
        $spaces = "&nbsp;&nbsp;";

        for ($i = 0; $i < $props["level"]; $i++) {
            $spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        $tmp_val = $tmpidcat;

        if ($sValue != $tmp_val) {
            $selectElem->appendOptionElement(new cHTMLOptionElement($spaces . ">" . $props["name"], $tmp_val));
        } else {
            $selectElem->appendOptionElement(new cHTMLOptionElement($spaces . ">" . $props["name"], $tmp_val, true));
        }
    }

    return $selectElem->toHTML();
}

/**
 * Converts a size in bytes in a human readable form
 *
 * @param int $number
 *         Some number of bytes
 * @return string
 */
function humanReadableSize($number) {
    $base = 1024;
    $suffixes = array(
        'Bytes',
        'KiB',
        'MiB',
        'GiB',
        'TiB',
        'PiB',
        'EiB'
    );

    $usesuf = 0;
    $n = (float) $number; // Appears to be necessary to avoid rounding
    while ($n >= $base) {
        $n /= (float) $base;
        $usesuf++;
    }

    $places = 2 - floor(log10($n));
    $places = max($places, 0);
    $retval = number_format($n, $places, '.', '') . ' ' . $suffixes[$usesuf];
    return $retval;
}

/**
 * Converts a byte size like "8M" to the absolute number of bytes
 *
 * @param string $sizeString
 *         contains the size acquired from ini_get for example
 * @return number
 */
function machineReadableSize($sizeString) {

    // If sizeString is a integer value (i. e. 64242880), return it
    if (cSecurity::isInteger($sizeString)) {
        return $sizeString;
    }

    $val = trim($sizeString);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (float) substr($val, 0, strlen($val) - 1);
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * Checks if the script is being runned from the web
 *
 * @return bool
 *         True if the script is running from the web
 */
function isRunningFromWeb() {
    if ($_SERVER['PHP_SELF'] == '' || php_sapi_name() == 'cgi' || php_sapi_name() == 'cli') {
        return false;
    }

    return true;
}

/**
 * Scans a given plugin directory and places the found plugins into the array
 * $cfg['plugins'].
 *
 * Result:
 * $cfg['plugins']['frontendusers'] => array with all found plugins
 *
 * Note: Plugins are only "found" if the following directory structure if found:
 *
 * entity/
 * plugin1/plugin1.php
 * plugin2/plugin2.php
 *
 * The plugin's directory and file name have to be the same, otherwise the
 * function
 * won't find them!
 *
 * @param string $entity
 *         Name of the directory to scan
 */
function scanPlugins($entity) {
    global $cfg;

    $basedir = cRegistry::getBackendPath() . $cfg['path']['plugins'] . $entity . '/';
    if (is_dir($basedir) === false) {
        return;
    }

    $pluginorder = getSystemProperty('plugin', $entity . '-pluginorder');
    $lastscantime = (int) getSystemProperty('plugin', $entity . '-lastscantime');

    $plugins = array();

    // Fetch and trim the plugin order
    if ($pluginorder != '') {
        $plugins = explode(',', $pluginorder);
        foreach ($plugins as $key => $plugin) {
            $plugins[$key] = trim($plugin);
        }
    }

    // Don't scan all the time, but each 5 minutes
    if ($lastscantime + 300 < time()) {
        setSystemProperty('plugin', $entity . '-lastscantime', time());
        if (is_dir($basedir)) {
            if (false !== ($handle = cDirHandler::read($basedir))) {
                foreach ($handle as $file) {
                    if (is_dir($basedir . $file) && $file != 'includes' && cFileHandler::fileNameIsDot($file) === false) {
                        if (!in_array($file, $plugins)) {
                            if (cFileHandler::exists($basedir . $file . '/' . $file . '.php')) {
                                $plugins[] = $file;
                            }
                        }
                    }
                }
            }
        }

        foreach ($plugins as $key => $value) {
            if (!is_dir($basedir . $value) || !cFileHandler::exists($basedir . $value . '/' . $value . '.php')) {
                unset($plugins[$key]);
            }
        }

        sort($plugins);

        $oldPlugins = explode(',', getSystemProperty('plugin', 'frontendusers-pluginorder'));
        sort($oldPlugins);

        $diff = array_diff($oldPlugins, $plugins);

        if (!empty($diff)) {
        	$pluginorder = implode(',', $plugins);
        	setSystemProperty('plugin', $entity . '-pluginorder', $pluginorder);
        }
    }

    foreach ($plugins as $key => $value) {
        if (!is_dir($basedir . $value) || !cFileHandler::exists($basedir . $value . '/' . $value . '.php')) {
            unset($plugins[$key]);
        } else {
            i18nRegisterDomain($entity . '_' . $value, $basedir . $value . '/locale/');
        }
    }

    $cfg['plugins'][$entity] = $plugins;
}

/**
 * Includes plugins for a given entity.
 *
 * @param unknown_type $entity
 *         string Name of the directory to scan
 */
function includePlugins($entity) {
    global $cfg;

    if (is_array($cfg['plugins'][$entity])) {
        foreach ($cfg['plugins'][$entity] as $plugin) {
            plugin_include($entity, $plugin . '/' . $plugin . '.php');
        }
    }
}

/**
 * Calls the plugin's store methods.
 *
 * @param string $entity
 *         Name of the directory to scan
 */
function callPluginStore($entity) {
    global $cfg;

    // Check out if there are any plugins
    if (is_array($cfg['plugins'][$entity])) {
        foreach ($cfg['plugins'][$entity] as $plugin) {
            if (function_exists($entity . '_' . $plugin . '_wantedVariables') && function_exists($entity . '_' . $plugin . '_store')) {
                $wantVariables = call_user_func($entity . '_' . $plugin . '_wantedVariables');

                if (is_array($wantVariables)) {
                    $varArray = array();
                    foreach ($wantVariables as $value) {
                        $varArray[$value] = stripslashes($GLOBALS[$value]);
                    }
                }
                $store = call_user_func($entity . '_' . $plugin . '_store', $varArray);
            }
        }
    }
}

/**
 * Creates a random name (example: Passwords).
 *
 * @param int $nameLength
 *         Length of the generated string
 * @return string
 *         Random name
 */
function createRandomName($nameLength) {
    $NameChars = 'abcdefghijklmnopqrstuvwxyz';
    $Vouel = 'aeiou';
    $Name = '';

    for ($index = 1; $index <= $nameLength; $index++) {
        if ($index % 3 == 0) {
            $randomNumber = rand(1, strlen($Vouel));
            $Name .= substr($Vouel, $randomNumber - 1, 1);
        } else {
            $randomNumber = rand(1, strlen($NameChars));
            $Name .= substr($NameChars, $randomNumber - 1, 1);
        }
    }

    return $Name;
}

/**
 * Returns the JavaScript help context code, if help confuguration is enabled
 *
 * @param string $area
 *         The area name
 * @return string
 *         The context context JS code
 */
function getJsHelpContext($area) {
    global $cfg;

    if ($cfg['help'] == true) {
        $hc = "parent.parent.parent.frames[0].document.getElementById('help').setAttribute('data', '$area');";
    } else {
        $hc = '';
    }

    return $hc;
}

/**
 * Defines a constant if not defined before.
 *
 * @param string $constant
 *         Name of constant to define
 * @param mixed $value
 *         It's value
 */
function defineIfNotDefined($constant, $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}

/**
 * CONTENIDO die-alternative.
 * Logs the message and calls die().
 *
 * @param string $file
 *         File name (use __FILE__)
 * @param int $line
 *         Line number (use __LINE__)
 * @param string $message
 *         Message to display
 */
function cDie($file, $line, $message) {
    cError($file, $line, $message);
    die("$file $line: $message");
}

/**
 * Returns a formatted string with a stack trace ready for output.
 * "\tfunction1() called in file $filename($line)"
 * "\tfunction2() called in file $filename($line)"
 * ...
 *
 * @param int $startlevel
 *         The startlevel. Note that 0 is always buildStackString
 *         and 1 is the function called buildStackString (e.g. cWarning)
 * @return string
 */
function buildStackString($startlevel = 2) {
    $e = new Exception();
    $stack = $e->getTrace();

    $msg = '';

    for ($i = $startlevel; $i < count($stack); $i++) {
        $filename = basename($stack[$i]['file']);

        $msg .= "\t" . $stack[$i]['function'] . "() called in file " . $filename . "(" . $stack[$i]['line'] . ")\n";
    }

    return $msg;
}

/**
 * CONTENIDO warning
 *
 * Examples:
 * <pre>
 * // New version
 * cWarning('Some warning message');
 * // Old version
 * cWarning(__FILE__, __LINE__, 'Some warning message');
 * </pre>
 *
 * @param Multiple parameters
 * @SuppressWarnings docBlocks
 */
function cWarning() {
    global $cfg;

    $args = func_get_args();
    if (count($args) == 3) {
        // Old version
        $file = $args[0];
        $line = $args[1];
        $message = $args[2];
    } else {
        // New version
        $file = '';
        $line = '';
        $message = $args[0];
    }

    $msg = "[" . date("Y-m-d H:i:s") . "] ";
    $msg .= "Warning: \"" . $message . "\" at ";

    $e = new Exception();
    $stack = $e->getTrace();
    $function_name = $stack[1]['function'];

    $msg .= $function_name . "() [" . basename($stack[0]['file']) . "(" . $stack[0]['line'] . ")]\n";

    if ($cfg['debug']['log_stacktraces'] == true) {
        $msg .= buildStackString();
        $msg .= "\n";
    }

    cFileHandler::write($cfg['path']['contenido_logs'] . 'errorlog.txt', $msg, true);

    trigger_error($message, E_USER_WARNING);
}

/**
 * CONTENIDO error
 *
 * Examples:
 * <pre>
 * // New version
 * cWarning('Some error message');
 * // Old version
 * cWarning(__FILE__, __LINE__, 'Some error message');
 * </pre>
 *
 * @param unknown_type $message
 * @param Multiple parameters
 */
function cError($message) {
    global $cfg;

    $args = func_get_args();
    if (count($args) == 3) {
        // Old version
        $file = $args[0];
        $line = $args[1];
        $message = $args[2];
    } else {
        // New version
        $file = '';
        $line = '';
        $message = $args[0];
    }

    $msg = "[" . date("Y-m-d H:i:s") . "] ";
    $msg .= "Error: \"" . $message . "\" at ";

    $e = new Exception();
    $stack = $e->getTrace();
    $function_name = $stack[1]['function'];

    $msg .= $function_name . "() called in " . basename($stack[1]['file']) . "(" . $stack[1]['line'] . ")\n";

    if ($cfg['debug']['log_stacktraces'] == true) {
        $msg .= buildStackString();
        $msg .= "\n";
    }

    cFileHandler::write($cfg['path']['contenido_logs'] . 'errorlog.txt', $msg, true);

    trigger_error($message, E_USER_ERROR);
}

/**
 * Writes a note to deprecatedlog.txt
 *
 * @param string $message
 *         Optional message (e.g. "Use function XYZ instead")
 */
function cDeprecated($message = '') {
    global $cfg;

    if (isset($cfg['debug']['log_deprecations']) && $cfg['debug']['log_deprecations'] == false) {
        return;
    }

    $e = new Exception();
    $stack = $e->getTrace();
    $function_name = $stack[1]['function'];

    $msg = "Deprecated call: " . $function_name . "() [" . basename($stack[0]['file']) . "(" . $stack[0]['line'] . ")]: ";
    if ($message != '') {
        $msg .= "\"" . $message . "\"" . "\n";
    } else {
        $msg .= "\n";
    }

    if ($cfg['debug']['log_stacktraces'] == true) {
        $msg .= buildStackString(2);
        $msg .= "\n";
    }

    cFileHandler::write($cfg['path']['contenido_logs'] . 'deprecatedlog.txt', $msg, true);
}

/**
 * Returns the name of the numeric frame given
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 * @param int $frame
 *         Frame number
 * @return string
 *         Canonical name of the frame
 */
function getNamedFrame($frame) {
    switch ($frame) {
        case 1:
            return 'left_top';
            break;
        case 2:
            return 'left_bottom';
            break;
        case 3:
            return 'right_top';
            break;
        case 4:
            return 'right_bottom';
            break;
        default:
            return '';
            break;
    }
}

/**
 * Starts the timing for a specific function
 *
 * @param string $function
 *         Name of the function
 * @param array $parameters
 *         All parameters for the function to measure
 * @return int
 *         uuid for this measure process
 */
function startTiming($function, $parameters = array()) {
    global $_timings, $cfg;

    if ($cfg['debug']['functiontiming'] == false) {
        return;
    }

    // Create (almost) unique ID
    $uuid = md5(uniqid(rand(), true));

    if (!is_array($parameters)) {
        cWarning(__FILE__, __LINE__, "Warning: startTiming's parameters parameter expects an array");
        $parameters = array();
    }

    $_timings[$uuid]['parameters'] = $parameters;
    $_timings[$uuid]['function'] = $function;

    $_timings[$uuid]['start'] = getmicrotime();

    return $uuid;
}

/**
 * Ends the timing process and logs it to the timings file
 *
 * @param int $uuid
 *         UUID which has been used for timing
 */
function endAndLogTiming($uuid) {
    global $_timings, $cfg;

    if ($cfg['debug']['functiontiming'] == false) {
        return;
    }

    $_timings[$uuid]['end'] = getmicrotime();

    $timeSpent = $_timings[$uuid]['end'] - $_timings[$uuid]['start'];

    $myparams = array();

    // Build nice representation of the function
    foreach ($_timings[$uuid]['parameters'] as $parameter) {
        switch (gettype($parameter)) {
            case 'string':
                $myparams[] = '"' . $parameter . '"';
                break;
            case 'boolean':
                if ($parameter == true) {
                    $myparams[] = 'true';
                } else {
                    $myparams[] = 'false';
                }
                break;
            default:
                if ($parameter == '') {
                    $myparams[] = '"' . $parameter . '"';
                } else {
                    $myparams[] = $parameter;
                }
        }
    }

    $parameterString = implode(', ', $myparams);

    cDebug::out('calling function ' . $_timings[$uuid]['function'] . '(' . $parameterString . ') took ' . $timeSpent . ' seconds');
}

/**
 * Function checks current language and client settings by HTTP-Params and DB
 * settings.
 * Based on this informations it will send an HTTP header for right encoding.
 *
 * @param cDb $db
 *         NO MORE NEEDED
 * @param array $cfg
 *         Global cfg-array
 * @param int $lang
 *         Global language id
 * @param string $contentType
 *         Mime type
 */
function sendEncodingHeader($db, $cfg, $lang, $contentType = 'text/html') {
    if (isset($_GET['use_encoding'])) {
        $use_encoding = trim(strip_tags($_GET['use_encoding']));
    } elseif (isset($_POST['use_encoding'])) {
        $use_encoding = trim(strip_tags($_POST['use_encoding']));
    } else {
        $use_encoding = true;
    }

    if (is_string($use_encoding)) {
        $use_encoding = ($use_encoding == 'false') ? false : true;
    }

    if ($use_encoding != false) {
        $aLanguageEncodings = array();

        $oLangColl = new cApiLanguageCollection();
        $oLangColl->select();
        while (($oItem = $oLangColl->next()) !== false) {
            $aLanguageEncodings[$oItem->get('idlang')] = $oItem->get('encoding');
        }

        $charset = 'utf-8';
        if (isset($aLanguageEncodings[$lang])) {
            if (in_array($aLanguageEncodings[$lang], $cfg['AvailableCharsets'])) {
                $charset = $aLanguageEncodings[$lang];
            }
        }
        header('Content-Type: ' . $contentType . '; charset=' . $charset);
    }
}

/**
 * IP match
 *
 * @param string $network
 * @param string $mask
 * @param string $ip
 * @return boolean
 */
function ipMatch($network, $mask, $ip) {
    bcscale(3);
    $ip_long = ip2long($ip);
    $mask_long = ip2long($network);

    // Convert mask to divider
    if (preg_match('/^[0-9]+$/', $mask)) {
        // / 212.50.13.0/27 style mask (Cisco style)
        $divider = bcpow(2, (32 - $mask));
    } else {
        // / 212.50.13.0/255.255.255.0 style mask
        $xmask = ip2long($mask);
        if ($xmask < 0) {
            $xmask = bcadd(bcpow(2, 32), $xmask);
        }
        $divider = bcsub(bcpow(2, 32), $xmask);
    }
    // Test is IP within specified mask
    if (floor(bcdiv($ip_long, $divider)) == floor(bcdiv($mask_long, $divider))) {
        // match - this IP is within specified mask
        return true;
    } else {
        // fail - this IP is NOT within specified mask
        return false;
    }
}

/**
 * Checks, if a function is disabled or not ('disable_functions' setting in php.ini)
 *
 * @param string $functionName
 *         Name of the function to check
 * @return bool
 */
function isFunctionDisabled($functionName) {
    static $disabledFunctions;

    if (empty($functionName)) {
        return true;
    }

    if (!isset($disabledFunctions)) {
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));
    }

    return (in_array($functionName, $disabledFunctions));
}

/**
 * Generates category article breadcrumb for backend
 *
 * @param string $syncoptions
 *         syncstate of backend
 * @param string $showArticle
 *         show also current article or categories only (optional)
 * @param bool $return [optional]
 *         Return or print template
 * @return string|void
 *         Complete template string or nothing
 */
function renderBackendBreadcrumb($syncoptions, $showArticle = true, $return = false) {
    $tplBread = new cTemplate();
    $tplBread->set('s', 'LABEL', i18n("You are here"));
    $syncoptions = (int) $syncoptions;

    $helper = cCategoryHelper::getInstance();
    $categories = $helper->getCategoryPath(cRegistry::getCategoryId(), 1);
    $catIds = array_reverse($helper->getParentCategoryIds(cRegistry::getCategoryId()));
    $catIds[] = cRegistry::getCategoryId();
    $catCount = count($categories);
    $tplCfg = new cApiTemplateConfiguration();
    $sess = cRegistry::getSession();
    $cfg = cRegistry::getConfig();
    $lang = cRegistry::getLanguageId();
    $idart = cRegistry::getArticleId();

    for ($i = 0; $i < $catCount; $i++) {
        $idcat_tpl = 0;
        $idcat_bread = $categories[$i]->getField('idcat');
        $idcat_name = $categories[$i]->getField('name');
        $idcat_tplcfg = $categories[$i]->getField('idtplcfg');
        if ((int) $idcat_tplcfg > 0) {
            $tplCfg->loadByPrimaryKey($idcat_tplcfg);
            if ($tplCfg->isLoaded()) {
                $idcat_tpl = $tplCfg->getField('idtpl');
            }
        }

        $linkUrl = $sess->url(cRegistry::getBackendUrl() . "main.php?area=con&frame=4&idcat=$idcat_bread&idtpl=$idcat_tpl&syncoptions=$syncoptions&contenido=1");

        $disabled = false;
        if(!$categories[$i]->isLoaded() && $syncoptions > 0) {
            $idcat_name = sprintf(i18n("Unsynchronized category (%s)"), $catIds[$i]);
            $linkUrl = "#";
            $disabled = true;
        }
        $tplBread->set('d', 'LINK', $linkUrl);
        $tplBread->set('d', 'NAME', $idcat_name);
        $tplBread->set('d', 'DISABLED', $disabled ? 'disabled' : '');

        $sepArrow = '';
        if ($i < $catCount - 1) {
            $sepArrow = ' > ';
        } else {
            if ((int) $idart > 0 && $showArticle === true) {
                $art = new cApiArticleLanguage();
                $art->loadByArticleAndLanguageId($idart, $lang);
                if ($art->isLoaded()) {
                    $name = $art->getField('title');
                    $sepArrow = ' > ' . $name;
                }
            }
        }
        $tplBread->set('d', 'SEP_ARROW', $sepArrow);

        $tplBread->next();
    }

    return $tplBread->generate($cfg['path']['templates'] . $cfg['templates']['breadcrumb'], $return);
}
