<?php

/**
 * This file contains the workflow functions.
 *
 * @package    Plugin
 * @subpackage Workflow
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');

/**
 * @param int|string $listId
 * @param int|string $default
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_getUsers($listId, $default): string
{
    $cfg = cRegistry::getConfig();
    $auth = cRegistry::getAuth();

    $userColl = new cApiUserCollection();
    $users = $userColl->getAccessibleUsers($auth->getPermsArray());
    $groupColl = new cApiGroupCollection();
    $groups = $groupColl->getAccessibleGroups($auth->getPermsArray());

    $tpl2 = new cTemplate();
    $tpl2->set('s', 'NAME', 'user' . $listId);
    $tpl2->set('s', 'CLASS', 'text_small');
    $tpl2->set('s', 'OPTIONS', 'size=1');

    $tpl2->set('d', 'VALUE', 0);
    $tpl2->set('d', 'CAPTION', '--- ' . i18n("None", "workflow") . ' ---');
    if ($default == 0) {
        $tpl2->set('d', 'SELECTED', 'SELECTED');
    } else {
        $tpl2->set('d', 'SELECTED', '');
    }
    $tpl2->next();

    foreach ($users as $key => $value) {
        $tpl2->set('d', 'VALUE', $key);
        $tpl2->set('d', 'CAPTION', $value['realname'] . " (" . $value['username'] . ")");

        if ($default == $key) {
            $tpl2->set('d', 'SELECTED', 'SELECTED');
        } else {
            $tpl2->set('d', 'SELECTED', '');
        }

        $tpl2->next();
    }

    $tpl2->set('d', 'VALUE', '0');
    $tpl2->set('d', 'CAPTION', '------------------------------------');
    $tpl2->set('d', 'SELECTED', 'disabled');
    $tpl2->next();

    foreach ($groups as $key => $value) {
        $tpl2->set('d', 'VALUE', $key);
        $tpl2->set('d', 'CAPTION', $value['groupname']);

        if ($default == $key) {
            $tpl2->set('d', 'SELECTED', 'SELECTED');
        } else {
            $tpl2->set('d', 'SELECTED', '');
        }

        $tpl2->next();
    }

    return $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
}

/**
 * @param string $userId
 * @throws cDbException|cException
 */
function piwf_isCurrentEditor($userId): bool
{
    $auth = cRegistry::getAuth();

    // Check if the UID is a group. If yes, check if we are in it
    $user = new cApiUser();
    if (!$user->loadByPrimaryKey($userId)) {
        $db2 = cRegistry::getDb();

        // Yes, it's a group. Let's try to load the group members!
        $sql = "SELECT `user_id` FROM `%s` WHERE `group_id` = '%s'";
        $db2->query($sql, cDb::getTableName('groupmembers'), $userId);
        while ($db2->nextRecord()) {
            if ($db2->f('user_id') == $auth->getUserId()) {
                return true;
            }
        }
    } else {
        if ($userId == $auth->getUserId()) {
            return true;
        }
    }

    return false;
}

