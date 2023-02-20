<?php
/**
 * This file contains the workflow list.
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var array $cfg
 */

$requestIdWorkflow = cSecurity::toInteger($_GET['idworkflow'] ?? '0');

$page = new cGuiPage('workflow_list', 'workflow');
$page->addStyle('workflow.css');
$workflows = new Workflows();
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$delTitle = i18n('Delete workflow', 'workflow');

$page->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');

$ui = new cGuiMenu();
$workflows->select("idclient = '$client' AND idlang = '$lang'");

while (($workflow = $workflows->next()) !== false) {
    $wfid = cSecurity::toInteger($workflow->getField('idworkflow'));
    $wfname = preg_replace("/\"/", '', ($workflow->getField('name')));
    $wfdescription = preg_replace("/\"/", '', ($workflow->getField('description')));

    $ui->setId($wfid, $wfid);
    $ui->setTitle($wfid, $wfname);

    // Create the link to show/edit the workflow
    $link = new cHTMLLink();
    $link->setClass('show_item')
        ->setLink('javascript:void(0)')
        ->setAlt($wfdescription)
        ->setAttribute('data-action', 'workflow_show');
    $ui->setLink($wfid, $link);

    // Delete recipient
    $image = new cHTMLImage($cfg['path']['images'] . 'delete.gif', 'vAlignMiddle');
    $image->setAlt($delTitle);
    $delete = new cHTMLLink();
    $delete->setLink('javascript:void(0)')
        ->setAlt($delTitle)
        ->setAttribute('data-action', 'workflow_delete')
        ->setContent($image->render());
    $ui->setActions($wfid, 'delete', $delete->render());

    if ($wfid == $requestIdWorkflow) {
        $ui->setMarked($wfid);
    }
}

$page->set('s', 'DELETE_MESSAGE', i18n("Do you really want to delete the following workflow:<br><br>%s<br>", 'workflow'));
$page->set('s', 'CONTENT', $ui->render(0));

$page->render();
