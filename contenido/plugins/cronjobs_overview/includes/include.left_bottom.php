<?php
/**
 * This file contains the left bottom frame backend page for the plugin cronjob overview.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

//Has the user permission for view the cronjobs
if (!$perm->have_perm_area_action($area, 'cronjob_overview')) {
    $notification->displayNotification('error', i18n('Permission denied', 'cronjobs_overview'));
    return -1;
}

// TODO: this should not be necessary
include_once(dirname(__FILE__).'/config.plugin.php');

$page = new cGuiPage('cronjobs_overview', 'cronjobs_overview');
$menu = new cGuiMenu();

$counter = 0;
$cronjobs = new Cronjobs();
foreach ($cronjobs->getAllCronjobs() as $row) {
    $counter++;

    $link = new cHTMLLink();
    $link->setClass('show_item')
        ->setLink('javascript:;')
        ->setAttribute('data-action', 'show_cronjob');

    $menu->setId($counter, $row);
    $menu->setTitle($counter, conHtmlSpecialChars($row));
    $menu->setLink($counter, $link);
    $menu->setImage($counter, $cfg['path']['images'] . 'article.gif');

    if ($_GET['file'] === $row) {
        $menu->setMarked($counter);
    }
}

$page->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');

$page->set('s', 'FORM', $menu->render(false));
$page->render();