/**
 * @param int $articleLanguageId
 * @param int $userSequenceId
 * @return bool|string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_getActionSelect($articleLanguageId, $userSequenceId)
{
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);
    $userSequenceId = cSecurity::toInteger($userSequenceId);

    $cfg = cRegistry::getConfig();

    $workflowActions = new WorkflowActions();
    $allActions = $workflowActions->getAvailableWorkflowActions();

    $wfSelect = new cTemplate();
    $wfSelect->set('s', 'NAME', 'wfselect' . $articleLanguageId);
    $wfSelect->set('s', 'CLASS', 'text_medium');

    $userSequence = new WorkflowUserSequence();
    $userSequence->loadByPrimaryKey($userSequenceId);

    $workflowItem = $userSequence->getWorkflowItem();

    if ($workflowItem === false) {
        return false;
    }

    $wfRights = $workflowItem->getStepRights();

    $lastStep = 0;
    $artAllocation = new WorkflowArtAllocations();
    $artAllocation->select("idartlang = '$articleLanguageId'");
    if (($obj = $artAllocation->next()) !== false) {
        $lastStep = cSecurity::toInteger($obj->get('lastusersequence'));
    }

    $bExistOption = false;
    if ($lastStep != $userSequenceId) {
        $wfSelect->set('d', 'VALUE', 'next');
        $wfSelect->set('d', 'CAPTION', i18n("Confirm", "workflow"));
        $wfSelect->set('d', 'SELECTED', 'SELECTED');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($wfRights['last']) {
        $wfSelect->set('d', 'VALUE', 'last');
        $wfSelect->set('d', 'CAPTION', i18n("Back to last editor", "workflow"));
        $wfSelect->set('d', 'SELECTED', '');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($wfRights['reject']) {
        $wfSelect->set('d', 'VALUE', 'reject');
        $wfSelect->set('d', 'CAPTION', i18n("Reject article", "workflow"));
        $wfSelect->set('d', 'SELECTED', '');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($wfRights['revise']) {
        $wfSelect->set('d', 'VALUE', 'revise');
        $wfSelect->set('d', 'CAPTION', i18n("Revise article", "workflow"));
        $wfSelect->set('d', 'SELECTED', '');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($bExistOption)
        return $wfSelect->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
    else {
        return false;
    }
}

/**
 * function for inserting todos in wokflow_art_allocation used, when a workflow
 * is associated with a category in content->category
 *
 * @param int $articleLanguageId
 * @param int $defaultWorkflowId
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_setUserSequence($articleLanguageId, $defaultWorkflowId): bool
{
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);
    $defaultWorkflowId = cSecurity::toInteger($defaultWorkflowId);

    $wfaa = new WorkflowArtAllocations();
    $wfaa->select("idartlang = $articleLanguageId");

    if (($associatedUserSequence = $wfaa->next()) !== false) {
        $idartallocation = $associatedUserSequence->get('idartallocation');
        $wfaa->delete($idartallocation);
    }

    if ($defaultWorkflowId > 0) {
        $newObj = $wfaa->create($articleLanguageId);
        if (!$newObj) {
            return false;
        }

        // Get the first idusersequence for the new item
        $workflowItems = new WorkflowItems();
        $workflowItems->select("idworkflow = $defaultWorkflowId AND position = 1");
        $firstitem = 0;
        if (($obj = $workflowItems->next()) !== false) {
            $firstitem = cSecurity::toInteger($obj->get('idworkflowitem'));
        }

        $workflowUserSequences = new WorkflowUserSequences();
        $workflowUserSequences->select("idworkflowitem = $firstitem AND position = 1'");

        if (($obj = $workflowUserSequences->next()) !== false) {
            $firstIDUserSequence = $obj->get('idusersequence');
        }

        $newObj->set('idusersequence', $firstIDUserSequence);
        $newObj->store();

        return true;
    }

    return false;
}

/**
 * Returns current user sequence, either from workflow article allocations or
 * from workflow user sequences.
 *
 * @param int $articleLanguageId Article language id
 * @param int $defaultWorkflowId Default workflow id
 * @return int|false of found user sequence or false
 * @throws cDbException|cException
 */
function piwf_getCurrentUserSequence($articleLanguageId, $defaultWorkflowId)
{
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);
    $defaultWorkflowId = cSecurity::toInteger($defaultWorkflowId);

    $wfaa = new WorkflowArtAllocations();
    $wfaa->select("idartlang = $articleLanguageId");
    $userSequenceId = 0;

    if (($associatedUserSequence = $wfaa->next()) !== false) {
        $userSequenceId = $associatedUserSequence->get('idusersequence');
    }

    if ($userSequenceId == 0) {
        if ($associatedUserSequence != false) {
            $newObj = $associatedUserSequence;
        } else {
            $newObj = $wfaa->create($articleLanguageId);

            if (!$newObj) {
                return false;
            }
        }

        // Get the first idusersequence for the new item
        $workflowItems = new WorkflowItems();
        $workflowItems->select("idworkflow = $defaultWorkflowId AND position = 1");
        $firstitem = 0;
        if (($obj = $workflowItems->next()) !== false) {
            $firstitem = $obj->get('idworkflowitem');
        }

        $workflowUserSequences = new WorkflowUserSequences();
        $workflowUserSequences->select("idworkflowitem = $firstitem AND position = 1");

        if (($obj = $workflowUserSequences->next()) !== false) {
            $firstIDUserSequence = $obj->get('idusersequence');
        }

        $newObj->set('idusersequence', $firstIDUserSequence);
        $newObj->store();

        $userSequenceId = $newObj->get('idusersequence');
    }

    return $userSequenceId;
}

