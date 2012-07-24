<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Defines the general CONTENIDO functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.4.5
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.file.php');

/**
 * Extracts the available content-types from the database
 *
 * Creates an array $a_content[type][number] = content string
 * f.e. $a_content['CMS_HTML'][1] = content string
 * Same for array $a_description
 *
 * @param   int  $idartlang  Language specific ID of the arcticle
 * @return  void
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG
 */
function getAvailableContentTypes($idartlang)
{
    global $db, $cfg, $a_content, $a_description;

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["content"]." AS a,
                ".$cfg["tab"]["art_lang"]." AS b,
                ".$cfg["tab"]["type"]." AS c
            WHERE
                a.idtype    = c.idtype AND
                a.idartlang = b.idartlang AND
                b.idartlang = " . (int) $idartlang;

    $db->query($sql);

    while ($db->next_record()) {
        $a_content[$db->f('type')][$db->f('typeid')] = $db->f('value');
        $a_description[$db->f('type')][$db->f('typeid')] = i18n($db->f('description'));
    }
}

/**
 * Checks if an article is assigned to multiple categories
 *
 * @param   int  $idart  Article-Id
 * @return  bool  Article assigned to multiple categories
 */
function isArtInMultipleUse($idart)
{
    global $cfg;

    $db = cRegistry::getDb();
    $sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idart = ". (int) $idart;
    $db->query($sql);

    return ($db->affected_rows() > 1);
}

/**
 * Checks if a value is alphanumeric
 *
 * @param   mixed  $test     Value to test
 * @param   bool   $umlauts  [Use german Umlaute] Optional
 * @return  bool   Value is alphanumeric
 */
function is_alphanumeric($test, $umlauts = true)
{
    if ($umlauts == true) {
        $match = "/^[a-z0-9ÄäÖöÜüß ]+$/i";
    } else {
        $match = "/^[a-z0-9 ]+$/i";
    }

    return (preg_match($match, $test));
}

/**
 * Returns wether a string is UTF-8 encoded or not
 *
 * @param string $input
 * @return boolean
 */
