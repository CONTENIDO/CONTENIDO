<?php

/**
 * This file contains the backend page for the content allocation plugin in
 * content area.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var array $cfg
 * @var int $idart
 * @var int $idcat
 * @var int $lang
 * @var cDb $db
 * @var cGuiNotification $notification
 * @var cSession $sess
 * @var string $area
 * @var int $frame
 * @var cTemplate|null $tpl
 * @var int|string $syncoptions
 * @var string $contenido
 */

cInclude('includes', 'functions.pathresolver.php');

$oPage = new cGuiPage("contentallocation_article", "content_allocation", "7");

$this_idartlang = 0;
$this_locked = 0;

// fetch idartlang for idart
$articleLanguage = new cApiArticleLanguage();
$articleLanguage->loadByArticleAndLanguageId($idart, $lang);
if ($articleLanguage->isLoaded()) {
    $this_idartlang = cSecurity::toInteger($articleLanguage->get('idartlang'));
    $this_locked = cSecurity::toInteger($articleLanguage->get('locked'));
}

if ($this_locked == 1) {
    $disabled = 'disabled="disabled"';
    $oPage->displayWarning(i18n('This article is currently frozen and can not be edited!'));
}

$oTree = new pApiContentAllocationComplexList('06bd456d-fe76-40cb-b041-b9ba90dc400a');
$oAlloc = new pApiContentAllocation();

$requestAction = $_POST['action'] ?? '';
$requestStep = $_GET['step'] ?? '';

if ($requestAction == 'storeallocation') {
    $oAlloc->storeAllocations($this_idartlang, $_POST['allocation'] ?? []);
}
if ($requestStep == 'collapse') {
    $oTree->setTreeStatus($_GET['idpica_alloc']);
}

// Build category path
$syncoptions = $syncoptions ?? '';
$sLocationString = renderBackendBreadcrumb($syncoptions, true, true);

// load allocations
$loadedAllocations = $oAlloc->loadAllocations($this_idartlang);

$oTree->setChecked($loadedAllocations);
$result = $oTree->renderTree(true);

if (!$result) {
    $result = $notification->returnNotification('warning', i18n('There is no tagging tree.', 'content_allocation'));
} else {
    if (!is_object($tpl)) {
        $tpl = new cTemplate();
    }
    $hiddenfields = '<input type="hidden" name="action" value="storeallocation">
        <input type="hidden" name="idart" value="' . $idart . '">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <input type="hidden" name="area" value="' . $area . '">
        <input type="hidden" name="frame" value="' . $frame . '">
        <input type="hidden" name="idcat" value="' . $idcat . '">';
    $tpl->set('s', 'HIDDENFIELDS', $hiddenfields);

    if (sizeof($loadedAllocations) > 0) {
        $tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [' . implode(',', $loadedAllocations) . '];');
    } else {
        $tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [];');
    }

    $oDiv = new cHTMLDiv();
    $oDiv->updateAttributes([
        'style' => 'text-align:right;padding:5px;width:730px;border:1px #B3B3B3 solid;background-color:#FFF;'
    ]);
    $oDiv->setContent('<input type="image" alt="" src="images/but_ok.gif">');
    $tpl->set('s', 'DIV', '<br>' . $oDiv->render());

    $tpl->set('s', 'TREE', $result);

    // Show delete box only if article is not locked
    if ($this_locked == 0) {
        $tpl->set('s', 'REMOVE_ALL', i18n("Remove all", 'content_allocation'));
        $tpl->set('s', 'REMOVE', i18n("Remove", 'content_allocation'));
        $result = $tpl->generate($cfg['pica']['treetemplate_complexlist'], true);
    }

    $oPage->addStyle($cfg['pica']['style_complexlist']);
    $oPage->addScript($cfg['pica']['script_complexlist']);
}

// breadcrumb onclick
if (!isset($syncfrom)) {
    $syncfrom = -1;
}

$syncoptions = $syncfrom;
$sLocationString = <<<JS
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        $('div#categorypath > a').click(function() {
            var url = $(this).attr('href'),
                params = Con.UtilUrl.getParams(url);
            Con.multiLink(
                'right_top', 'main.php?area=con&frame=3&idcat=' + params.idcat + '&idtpl=' + params.idtpl + '&display_menu=1&syncoptions={$syncoptions}&contenido={$contenido}',
                'right_bottom', url,
                'left_bottom', 'main.php?area=con&frame=2&idcat=' + params.idcat + '&idtpl=' + params.idtpl + '&contenido={$contenido}'
            );
            return false;
        });
    });
})(Con, Con.$);
</script>
JS;

$div = new cHTMLDiv();
$div->setContent($sLocationString . $result);

$oPage->setContent($div);
$oPage->render();