/**
 * @param int $articleLanguageId
 * @return bool|string
 * @throws cDbException|cException
 */
function piwf_getLastWorkflowStatus($articleLanguageId)
{
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);

    $wfaa = new WorkflowArtAllocations();
    $wfaa->select("idartlang = $articleLanguageId");
    if (($associatedUserSequence = $wfaa->next()) !== false) {
        $laststatus = $associatedUserSequence->get('laststatus');
    } else {
        return false;
    }

    switch ($laststatus) {
        case "reject":
            return (i18n("Rejected", "workflow"));
        case "revise":
            return (i18n("Revised", "workflow"));
        case "last":
            return (i18n("Last", "workflow"));
        case "confirm":
            return (i18n("Confirmed", "workflow"));
        default:
            return (i18n("None", "workflow"));
    }
}

/**
 * @param int $articleLanguageId
 * @param string $action
 * @throws cDbException|cException
 */
function piwf_doWorkflowAction($articleLanguageId, $action)
{
    $idcat = cRegistry::getCategoryId();
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);

    switch ($action) {
        case "last":
            $artAllocations = new WorkflowArtAllocations();
            $artAllocations->select("idartlang = {$articleLanguageId}");

            if (($obj = $artAllocations->next()) !== false) {
                $usersequence = new WorkflowUserSequence();
                $usersequence->loadByPrimaryKey($obj->get('idusersequence'));

                $workflowitem = $usersequence->getWorkflowItem();

                $idworkflow = cSecurity::toInteger($workflowitem->get('idworkflow'));
                $newpos = cSecurity::toInteger($workflowitem->get('position') - 1);
                if ($newpos < 1) {
                    $newpos = 1;
                }

                $workflowitems = new WorkflowItems();
                $workflowitems->select("idworkflow = $idworkflow AND position = " . $newpos);

                if (($nextObj = $workflowitems->next()) !== false) {
                    $userSequences = new WorkflowUserSequences();
                    $workflowItemId = cSecurity::toInteger($nextObj->get('idworkflowitem'));
                    $userSequences->select("idworkflowitem = $workflowItemId");

                    if (($nextSeqObj = $userSequences->next()) !== false) {
                        $obj->set('lastusersequence', $obj->get('idusersequence'));
                        $obj->set('idusersequence', $nextSeqObj->get('idusersequence'));
                        $obj->set('laststatus', "last");
                        $obj->store();
                    }
                }
            }
            break;
        case "next":
            $artAllocations = new WorkflowArtAllocations();
            $artAllocations->select("idartlang = {$articleLanguageId}");

            if (($obj = $artAllocations->next()) !== false) {
                $usersequence = new WorkflowUserSequence();
                $usersequence->loadByPrimaryKey($obj->get('idusersequence'));

                $workflowitem = $usersequence->getWorkflowItem();

                $idworkflow = cSecurity::toInteger($workflowitem->get('idworkflow'));
                $newpos = cSecurity::toInteger($workflowitem->get('position') + 1);

                $workflowitems = new WorkflowItems();
                $workflowitems->select("idworkflow = $idworkflow AND position = " . $newpos);

                if (($nextObj = $workflowitems->next()) !== false) {
                    $userSequences = new WorkflowUserSequences();
                    $workflowItemId = cSecurity::toInteger($nextObj->get('idworkflowitem'));
                    $userSequences->select("idworkflowitem = $workflowItemId");

                    if (($nextSeqObj = $userSequences->next()) !== false) {
                        $obj->set('lastusersequence', '10');
                        $obj->set('idusersequence', $nextSeqObj->get('idusersequence'));
                        $obj->set('laststatus', "confirm");
                        $obj->store();
                    }
                } else {
                    $workflowitems->select("idworkflow = $idworkflow AND position = " . (int)$workflowitem->get('position'));
                    if (($nextObj = $workflowitems->next()) !== false) {
                        $userSequences = new WorkflowUserSequences();
                        $workflowItemId = cSecurity::toInteger($nextObj->get('idworkflowitem'));
                        $userSequences->select("idworkflowitem = $workflowItemId");

                        if (($nextSeqObj = $userSequences->next()) !== false) {
                            $obj->set('lastusersequence', $obj->get('idusersequence'));
                            $obj->set('idusersequence', $nextSeqObj->get('idusersequence'));
                            $obj->set('laststatus', "confirm");
                            $obj->store();
                        }
                    }
                }
            }
            break;
        case "reject":
            $artAllocations = new WorkflowArtAllocations();
            $artAllocations->select("idartlang = {$articleLanguageId}");

            if (($obj = $artAllocations->next()) !== false) {
                $usersequence = new WorkflowUserSequence();
                $usersequence->loadByPrimaryKey($obj->get('idusersequence'));

                $workflowitem = $usersequence->getWorkflowItem();

                $idworkflow = cSecurity::toInteger($workflowitem->get('idworkflow'));
                $newpos = 1;

                $workflowitems = new WorkflowItems();
                $workflowitems->select("idworkflow = $idworkflow AND position = " . $newpos);

                if (($nextObj = $workflowitems->next()) !== false) {
                    $userSequences = new WorkflowUserSequences();
                    $workflowItemId = cSecurity::toInteger($nextObj->get('idworkflowitem'));
                    $userSequences->select("idworkflowitem = $workflowItemId");

                    if (($nextSeqObj = $userSequences->next()) !== false) {
                        $obj->set('lastusersequence', $obj->get('idusersequence'));
                        $obj->set('idusersequence', $nextSeqObj->get('idusersequence'));
                        $obj->set('laststatus', "reject");
                        $obj->store();
                    }
                }
            }
            break;

        case "revise":
            $db = cRegistry::getDb();
            $sql = "SELECT `idart`, `idlang` FROM `%s` WHERE `idartlang` = %d";
            $db->query($sql, cDb::getTableName('art_lang'), $articleLanguageId);
            $db->nextRecord();
            $articleId = $db->f('idart');
            $languageId = $db->f('idlang');

            $newidart = conCopyArticle($articleId, $idcat, "foo");

            break;
        default:
    }
}

