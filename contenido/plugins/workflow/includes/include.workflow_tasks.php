<?php
/**
 * This file contains the workflow task overview mask.
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

plugin_include('workflow', 'includes/functions.workflow.php');

/**
 * @var cSession $sess
 * @var cPermission $perm
 * @var cAuth $auth
 * @var cTemplate $tpl
 * @var cDb $db
 * @var string $area
 * @var string $sFlagTitle
 * @var string $action
 * @var int $frame
 * @var int $lang
 * @var int $client
 * @var int $idtpl
 * @var array $cfg
 */

$sSession = $sess->id;

$wfa = new WorkflowArtAllocations();
$wfu = new WorkflowUserSequences();
$user = new cApiUser();
$db2 = cRegistry::getDb();

$usershow = $usershow ?? '';
$modidartlang = cSecurity::toInteger($modidartlang ?? '0');

ob_start();

if ($usershow == "") {
    $usershow = $auth->auth["uid"];
}

if (!$perm->have_perm_area_action($area, "workflow_task_user_select")) {
    $usershow = $auth->auth["uid"];
}

if ($action == "workflow_do_action") {
    $selectedAction = "wfselect" . $modidartlang;
    doWorkflowAction($modidartlang, $GLOBALS[$selectedAction]);
}

$usersequence = [];
$lastusersequence = [];
$article = [];

$wfa->select();
while (($wfaitem = $wfa->next()) !== false) {
    $wfaid = $wfaitem->get("idartallocation");
    $usersequence[$wfaid] = $wfaitem->get("idusersequence");
    $lastusersequence[$wfaid] = $wfaitem->get("lastusersequence");
    $article[$wfaid] = $wfaitem->get("idartlang");
}

$userids = [];
if (is_array($usersequence)) {
    foreach ($usersequence as $key => $value) {
        $wfu->select("idusersequence = '$value'");
        if (($obj = $wfu->next()) !== false) {
            $userids[$key] = $obj->get("iduser");
        }
    }
}

if (is_array($userids)) {
    foreach ($userids as $key => $value) {
        $isCurrent[$key] = false;

        if ($usershow == $value) {
            $isCurrent[$key] = true;
        }

        if ($user->loadByPrimaryKey($value) == false) {
            // Yes, it's a group. Let's try to load the group members!
            $sql = "SELECT user_id FROM " . cRegistry::getDbTableName('groupmembers') . " WHERE group_id = '" . $db2->escape($value) . "'";
            $db2->query($sql);

            while ($db2->nextRecord()) {
                if ($db2->f("user_id") == $usershow) {
                    $isCurrent[$key] = true;
                }
            }
        } else {
            if ($value == $usershow) {
                $isCurrent[$key] = true;
            }
        }

        if ($lastusersequence[$key] == $usersequence[$key]) {
            $isCurrent[$key] = false;
        }
    }
}

$tpl->reset();
$tpl->setEncoding('iso-8859-1');
$iIDCat = 0;
$iIDTpl = 0;

if ($perm->have_perm_area_action($area, "workflow_task_user_select")) {
    $form = new cHTMLForm("showusers", $sess->url("main.php?area=$area&frame=$frame"));
    $form->setVar("area", $area);
    $form->setEvent("submit", "setUsershow();");
    $form->setVar("frame", $frame);
    $form->setVar("action", "workflow_task_user_select");
    $form->appendContent(i18n("Show users") . ": " . getUsers("show", $usershow));
    $form->appendContent('<input class="vAlignMiddle" type="image" src="' . $cfg["path"]["htmlpath"] . $cfg["path"]["images"] . "submit.gif" . '">');

    $tpl->set('s', 'USERSELECT', $form->render());
} else {
    $tpl->set('s', 'USERSELECT', '');
}

$pageTitle = i18n('Search results') . ' - ' . i18n('Workflow tasks', 'workflow');
$tpl->set('s', 'PAGE_TITLE', $pageTitle);

$tpl->set('s', 'TH_START', i18n("Article"));
$tpl->set('s', 'TH_TEMPLATE', i18n("Template"));
$tpl->set('s', 'TH_ACTIONS', i18n("Actions"));
$tpl->set('s', 'TH_TITLE', i18n("Title"));
$tpl->set('s', 'TH_CHANGED', i18n("Changed"));
$tpl->set('s', 'TH_PUBLISHED', i18n("Published"));
$tpl->set('s', 'TH_WORKFLOW_STEP', i18n("Workflow Step", 'workflow'));
$tpl->set('s', 'TH_WORKFLOW_ACTION', i18n("Workflow Action", 'workflow'));
$tpl->set('s', 'TH_WORKFLOW_EDITOR', i18n("Workflow Editor"));
$tpl->set('s', 'TH_LAST_STATUS', i18n("Last status", 'workflow'));

$currentUserSequence = new WorkflowUserSequence();

