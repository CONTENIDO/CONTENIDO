<?php
/**
 * This file contains the Custom subnavigation for the newsletter recipient groups.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cTemplate $tpl
 * @var cSession $sess
 * @var array $cfg
 * @var string $area
 */

$requestIddRecipientGroup = cSecurity::toInteger($_GET['idrecipientgroup'] ?? '');

if ($requestIddRecipientGroup <= 0) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';
$caption = i18n("Overview", 'newsletter');
$areaName = 'foo2';

// Set template data
$tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
$tpl->set('d', 'DATA_NAME', $area);
$tpl->set('d', 'CLASS', '');
$tpl->set('d', 'OPTIONS', '');
$tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$area&frame=4&idrecipientgroup=$requestIddRecipientGroup"), $caption));
$tpl->next();

if (cHasPlugins('recipientslogic')) {
    cIncludePlugins('recipientslogic');
    foreach ($cfg['plugins']['recipientslogic'] as $plugin) {
        $className = 'recipientslogic_' . $plugin;
        $class = new $className();

        $caption = $class->getFriendlyName();
        $areaName = 'foo2';
        $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'DATA_NAME', 'recipientgroup_rights');
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=recipientgroup_rights&frame=4&useplugin=$plugin&idrecipientgroup=$requestIddRecipientGroup"), $caption));
        $tpl->next();
    }
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