/**
 * @param int $usersequence
 * @return bool|mixed
 * @throws cDbException|cException
 */
function piwf_getWorkflowForUserSequence($usersequence)
{
    $usersequence = cSecurity::toInteger($usersequence);
    $usersequences = new WorkflowUserSequences();
    $usersequences->select("idusersequence = $usersequence");

    if (($obj = $usersequences->next()) !== false) {
        $workflowItemId = cSecurity::toInteger($obj->get('idworkflowitem'));
        $workflowitems = new WorkflowItems();
        $workflowitems->select("idworkflowitem = '$workflowItemId'");
        if (($obj = $workflowitems->next()) !== false) {
            return $obj->get('idworkflow');
        }
    }

    return false;
}

/**
 * @param int|string $listId
 * @param int|string $default
 * @param int $categoryId
 */
function piwf_workflowSelect($listId, $default, $categoryId): string
{
    global $workflowSelectBox;

    $cfg = cRegistry::getConfig();

    $oSelectBox = new cHTMLSelectElement('workflow');
    $oSelectBox = $workflowSelectBox;

    $default = cSecurity::toInteger($default);
    $workflowSelectBox->updateAttributes([
        "id" => "wfselect" . $categoryId
    ]);
    $workflowSelectBox->updateAttributes([
        "name" => "wfselect" . $categoryId
    ]);
    $workflowSelectBox->setDefault($default);

    $sButton = '<a href="javascript:setWorkflow(' . $categoryId . ', \'' . "wfselect" . $categoryId . '\')"><img src="' . $cfg['path']['images'] . 'submit.gif" alt="" class="spaced"></a>';

    return $workflowSelectBox->render() . $sButton;
}

/**
 * @param int $categoryId
 */
function piwf_workflowInherit($categoryId): string
{
    $categoryId = cSecurity::toInteger($categoryId);
    $cfg = cRegistry::getConfig();
    $frame = cRegistry::getFrame();
    $area = cRegistry::getArea();
    $sess = cRegistry::getSession();

    $sUrl = $sess->url("main.php?area=$area&frame=$frame&modidcat=$categoryId&action=workflow_inherit_down");
    return '<a class="con_img_button mgr5" href="' . $sUrl . '"><img src="' . $cfg['path']['images'] . 'pfeil_runter.gif" alt="" title="' . i18n("Inherit workflow to sub-categories", "workflow") . '"></a>';
}


