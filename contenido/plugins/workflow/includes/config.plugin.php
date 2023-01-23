<?php
/**
 * This file contains the workflow allocation.
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

plugin_include('workflow', 'classes/class.workflow.php');
plugin_include('workflow', 'includes/functions.workflow.php');

global $cfg, $lngAct, $modidartlang;

// Plugin tables
$cfg['tab']['workflow'] = $cfg['sql']['sqlprefix'] . '_piwf_workflow';
$cfg['tab']['workflow_allocation'] = $cfg['sql']['sqlprefix'] . '_piwf_allocation';
$cfg['tab']['workflow_art_allocation'] = $cfg['sql']['sqlprefix'] . '_piwf_art_allocation';
$cfg['tab']['workflow_items'] = $cfg['sql']['sqlprefix'] . '_piwf_items';
$cfg['tab']['workflow_tasks'] = $cfg['sql']['sqlprefix'] . '_piwf_tasks';
$cfg['tab']['workflow_user_sequences'] = $cfg['sql']['sqlprefix'] . '_piwf_user_sequences';
$cfg['tab']['workflow_actions'] = $cfg['sql']['sqlprefix'] . '_piwf_actions';

// Plugin language strings (for menus and areas)
$lngAct['workflow']['workflow_delete'] = i18n('Delete workflow', 'workflow');
$lngAct['con_workflow']['workflow_task_user_select'] = i18n('Select workflow task', 'workflow');
$lngAct['workflow_common']['workflow_show'] = i18n('Show workflow', 'workflow');
$lngAct['workflow_common']['workflow_create'] = i18n('Create workflow', 'workflow');
$lngAct['workflow_common']['workflow_save'] = i18n('Edit workflow', 'workflow');
$lngAct['con']['workflow_do_action'] = i18n('Process workflow step', 'workflow');
$lngAct['con_workflow']['workflow_do_action'] = i18n('Process workflow step', 'workflow');
$lngAct['str']['workflow_inherit_down'] = i18n('Inherit workflow down', 'workflow');
$lngAct['workflow_steps']['workflow_step_edit'] = i18n('Edit workflow step', 'workflow');
$lngAct['workflow_steps']['workflow_step_up'] = i18n('Move workflowstep up', 'workflow');
$lngAct['workflow_steps']['workflow_step_down'] = i18n('Move workflowstep down', 'workflow');
$lngAct['workflow_steps']['workflow_save_step'] = i18n('Save Workflowstep', 'workflow');
$lngAct['workflow_steps']['workflow_create_step'] = i18n('Create workflowstep', 'workflow');
$lngAct['workflow_steps']['workflow_step_delete'] = i18n('Delete workflowstep', 'workflow');
$lngAct['workflow_steps']['workflow_user_up'] = i18n('Move workflowstepuser up', 'workflow');
$lngAct['workflow_steps']['workflow_user_down'] = i18n('Move workflowstepuser down', 'workflow');
$lngAct['workflow_steps']['workflow_create_user'] = i18n('Create Workflowstepuser', 'workflow');
$lngAct['workflow_steps']['workflow_user_delete'] = i18n('Delete Workflowstepuser', 'workflow');
$lngAct['str']['workflow_cat_assign'] = i18n('Associate workflow with category', 'workflow');

// Plugin class-loader configuration
$classesPath = 'contenido/plugins/workflow/';
cAutoload::addClassmapConfig([
    'Workflows' => $classesPath . 'classes/class.workflow.php',
    'Workflow' => $classesPath . 'classes/class.workflow.php',
    'WorkflowActions' => $classesPath . 'classes/class.workflowactions.php',
    'WorkflowAction' => $classesPath . 'classes/class.workflowactions.php',
    'WorkflowAllocations' => $classesPath . 'classes/class.workflowallocation.php',
    'WorkflowAllocation' => $classesPath . 'classes/class.workflowallocation.php',
    'WorkflowArtAllocations' => $classesPath . 'classes/class.workflowartallocation.php',
    'WorkflowArtAllocation' => $classesPath . 'classes/class.workflowartallocation.php',
    'WorkflowItems' => $classesPath . 'classes/class.workflowitems.php',
    'WorkflowItem' => $classesPath . 'classes/class.workflowitems.php',
    'WorkflowTasks' => $classesPath . 'classes/class.workflowtasks.php',
    'WorkflowTask' => $classesPath . 'classes/class.workflowtasks.php',
    'WorkflowUserSequences' => $classesPath . 'classes/class.workflowusersequence.php',
    'WorkflowUserSequence' => $classesPath . 'classes/class.workflowusersequence.php',
]);

$_cecRegistry = cApiCecRegistry::getInstance();
$_cecRegistry->addChainFunction('Contenido.ArticleCategoryList.ListItems', 'piworkflowCreateTasksFolder');
$_cecRegistry->addChainFunction('Contenido.ArticleList.Columns', 'piworkflowProcessArticleColumns');
$_cecRegistry->addChainFunction('Contenido.ArticleList.Actions', 'piworkflowProcessActions');
$_cecRegistry->addChainFunction('Contenido.ArticleList.RenderColumn', 'piworkflowRenderColumn');
$_cecRegistry->addChainFunction('Contenido.ArticleList.RenderAction', 'piworkflowRenderAction');
$_cecRegistry->addChainFunction('Contenido.CategoryList.Columns', 'piworkflowCategoryColumns');
$_cecRegistry->addChainFunction('Contenido.CategoryList.RenderColumn', 'piworkflowCategoryRenderColumn');
$_cecRegistry->addChainFunction('Contenido.Frontend.AllowEdit', 'piworkflowAllowArticleEdit');

