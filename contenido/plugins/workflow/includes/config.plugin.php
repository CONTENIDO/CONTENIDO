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

global $_cecRegistry, $lngAct, $modidartlang;

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

$_cecRegistry->addChainFunction('Contenido.ArticleCategoryList.ListItems', 'piworkflowCreateTasksFolder');
$_cecRegistry->addChainFunction('Contenido.ArticleList.Columns', 'piworkflowProcessArticleColumns');
$_cecRegistry->addChainFunction('Contenido.ArticleList.Actions', 'piworkflowProcessActions');
$_cecRegistry->addChainFunction('Contenido.ArticleList.RenderColumn', 'piworkflowRenderColumn');
$_cecRegistry->addChainFunction('Contenido.ArticleList.RenderAction', 'piworkflowRenderAction');
$_cecRegistry->addChainFunction('Contenido.CategoryList.Columns', 'piworkflowCategoryColumns');
$_cecRegistry->addChainFunction('Contenido.CategoryList.RenderColumn', 'piworkflowCategoryRenderColumn');
$_cecRegistry->addChainFunction('Contenido.Frontend.AllowEdit', 'piworkflowAllowArticleEdit');

function prepareWorkflowItems() {
    global $action, $lang, $modidcat, $workflowSelectBox, $workflowworkflows, $client, $tpl, $cfg;

    $workflowworkflows = new Workflows();

    if ($action === 'workflow_inherit_down') {
        $tmp = strDeeperCategoriesArray($modidcat);
        $asworkflow = getWorkflowForCat($modidcat);

        $wfa = new WorkflowAllocations();

        foreach ($tmp as $tmp_cat) {
            $idcatlang = getCatLang($tmp_cat, $lang);

            if ($asworkflow == 0) {
                $wfa->select("idcatlang = '$idcatlang'");

                if (($item = $wfa->next()) !== false) {
                    $wfa->delete($item->get("idallocation"));
                    // delete user sequences for listing in tasklist for each
                    // included article
                    $oArticles = new cArticleCollector(array(
                        'idcat' => $idcatlang,
                        'start' => true,
                        'offline' => true
                    ));
                    while (($oArticle = $oArticles->nextArticle()) !== false) {
                        setUserSequence($oArticle->getField('idartlang'), -1);
                    }
                }
            } else {
                $wfa->select("idcatlang = '$idcatlang'");

                if (($item = $wfa->next()) !== false) {
                    $item->setWorkflow($asworkflow);
                    $item->store();
                } else {
                    $wfa->create($asworkflow, $idcatlang);
                    // generate user sequences for listing in tasklist for each
                    // included article
                    $oArticles = new cArticleCollector(array(
                        'idcat' => $tmp_cat,
                        'start' => true,
                        'offline' => true
                    ));
                    while (($oArticle = $oArticles->nextArticle()) !== false) {
                        setUserSequence($oArticle->getField('idartlang'), $asworkflow);
                    }
                }
            }
        }
    }

    if ($action == "workflow_cat_assign") {
        $seltpl = "wfselect" . $modidcat;

        $wfa = new WorkflowAllocations();
        $idcatlang = getCatLang($modidcat, $lang);

        // associate workflow with category
        if ($GLOBALS[$seltpl] != 0) {
            $wfa->select("idcatlang = '$idcatlang'");
            if (($item = $wfa->next()) !== false) {
                $item->setWorkflow($GLOBALS[$seltpl]);
                $item->store();
            } else {
                $wfa->create($GLOBALS[$seltpl], $idcatlang);
            }

            // generate user sequences for listing in tasklist for each included
            // article
            $oArticles = new cArticleCollector(array(
                'idcat' => $modidcat,
                'start' => true,
                'offline' => true
            ));
            while (($oArticle = $oArticles->nextArticle()) !== false) {
                setUserSequence($oArticle->getField('idartlang'), $GLOBALS[$seltpl]);
            }
        } else {
            // unlink workflow with category
            $wfa->select("idcatlang = '$idcatlang'");

            if (($item = $wfa->next()) !== false) {
                $alloc = $item->get("idallocation");
            }
            $wfa->delete($alloc);

            // delete user sequences for listing in tasklist for each included
            // article
            $oArticles = new cArticleCollector(array(
                'idcat' => $modidcat,
                'start' => true,
                'offline' => true
            ));
            while (($oArticle = $oArticles->nextArticle()) !== false) {
                setUserSequence($oArticle->getField('idartlang'), -1);
            }
        }
    }

    $workflowSelectBox = new cHTMLSelectElement("foo");
    $workflowSelectBox->setClass("text_medium");
    $workflowworkflows->select("idclient = '$client' AND idlang = " . cSecurity::toInteger($lang));

    $workflowOption = new cHTMLOptionElement("--- " . i18n("None", "workflow") . " ---", 0);
    $workflowSelectBox->addOptionElement(0, $workflowOption);

    while (($workflow = $workflowworkflows->next()) !== false) {
        $wfa = new WorkflowItems();
        $wfa->select("idworkflow = '".$workflow->get("idworkflow")."'");

        if ($wfa->next() !== false) {
            $workflowOption = new cHTMLOptionElement($workflow->get("name"), $workflow->get("idworkflow"));
            $workflowSelectBox->addOptionElement($workflow->get("idworkflow"), $workflowOption);
        }
    }

    $workflowSelectBox->updateAttributes(array(
        "id" => "wfselect{IDCAT}"
    ));
    $workflowSelectBox->updateAttributes(array(
        "name" => "wfselect{IDCAT}"
    ));

    $tpl->set('s', 'PLUGIN_WORKFLOW', $workflowSelectBox->render() . '<a href="javascript:setWorkflow({IDCAT}, \\\'wfselect{IDCAT}\\\')"><img src="' . $cfg["path"]["images"] . 'submit.gif" class="spaced"></a>');
    $tpl->set('s', 'PLUGIN_WORKFLOW_TRANSLATION', i18n("Inherit workflow down", "workflow"));
}