/**
 * @param int $categoryId
 * @throws cDbException|cException
 */
function piwf_getWorkflowForCat($categoryId): int
{
    $categoryId = cSecurity::toInteger($categoryId);
    $lang = cRegistry::getLanguageId();

    $idcatlang = piwf_getCatLang($categoryId, $lang);
    if (!$idcatlang) {
        return 0;
    }
    $workflows = new WorkflowAllocations();
    $workflows->select('idcatlang = ' . $idcatlang);
    if (($obj = $workflows->next()) !== false) {
        // Sanity: Check if the workflow still exists
        $workflow = new Workflow();
        $res = $workflow->loadByPrimaryKey($obj->get('idworkflow'));
        return $res ? cSecurity::toInteger($obj->get('idworkflow')) : 0;
    }

    return 0;
}

/**
 * @param $categoryId
 * @param $languageId
 * @throws cDbException
 */
function piwf_getCatLang($categoryId, $languageId): int
{
    $categoryId = cSecurity::toInteger($categoryId);
    $languageId = cSecurity::toInteger($languageId);
    // Get the idcatlang
    $oCatLangColl = new cApiCategoryLanguageCollection();
    $aIds = $oCatLangColl->getIdsByWhereClause('idlang = ' . $languageId . ' AND idcat = ' . $categoryId);

    return (count($aIds) > 0) ? cSecurity::toInteger($aIds[0]) : 0;
}


/**
 * Returns the template (workflow select and button) to add to the category overview table.
 *
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_prepareWorkflowItems(): string
{
    global $modidcat, $workflowSelectBox, $workflowworkflows, $tpl;

    $action = cRegistry::getAction();
    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $cfg = cRegistry::getConfig();

    $workflowworkflows = new Workflows();

    if ($action === 'workflow_inherit_down') {
        $tmp = strDeeperCategoriesArray($modidcat);
        $asworkflow = piwf_getWorkflowForCat($modidcat);

        $wfa = new WorkflowAllocations();

        foreach ($tmp as $tmp_cat) {
            $idcatlang = piwf_getCatLang($tmp_cat, $lang);

            if ($asworkflow == 0) {
                $wfa->select("idcatlang = $idcatlang");

                if (($item = $wfa->next()) !== false) {
                    $wfa->delete($item->get('idallocation'));
                    // delete user sequences for listing in tasklist for each
                    // included article
                    $oArticles = new cArticleCollector([
                        'idcat' => $idcatlang,
                        'start' => true,
                        'offline' => true
                    ]);
                    while ($oArticle = $oArticles->nextArticle()) {
                        piwf_setUserSequence($oArticle->getField('idartlang'), -1);
                    }
                }
            } else {
                $wfa->select("idcatlang = $idcatlang");

                if (($item = $wfa->next()) !== false) {
                    $item->setWorkflow($asworkflow);
                    $item->store();
                } else {
                    $wfa->create($asworkflow, $idcatlang);
                    // generate user sequences for listing in tasklist for each
                    // included article
                    $oArticles = new cArticleCollector([
                        'idcat' => $tmp_cat,
                        'start' => true,
                        'offline' => true
                    ]);
                    while ($oArticle = $oArticles->nextArticle()) {
                        piwf_setUserSequence($oArticle->getField('idartlang'), $asworkflow);
                    }
                }
            }
        }
    }

    if ($action === 'workflow_cat_assign') {
        $seltpl = 'wfselect' . $modidcat;

        $wfa = new WorkflowAllocations();
        $idcatlang = piwf_getCatLang($modidcat, $lang);

        // associate workflow with category
        if (isset($GLOBALS[$seltpl]) && $GLOBALS[$seltpl] != 0) {
            $wfa->select("idcatlang = $idcatlang");
            if (($item = $wfa->next()) !== false) {
                $item->setWorkflow($GLOBALS[$seltpl]);
                $item->store();
            } else {
                $wfa->create($GLOBALS[$seltpl], $idcatlang);
            }

            // generate user sequences for listing in tasklist for each included
            // article
            $oArticles = new cArticleCollector([
                'idcat' => $modidcat,
                'start' => true,
                'offline' => true
            ]);
            while ($oArticle = $oArticles->nextArticle()) {
                piwf_setUserSequence($oArticle->getField('idartlang'), $GLOBALS[$seltpl]);
            }
        } else {
            // unlink workflow with category
            $wfa->select("idcatlang = $idcatlang");
            if (($item = $wfa->next()) !== false) {
                $alloc = $item->get('idallocation');
                $wfa->delete($alloc);
            }

            // delete user sequences for listing in tasklist for each included
            // article
            $oArticles = new cArticleCollector([
                'idcat' => $modidcat,
                'start' => true,
                'offline' => true
            ]);
            while ($oArticle = $oArticles->nextArticle()) {
                piwf_setUserSequence($oArticle->getField('idartlang'), -1);
            }
        }
    }

    $workflowSelectBox = new cHTMLSelectElement('foo');
    $workflowSelectBox->setClass('text_medium');
    $workflowworkflows->select("idclient = $client AND idlang = " . cSecurity::toInteger($lang));

    $workflowOption = new cHTMLOptionElement("--- " . i18n("None", "workflow") . " ---", '0');
    $workflowSelectBox->addOptionElement(0, $workflowOption);

    while ($workflow = $workflowworkflows->next()) {
        $idWorkflow = cSecurity::toInteger($workflow->get('idworkflow'));
        $wfa = new WorkflowItems();
        $wfa->select("idworkflow = " . $idWorkflow);

        if ($wfa->next() !== false) {
            $workflowOption = new cHTMLOptionElement($workflow->get('name'), $idWorkflow);
            $workflowSelectBox->addOptionElement($idWorkflow, $workflowOption);
        }
    }

    $workflowSelectBox->updateAttributes([
        'id' => 'wfselect{IDCAT}'
    ]);
    $workflowSelectBox->updateAttributes([
        'name' => 'wfselect{IDCAT}'
    ]);

    return $workflowSelectBox->render()
        . '<a class="con_img_button mgl5" href="javascript:void(0)" data-action-workflow="set_workflow"><img src="' . $cfg['path']['images'] . 'submit.gif" alt=""></a>';
}

/**
 * @param int $categoryId
 * @param string $type
 * @throws cDbException|cException
 */