if (is_array($isCurrent)) {
    foreach ($isCurrent as $key => $value) {
        if ($value == true) {
            $idartlang = cSecurity::toInteger($article[$key]);
            $lang = cSecurity::toInteger($lang);
            $client = cSecurity::toInteger($client);

            $sql = "SELECT B.idcat AS idcat, A.title AS title, A.created AS created, A.lastmodified AS changed,
                           A.idart as idart, E.name as tpl_name, A.idartlang as idartlang, F.idcatlang as idcatlang,
                           B.idcatart as idcatart, A.idlang as art_lang, F.startidartlang as startidartlang
                    FROM (" . cRegistry::getDbTableName('art_lang') . " AS A,
                         " . cRegistry::getDbTableName('cat_art') . " AS B,
                          " . cRegistry::getDbTableName('art') . " AS C)
                          LEFT JOIN " . cRegistry::getDbTableName('tpl_conf') . " as D ON A.idtplcfg = D.idtplcfg
                          LEFT JOIN " . cRegistry::getDbTableName('tpl') . " as E ON D.idtpl = E.`idtpl`
                          LEFT JOIN " . cRegistry::getDbTableName('cat_lang') . " as F ON B.idcat = F.`idcat`
                         WHERE A.idartlang = '$idartlang' AND
                               A.idart = B.idart AND
                               A.idart = C.idart AND
                               A.idlang = '$lang' AND
                                C.idclient = '$client';";

            $db->query($sql);

            if ($db->nextRecord()) {
                global $area;
                // $area = "con";
                $idcat = $db->f("idcat");
                $idart = $db->f("idart");

                // Create javascript multilink
                $tmp_mstr = '<a href="javascript:void(0)" onclick="Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')"  title="idart: ' . $db->f('idart') . ' idcatart: ' . $db->f('idcatart') . '" title="idart: ' . $db->f('idart') . ' idcatart: ' . $db->f('idcatart') . '">%s</a>';

                $mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=con&frame=3&idcat=$idcat&idtpl=$idtpl"), 'right_bottom', $sess->url("main.php?area=con_editart&action=con_edit&frame=4&idcat=$idcat&idtpl=$idtpl&idart=$idart"), $db->f("title"));

                $laststatus = getLastWorkflowStatus($idartlang);
                $username = getGroupOrUserName($userids[$key]);
                $actionSelect = piworkflowRenderColumn($idcat, $idart, $db->f('idartlang'), 'wfaction');

                $currentUserSequence->loadByPrimaryKey($usersequence[$key]);
                $workflowItem = $currentUserSequence->getWorkflowItem();
                $step = $workflowItem->get("name");
                $description = $workflowItem->get("description");

                $sRowId = $db->f('idart') . '-' . $db->f('idartlang') . '-' . $db->f('idcat') . '-' . $db->f('idcatlang') . '-' . $db->f('idcatart') . '-' . $db->f('art_lang');

                if ($db->f('startidartlang') == $db->f('idartlang')) {
                    $makeStartarticle = "<img src=\"images/isstart1.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\">";
                } else {
                    $makeStartarticle = "<img src=\"images/isstart0.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\">";
                }

                $todoListeSubject = i18n("Reminder");
                $sReminder = i18n("Set reminder / add to todo list");
                $sReminderHtml = "<a id=\"m1\" onclick=\"window.open('main.php?subject=$todoListeSubject&amp;area=todo&amp;frame=1&amp;itemtype=idart&amp;itemid=$idart&amp;contenido=$sSession', 'todo', 'scrollbars=yes, height=300, width=550');\" href=\"#\"><img alt=\"$sReminder\" title=\"$sReminder\" id=\"m2\" src=\"images/but_setreminder.gif\" border=\"0\"></a>";

                $templatename = $db->f('tpl_name');
                if (!empty($templatename)) {
                    $templatename = conHtmlentities($templatename);
                } else {
                    $templatename = '--- ' . i18n("None") . ' ---';
                }

                if ($i == 0) {
                    $iIDCat = $db->f("idcat");
                    $iIDTpl = $idtpl;
                    $tpl->set('s', 'FIRST_ROWID', $sRowId);
                }

                $tpl->set('d', 'START', $makeStartarticle);
                $tpl->set('d', 'TITLE', $mstr);
                $tpl->set('d', 'LAST_STATUS', $laststatus);
                $tpl->set('d', 'WORKFLOW_EDITOR', $username);
                $tpl->set('d', 'WORKFLOW_STEP', $step);
                $tpl->set('d', 'WORKFLOW_ACTION', $actionSelect);
                $tpl->set('d', 'TEMPLATE', $templatename);
                $tpl->set('d', 'ROWID', $sRowId);
                $tpl->set('d', 'ACTIONS', $sReminderHtml);
                $tpl->next();
                $i++;
            }
        }
    }
}

if ($i > 0) {
    $tpl->set('s', 'NO_ARTICLES_ROW', '');
} else {
    $sRow = '<tr><td colspan="8" class="bordercell">' . i18n("No article found.") . '</td></tr>';
    $tpl->set('s', 'NO_ARTICLES_ROW', $sRow);
}

$sLoadSubnavi = 'Con.getFrame(\'right_top\').location.href = \'main.php?area=con&frame=3&idcat=' . $iIDCat . '&idtpl=' . $iIDTpl . '&contenido=' . $sSession . "';";
$tpl->set('s', 'SUBNAVI', $sLoadSubnavi);

$frame = ob_get_contents();
ob_end_clean();

$tpl->generate($cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/templates/template.workflow_tasks.html");
