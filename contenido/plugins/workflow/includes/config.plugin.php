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

global $cfg, $lngAct, $modidartlang;

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// Plugin tables
$cfg['tab']['workflow'] = $cfg['sql']['sqlprefix'] . '_piwf_workflow';
$cfg['tab']['workflow_allocation'] = $cfg['sql']['sqlprefix'] . '_piwf_allocation';
$cfg['tab']['workflow_art_allocation'] = $cfg['sql']['sqlprefix'] . '_piwf_art_allocation';
$cfg['tab']['workflow_items'] = $cfg['sql']['sqlprefix'] . '_piwf_items';
$cfg['tab']['workflow_tasks'] = $cfg['sql']['sqlprefix'] . '_piwf_tasks';
$cfg['tab']['workflow_user_sequences'] = $cfg['sql']['sqlprefix'] . '_piwf_user_sequences';
$cfg['tab']['workflow_actions'] = $cfg['sql']['sqlprefix'] . '_piwf_actions';

// Plugin language strings (for menus and areas)
$lngAct['workflow']['workflow_delete'] = i18n('Delete workflow', $pluginName);
$lngAct['con_workflow']['workflow_task_user_select'] = i18n('Select workflow task', $pluginName);
$lngAct['workflow_common']['workflow_show'] = i18n('Show workflow', $pluginName);
$lngAct['workflow_common']['workflow_create'] = i18n('Create workflow', $pluginName);
$lngAct['workflow_common']['workflow_save'] = i18n('Edit workflow', $pluginName);
$lngAct['con']['workflow_do_action'] = i18n('Process workflow step', $pluginName);
$lngAct['con_workflow']['workflow_do_action'] = i18n('Process workflow step', $pluginName);
$lngAct['str']['workflow_inherit_down'] = i18n('Inherit workflow down', $pluginName);
$lngAct['workflow_steps']['workflow_step_edit'] = i18n('Edit workflow step', $pluginName);
$lngAct['workflow_steps']['workflow_step_up'] = i18n('Move workflowstep up', $pluginName);
$lngAct['workflow_steps']['workflow_step_down'] = i18n('Move workflowstep down', $pluginName);
$lngAct['workflow_steps']['workflow_save_step'] = i18n('Save Workflowstep', $pluginName);
$lngAct['workflow_steps']['workflow_create_step'] = i18n('Create workflowstep', $pluginName);
$lngAct['workflow_steps']['workflow_step_delete'] = i18n('Delete workflowstep', $pluginName);
$lngAct['workflow_steps']['workflow_user_up'] = i18n('Move workflowstepuser up', $pluginName);
$lngAct['workflow_steps']['workflow_user_down'] = i18n('Move workflowstepuser down', $pluginName);
$lngAct['workflow_steps']['workflow_create_user'] = i18n('Create Workflowstepuser', $pluginName);
$lngAct['workflow_steps']['workflow_user_delete'] = i18n('Delete Workflowstepuser', $pluginName);
$lngAct['str']['workflow_cat_assign'] = i18n('Associate workflow with category', $pluginName);

// Plugin class-loader configuration
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'Workflows' => $pluginClassesPath . '/class.workflow.php',
    'Workflow' => $pluginClassesPath . '/class.workflow.php',
    'WorkflowActions' => $pluginClassesPath . '/class.workflowactions.php',
    'WorkflowAction' => $pluginClassesPath . '/class.workflowactions.php',
    'WorkflowAllocations' => $pluginClassesPath . '/class.workflowallocation.php',
    'WorkflowAllocation' => $pluginClassesPath . '/class.workflowallocation.php',
    'WorkflowArtAllocations' => $pluginClassesPath . '/class.workflowartallocation.php',
    'WorkflowArtAllocation' => $pluginClassesPath . '/class.workflowartallocation.php',
    'WorkflowItems' => $pluginClassesPath . '/class.workflowitems.php',
    'WorkflowItem' => $pluginClassesPath . '/class.workflowitems.php',
    'WorkflowTasks' => $pluginClassesPath . '/class.workflowtasks.php',
    'WorkflowTask' => $pluginClassesPath . '/class.workflowtasks.php',
    'WorkflowUserSequences' => $pluginClassesPath . '/class.workflowusersequence.php',
    'WorkflowUserSequence' => $pluginClassesPath . '/class.workflowusersequence.php',
]);
plugin_include($pluginName, 'includes/functions.workflow.php');

$_cecRegistry = cApiCecRegistry::getInstance();
$_cecRegistry->addChainFunction('Contenido.ArticleCategoryList.ListItems', 'piworkflowCreateTasksFolder');
$_cecRegistry->addChainFunction('Contenido.ArticleList.Columns', 'piworkflowProcessArticleColumns');
$_cecRegistry->addChainFunction('Contenido.ArticleList.Actions', 'piworkflowProcessActions');
$_cecRegistry->addChainFunction('Contenido.ArticleList.RenderColumn', 'piworkflowRenderColumn');
$_cecRegistry->addChainFunction('Contenido.ArticleList.RenderAction', 'piworkflowRenderAction');
$_cecRegistry->addChainFunction('Contenido.CategoryList.Columns', 'piworkflowCategoryColumns');
$_cecRegistry->addChainFunction('Contenido.CategoryList.RenderColumn', 'piworkflowCategoryRenderColumn');
$_cecRegistry->addChainFunction('Contenido.Frontend.AllowEdit', 'piworkflowAllowArticleEdit');

unset($pluginName, $pluginClassesPath);