function piwf_categoryRenderColumn($categoryId, $type): string
{
    $categoryId = cSecurity::toInteger($categoryId);
    $value = '';
    switch ($type) {
        case 'workflow':
            $wfForCat = piwf_getWorkflowForCat($categoryId);
            $value = piwf_workflowInherit($categoryId);
            $value .= '<span data-action-workflow-init="render_select" data-idcat="' . $categoryId . '" data-workflow="' . $wfForCat . '"></span>';
            break;
    }

    return $value;
}

/**
 * Returns the code to add to the page end at the category overview page.
 *
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_categoryPageEnd(): string
{
    // Get select/span template
    $template = piwf_prepareWorkflowItems();

    return '
<script type="text/javascript">
(function(Con, $) {
    var $root = $("#str_overview");

    /* Action function for setting Workflow */
    function actionSetWorkflow($element) {
        var $select = $element.parent().find("select");
        var idcat = $element.closest("tr").data("idcat");
        var params = {
            area: Con.cfg.area,
            action: "workflow_cat_assign",
            frame: Con.cfg.frame,
            modidcat: idcat
        };
        params[$select.attr("id")] = $select.val();
        console.log(params);

        window.location.href = Con.UtilUrl.build("main.php", params);
    }

    $(function() {
        // Initialize Workflows, render the Workflow controls into the categories overview table
        $root.find("[data-action-workflow-init]").each(function(pos, element) {
            var $element = $(element),
                action = $element.data("action-workflow-init");

            if (action === "render_select") {
                var idcat = $element.data("idcat"),
                    workflow = $element.data("workflow"),
                    template = \'' . $template . '\',
                    $span = $("<span>");

                $span.html(template.replace(/{IDCAT}/g, idcat));
                $element.replaceWith($span);
                $span.parent().find("select").val(workflow);
            }
        });

        $root.find("[data-action-workflow]").live("click", function() {
            var $element = $(this),
                action = $element.data("action-workflow");

            if (action === "set_workflow") {
                actionSetWorkflow($element);
            }
        });
    });
})(Con, Con.$);
</script>
';
}

