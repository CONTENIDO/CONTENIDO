<?php

/**
 * This file contains the sub navigation frame backend page in frontend group management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($_GET['idfrontendgroup'])) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$caption = i18n("Overview");
$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';
$areaName = 'frontendgroups_rights';

// Set template data
$tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
$tpl->set('d', 'DATA_NAME', $areaName);
$tpl->set('d', 'CLASS', '');
$tpl->set('d', 'OPTIONS', '');
$tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$area&frame=4&idfrontendgroup=$idfrontendgroup"), $caption));
$tpl->next();

if (is_array($cfg['plugins']['frontendlogic'])) {
    foreach ($cfg['plugins']['frontendlogic'] as $plugin) {
        cInclude('plugins', "frontendlogic/{$plugin}/{$plugin}.php");

        $className = 'frontendlogic_' . $plugin;

        if (class_exists($className)) {
            $class = new $className;

            $caption = $class->getFriendlyName();

            $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
            $tpl->set('d', 'DATA_NAME', $areaName);
            $tpl->set('d', 'CLASS', '');
            $tpl->set('d', 'OPTIONS', '');
            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$areaName&frame=4&useplugin=$plugin&idfrontendgroup=$idfrontendgroup"), $caption));
            $tpl->next();
        }
    }
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