function piworkflowCategoryRenderColumn($idcat, $type) {
    switch ($type) {
        case "workflow":
            $wfForCat = (int) getWorkflowForCat($idcat);
            $value = workflowInherit($idcat);
            $value .= <<<JS
            <script type="text/javascript" id="wf{$idcat}">
            (function(Con, $) {
                $(function() {
                    printWorkflowSelect({$idcat}, {$wfForCat});
                });
            })(Con, Con.$);
            </script>
JS;
            break;
    }

    return $value;
}

function piworkflowCategoryColumns($array) {
    prepareWorkflowItems();
    $myarray = array(
        "workflow" => i18n("Workflow", "workflow")
    );

    return ($myarray);
}

function piworkflowProcessActions($array) {
    global $idcat;

    $defaultidworkflow = getWorkflowForCat($idcat);
    if ($defaultidworkflow != 0) {
        $narray = array(
            "todo",
            "wfartconf",
            "wftplconf",
            "wfonline",
            "wflocked",
            "duplicate",
            "delete",
            "usetime"
        );
    } else {
        $narray = $array;
    }

    return $narray;
}

function piworkflowRenderAction($idcat, $idart, $idartlang, $type) {
    global $area, $frame, $idtpl, $cfg, $alttitle, $tmp_articletitle;
    global $tmp_artconf, $onlinelink, $lockedlink, $tplconf_link;

    $defaultidworkflow = getWorkflowForCat($idcat);

    $idusersequence = getCurrentUserSequence($idartlang, $defaultidworkflow);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($idusersequence);

    $currentEditor = $associatedUserSequence->get("iduser");
    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (isCurrentEditor($associatedUserSequence->get("iduser"))) {
        /* Query rights for this user */
        $wfRights = $workflowItem->getStepRights();
        $mayEdit = true;
    } else {
        $wfRights = "";
        $mayEdit = false;
    }

    switch ($type) {
        case "wfartconf":
            if ($wfRights["propertyedit"] == true) {
                return $tmp_artconf;
            }
            break;
        case "wfonline":
            if ($wfRights["publish"] == true) {
                return $onlinelink;
            }
            break;
        case "wflocked":
            if ($wfRights["lock"] == true) {
                return $lockedlink;
            }
            break;
        case "wftplconf":
            if ($wfRights["templateedit"] == true) {
                return $tplconf_link;
            }
            break;
        default:
            break;
    }

    return "";
}