/**
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_categoryColumns(array $array): array
{
    return [
        'workflow' => i18n("Workflow", "workflow")
    ];
}

/**
 * @throws cDbException|cException
 */
function piwf_processActions(array $array): array
{
    $categoryId = cRegistry::getCategoryId();

    $defaultWorkflowId = piwf_getWorkflowForCat($categoryId);
    if ($defaultWorkflowId != 0) {
        $newArray = [
            'todo',
            'wfartconf',
            'wftplconf',
            'wfonline',
            'wflocked',
            'duplicate',
            'delete',
            'usetime'
        ];
    } else {
        $newArray = $array;
    }

    return $newArray;
}

/**
 * @param int $categoryId
 * @param int $articleId
 * @param int $articleLanguageId
 * @param string $type
 * @return string
 * @throws cDbException|cException
 */
function piwf_renderAction($categoryId, $articleId, $articleLanguageId, $type): string
{
    global $articleConfigurationLink, $onlineLink, $lockedLink, $templateConfigurationLink;

    $categoryId = cSecurity::toInteger($categoryId);
    $articleId = cSecurity::toInteger($articleId);
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);

    $defaultWorkflowId = piwf_getWorkflowForCat($categoryId);

    $userSequenceId = piwf_getCurrentUserSequence($articleLanguageId, $defaultWorkflowId);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($userSequenceId);

    $currentEditor = $associatedUserSequence->get('iduser');
    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (piwf_isCurrentEditor($associatedUserSequence->get('iduser'))) {
        // Query rights for this user
        $wfRights = $workflowItem->getStepRights();
    } else {
        $wfRights = [];
    }

    switch ($type) {
        case 'wfartconf':
            if (!empty($wfRights['propertyedit'])) {
                return $articleConfigurationLink;
            }
            break;
        case 'wfonline':
            if (!empty($wfRights['publish'])) {
                return $onlineLink;
            }
            break;
        case 'wflocked':
            if (!empty($wfRights['lock'])) {
                return $lockedLink;
            }
            break;
        case 'wftplconf':
            if (!empty($wfRights['templateedit'])) {
                return $templateConfigurationLink;
            }
            break;
        default:
            break;
    }

    return '';
}

/**
 * @throws cDbException|cException
 */
function piwf_processArticleColumns(array $array): array
{
    global $modidartlang;

    $categoryId = cRegistry::getCategoryId();
    $action = cRegistry::getAction();

    if ($action === 'workflow_do_action') {
        $selectedAction = 'wfselect' . $modidartlang;
        piwf_doWorkflowAction($modidartlang, $GLOBALS[$selectedAction]);
    }

    $defaultWorkflowId = piwf_getWorkflowForCat($categoryId);

    if ($defaultWorkflowId != 0) {
        $newArray = [];
        $bInserted = false;
        foreach ($array as $sKey => $sValue) {
            $newArray[$sKey] = $sValue;
            if ($sKey == 'title' && !$bInserted) {
                $newArray['wftitle'] = $array['title'];
                $newArray['wfstep'] = i18n("Workflow Step", "workflow");
                $newArray['wfaction'] = i18n("Workflow Action", "workflow");
                $newArray['wfeditor'] = i18n("Workflow Editor", "workflow");
                $newArray['wflaststatus'] = i18n("Last status", "workflow");
                $bInserted = true;
            }
        }
        unset($newArray['title']);
        unset($newArray['changeddate']);
        unset($newArray['publisheddate']);
        unset($newArray['sortorder']);
    } else {
        $newArray = $array;
    }

    return $newArray;
}

/**
 * @param int $languageId
 * @param int $categoryId
 * @param int $articleId
 * @param string $user User id
 * @throws cDbException|cException
 */
function piwf_allowArticleEdit($languageId, $categoryId, $articleId, $user): bool
{
    $languageId = cSecurity::toInteger($languageId);
    $categoryId = cSecurity::toInteger($categoryId);
    $articleId = cSecurity::toInteger($articleId);

    $defaultWorkflowId = piwf_getWorkflowForCat($categoryId);

    if ($defaultWorkflowId == 0) {
        return true;
    }

    $articleLanguageId = getArtLang($articleId, $languageId);
    $userSequenceId = piwf_getCurrentUserSequence($articleLanguageId, $defaultWorkflowId);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($userSequenceId);

    $currentEditor = $associatedUserSequence->get('iduser');

    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (piwf_isCurrentEditor($associatedUserSequence->get('iduser'))) {
        $wfRights = $workflowItem->getStepRights();
    } else {
        $wfRights = [];
    }

    if (!empty($wfRights['articleedit'])) {
        return true;
    } else {
        return false;
    }
}

