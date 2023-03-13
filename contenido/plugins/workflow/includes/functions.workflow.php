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

cInclude("includes", "functions.con.php");

/**
 * @param $listid
 * @param $default
 *
 * @return string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function getUsers($listid, $default) {
    $cfg = cRegistry::getConfig();
    $auth = cRegistry::getAuth();

    $userColl = new cApiUserCollection();
    $users = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']));
    $groupColl = new cApiGroupCollection();
    $groups = $groupColl->getAccessibleGroups(explode(',', $auth->auth['perm']));

    $tpl2 = new cTemplate();
    $tpl2->set('s', 'NAME', 'user' . $listid);
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

    if (is_array($users)) {
        foreach ($users as $key => $value) {
            $tpl2->set('d', 'VALUE', $key);
            $tpl2->set('d', 'CAPTION', $value["realname"] . " (" . $value["username"] . ")");

            if ($default == $key) {
                $tpl2->set('d', 'SELECTED', 'SELECTED');
            } else {
                $tpl2->set('d', 'SELECTED', '');
            }

            $tpl2->next();
        }
    }

    $tpl2->set('d', 'VALUE', '0');
    $tpl2->set('d', 'CAPTION', '------------------------------------');
    $tpl2->set('d', 'SELECTED', 'disabled');
    $tpl2->next();

    if (is_array($groups)) {
        foreach ($groups as $key => $value) {
            $tpl2->set('d', 'VALUE', $key);
            $tpl2->set('d', 'CAPTION', $value["groupname"]);

            if ($default == $key) {
                $tpl2->set('d', 'SELECTED', 'SELECTED');
            } else {
                $tpl2->set('d', 'SELECTED', '');
            }

            $tpl2->next();
        }
    }

    return $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);
}

/**
 * @param string $uid
 *
 * @return bool
 * @throws cDbException|cException
 */
function isCurrentEditor($uid) {
    $auth = cRegistry::getAuth();

    // Check if the UID is a group. If yes, check if we are in it
    $user = new cApiUser();
    if ($user->loadByPrimaryKey($uid) == false) {
        $db2 = cRegistry::getDb();

        // Yes, it's a group. Let's try to load the group members!
        $sql = "SELECT `user_id` FROM `%s` WHERE `group_id` = '%s'";
        $db2->query($sql, cRegistry::getDbTableName('groupmembers'), $uid);
        while ($db2->nextRecord()) {
            if ($db2->f("user_id") == $auth->auth["uid"]) {
                return true;
            }
        }
    } else {
        if ($uid == $auth->auth["uid"]) {
            return true;
        }
    }

    return false;
}

/**
 * @param int $idartlang
 * @param int $idusersequence
 *
 * @return bool|string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function getActionSelect($idartlang, $idusersequence) {
    $idartlang = cSecurity::toInteger($idartlang);
    $idusersequence = cSecurity::toInteger($idusersequence);

    $cfg = cRegistry::getConfig();

    $workflowActions = new WorkflowActions();
    $allActions      = $workflowActions->getAvailableWorkflowActions();

    $wfSelect = new cTemplate();
    $wfSelect->set('s', 'NAME', 'wfselect' . $idartlang);
    $wfSelect->set('s', 'CLASS', 'text_medium');

    $userSequence = new WorkflowUserSequence();
    $userSequence->loadByPrimaryKey($idusersequence);

    $workflowItem = $userSequence->getWorkflowItem();

    if ($workflowItem === false) {
        return false;
    }

    $wfRights = $workflowItem->getStepRights();

    $laststep = 0;
    $artAllocation = new WorkflowArtAllocations();
    $artAllocation->select("idartlang = '$idartlang'");
    if (($obj = $artAllocation->next()) !== false) {
        $laststep = cSecurity::toInteger($obj->get("lastusersequence"));
    }

    $bExistOption = false;
    if ($laststep != $idusersequence) {
        $wfSelect->set('d', 'VALUE', 'next');
        $wfSelect->set('d', 'CAPTION', i18n("Confirm", "workflow"));
        $wfSelect->set('d', 'SELECTED', 'SELECTED');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($wfRights["last"] == true) {
        $wfSelect->set('d', 'VALUE', 'last');
        $wfSelect->set('d', 'CAPTION', i18n("Back to last editor", "workflow"));
        $wfSelect->set('d', 'SELECTED', '');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($wfRights["reject"] == true) {
        $wfSelect->set('d', 'VALUE', 'reject');
        $wfSelect->set('d', 'CAPTION', i18n("Reject article", "workflow"));
        $wfSelect->set('d', 'SELECTED', '');
        $wfSelect->next();
        $bExistOption = true;
    }

    if ($wfRights["revise"] == true) {
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
 * @param int $idartlang
 * @param int $defaultidworkflow
 *
 * @return bool
 * @throws cDbException|cException|cInvalidArgumentException
 */
