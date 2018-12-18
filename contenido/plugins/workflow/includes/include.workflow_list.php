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

$iIdMarked = (int) $_GET['idworkflow'];

plugin_include('workflow', 'classes/class.workflow.php');

$page = new cGuiPage('workflow_list', 'workflow');
$page->addStyle('workflow.css');
$workflows = new Workflows();
$sScript = '';

if ($action == 'workflow_delete') {
    $workflows->delete($idworkflow);
    $urlRightTop = $sess->url('main.php?area=workflow&frame=3');
    $urlRightBottom = $sess->url('main.php?area=workflow_common&frame=4&action=workflow_delete');

    $sScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var right_top = Con.getFrame('right_top'),
        right_bottom = Con.getFrame('right_bottom');
    if (right_top) {
        right_top.location.href = "{$urlRightTop}";
    }
    if (right_bottom) {
        right_bottom.location.href = "{$urlRightBottom}";
    }
})(Con, Con.$);
</script>
JS;
}

$ui = new cGuiMenu();
$workflows->select("idclient = '$client' AND idlang = '$lang'");


while (($workflow = $workflows->next()) !== false) {
    $wfid = $workflow->getField('idworkflow');
    $wfname = preg_replace("/\"/", '', ($workflow->getField('name')));
    $wfdescription = preg_replace("/\"/", '', ($workflow->getField('description')));

    // Create the link to show/edit the workflow
    $link = new cHTMLLink();
    $link->setMultiLink('workflow', '', 'workflow_common', 'workflow_show');
    $link->setAlt($wfdescription);
    $link->setCustom('idworkflow', $wfid);

    $delTitle = i18n('Delete workflow', 'workflow');
    $delDescr = sprintf(i18n("Do you really want to delete the following workflow:<br><br>%s<br>", 'workflow'), $wfname);
    $delete = '<a class="jsDelete" title="' . $delTitle . '" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteWorkflow(' . $wfid . '); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';

    $ui->setTitle($wfid, $wfname);
    $ui->setLink($wfid, $link);

    $ui->setActions($wfid, 'delete', $delete);

    if ($wfid == $iIdMarked) {
        $ui->setMarked($wfid);
    }
}

if (!empty($sScript)) {
    $page->addScript($sScript);
}
$page->set('s', 'FORM', $ui->render(0));

$page->render();