/**
 * @param int $categoryId
 * @param int $articleId
 * @param int $articleLanguageId
 * @param string $column
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piwf_renderColumn($categoryId, $articleId, $articleLanguageId, $column): string
{
    global $idtpl, $articleAltText, $articleTitleLink;

    $categoryId = cSecurity::toInteger($categoryId);
    $articleId = cSecurity::toInteger($articleId);
    $articleLanguageId = cSecurity::toInteger($articleLanguageId);

    $area = cRegistry::getArea();
    $frame = cRegistry::getFrame();
    $cfg = cRegistry::getConfig();

    $defaultWorkflowId = piwf_getWorkflowForCat($categoryId);

    $userSequenceId = piwf_getCurrentUserSequence($articleLanguageId, $defaultWorkflowId);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($userSequenceId);

    $currentEditor = $associatedUserSequence->get('iduser');

    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (piwf_isCurrentEditor($associatedUserSequence->get('iduser'))) {
        $wfRights = $workflowItem->getStepRights();
        $mayEdit = true;
    } else {
        $wfRights = '';
        $mayEdit = false;
    }

    switch ($column) {
        case 'wftitle':
            if ($wfRights['articleedit'] == true) {
                $mtitle = $articleTitleLink;
            } else {
                $mtitle = strip_tags($articleTitleLink);
            }
            return ($mtitle);
        case 'wfstep':
            if ($workflowItem === false) {
                return 'nobody';
            }

            return ($workflowItem->get('position') . '.) ' . $workflowItem->get('name'));
        case 'wfeditor':
            $sEditor = getGroupOrUserName($currentEditor);
            if (!$sEditor) {
                $sEditor = 'nobody';
            }
            return $sEditor;
        case 'wfaction':
            $defaultWorkflowId = piwf_getWorkflowForCat($categoryId);
            $userSequenceId = piwf_getCurrentUserSequence($articleLanguageId, $defaultWorkflowId);

            $sActionSelect = piwf_getActionSelect($articleLanguageId, $userSequenceId);
            if (!$sActionSelect) {
                $mayEdit = false;
            }

            $form = new cHTMLForm('wfaction' . $articleLanguageId, 'main.php', 'get');
            $form->setVar('area', $area);
            $form->setVar('action', 'workflow_do_action');
            $form->setVar('frame', $frame);
            $form->setVar('idcat', $categoryId);
            $form->setVar('modidartlang', $articleLanguageId);
            $form->setVar('idtpl', $idtpl);
            $form->appendContent('<table cellspacing="0" border="0"><tr><td>' . $sActionSelect . '</td><td>');
            $form->appendContent('<input type="image" src="' . cRegistry::getBackendUrl() . $cfg['path']['images'] . "submit.gif" . '" alt=""></tr></table>');

            if ($mayEdit == true) {
                return ($form->render());
            } else {
                return '--- ' . i18n("None") . ' ---';
            }

        case 'wflaststatus':
            $sStatus = piwf_getLastWorkflowStatus($articleLanguageId);
            if (!$sStatus) {
                $sStatus = '--- ' . i18n("None") . ' ---';
            }
            return $sStatus;
    }

    return '';
}

function piwf_createTasksFolder(): array
{
    $sess = cRegistry::getSession();
    $cfg = cRegistry::getConfig();

    $item = [];

    // Create workflow tasks folder
    $tmp_mstr = '<a href="javascript:void(0)" onclick="Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

    $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=con_workflow&frame=4"), 'right_top', $sess->url("main.php?area=con_workflow&frame=3"), 'Workflow / Todo');

    $item['image'] = '<img alt="" src="' . cRegistry::getBackendUrl() . $cfg['path']['plugins'] . 'workflow/images/workflow_erstellen.gif">';
    $item['title'] = $mstr;

    return $item;
}