function setUserSequence($idartlang, $defaultidworkflow) {
    $idartlang = cSecurity::toInteger($idartlang);
    $defaultidworkflow = cSecurity::toInteger($defaultidworkflow);

    $wfaa = new WorkflowArtAllocations();
    $wfaa->select("idartlang = $idartlang");

    if (($associatedUserSequence = $wfaa->next()) !== false) {
        $idartallocation = $associatedUserSequence->get("idartallocation");
        $wfaa->delete($idartallocation);
    }

    if ($defaultidworkflow > 0) {
        $newObj = $wfaa->create($idartlang);
        if (!$newObj) {
            return false;
        }

        // Get the first idusersequence for the new item
        $workflowItems = new WorkflowItems();
        $workflowItems->select("idworkflow = $defaultidworkflow AND position = 1");
        $firstitem = 0;
        if (($obj = $workflowItems->next()) !== false) {
            $firstitem = cSecurity::toInteger($obj->get("idworkflowitem"));
        }

        $workflowUserSequences = new WorkflowUserSequences();
        $workflowUserSequences->select("idworkflowitem = $firstitem AND position = 1'");

        if (($obj = $workflowUserSequences->next()) !== false) {
            $firstIDUserSequence = $obj->get("idusersequence");
        }

        $newObj->set("idusersequence", $firstIDUserSequence);
        $newObj->store();

        return true;
    }

    return false;
}

/**
 * Returns current user sequence, either from workflow article allocations or
 * from workflow user sequences.
 *
 * @param int $idartlang         Article language id
 * @param int $defaultidworkflow Default workflow id
 *
 * @return int false of found user sequence or false
 * @throws cDbException|cException
 */
function getCurrentUserSequence($idartlang, $defaultidworkflow) {
    $idartlang = cSecurity::toInteger($idartlang);
    $defaultidworkflow = cSecurity::toInteger($defaultidworkflow);

    $wfaa = new WorkflowArtAllocations();
    $wfaa->select("idartlang = $idartlang");
    $idusersequence = 0;

    if (($associatedUserSequence = $wfaa->next()) !== false) {
        $idusersequence = $associatedUserSequence->get("idusersequence");
    }

    if ($idusersequence == 0) {
        if ($associatedUserSequence != false) {
            $newObj = $associatedUserSequence;
        } else {
            $newObj = $wfaa->create($idartlang);

            if (!$newObj) {
                return false;
            }
        }

        // Get the first idusersequence for the new item
        $workflowItems = new WorkflowItems();
        $workflowItems->select("idworkflow = $defaultidworkflow AND position = 1");
        $firstitem = 0;
        if (($obj = $workflowItems->next()) !== false) {
            $firstitem = $obj->get("idworkflowitem");
        }

        $workflowUserSequences = new WorkflowUserSequences();
        $workflowUserSequences->select("idworkflowitem = $firstitem AND position = 1");

        if (($obj = $workflowUserSequences->next()) !== false) {
            $firstIDUserSequence = $obj->get("idusersequence");
        }

        $newObj->set("idusersequence", $firstIDUserSequence);
        $newObj->store();

        $idusersequence = $newObj->get("idusersequence");
    }

    return $idusersequence;
}

/**
 * @param int $idartlang
 *
 * @return bool|string
 * @throws cDbException|cException
 */