function is_utf8($input) {
    $len = strlen($input);

    for ($i = 0; $i < $len; $i++) {
        $char = ord($input[$i]);
        $n = 0;

        if ($char < 0x80) { //ASCII char
            continue;
        } else if (($char & 0xE0) === 0xC0 && $char > 0xC1) { //2 byte long char
            $n = 1;
        } else if (($char & 0xF0) === 0xE0) { //3 byte long char
            $n = 2;
        } else if (($char & 0xF8) === 0xF0 && $char < 0xF5) { //4 byte long char
            $n = 3;
        } else {
            return false;
        }

        for ($j = 0; $j < $n; $j++) {
            $i++;

            if ($i == $len || (ord($input[$i]) & 0xC0) !== 0x80) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Returns multi-language month name (canonical) by its numeric value
 *
 * @param   int  $month
 * @return  string
 */
function getCanonicalMonth($month)
{
    switch ($month) {
        case 1 :
            return (i18n("January"));
            break;
        case 2 :
            return (i18n("February"));
            break;
        case 3 :
            return (i18n("March"));
            break;
        case 4 :
            return (i18n("April"));
            break;
        case 5 :
            return (i18n("May"));
            break;
        case 6 :
            return (i18n("June"));
            break;
        case 7 :
            return (i18n("July"));
            break;
        case 8 :
            return (i18n("August"));
            break;
        case 9 :
            return (i18n("September"));
            break;
        case 10 :
            return (i18n("October"));
            break;
        case 11 :
            return (i18n("November"));
            break;
        case 12 :
            return (i18n("December"));
            break;
    }
}

/**
 * Get multi-language day
 *
 * @param   int     $iDay  The day number of date(w)
 * @return  string  Dayname of current language
 */
function getCanonicalDay($iDay)
{
    switch ($iDay) {
        case 1 :
            return (i18n("Monday"));
            break;
        case 2 :
            return (i18n("Tuesday"));
            break;
        case 3 :
            return (i18n("Wednesday"));
            break;
        case 4 :
            return (i18n("Thursday"));
            break;
        case 5 :
            return (i18n("Friday"));
            break;
        case 6 :
            return (i18n("Saturday"));
            break;
        case 0 :
            return (i18n("Sunday"));
            break;
        default: break;
    }
}

/**
 * Returns a formatted date and/or timestring according to the current settings
 *
 * @param mixed $timestamp a timestamp. If no value is given the current time will be used.
 * @param bool $date if true the date will be included in the string
 * @param bool $time if true the time will be included in the string
 * @return string the formatted timestring.
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
 * @param   int|string  $area  Area name or id
 * @return  int|string
 */
function getIDForArea($area)
{
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
 * @param   mixed  $area
 * @return  int
 */
function getParentAreaId($area)
{
    $oAreaColl = new cApiAreaCollection();
    return $oAreaColl->getParentAreaID($area);
}

/**
 * Write JavaScript to mark
 *
 * @param int $menuitem Which menuitem to mark
 * @param bool $return Return or echo script
 */
function markSubMenuItem($menuitem, $return = false)
{
    $str = '<script type="text/javascript">
            try {
                // Check if we are in a dual-frame or a quad-frame
                if (parent.parent.frames[0].name == "header") {
                    if (parent.frames["right_top"].document.getElementById("c_'.$menuitem.'")) {
                        menuItem = parent.frames["right_top"].document.getElementById("c_'.$menuitem.'").getElementsByTagName(\'a\')[0];
                        parent.frames["right_top"].sub.clicked(menuItem);
                    }
                } else {
                    // Check if submenuItem is existing and mark it
                    if (parent.parent.frames["right"].frames["right_top"].document.getElementById("c_'.$menuitem.'")) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_'.$menuitem.'").getElementsByTagName(\'a\')[0];
                        parent.parent.frames["right"].frames["right_top"].sub.clicked(menuItem);
                    }
                }
            } catch (e) {}
            </script>';

    if ($return) {
        return $str;
    } else {
        echo $str;
    }
}

/**
 * Redirect to main area
 *
 * @param bool $send Redirect Yes/No
 */
function backToMainArea($send)
{
    if ($send) {
        // Global vars
        global $area, $sess, $idart, $idcat, $idartlang, $idcatart, $frame;

        // Get main area
        $oAreaColl = new cApiAreaCollection();
        $parent = $oAreaColl->getParentAreaID($area);

        // Create url string
        $url_str = 'main.php?'.'area='.$parent.'&'.'idcat='.$idcat.'&'.'idart='.$idart.'&'.
                   'idartlang='.$idartlang.'&'.'idcatart='.$idcatart.'&'.'force=1&'.'frame='.$frame;
        $url = $sess->url($url_str);

        // Redirect
        header("location: $url");
    }
}

/**
 * Returns list of languages (language ids) by passed client.
 * @param  int  $client
 * @return  array
 */
function getLanguagesByClient($client)
{
    $oClientLangColl = new cApiClientLanguageCollection();
    return $oClientLangColl->getLanguagesByClient($client);
}

/**
 * Returns all languages (language ids and names) of an client
 *
 * @param   int  $client
 * @return  array  List of languages where the key is the language id and value the language name
 */
function getLanguageNamesByClient($client)
{
    $oClientLangColl = new cApiClientLanguageCollection();
    return $oClientLangColl->getLanguageNamesByClient($client);
}

/**
 * Adds slashes to passed string if PHP setting for magic quotes is disabled
 * @param  string  $code  String by reference
 */
function set_magic_quotes_gpc(&$code)
{
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
 * @return  array  Indexed array where the value is an assoziative array as follows:
 *                 - $arr[0]['idlang']
 *                 - $arr[0]['langname']
 *                 - $arr[0]['idclient']
 *                 - $arr[0]['clientname']
 */
function getAllClientsAndLanguages()
{
    global $db, $cfg;

    $sql = "SELECT
                a.idlang as idlang,
                a.name as langname,
                b.name as clientname,
                b.idclient as idclient
             FROM
                " .$cfg["tab"]["lang"]." as a,
                " .$cfg["tab"]["clients_lang"]." as c,
                " .$cfg["tab"]["clients"]." as b
             WHERE
                a.idlang = c.idlang AND
                c.idclient = b.idclient";
    $db->query($sql);

    $aRs = array();
    while ($db->next_record()) {
        $aRs[] = array(
            'idlang'     => $db->f('idlang'),
            'langname'   => $db->f('langname'),
            'idclient'   => $db->f('idclient'),
            'clientname' => $db->f('clientname'),
        );
    }
    return $aRs;
}

function getmicrotime()
{
    list ($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

/**
 * Small hack to clean up unused sessions. As we are probably soon rewriting the
 * session management, this hack is OK.
 * @deprecated PHP will handle cleaning up sessions
 */
function cleanupSessions()
{
    global $cfg;

    cDeprecated("Sessions don't need to be cleaned up anymore");

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();
    $col = new cApiInUseCollection();
    $auth = new Contenido_Challenge_Crypt_Auth();

    $maxdate = date("YmdHis", time() - ($auth->lifetime * 60));

    // Expire old sessions
    $sql = "SELECT changed, sid FROM ".$cfg["tab"]["phplib_active_sessions"];
    $db->query($sql);

    while ($db->next_record()) {
        if ($db->f("changed") < $maxdate) {
            $sql = "DELETE FROM ".$cfg["tab"]["phplib_active_sessions"]." WHERE sid = '".$db2->escape($db->f("sid"))."'";
            $db2->query($sql);
            $col->removeSessionMarks($db->f("sid"));
        }
    }

    // Expire invalid InUse-Entries
    $col->select();

    while ($c = $col->next()) {
        $sql = "SELECT sid FROM ".$cfg["tab"]["phplib_active_sessions"]." WHERE sid = '".$db2->escape($c->get("session"))."'";
        $db2->query($sql);
        if (!$db2->next_record()) {
            $col->delete($c->get("idinuse"));
        }
    }
}

function isGroup($uid)
{
    $user = new cApiUser();
    if ($user->loadByPrimaryKey($uid) === false) {
        return true;
    } else {
        return false;
    }
}

function getGroupOrUserName($uid)
{
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
        return $user->getField("realname");
    }
}

/**
 * Checks if passed email address is valid or not
 * @param  string  $email
 * @param  bool    $strict  No more used!
 */
function isValidMail($email, $strict = false)
{
    $validator = cValidatorFactory::getInstance('email');
    return $validator->isValid($email);
}

function htmldecode($string)
{
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    $ret = strtr($string, $trans_tbl);
    return $ret;
}

function updateClientCache($idclient = 0, $htmlpath = '', $frontendpath = '') {
    global $cfg, $cfgClient;

    if ($idclient != 0 && $htmlpath != '' && $frontendpath != '') {
        $cfgClient[$idclient]['path']['frontend'] = cSecurity::escapeString($frontendpath);
        $cfgClient[$idclient]['path']['htmlpath'] = cSecurity::escapeString($htmlpath);
    }

    $aConfigFileContent = array();
    $aConfigFileContent[] = '<?php';

    foreach ($cfgClient as $iIdClient => $aClient) {
        if ((int) $iIdClient > 0) {
            $aConfigFileContent[] = '';
            $aConfigFileContent[] = '/* ' . $aClient['name'] . ' */';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["path"]["htmlpath"] = "' . $aClient['path']['htmlpath'] . '";';
            $aConfigFileContent[] = '$cfgClient[' . $iIdClient . ']["path"]["frontend"] = "' . $aClient['path']['frontend'] . '";';
        }
    }

    $aConfigFileContent[] = '?>';
    cFileHandler::write($cfg['path']['contenido_config'] . 'config.clients.php', implode(PHP_EOL, $aConfigFileContent));
}

function rereadClients()
{
    global $cfgClient, $errsite_idcat, $errsite_idart, $db, $cfg;

    if (!is_object($db)) {
        $db = cRegistry::getDb();
    }

    $sql = 'SELECT idclient, name, errsite_cat, errsite_art FROM ' . $cfg['tab']['clients'];
    $db->query($sql);

    while ($db->next_record()) {
        $iClient = $db->f('idclient');
        $cfgClient['set'] = 'set';

        $cfgClient[$iClient]['name'] = $db->f('name');

        $errsite_idcat[$iClient] = $db->f('errsite_cat');
        $errsite_idart[$iClient] = $db->f('errsite_art');

        $cfgClient[$iClient]['images'] = $cfgClient[$iClient]['path']['htmlpath'] . 'images/';
        $cfgClient[$iClient]['upload'] = 'upload/';

        $cfgClient[$iClient]['htmlpath']['frontend'] = $cfgClient[$iClient]['path']['htmlpath'];
        $cfgClient[$iClient]['upl']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'upload/';
        $cfgClient[$iClient]['upl']['htmlpath'] = $cfgClient[$iClient]['htmlpath']['frontend'] . 'upload/';
        $cfgClient[$iClient]['upl']['frontendpath'] = 'upload/';
        $cfgClient[$iClient]['css']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'css/';
        $cfgClient[$iClient]['js']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'js/';
        $cfgClient[$iClient]['tpl']['path'] = $cfgClient[$iClient]['path']['frontend'] . 'templates/';

// @todo define a common data path
        $cfgClient[$iClient]['cache_path'] = $cfgClient[$iClient]['path']['frontend'] . 'cache/';
        $cfgClient[$iClient]['data_path'] = $cfgClient[$iClient]['path']['frontend'];
        $cfgClient[$iClient]['code_path'] = $cfgClient[$iClient]['data_path'] . 'cache/code/';
#        $cfgClient[$iClient]['config_path'] = $cfgClient[$iClient]['data_path'] . 'config/';
        $cfgClient[$iClient]['layout_path'] = $cfgClient[$iClient]['data_path'] . 'layouts/';
        $cfgClient[$iClient]['log_path'] = $cfgClient[$iClient]['data_path'] . 'logs/';
        $cfgClient[$iClient]['module_path'] = $cfgClient[$iClient]['data_path'] . 'modules/';
        $cfgClient[$iClient]['template_path'] = $cfgClient[$iClient]['data_path'] . 'templates/';
        $cfgClient[$iClient]['version_path'] = $cfgClient[$iClient]['data_path'] . 'version/';
    }
}

/**
 * Sets a system property entry
 *
 * @modified Timo Trautmann 22.02.2008 Support for editing name and type
 *
 * @param  string  $type  The type of the item
 * @param  string  $name  The name of the item
 * @param  string  $value  The value of the item
 * @param  int  $idsystemprop  The sysprop id, use optional. If set it allows to modify type name and value
 */
function setSystemProperty($type, $name, $value, $idsystemprop = 0)
{
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
 * @param   string  $type  The type of the item
 * @param   string  $name  The name of the item
 * @return  bool
 */
function deleteSystemProperty($type, $name)
{
    $systemPropColl = new cApiSystemPropertyCollection();
    $systemPropColl->deleteByTypeName($type, $name);
}

/**
 * Retrieves all available system properties.
 * Array format:
 *
 * $array[$type][$name] = $value;
 *
 * @modified Timo Trautmann 22.02.2008 Support for editing name and type editing by primaray key idsystemprop
 * if bGetPropId is set:
 * $array[$type][$name][value] = $value;
 * $array[$type][$name][idsystemprop] = $idsystemprop;
 *
 * @param  bool  $bGetPropId  If true special mode is activated which generates for
 *                            each property a third array, which also contains idsystemprop value
 * @return array
 */
function getSystemProperties($bGetPropId = false)
{
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
 * @param   string  $type  The type of the item
 * @param   string  $name  The name of the item
 * @return  string|bool  The property value or false if nothing was found
 */
function getSystemProperty($type, $name)
{
    $systemPropColl = new cApiSystemPropertyCollection();
    $prop = $systemPropColl->fetchByTypeName($type, $name);
    return ($prop) ? $prop->get('value') : false;
}

/**
 * Gets system property entries
 *
 * @param  string  $type  The type of the properties
 * @return array  Assoziative array like $arr[name] = value
 */
function getSystemPropertiesByType($type)
{
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
 * Returns the current effective setting for a property.
 *
 * The order is:
 * System => Client => Group => User
 *
 * System properties can be overridden by the group, and group
 * properties can be overridden by the user.
 *
 * @param string $type The type of the item
 * @param string $name The name of the item
 * @param string $default Optional default value
 * @return mixed boolean false if nothing was found
 */
function getEffectiveSetting($type, $name, $default = '')
{
    return cEffectiveSetting::get($type, $name, $default);
}

/**
 * Returns the current effective settings for a type of properties.
 *
 * The order is:
 * System => Client => Group => User
 *
 * System properties can be overridden by the group, and group
 * properties can be overridden by the user.
 *
 * @param string $type The type of the item
 * @return array Value
 */
function getEffectiveSettingsByType($type)
{
    return cEffectiveSetting::getByType($type);
}

/**
 * Retrieve list of article specifications for current client and language
 *
 * @return array list of article specifications
 */
function getArtspec()
{
    global $db, $cfg, $lang, $client;
    $sql = "SELECT artspec, idartspec, online, artspecdefault FROM ".$cfg['tab']['art_spec']."
            WHERE client=".(int) $client." AND lang=".(int) $lang." ORDER BY artspec ASC";
    $db->query($sql);

    $artspec = array();

    while ($db->next_record()) {
        $artspec[$db->f("idartspec")]['artspec'] = $db->f("artspec");
        $artspec[$db->f("idartspec")]['online'] = $db->f("online");
        $artspec[$db->f("idartspec")]['default'] = $db->f("artspecdefault");
    }
    return $artspec;
}

/**
 * Add new article specification
 *
 * @param string $artspectext specification text
 * @param  int  $online  Online status (1 or 0)
 * @return void
 */
function addArtspec($artspectext, $online)
{
    global $db, $cfg, $lang, $client;

    if (isset($_POST['idartspec'])) { //update
        $fields = array('artspec' => $artspectext, 'online' => (int) $online);
        $where = array('idartspec' => (int) $_POST['idartspec']);
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
 * @param int  $idartspec  article specification id
 * @return void
 */
function deleteArtspec($idartspec)
{
    global $db, $cfg;
    $sql = "DELETE FROM ".$cfg['tab']['art_spec']." WHERE idartspec = ".(int) $idartspec;
    $db->query($sql);

    $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET artspec = 0 WHERE artspec = ".(int) $idartspec;
    $db->query($sql);
}

/**
 * Set article specifications online
 *
 * Flag to switch if an article specification should be shown the frontend or not
 *
 * @param int  $idartspec  article specification id
 * @param int  $online  0/1 switch the status between on an offline
 * @return void
 */
function setArtspecOnline($idartspec, $online)
{
    global $db, $cfg;
    $sql = "UPDATE ".$cfg['tab']['art_spec']." SET online=".(int) $online." WHERE idartspec=".(int) $idartspec;
    $db->query($sql);
}

/**
 * Set a default article specification
 *
 * While creating a new article this defined article specification will be default setting
 *
 * @param int  $idartspec  Article specification id
 * @return void
 */
function setArtspecDefault($idartspec)
{
    global $db, $cfg, $lang, $client;
    $sql = "UPDATE ".$cfg['tab']['art_spec']." SET artspecdefault=0 WHERE client=".(int) $client." AND lang=".(int) $lang;
    $db->query($sql);

    $sql = "UPDATE ".$cfg['tab']['art_spec']." SET artspecdefault=1 WHERE idartspec=".(int) $idartspec;
    $db->query($sql);
}

/**
 * Build a Article select Box
 *
 * @param string  $sName  Name of the SelectBox
 * @param string  $iIdCat  category id
 * @param string  $sValue  Value of the SelectBox
 * @return string HTML
 */
function buildArticleSelect($sName, $iIdCat, $sValue)
{
    global $cfg, $client, $lang, $idcat;
    $db = cRegistry::getDb();

    $html = '';
    $html .= '<select id="'.$sName.'" name="'.$sName.'">';
    $html .= '  <option value="">'.i18n("Please choose").'</option>';

    $sql = "SELECT b.title, b.idart FROM
               ".$cfg["tab"]["art"]." AS a, ".$cfg["tab"]["art_lang"]." AS b, ".$cfg["tab"]["cat_art"]." AS c
               WHERE c.idcat = ".(int) $iIdCat."
               AND b.idlang = ".(int) $lang." AND b.idart = a.idart and b.idart = c.idart
               ORDER BY b.title";

    $db->query($sql);

    while ($db->next_record()) {
        if ($sValue != $db->f('idart')) {
            $html .= '<option value="'.$db->f('idart').'" style="background-color:#EFEFEF">'.$db->f('title').'</option>';
        } else{
            $html .= '<option value="'.$db->f('idart').'" style="background-color:#EFEFEF" selected="selected">'.$db->f('title').'</option>';
        }
    }

    $html .= '</select>';

    return $html;
}

/**
 * Build a Category / Article select Box
 *
 * @param  string  Name of the SelectBox
 * @param  string  Value of the SelectBox
 * @param  int  Value of highest level that should be shown
 * @param  string  Optional style informations for select
 * @return  string  HTML
 */
function buildCategorySelect($sName, $sValue, $sLevel = 0, $sStyle = '')
{
    global $cfg, $client, $lang, $idcat;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $html = '';
    $html .= '<select id="'.$sName.'" style="'.$sStyle.'" name="'.$sName.'">';
    $html .= '  <option value="">'.i18n("Please choose").'</option>';

    if ($sLevel > 0) {
        $addString = "AND c.level < " . (int) $sLevel;
    }

    $sql = "SELECT a.idcat AS idcat, b.name AS name, c.level FROM
           ".$cfg["tab"]["cat"]." AS a, ".$cfg["tab"]["cat_lang"]." AS b,
           ".$cfg["tab"]["cat_tree"]." AS c WHERE a.idclient = ".(int) $client."
           AND b.idlang = ".(int) $lang." AND b.idcat = a.idcat AND c.idcat = a.idcat ".$addString."
           ORDER BY c.idtree";

    $db->query($sql);

    $categories = array();

    while ($db->next_record()) {
        $categories[$db->f("idcat")]["name"] = $db->f("name");

        $sql2 = "SELECT level FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat = ".(int) $db->f("idcat");
        $db2->query($sql2);

        if ($db2->next_record()) {
            $categories[$db->f("idcat")]["level"] = $db2->f("level");
        }

        $sql2 = "SELECT a.title AS title, b.idcatart AS idcatart FROM
                ".$cfg["tab"]["art_lang"]." AS a,  ".$cfg["tab"]["cat_art"]." AS b
                WHERE b.idcat = '".$db->f("idcat")."' AND a.idart = b.idart AND
                a.idlang = ".(int) $lang;

        $db2->query($sql2);

        while ($db2->next_record()) {
            $categories[$db->f("idcat")]["articles"][$db2->f("idcatart")] = $db2->f("title");
        }
    }

    foreach ($categories as $tmpidcat => $props) {
        $spaces = "&nbsp;&nbsp;";

        for ($i = 0; $i < $props["level"]; $i ++) {
            $spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        $tmp_val = $tmpidcat;

        if ($sValue != $tmp_val) {
            $html .= '<option value="'.$tmp_val.'" style="background-color:#EFEFEF">'.$spaces.">".$props["name"].'</option>';
        } else {
            $html .= '<option value="'.$tmp_val.'" style="background-color:#EFEFEF" selected="selected">'.$spaces.">".$props["name"].'</option>';
        }
    }

    $html .= '</select>';

    return $html;
}

function human_readable_size($number)
{
    $base = 1024;
    $suffixes = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB');

    $usesuf = 0;
    $n = (float) $number; //Appears to be necessary to avoid rounding
    while ($n >= $base) {
        $n /= (float) $base;
        $usesuf ++;
    }

    $places = 2 - floor(log10($n));
    $places = max($places, 0);
    $retval = number_format($n, $places, '.', '') . ' ' . $suffixes[$usesuf];
    return $retval;
}

/**
 * Trims an array
 *
 * @param array Array to trim
 * @return array Trimmed array
 */
function trim_array($array)
{
    if (!is_array($array)) {
        return $array;
    }

    foreach ($array as $key => $value) {
        $array[$key] = trim($value);
    }

    return $array;
}

function array_csort()
{ //coded by Ichier2003
    $args = func_get_args();
    $marray = array_shift($args);
    $msortline = "return(array_multisort(";
    $i = 0;
    foreach ($args as $arg) {
        $i ++;
        if (is_string($arg)) {
            foreach ($marray as $row) {
                $a = strtoupper($row[$arg]);
                $sortarr[$i][] = $a;
            }
        } else {
            $sortarr[$i] = $arg;
        }
        $msortline .= "\$sortarr[".$i."],";
    }
    $msortline .= "\$marray));";
    @eval($msortline);
    return $marray;
}

/**
 * Replaces a string only once
 *
 * Caution: This function only takes strings as parameters, not arrays!
 *
 * @param  string  $find  String to find
 * @param  string  $replace  String to replace
 * @param  string  $subject String to process
 * @return string Processed string
 */
function str_ireplace_once($find, $replace, $subject)
{
    $start = strpos(strtolower($subject), strtolower($find));

    if ($start === false) {
        return $subject;
    }

    $end = $start +strlen($find);
    $first = substr($subject, 0, $start);
    $last = substr($subject, $end, strlen($subject) - $end);

    $result = $first.$replace.$last;

    return $result;
}

/**
 * Replaces a string only once, in reverse direction
 *
 * Caution: This function only takes strings as parameters, not arrays!
 * @param  string  $find  String to find
 * @param  string  $replace  String to replace
 * @param  string  $subject  String to process
 * @return string Processed string
 */
function str_ireplace_once_reverse($find, $replace, $subject)
{
    $start = str_rpos(strtolower($subject), strtolower($find));

    if ($start === false) {
        return $subject;
    }

    $end = $start +strlen($find);

    $first = substr($subject, 0, $start);
    $last = substr($subject, $end, strlen($subject) - $end);

    $result = $first.$replace.$last;

    return ($result);
}

/**
 * Finds a string position in reverse direction
 *
 * NOTE: The original strrpos-Function of PHP4 only finds a single character as needle.
 *
 * @param  string  $haystack   String to search in
 * @param  string  $needle     String to search for
 * @param  int     $start     Offset
 * @return string Processed string
 */
function str_rpos($haystack, $needle, $start = 0)
{
    $tempPos = strpos($haystack, $needle, $start);

    if ($tempPos === false) {
        if ($start == 0) {
            //Needle not in string at all
            return false;
        } else {
            //No more occurances found
            return $start -strlen($needle);
        }
    } else {
        //Find the next occurance
        return str_rpos($haystack, $needle, $tempPos +strlen($needle));
    }
}

/**
 * Checks if the script is being runned from the web
 *
 * @return  bool  True if the script is running from the web
 */
function isRunningFromWeb()
{
    if ($_SERVER['PHP_SELF'] == '' || php_sapi_name() == 'cgi' || php_sapi_name() == 'cli') {
        return false;
    }

    return true;
}

/**
 * Scans a given plugin directory and places the found plugins into the array $cfg['plugins'].
 *
 * Result:
 * $cfg['plugins']['frontendusers'] => array with all found plugins
 *
 * Note: Plugins are only "found" if the following directory structure if found:
 *
 * entity/
 *        plugin1/plugin1.php
 *        plugin2/plugin2.php
 *
 * The plugin's directory and file name have to be the same, otherwise the function
 * won't find them!
 *
 * @param  string  $entity Name of the directory to scan
 * @return  void
 */
function scanPlugins($entity)
{
    global $cfg;

    $pluginorder = getSystemProperty('plugin', $entity . '-pluginorder');
    $lastscantime = getSystemProperty('plugin', $entity . '-lastscantime');

    $plugins = array();

    // Fetch and trim the plugin order
    if ($pluginorder != '') {
        $plugins = explode(',', $pluginorder);
        foreach ($plugins as $key => $plugin) {
            $plugins[$key] = trim($plugin);
        }
    }

    $basedir = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $entity . '/';

    // Don't scan all the time, but each 60 seconds
    if ($lastscantime + 60 < time()) {
        setSystemProperty('plugin', $entity . '-lastscantime', time());

        $dh = opendir($basedir);

        while (($file = readdir($dh)) !== false) {
            if (is_dir($basedir . $file) && $file != 'includes' && $file != '.' && $file != '..') {
                if (!in_array($file, $plugins)) {
                    if (cFileHandler::exists($basedir . $file . '/' . $file . '.php')) {
                        $plugins[] = $file;
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

        $pluginorder = implode(',', $plugins);
        setSystemProperty('plugin', $entity . '-pluginorder', $pluginorder);
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
 * @param $entity Name of the directory to scan
 */
function includePlugins($entity)
{
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
 * @param  string  $entity  Name of the directory to scan
 */
function callPluginStore($entity)
{
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
 * @param  int  $nameLength  Length of the generated string
 * @return string  Random name
 */
function createRandomName($nameLength)
{
    $NameChars = 'abcdefghijklmnopqrstuvwxyz';
    $Vouel = 'aeiou';
    $Name = '';

    for ($index = 1; $index <= $nameLength; $index ++) {
        if ($index % 3 == 0) {
            $randomNumber = rand(1, strlen($Vouel));
            $Name .= substr($Vouel, $randomNumber -1, 1);
        } else {
            $randomNumber = rand(1, strlen($NameChars));
            $Name .= substr($NameChars, $randomNumber -1, 1);
        }
    }

    return $Name;
}

function setHelpContext($area)
{
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
 * @param  string  $constant  Name of constant to define
 * @param  mixed  $value  It's value
 */
function define_if($constant, $value)
{
    if (!defined($constant)) {
        define($constant, $value);
    }
}

function locale_arsort($locale, $array)
{
    $oldlocale = setlocale(LC_COLLATE, 0);
    setlocale(LC_COLLATE, $locale);

    uasort($array, 'strcoll');

    setlocale(LC_COLLATE, $oldlocale);

    return $array;
}

/* TODO: Ask timo to document this. */
/* Note: If subarrays exists, this function currently returns the key of the array
   given by $array, and not from the subarrays (todo: add flag to allow this) */
function array_search_recursive($search, $array, $partial = false, $strict = false)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $val = array_search_recursive($search, $value, $partial, $strict);
            if ($val !== false) {
                return ($key);
            }
        } else {
            if ($partial == false) {
                if ($strict == true) {
                    if ($value === $search) {
                        return $key;
                    }
                } else {
                    if ($value == $search) {
                        return $key;
                    }
                }
            } else {
                if (strpos($value, $search) !== FALSE) {
                    return $key;
                }
            }
        }
    }

    return false;
}

/**
 * CONTENIDO die-alternative. Logs the message and calls die().
 *
 * @param   string  $file     File name   (use __FILE__)
 * @param   int     $line     Line number (use __LINE__)
 * @param   string  $message  Message to display
 */
function cDie($file, $line, $message)
{
    cError($file, $line, $message);
    die("$file $line: $message");
}

/**
 * Returns a formatted string with a stack trace ready for output.
 *        "\tfunction1() called in file $filename($line)"
 *        "\tfunction2() called in file $filename($line)"
 *        ...
 *
 * @param  int  $startlevel  The startlevel. Note that 0 is always buildStackString
 *     and 1 is the function called buildStackString (e.g. cWarning)
 * @return string
 */
function buildStackString($startlevel = 2)
{
    $e = new Exception();
    $stack = $e->getTrace();

    $msg = '';

    for ($i = $startlevel; $i < count($stack); $i++) {
        $filename = basename($stack[$i]['file']);

        $msg .= "\t".$stack[$i]['function']."() called in file ".$filename."(".$stack[$i]['line'].")\n";
    }

    return $msg;
}

/**
 * Returns the debugger for the current system settings
 *
 * @return IDebug
 */
function getDebugger()
{
    $debugger = cDebugFactory::getDebugger('devnull');
    if (getSystemProperty('debug', 'debug_to_file') == 'true') {
        $debugger = cDebugFactory::getDebugger('file');
    } else if (getSystemProperty('debug', 'debug_to_screen') == 'true') {
        $debugger = cDebugFactory::getDebugger('visible_adv');
    }
    if ((getSystemProperty('debug', 'debug_to_screen') == 'true') && (getSystemProperty('debug', 'debug_to_file') == 'true')) {
        $debugger = cDebugFactory::getDebugger('vis_and_file');
    }

    return $debugger;
}

/**
 * Prints a debug message if the settings allow it. The debug messages will be
 * in a textrea in the header and in the file debuglog.txt. All messages are immediately
 * written to the filesystem but they will only show up when debugPrint() is called.
 *
 * @param  string  $message  Message to display. NOTE: You can use buildStackString to show stacktraces
 */
function cDebug($message)
{
    $debugger = getDebugger();
    $debugger->out($message);
}

/**
 * Adds a variable to the debugger. This variable will be watched.
 *
 * @param mixed $var A variable or an object
 * @param string $label An optional description for the variable
 */
function debugAdd($var, $label = '')
{
    $debugger = getDebugger();
    $debugger->add($var, $label);
}

/**
 * Prints the cached debug messages to the screen
 */
function debugPrint()
{
    $debugger = getDebugger();
    $debugger->showAll();
}

/**
 * CONTENIDO warning
 *
 * @param   string  $file     File name   (use __FILE__)
 * @param   int     $line     Line number (use __LINE__)
 * @param   string  $message  Message to display
 */
function cWarning($file, $line, $message)
{
    global $cfg;

    $msg = "[".date("Y-m-d H:i:s")."] ";
    $msg .= "Warning: \"".$message."\" at ";

    $e = new Exception();
    $stack = $e->getTrace();
    $function_name = $stack[1]['function'];

    $msg .= $function_name."() [".basename($stack[0]['file'])."(".$stack[0]['line'].")]\n";
    $msg .= buildStackString();
    $msg .= "\n";

    cFileHandler::write($cfg['path']['contenido_logs'] . 'errorlog.txt', $msg, true);

    trigger_error($message, E_USER_WARNING);
}

/**
 * CONTENIDO error
 *
 * @param   string  $file     File name   (use __FILE__)
 * @param   int     $line     Line number (use __LINE__)
 * @param   string  $message  Message to display
 */
function cError($file, $line, $message)
{
    global $cfg;

    $msg = "[".date("Y-m-d H:i:s")."] ";
    $msg .= "Error: \"".$message."\" at ";

    $e = new Exception();
    $stack = $e->getTrace();
    $function_name = $stack[1]['function'];

    $msg .= $function_name."() called in ".basename($stack[1]['file'])."(".$stack[1]['line'].")\n";
    $msg .= buildStackString();
    $msg .= "\n";

    cFileHandler::write($cfg['path']['contenido_logs'] . 'errorlog.txt', $msg, true);

    trigger_error($message, E_USER_ERROR);
}

/**
 * Writes a note to deprecatedlog.txt
 *
 * @param  string  $amsg  Optional message (e.g. "Use function XYZ instead")
 * @return void
 */
function cDeprecated($amsg = '')
{
    global $cfg;

    $e = new Exception();
    $stack = $e->getTrace();
    $function_name = $stack[1]['function'];

    $msg = "Deprecated call: ".$function_name."() [".basename($stack[0]['file'])."(".$stack[0]['line'].")]: ";
    if ($amsg != '') {
        $msg .= "\"".$amsg."\""."\n";
    } else {
        $msg .= "\n";
    }

    $msg .= buildStackString(2)."\n";

    cFileHandler::write($cfg['path']['contenido_logs'] . 'deprecatedlog.txt', $msg, true);
}

/**
 * Returns the name of the numeric frame given
 *
 * @param   int  $frame   Frame number
 * @return string  Canonical name of the frame
 */
function getNamedFrame($frame)
{
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
 * @param  string  $function  Name of the function
 * @param  array  $parameters  All parameters for the function to measure
 * @return int uuid for this measure process
 */
function startTiming($function, $parameters = array())
{
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
 * @param  $uuid  int  UUID which has been used for timing
 */
function endAndLogTiming($uuid)
{
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
                $myparams[] = '"'.$parameter.'"';
                break;
            case 'boolean':
                if ($parameter == true) {
                    $myparams[] = 'true';
                } else {
                    $myparams[] = 'false';
                }
                break;
            default :
                if ($parameter == '') {
                    $myparams[] = '"'.$parameter.'"';
                } else {
                    $myparams[] = $parameter;
                }
        }
    }

    $parameterString = implode(', ', $myparams);

    cDebug('calling function ' . $_timings[$uuid]['function'] . '(' . $parameterString . ') took ' . $timeSpent . ' seconds');
}

function notifyOnError($errortitle, $errormessage)
{
    global $cfg;

    $notifyFile = $cfg['path']['contenido_logs'] . 'notify.txt';

    if (cFileHandler::exists($notifyFile)) {
        $notifytimestamp = cFileHandler::read($notifyFile);
    } else {
        $notifytimestamp = 0;
    }

    if ((time() - $notifytimestamp) > $cfg['contenido']['notifyinterval'] * 60) {
        if ($cfg['contenido']['notifyonerror'] != '') {
            $sMailhost = getSystemProperty('system', 'mail_host');
            if ($sMailhost == '') {
                $sMailhost = 'localhost';
            }

            $oMail = new PHPMailer();
            $oMail->Host = $sMailhost;
            $oMail->IsHTML(0);
            $oMail->WordWrap = 1000;
            $oMail->IsMail();

            $oMail->AddAddress($cfg['contenido']['notifyonerror'], '');
            $oMail->Subject = $errortitle;
            $oMail->Body = $errormessage;

            // Notify configured email
            $oMail->Send();

            // Write last notify log file
            cFileHandler::write($notifyFile, time());
        }
    }
}

function cInitializeArrayKey(&$aArray, $sKey, $mDefault = '')
{
    if (!is_array($aArray)) {
        if (isset($aArray)) {
            return false;
        }
        $aArray = array();
    }

    if (!array_key_exists($sKey, $aArray)) {
        $aArray[$sKey] = $mDefault;
    }
}

/**
 * Function checks current language and client settings by HTTP-Params and DB
 * settings. Based on this informations it will send an HTTP header for right encoding.
 *
 * @param  DB_Contenido  $db  NO MORE NEEDED
 * @param  array $cfg  Global cfg-array
 * @param  int $lang  Global language id
 * @param  string   $contentType  Mime type
 */
function sendEncodingHeader($db, $cfg, $lang, $contentType = 'text/html')
{
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
        while ($oItem = $oLangColl->next()) {
            $aLanguageEncodings[$oItem->get('idlang')] = $oItem->get('encoding');
        }

        $charset = 'ISO-8859-1';
        if (isset($aLanguageEncodings[$lang])) {
            if (in_array($aLanguageEncodings[$lang], $cfg['AvailableCharsets'])) {
                $charset = $aLanguageEncodings[$lang];
            }
        }
        header('Content-Type: ' . $contentType . '; charset=' . $charset);
    }
}

/**
 * IP_match
 *
 * @param string $network
 * @param string $mask
 * @param string $ip
 * @return boolean
 */
function IP_match($network, $mask, $ip)
{
    bcscale(3);
    $ip_long = ip2long($ip);
    $mask_long = ip2long($network);

    // Convert mask to divider
    if (preg_match('/^[0-9]+$/', $mask)) {
        /// 212.50.13.0/27 style mask (Cisco style)
        $divider = bcpow(2, (32 - $mask));
    } else {
        /// 212.50.13.0/255.255.255.0 style mask
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


/** @deprecated  [2012-06-21]  Use capiIsImageMagickAvailable() from functions.api.images.php */
function isImageMagickAvailable()
{
    cDeprecated('Use capiIsImageMagickAvailable() from functions.api.images.php');
    cInclude('includes', 'functions.api.images.php');
    return capiIsImageMagickAvailable();
}

/** @deprecated  [2012-06-21]  Use cApiClientCollection->getClientname() */
function getClientName($idclient)
{
    cDeprecated("Use cApiClientCollection->getClientname()");
    $oClientColl = new cApiClientCollection();
    return $oClientColl->getClientname($idclient);
}

/** @deprecated  [2011-08-24]  This function is not supported any longer */
function cIDNAEncode($sourceEncoding, $string)
{
    cDeprecated("This function is not supported any longer");
    if (extension_loaded("iconv")) {
        cInclude('pear', 'Net/IDNA.php');
        $idn = Net_IDNA::getInstance();
        $string = iconv("UTF-8", $sourceEncoding, $string);
        $string = $idn->encode($string);
        return ($string);
    }
    if (extension_loaded("recode")) {
        cInclude('pear', 'Net/IDNA.php');
        $idn = Net_IDNA::getInstance();
        $string = $idn->decode($string);
        $string = recode_string("UTF-8..".$sourceEncoding, $string);
        return $string;
    }
    return $string;
}

/** @deprecated  [2011-08-24]  This function is not supported any longer */
function cIDNADecode($targetEncoding, $string)
{
    cDeprecated("This function is not supported any longer");
    if (extension_loaded("iconv")) {
        cInclude('pear', 'Net/IDNA.php');
        $idn = Net_IDNA::getInstance();
        $string = $idn->decode($string);
        $string = iconv($targetEncoding, "UTF-8", $string);
        return ($string);
    }
    if (extension_loaded("recode")) {
        cInclude('pear', 'Net/IDNA.php');
        $idn = Net_IDNA::getInstance();
        $string = recode_string($targetEncoding."..UTF-8", $string);
        $string = $idn->decode($string);
        return $string;
    }
    return $string;
}

/** @deprecated [2012-01-18] DB_Contenido performs the check for itself. This method is no longer needed */
function checkMySQLConnectivity()
{
    cDeprecated("DB_Contenido performs the check for itself. This method is no longer needed");
}

/** @deprecated 2011-08-23  This function is not supported any longer */
function sendPostRequest($host, $path, $data, $referer = '', $port = 80)
{
    cDeprecated("This function is not supported any longer");
    $fp = fsockopen($host, $port);
    fputs($fp, "POST $path HTTP/1.1\n");
    fputs($fp, "Host: $host\n");
    fputs($fp, "Referer: $referer\n");
    fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
    fputs($fp, "Content-length: ".strlen($data)."\n");
    fputs($fp, "Connection: close\n\n");
    fputs($fp, "$data\n");
    while (!feof($fp)) {
        $res .= fgets($fp, 128);
    }
    fclose($fp);
    return $res;
}

/** @deprecated  [2012-02-26] Function does not work and is not longer supported */
function displayPlugin($entity, & $form) {
    cDeprecated("This function does not work and is not longer supported");
}

/** @deprecated  [2012-03-10] This function is not longer supported. */
function getPhpModuleInfo($moduleName)
{
    cDeprecated("This function is not longer supported");
    $moduleSettings = array();
    ob_start();
    phpinfo(INFO_MODULES);
    $string = ob_get_contents();
    ob_end_clean();
    $pieces = explode("<h2", $string);
    foreach ($pieces as $val) {
        preg_match("/<a name=\"module_([^<>]*)\">/", $val, $sub_key);
        preg_match_all("/<tr[^>]*>
                <td[^>]*>(.*)<\/td>
                <td[^>]*>(.*)<\/td>/Ux", $val, $sub);
        preg_match_all("/<tr[^>]*>
                <td[^>]*>(.*)<\/td>
                <td[^>]*>(.*)<\/td>
                <td[^>]*>(.*)<\/td>/Ux", $val, $sub_ext);
        if (isset($moduleName)) {
            if (extension_loaded($moduleName)) {
                if ($sub_key[1] == $moduleName) {
                    foreach ($sub[0] as $key => $val) {
                        $moduleSettings[strip_tags($sub[1][$key])] = array(strip_tags($sub[2][$key]));
                    }
                }
            } else {
                $moduleSettings['error'] = 'extension is not available';
            }
        } else {
            foreach ($sub[0] as $key => $val) {
                $moduleSettings[$sub_key[1]][strip_tags($sub[1][$key])] = array(strip_tags($sub[2][$key]));
            }
            foreach ($sub_ext[0] as $key => $val) {
                $moduleSettings[$sub_key[1]][strip_tags($sub_ext[1][$key])] = array(strip_tags($sub_ext[2][$key]), strip_tags($sub_ext[3][$key]));
            }
        }
    }
    return $moduleSettings;
}

/** @deprecated  [2012-03-05]  This function is not longer supported. */
function fakeheader($time)
{
    cDeprecated("This function is not longer supported.");
    global $con_time0;
    if (!isset($con_time0)) {
        $con_time0 = $time;
    }
    if ($time >= $con_time0 + 1000) {
        $con_time0 = $time;
        header('X-pmaPing: Pong');
    }
}

/** @deprecated  [2011-09-02] This function is not supported any longer */
function showLocation($area)
{
    cDeprecated("This function is not supported any longer");
    global $db, $cfgPath, $cfg, $belang;
    $xml = new XML_doc();
    if ($xml->load($cfg['path']['xml'].$cfg['lang'][$belang]) == false) {
        if ($xml->load($cfg['path']['xml'].'lang_en_US.xml') == false) {
            die("Unable to load any XML language file");
        }
    }
    $sql = "SELECT location FROM ".$cfg["tab"]["area"]." as A, ".$cfg["tab"]["nav_sub"]." as B "
        . "WHERE A.name='".cSecurity::escapeDB($area, $db)."' AND A.idarea=B.idarea AND A.online='1'";
    $db->query($sql);
    if ($db->next_record()) {
        echo "<b>".$xml->valueOf($db->f("location"))."</b>";
    } else {
        $sql = "SELECT parent_id FROM ".$cfg["tab"]["area"]." WHERE "
             . "name='".cSecurity::escapeDB($area, $db)."' AND online='1'";
        $db->query($sql);
        $db->next_record();
        $parent = $db->f("parent_id");
        $sql = "SELECT location FROM ".$cfg["tab"]["area"]." as A, ".$cfg["tab"]["nav_sub"]." as B "
             . "WHERE A.name='".cSecurity::escapeDB($parent, $db)."' AND A.idarea = B.idarea AND A.online='1'";
        $db->query($sql);
        $db->next_record();
        echo "<b>".$xml->valueOf($db->f("location")).$area."</b>";
    }
}

/** @deprecated  [2011-08-23] This function is not supported any longer */
function showTable($tablename)
{
    cDeprecated("This function is not supported any longer");
    global $db;
    $sql = "SELECT * FROM $tablename";
    $db->query($sql);
    while ($db->next_record()) {
        while (list ($key, $value) = each($db->Record)) {
            print (is_string($key) ? "<b>$key</b>: $value | " : '');
        }
        print ("<br>");
    }
}

/** @deprecated  [2012-06-20] Use getFileType() from functions.file.php */
function recursive_copy($from_path, $to_path)
{
    cDeprecated("Use recursiveCopy() from functions.file.php");
    recursiveCopy($from_path, $to_path);
}

/** @deprecated  [2012-06-20] Use getFileType() from functions.file.php */
function getFileExtension($filename)
{
    cDeprecated("Use getFileType() from functions.file.php");
    $dotposition = strrpos($filename, ".");
    if ($dotposition !== false) {
        return (strtolower(substr($filename, $dotposition +1)));
    } else {
        return false;
    }
}

/** @deprecated  [2012-06-20] Use cApiDbfs::isDbfs() */
function is_dbfs($file)
{
    cDeprecated("Use cApiDbfs::isDbfs()");
    return cApiDbfs::isDbfs($file);
}

?>