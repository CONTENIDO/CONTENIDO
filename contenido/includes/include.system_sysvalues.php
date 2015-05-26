<?php

/**
 * This file contains the system variables backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Marco Jahn
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->reset();

// print out tmp_notifications if any action has been done
if (isset($tmp_notification)) {
    $tpl->set('s', 'TEMPNOTIFICATION', $tmp_notification);
} else {
    $tpl->set('s', 'TEMPNOTIFICATION', '');
}

// server configuration
$aChecks = array(
    1 => $cfg['path']['frontend'],
    2 => cRegistry::getBackendPath(),
    3 => $cfg['path']['all_wysiwyg'],
    4 => cRegistry::getBackendUrl(),
    5 => $cfg['path']['all_wysiwyg_html']
);

$aServerConfiguration = array(
    array(i18n('System environment'), CON_ENVIRONMENT),
    array(i18n('Host name'), $_SERVER['HTTP_HOST'], 0),
    array(i18n('CONTENIDO server path'), $cfg['path']['frontend'], 0),
    array(i18n('CONTENIDO backend path'), cRegistry::getBackendPath(), 1),
    array(i18n('CONTENIDO WYSIWYG repository path'), $cfg['path']['all_wysiwyg'], 2),
    array(i18n('CONTENIDO WYSIWYG editor path'), $cfg['path']['wysiwyg'], 3),
    array(i18n('CONTENIDO backend URL'),  cRegistry::getBackendUrl(), 0),
    array(i18n('CONTENIDO WYSIWYG repository URL'), $cfg['path']['all_wysiwyg_html'], 4),
    array(i18n('CONTENIDO WYSIWYG editor URL'), $cfg['path']['wysiwyg_html'], 5),
);

$oTpl2 = new cTemplate();
$oTpl2->set('s', 'HEADLINE', i18n('System configuration'));
foreach ($aServerConfiguration as $aConfData) {
    $sValue = $aConfData[1];

    if (isset($aChecks[$aConfData[2]])) {
        $sValue = str_replace(
            $aChecks[$aConfData[2]],
            '<span class="unhighlighted">' . $aChecks[$aConfData[2]] . '</span>',
            $sValue
        );
    }

    $oTpl2->set('d', 'NAME', $aConfData[0]);
    $oTpl2->set('d', 'VALUE', $sValue);
    $oTpl2->next();
}

$oTpl2->set('s', 'ADDITIONAL', '');

$sServerConfiguration = $oTpl2->generate($cfg['path']['templates'] . $cfg['templates']['system_variables_block'], 1);
$tpl->set('s', 'SERVER_CONFIGURATION', $sServerConfiguration);

// system statistics
$aSystemStatistics = array(
    array(i18n('Number of clients'), 'cApiClientCollection'),
    array(i18n('Number of languages'), 'cApiLanguageCollection'),
    array(i18n('Number of layouts'), 'cApiLayoutCollection'),
    array(i18n('Number of modules'), 'cApiModuleCollection'),
    array(i18n('Number of templates'), 'cApiTemplateCollection'),
    array(i18n('Number of articles'), 'cApiArticleCollection'),
    array(i18n('Number of categories'), 'cApiCategoryCollection'),
    array(i18n('Number of users'), 'cApiUserCollection'),
    array(i18n('Number of groups'), 'cApiGroupCollection'),
);

$oTpl2 = new cTemplate();
$oTpl2->set('s', 'HEADLINE', i18n('System statistics (all clients)'));
foreach ($aSystemStatistics as $aStatData) {
    $sCollName = $aStatData[1];
    $oColl = new $sCollName();
    $oColl->select();

    $oTpl2->set('d', 'NAME', $aStatData[0]);
    $oTpl2->set('d', 'VALUE', $oColl->count());
    $oTpl2->next();
}

$oTpl2->set('s', 'ADDITIONAL', '');

$sSystemStatistics = $oTpl2->generate($cfg['path']['templates'] . $cfg['templates']['system_variables_block'], 1);
$tpl->set('s', 'SYSTEM_STATISTICS', $sSystemStatistics);

// installed versions
$sql_server_info = $db->getServerInfo();

$aInstalledVersions = array(
    array(i18n('CONTENIDO version'), CON_VERSION),
    array(i18n('Server operating system'), $_SERVER['SERVER_SOFTWARE']),
    array(i18n('Installed PHP version'), phpversion()),
    array(i18n('Database server version'), $sql_server_info['description']),
    array(i18n('PHP database extension'), $cfg['database_extension'])
);

$oTpl2 = new cTemplate();
$oTpl2->set('s', 'HEADLINE', i18n('Installed versions'));
foreach ($aInstalledVersions as $aVersionInfo) {
    $oTpl2->set('d', 'NAME', $aVersionInfo[0]);
    $oTpl2->set('d', 'VALUE', $aVersionInfo[1]);
    $oTpl2->next();
}

$oTpl2->set('s', 'ADDITIONAL', '');

$sInstalledVersions = $oTpl2->generate($cfg['path']['templates'] . $cfg['templates']['system_variables_block'], 1);
$tpl->set('s', 'INSTALLED_VERSIONS', $sInstalledVersions);

// php configuration
$aPhpConfiguration = array(
    'date.timezone', 'include_path', 'memory_limit', 'upload_max_filesize', 'post_max_size',
    'max_execution_time', 'max_file_uploads', 'max_input_time',  'sql.safe_mode', 'disable_classes', 'disable_functions'
);

$oTpl2 = new cTemplate();
$oTpl2->set('s', 'HEADLINE', i18n('PHP configuration'));
foreach ($aPhpConfiguration as $sConfigName) {
    $sValue = ini_get($sConfigName);

    if ($sConfigName == 'disable_classes' || $sConfigName == 'disable_functions') {
        if ($sValue == '') {
            $sValue = '<span class="settingFine">' . i18n('nothing disabled') . "</span>";
        } else {
            $sValue = '<span class="settingWrong">' . str_replace(',', ', ', $sValue) . "</span>";
        }
    }

    if ($sConfigName == 'sql.safe_mode') {
        if ($sValue == 1) {
            $sValue = '<span class="settingWrong">' . i18n('activated') . "</span>";
        } else {
            $sValue = '<span class="settingFine">' . i18n('deactivated') . "</span>";
        }
    }

    $oTpl2->set('d', 'NAME', $sConfigName);
    $oTpl2->set('d', 'VALUE', $sValue);
    $oTpl2->next();
}

$extensions = get_loaded_extensions();
sort($extensions);
$oTpl2->set('s', 'ADDITIONAL', '<tr><td colspan="2"><b>' . i18n('Loaded extensions') . ':</b><br>' . implode(', ', $extensions) . '</td></tr>');

$sPhpConfig = $oTpl2->generate($cfg['path']['templates'] . $cfg['templates']['system_variables_block'], 1);
$tpl->set('s', 'PHP_CONFIGURATION', $sPhpConfig);

// clients
$oClientColl = new cApiClientCollection();
$oClientColl->select();
$sClients = '';

while ($oItem = $oClientColl->next()) {
    $iIdClient = $oItem->get('idclient');

    if (systemHavePerm($iIdClient)) {
        $htmlpath = $cfgClient[$iIdClient]['path']['htmlpath'];
        $frontendpath = $cfgClient[$iIdClient]['path']['frontend'];

        $oTpl2 = new cTemplate();
        $oTpl2->set('s', 'HEADLINE', i18n('Client') . ' ' . $oItem->get('name') . ' (' . $oItem->get('idclient') . ')');
        $oTpl2->set('s', 'ADDITIONAL', '');

        $oTpl2->set('d', 'NAME', i18n("HTML path"));
        $oTpl2->set('d', 'VALUE', $htmlpath);
        $oTpl2->next();

        $oTpl2->set('d', 'NAME', i18n("Frontend path"));
        $oTpl2->set('d', 'VALUE', $frontendpath);
        $oTpl2->next();

        $oClientLanguageColl = new cApiClientLanguageCollection();
        $oClientLanguageColl->setWhere('idclient', $iIdClient);
        $oClientLanguageColl->query();

        $aLanguages = array();

        if ($oClientLanguageColl->count() > 0) {
            while ($oClientLang = $oClientLanguageColl->next()) {
                $iIdLang = $oClientLang->get('idlang');

                $oLang = new cApiLanguage($iIdLang);
                $aLanguages[$iIdLang] = $oLang->get('name') . ' (' . $iIdLang . ', ' . $oLang->get('encoding') . ')';
            }

            $sLanguages = implode(', ', $aLanguages);
        } else {
            $sLanguages = i18n('No languages were found for this client');
        }

        $oTpl2->set('d', 'NAME', i18n("language(s)"));
        $oTpl2->set('d', 'VALUE', $sLanguages);
        $oTpl2->next();

        $sClients .= $oTpl2->generate($cfg['path']['templates'] . $cfg['templates']['system_variables_block'], 1) . '<br>';
    }
}

$tpl->set('s', 'CLIENTS', $sClients);


$oTpl2 = new cTemplate();
$oTpl2->set('s', 'HEADLINE', i18n('Database configuration'));
$oTpl2->set("s", "ADDITIONAL", "");

$readableName = array(
    "host" => i18n("Host"),
    "database" => i18n("Database"),
    "user" => i18n("User"),
    "charset" => i18n("Charset")
);

foreach($cfg['db']['connection'] as $key => $value) {
    if($key == "password") { // Skip password
        continue;
    }
    $oTpl2->set("d", "NAME", $readableName[$key]);
    $oTpl2->set("d", "VALUE", $value);
    $oTpl2->next();
}

$tpl->set('s', 'DATABASE_CONFIGURATION', $oTpl2->generate($cfg['path']['templates'] . $cfg['templates']['system_variables_block'], true));


// parse out template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['system_variables']);

?>