function getLastWorkflowStatus($idartlang) {
    $idartlang = cSecurity::toInteger($idartlang);

    $wfaa = new WorkflowArtAllocations();
    $wfaa->select("idartlang = $idartlang");
    if (($associatedUserSequence = $wfaa->next()) !== false) {
        $laststatus = $associatedUserSequence->get("laststatus");
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
 * @param int $idartlang
 * @param string $action
 *
 * @throws cDbException|cException
 */
function doWorkflowAction($idartlang, $action) {
    $idcat = cRegistry::getCategoryId();
    $idartlang = cSecurity::toInteger($idartlang);

    switch ($action) {
        case "last":
            $artAllocations = new WorkflowArtAllocations();
            $artAllocations->select("idartlang = {$idartlang}");

            if (($obj = $artAllocations->next()) !== false) {
                $usersequence = new WorkflowUserSequence();
                $usersequence->loadByPrimaryKey($obj->get("idusersequence"));

                $workflowitem = $usersequence->getWorkflowItem();

                $idworkflow = cSecurity::toInteger($workflowitem->get("idworkflow"));
                $newpos = cSecurity::toInteger($workflowitem->get("position") - 1);
                if ($newpos < 1) {
                    $newpos = 1;
                }

                $workflowitems = new WorkflowItems();
                $workflowitems->select("idworkflow = $idworkflow AND position = " . $newpos);

                if (($nextObj = $workflowitems->next()) !== false) {
                    $userSequences = new WorkflowUserSequences();
                    $idworkflowitem = cSecurity::toInteger($nextObj->get("idworkflowitem"));
                    $userSequences->select("idworkflowitem = $idworkflowitem");

                    if (($nextSeqObj = $userSequences->next()) !== false) {
                        $obj->set("lastusersequence", $obj->get("idusersequence"));
                        $obj->set("idusersequence", $nextSeqObj->get("idusersequence"));
                        $obj->set("laststatus", "last");
                        $obj->store();
                    }
                }
            }
            break;
        case "next":
            $artAllocations = new WorkflowArtAllocations();
            $artAllocations->select("idartlang = {$idartlang}");

            if (($obj = $artAllocations->next()) !== false) {
                $usersequence = new WorkflowUserSequence();
                $usersequence->loadByPrimaryKey($obj->get("idusersequence"));

                $workflowitem = $usersequence->getWorkflowItem();

                $idworkflow = cSecurity::toInteger($workflowitem->get("idworkflow"));
                $newpos = cSecurity::toInteger($workflowitem->get("position") + 1);

                $workflowitems = new WorkflowItems();
                $workflowitems->select("idworkflow = $idworkflow AND position = " . $newpos);

                if (($nextObj = $workflowitems->next()) !== false) {
                    $userSequences = new WorkflowUserSequences();
                    $idworkflowitem = cSecurity::toInteger($nextObj->get("idworkflowitem"));
                    $userSequences->select("idworkflowitem = $idworkflowitem");

                    if (($nextSeqObj = $userSequences->next()) !== false) {
                        $obj->set("lastusersequence", '10');
                        $obj->set("idusersequence", $nextSeqObj->get("idusersequence"));
                        $obj->set("laststatus", "confirm");
                        $obj->store();
                    }
                } else {
                    $workflowitems->select("idworkflow = $idworkflow AND position = " . (int) $workflowitem->get("position"));
                    if (($nextObj = $workflowitems->next()) !== false) {
                        $userSequences = new WorkflowUserSequences();
                        $idworkflowitem = cSecurity::toInteger($nextObj->get("idworkflowitem"));
                        $userSequences->select("idworkflowitem = $idworkflowitem");

                        if (($nextSeqObj = $userSequences->next()) !== false) {
                            $obj->set("lastusersequence", $obj->get("idusersequence"));
                            $obj->set("idusersequence", $nextSeqObj->get("idusersequence"));
                            $obj->set("laststatus", "confirm");
                            $obj->store();
                        }
                    }
                }
            }
            break;
        case "reject":
            $artAllocations = new WorkflowArtAllocations();
            $artAllocations->select("idartlang = {$idartlang}");

            if (($obj = $artAllocations->next()) !== false) {
                $usersequence = new WorkflowUserSequence();
                $usersequence->loadByPrimaryKey($obj->get("idusersequence"));

                $workflowitem = $usersequence->getWorkflowItem();

                $idworkflow = cSecurity::toInteger($workflowitem->get("idworkflow"));
                $newpos = 1;

                $workflowitems = new WorkflowItems();
                $workflowitems->select("idworkflow = $idworkflow AND position = " . $newpos);

                if (($nextObj = $workflowitems->next()) !== false) {
                    $userSequences = new WorkflowUserSequences();
                    $idworkflowitem = cSecurity::toInteger($nextObj->get("idworkflowitem"));
                    $userSequences->select("idworkflowitem = $idworkflowitem");

                    if (($nextSeqObj = $userSequences->next()) !== false) {
                        $obj->set("lastusersequence", $obj->get("idusersequence"));
                        $obj->set("idusersequence", $nextSeqObj->get("idusersequence"));
                        $obj->set("laststatus", "reject");
                        $obj->store();
                    }
                }
            }
            break;

        case "revise":
            $db = cRegistry::getDb();
            $sql = "SELECT `idart`, `idlang` FROM `%s` WHERE `idartlang` = %d";
            $db->query($sql, cRegistry::getDbTableName('art_lang'), $idartlang);
            $db->nextRecord();
            $idart = $db->f("idart");
            $idlang = $db->f("idlang");

            $newidart = conCopyArticle($idart, $idcat, "foo");

            break;
        default:
    }
}

/**
 * @param int $usersequence
 *
 * @return bool|mixed
 * @throws cDbException
 * @throws cException
 */
function getWorkflowForUserSequence($usersequence) {
    $usersequence = cSecurity::toInteger($usersequence);
    $usersequences = new WorkflowUserSequences();
    $usersequences->select("idusersequence = $usersequence");

    if (($obj = $usersequences->next()) !== false) {
        $idworkflowitem = cSecurity::toInteger($obj->get("idworkflowitem"));
        $workflowitems = new WorkflowItems();
        $workflowitems->select("idworkflowitem = '$idworkflowitem'");
        if (($obj = $workflowitems->next()) !== false) {
            return $obj->get("idworkflow");
        }
    }

    return false;
}

/**
 * @param $listid
 * @param $default
 * @param $idcat
 *
 * @return string
 */
function workflowSelect($listid, $default, $idcat) {
    global $workflowSelectBox;

    $cfg = cRegistry::getConfig();

    $oSelectBox = new cHTMLSelectElement('workflow');
    $oSelectBox = $workflowSelectBox;

    $default = cSecurity::toInteger($default);
    $workflowSelectBox->updateAttributes([
        "id" => "wfselect" . $idcat
    ]);
    $workflowSelectBox->updateAttributes([
        "name" => "wfselect" . $idcat
    ]);
    $workflowSelectBox->setDefault($default);

    $sButton = '<a href="javascript:setWorkflow(' . $idcat . ', \'' . "wfselect" . $idcat . '\')"><img src="' . $cfg["path"]["images"] . 'submit.gif" alt="" class="spaced"></a>';

    return $workflowSelectBox->render() . $sButton;
}

/**
 * @param int $idcat
 *
 * @return string
 */
function workflowInherit($idcat) {
    $idcat = cSecurity::toInteger($idcat);
    $cfg = cRegistry::getConfig();
    $frame = cRegistry::getFrame();
    $area = cRegistry::getArea();
    $sess = cRegistry::getSession();

    $sUrl = $sess->url("main.php?area=$area&frame=$frame&modidcat=$idcat&action=workflow_inherit_down");
    return '<a class="con_img_button mgr5" href="' . $sUrl . '"><img src="' . $cfg["path"]["images"] . 'pfeil_runter.gif" alt="" title="' . i18n("Inherit workflow to sub-categories", "workflow") . '"></a>';
}


/**
 * @param int $idcat
 *
 * @return int
 * @throws cDbException|cException
 */
function getWorkflowForCat($idcat) {
    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger(cRegistry::getLanguageId());

    $idcatlang = getCatLang($idcat, $lang);
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
 * @param $idcat
 * @param $idlang
 *
 * @return int
 * @throws cDbException
 */
function getCatLang($idcat, $idlang) {
    $idcat = cSecurity::toInteger($idcat);
    $idlang = cSecurity::toInteger($idlang);
    // Get the idcatlang
    $oCatLangColl = new cApiCategoryLanguageCollection();
    $aIds = $oCatLangColl->getIdsByWhereClause('idlang = ' . $idlang . ' AND idcat = ' . $idcat);
    return (count($aIds) > 0) ? cSecurity::toInteger($aIds[0]) : 0;
}


/**
 * Returns the template (workflow select and button) to add to the category overview table.
 *
 * @return string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function prepareWorkflowItems() {
    global $modidcat, $workflowSelectBox, $workflowworkflows, $tpl;

    $action = cRegistry::getAction();
    $client = cSecurity::toInteger(cRegistry::getClientId());
    $lang = cSecurity::toInteger(cRegistry::getLanguageId());
    $cfg = cRegistry::getConfig();

    $workflowworkflows = new Workflows();

    if ($action === 'workflow_inherit_down') {
        $tmp = strDeeperCategoriesArray($modidcat);
        $asworkflow = getWorkflowForCat($modidcat);

        $wfa = new WorkflowAllocations();

        foreach ($tmp as $tmp_cat) {
            $idcatlang = getCatLang($tmp_cat, $lang);

            if ($asworkflow == 0) {
                $wfa->select("idcatlang = $idcatlang");

                if (($item = $wfa->next()) !== false) {
                    $wfa->delete($item->get("idallocation"));
                    // delete user sequences for listing in tasklist for each
                    // included article
                    $oArticles = new cArticleCollector([
                        'idcat' => $idcatlang,
                        'start' => true,
                        'offline' => true
                    ]);
                    while (($oArticle = $oArticles->nextArticle()) !== false) {
                        setUserSequence($oArticle->getField('idartlang'), -1);
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
            while (($oArticle = $oArticles->nextArticle()) !== false) {
                setUserSequence($oArticle->getField('idartlang'), $GLOBALS[$seltpl]);
            }
        } else {
            // unlink workflow with category
            $wfa->select("idcatlang = $idcatlang");
            if (($item = $wfa->next()) !== false) {
                $alloc = $item->get("idallocation");
                $wfa->delete($alloc);
            }

            // delete user sequences for listing in tasklist for each included
            // article
            $oArticles = new cArticleCollector([
                'idcat' => $modidcat,
                'start' => true,
                'offline' => true
            ]);
            while (($oArticle = $oArticles->nextArticle()) !== false) {
                setUserSequence($oArticle->getField('idartlang'), -1);
            }
        }
    }

    $workflowSelectBox = new cHTMLSelectElement("foo");
    $workflowSelectBox->setClass("text_medium");
    $workflowworkflows->select("idclient = $client AND idlang = " . cSecurity::toInteger($lang));

    $workflowOption = new cHTMLOptionElement("--- " . i18n("None", "workflow") . " ---", '0');
    $workflowSelectBox->addOptionElement(0, $workflowOption);

    while (($workflow = $workflowworkflows->next()) !== false) {
        $idWorkflow = cSecurity::toInteger($workflow->get("idworkflow"));
        $wfa = new WorkflowItems();
        $wfa->select("idworkflow = " . $idWorkflow);

        if ($wfa->next() !== false) {
            $workflowOption = new cHTMLOptionElement($workflow->get("name"), $idWorkflow);
            $workflowSelectBox->addOptionElement($idWorkflow, $workflowOption);
        }
    }

    $workflowSelectBox->updateAttributes([
        "id" => "wfselect{IDCAT}"
    ]);
    $workflowSelectBox->updateAttributes([
        "name" => "wfselect{IDCAT}"
    ]);

    return $workflowSelectBox->render()
        . '<a class="con_img_button mgl5" href="javascript:void(0)" data-action-workflow="set_workflow"><img src="' . $cfg["path"]["images"] . 'submit.gif" alt=""></a>';
}

/**
 * @param int $idcat
 * @param string $type
 *
 * @return string
 * @throws cDbException|cException
 */
function piworkflowCategoryRenderColumn($idcat, $type) {
    $idcat = cSecurity::toInteger($idcat);
    $value = '';
    switch ($type) {
        case "workflow":
            $wfForCat = getWorkflowForCat($idcat);
            $value = workflowInherit($idcat);
            $value .= '<span data-action-workflow-init="render_select" data-idcat="' . $idcat . '" data-workflow="' . $wfForCat . '"></span>';
            break;
    }

    return $value;
}

/**
 * Returns the code to add to the page end at the category overview page.
 *
 * @return string
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function piworkflowCategoryPageEnd()
{
    // Get select/span template
    $template = prepareWorkflowItems();

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
 * @param array $array
 *
 * @return array
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piworkflowCategoryColumns($array) {
    return [
        "workflow" => i18n("Workflow", "workflow")
    ];
}

/**
 * @param array $array
 *
 * @return array
 * @throws cDbException|cException
 */
function piworkflowProcessActions($array) {
    $idcat = cRegistry::getCategoryId();

    $defaultidworkflow = getWorkflowForCat($idcat);
    if ($defaultidworkflow != 0) {
        $newArray = [
            "todo",
            "wfartconf",
            "wftplconf",
            "wfonline",
            "wflocked",
            "duplicate",
            "delete",
            "usetime"
        ];
    } else {
        $newArray = $array;
    }

    return $newArray;
}

/**
 * @param int $idcat
 * @param int $idart
 * @param int $idartlang
 * @param string $type
 *
 * @return string
 * @throws cDbException|cException
 */
function piworkflowRenderAction($idcat, $idart, $idartlang, $type) {
    global $tmp_artconf, $onlinelink, $lockedlink, $tplconf_link;

    $idcat = cSecurity::toInteger($idcat);
    $idart = cSecurity::toInteger($idart);
    $idartlang = cSecurity::toInteger($idartlang);

    $defaultidworkflow = getWorkflowForCat($idcat);

    $idusersequence = getCurrentUserSequence($idartlang, $defaultidworkflow);
    $associatedUserSequence = new WorkflowUserSequence();
    $associatedUserSequence->loadByPrimaryKey($idusersequence);

    $currentEditor = $associatedUserSequence->get("iduser");
    $workflowItem = $associatedUserSequence->getWorkflowItem();

    if (isCurrentEditor($associatedUserSequence->get("iduser"))) {
        // Query rights for this user
        $wfRights = $workflowItem->getStepRights();
    } else {
        $wfRights = [];
    }

    switch ($type) {
        case "wfartconf":
            if (!empty($wfRights["propertyedit"])) {
                return $tmp_artconf;
            }
            break;
        case "wfonline":
            if (!empty($wfRights["publish"])) {
                return $onlinelink;
            }
            break;
        case "wflocked":
            if (!empty($wfRights["lock"])) {
                return $lockedlink;
            }
            break;
        case "wftplconf":
            if (!empty($wfRights["templateedit"])) {
                return $tplconf_link;
            }
            break;
        default:
            break;
    }

    return "";
}

/**
 * @param array $array
 *
 * @return array
 * @throws cDbException|cException
 */
function piworkflowProcessArticleColumns($array) {
    global $modidartlang;

    $idcat = cRegistry::getCategoryId();
    $action = cRegistry::getAction();

    if ($action == "workflow_do_action") {
        $selectedAction = "wfselect" . $modidartlang;
        doWorkflowAction($modidartlang, $GLOBALS[$selectedAction]);
    }

    $defaultidworkflow = getWorkflowForCat($idcat);

    if ($defaultidworkflow != 0) {
        $narray = [];
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

    return $narray;
}

/**
 * @param int $idlang
 * @param int $idcat
 * @param int $idart
 * @param string $user User id
 *
 * @return bool
 * @throws cDbException|cException
 */
function piworkflowAllowArticleEdit($idlang, $idcat, $idart, $user) {
    $idlang = cSecurity::toInteger($idlang);
    $idcat = cSecurity::toInteger($idcat);
    $idart = cSecurity::toInteger($idart);

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
    } else {
        $wfRights = [];
    }

    if (!empty($wfRights["articleedit"])) {
        return true;
    } else {
        return false;
    }
}

/**
 * @param int $idcat
 * @param int $idart
 * @param int $idartlang
 * @param $column
 *
 * @return string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function piworkflowRenderColumn($idcat, $idart, $idartlang, $column) {
    global $idtpl, $alttitle, $tmp_articletitle;

    $idcat = cSecurity::toInteger($idcat);
    $idart = cSecurity::toInteger($idart);
    $idartlang = cSecurity::toInteger($idartlang);

    $area = cRegistry::getArea();
    $frame = cRegistry::getFrame();
    $cfg = cRegistry::getConfig();

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
            $form->appendContent('<input type="image" src="' . cRegistry::getBackendUrl() . $cfg["path"]["images"] . "submit.gif" . '" alt=""></tr></table>');

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

    return '';
}

/**
 * @return array
 */
function piworkflowCreateTasksFolder() {
    $sess = cRegistry::getSession();
    $cfg = cRegistry::getConfig();

    $item = [];

    // Create workflow tasks folder
    $tmp_mstr = '<a href="javascript:void(0)" onclick="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

    $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=con_workflow&frame=4"), 'right_top', $sess->url("main.php?area=con_workflow&frame=3"), 'Workflow / Todo');

    $item["image"] = '<img alt="" src="' . cRegistry::getBackendUrl() . $cfg["path"]["plugins"] . 'workflow/images/workflow_erstellen.gif">';
    $item["title"] = $mstr;

    return $item;
}
