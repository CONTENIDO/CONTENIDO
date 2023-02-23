<?php
/**
 * This file contains the workflow allocation.
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage('workflow_left_top', 'workflow');
$page->addStyle('workflow.css');
$page->addBodyClassName('page_left_top');

$create = new cHTMLLink();
$create->setMultiLink('workflow', '', 'workflow_common', 'workflow_create');
// $create->setCLink('workflow_common',4,'workflow_create');
$create->setContent(i18n('Create workflow', 'workflow'));
$create->setCustom('idworkflow', '-1');
$create->updateAttributes(['class' => 'addfunction']);
$page->set('s', 'LINK', $create->render());
$page->render();