function piworkflowProcessArticleColumns($array) {
    global $idcat, $action, $modidartlang;

    if ($action == "workflow_do_action") {
        $selectedAction = "wfselect" . $modidartlang;
        doWorkflowAction($modidartlang, $GLOBALS[$selectedAction]);
    }

    $defaultidworkflow = getWorkflowForCat($idcat);

    if ($defaultidworkflow != 0) {
        $narray = array();
        $bInserted = false;
        foreach ($array as $sKey => $sValue) {
            $narray[$sKey] = $sValue;
            if ($sKey == 'title' && !$bInserted) {
                $narray["wftitle"] = $array["title"];
                $narray["wfstep"] = i18n("Workflow Step", "workflow");
                $narray["wfaction"] = i18n("Workflow Action", "workflow");
                $narray["wfeditor"] = i18n("Workflow Editor", "workflow");
                $narray["wflaststatus"] = i18n("Last status", "workflow");
                $bInserted = true;
            }
        }
        unset($narray['title']);
        unset($narray['changeddate']);
        unset($narray['publisheddate']);
        unset($narray['sortorder']);
    } else {
        $narray = $array;
    }

    return ($narray);
}

function piworkflowAllowArticleEdit($idlang, $idcat, $idart, $user) {
    $defaultidworkflow = getWorkflowForCat($idcat);

    if ($defaultidworkflow == 0) {
        return true;
    }

    $idartlang = getArtLang($idart, $idlang);
    $idusersequence = getCurrentUserSequence($idartlang, $defaultidworkflow);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($idusersequence);

    $currentEditor = $associatedUserSequence->get("iduser");

    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (isCurrentEditor($associatedUserSequence->get("iduser"))) {
        $wfRights = $workflowItem->getStepRights();
        $mayEdit = true;
    } else {
        $wfRights = "";
        $mayEdit = false;
    }

    if ($wfRights["articleedit"] == true) {
        return true;
    } else {
        return false;
    }
}

function piworkflowRenderColumn($idcat, $idart, $idartlang, $column) {
    global $area, $frame, $idtpl, $cfg, $alttitle, $tmp_articletitle;
    $defaultidworkflow = getWorkflowForCat($idcat);

    $idusersequence = getCurrentUserSequence($idartlang, $defaultidworkflow);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($idusersequence);

    $currentEditor = $associatedUserSequence->get("iduser");

    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (isCurrentEditor($associatedUserSequence->get("iduser"))) {
        $wfRights = $workflowItem->getStepRights();
        $mayEdit = true;
    } else {
        $wfRights = "";
        $mayEdit = false;
    }

    switch ($column) {
        case "wftitle":
            if ($wfRights["articleedit"] == true) {
                $mtitle = $tmp_articletitle;
            } else {
                $mtitle = strip_tags($tmp_articletitle);
            }
            return ($mtitle);
        case "wfstep":
            if ($workflowItem === false) {
                return "nobody";
            }

            return ($workflowItem->get("position") . ".) " . $workflowItem->get("name"));
        case "wfeditor":
            $sEditor = getGroupOrUserName($currentEditor);
            if (!$sEditor) {
                $sEditor = "nobody";
            }
            return $sEditor;
        case "wfaction":
            $defaultidworkflow = getWorkflowForCat($idcat);
            $idusersequence = getCurrentUserSequence($idartlang, $defaultidworkflow);

            $sActionSelect = getActionSelect($idartlang, $idusersequence);
            if (!$sActionSelect) {
                $mayEdit = false;
            }

            $form = new cHTMLForm("wfaction" . $idartlang, "main.php", "get");
            $form->setVar("area", $area);
            $form->setVar("action", "workflow_do_action");
            $form->setVar("frame", $frame);
            $form->setVar("idcat", $idcat);
            $form->setVar("modidartlang", $idartlang);
            $form->setVar("idtpl", $idtpl);
            $form->appendContent('<table cellspacing="0" border="0"><tr><td>' . $sActionSelect . '</td><td>');
            $form->appendContent('<input type="image" src="' . $cfg["path"]["htmlpath"] . $cfg["path"]["images"] . "submit.gif" . '"></tr></table>');

            if ($mayEdit == true) {
                return ($form->render());
            } else {
                return '--- ' . i18n("None") . ' ---';
            }

        case "wflaststatus":
            $sStatus = getLastWorkflowStatus($idartlang);
            if (!$sStatus) {
                $sStatus = '--- ' . i18n("None") . ' ---';
            }
            return $sStatus;
    }
}

function piworkflowCreateTasksFolder() {
    global $sess, $cfg;

    $item = array();

    // Create workflow tasks folder
    $tmp_mstr = '<a href="javascript://" onclick="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

    $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=con_workflow&frame=4"), 'right_top', $sess->url("main.php?area=con_workflow&frame=3"), 'Workflow / Todo');

    $item["image"] = '<img alt="" src="' . cRegistry::getBackendUrl() . $cfg["path"]["plugins"] . 'workflow/images/workflow_erstellen.gif">';
    $item["title"] = $mstr;

    return ($item);
}
