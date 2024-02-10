<?php

/**
 * This file contains the backend page for search results in upload section.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cApiUser $currentuser
 * @var cSession $sess
 * @var array $cfg
 * @var array $cfgClient
 * @var int $frame
 */

cInclude('includes', 'api/functions.frontend.list.php');
cInclude('includes', 'functions.upl.php');
cInclude('includes', 'functions.file.php');

$page = new cGuiPage('upl_search_results');

$client = cSecurity::toInteger(cRegistry::getClientId());
$area = cRegistry::getArea();

$resultsPerPageOptions = [
    10, 20, 50, 100, 200
];

$clientsUploadPath = $cfgClient[$client]['upl']['path'];
$clientsCachePath = $cfgClient[$client]['cache']['path'];
$clientsUploadUrlPath = $cfgClient[$client]['upl']['frontendpath'];
$clientsFrontendUrl = cRegistry::getFrontendUrl();

$appendparameters = $_REQUEST['appendparameters'] ?? '';
$searchfor = cSecurity::escapeString($_REQUEST['searchfor'] ?? '');
$startpage = cSecurity::toInteger($_REQUEST['startpage'] ?? '1');
$sortby = cSecurity::escapeString($_REQUEST['sortby'] ?? '');
$sortmode = cSecurity::escapeString($_REQUEST['sortmode'] ?? '');
$thumbnailmode = cSecurity::escapeString($_REQUEST['thumbnailmode'] ?? '');

if ($startpage < 1) {
    $startpage = 1;
}

if ($sortby == '') {
    $sortby = 7;
    $sortmode = 'DESC';
}

if (!in_array($sortmode, ['ASC', 'DESC'])) {
    $sortmode = 'DESC';
}

$thisfile = $sess->url("main.php?area=$area&frame=$frame&appendparameters=$appendparameters&searchfor=$searchfor&thumbnailmode=$thumbnailmode");
$scrollthisfile = $thisfile . "&sortmode=$sortmode&sortby=$sortby";

if ($sortby == 2 && $sortmode == 'DESC') {
    $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=2&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 2) {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=2&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=2&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '</a>';
    }
}

if ($sortby == 3 && $sortmode == 'DESC') {
    $pathsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Path") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 3) {
        $pathsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Path") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $pathsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Path") . "</a>";
    }
}

if ($sortby == 4 && $sortmode == 'DESC') {
    $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 4) {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . "</a>";
    }
}

if ($sortby == 5 && $sortmode == 'DESC') {
    $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 5) {
        $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . "</a>";
    }
}

if ($sortby == 6 && $sortmode == 'DESC') {
    $srelevance = '<a class="gray" href="' . $thisfile . '&sortby=6&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Relevance") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 6) {
        $srelevance = '<a class="gray" href="' . $thisfile . '&sortby=6&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Relevance") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $srelevance = '<a class="gray" href="' . $thisfile . '&sortby=6&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Relevance") . "</a>";
    }
}

// Templates

$sToolsRowTpl = '
    <tr class="textg_medium">
        <th colspan="6" class="con_navbar">
            <div class="right">' . i18n("Searched for:") . " " . $searchfor . '</div>
        </th>
    </tr>
';

$sSpacedRowTpl = '
    <tr>
        <td colspan="6" class="con_empty_cell"></td>
    </tr>
';

$sPagerWrapTpl = '
    <tr>
        <td colspan="6" class="con_navbar align_middle">
            <span class="align_middle no_wrap">' . i18n("Files per Page") . ' -C-FILESPERPAGE-</span>
            <div class="right">
                <div class="align_middle">-C-SCROLLLEFT-</div>
                <div class="align_middle">-C-PAGE-</div>
                <div class="align_middle">-C-SCROLLRIGHT-</div>
            </div>
        </td>
    </tr>
';

$sStartWrapTpl = '
<table class="hoverbox generic">
    ' . $sPagerWrapTpl . $sSpacedRowTpl . $sToolsRowTpl . $sSpacedRowTpl . '
    <tr>
        <th>' . i18n("Preview") . '</th>
        <th class="col_100p">' . $fnsort . '</th>
        <th>' . $pathsort . '</th>
        <th>' . $sizesort . '</th>
        <th>' . $typesort . '</th>
        <th>' . $srelevance . '</th>
    </tr>
';

$sItemWrapTpl = '
    <tr data-list-item="{LIST_ITEM_POS}">
        <td class="text_center align_middle">%s</td>
        <td class="align_middle no_wrap">%s</td>
        <td class="align_middle no_wrap">%s</td>
        <td class="align_middle no_wrap">%s</td>
        <td class="align_middle no_wrap">%s</td>
        <td class="text_center align_middle">%s</td>
    </tr>
';

$sEndWrapTpl = $sSpacedRowTpl . $sToolsRowTpl . $sSpacedRowTpl . $sPagerWrapTpl . '</table>';

// Object initializing
$list2 = new cFrontendListUploadSearchResult($sStartWrapTpl, $sEndWrapTpl, $sItemWrapTpl);

// Fetch data
$files = uplSearch($searchfor);

if ($thumbnailmode == '') {
    $current_mode = cSecurity::toInteger($currentuser->getUserProperty('upload_folder_thumbnailmode', md5('search_results_num_per_page')));
    if ($current_mode > 0) {
        $thumbnailmode = $current_mode;
    } else {
        $thumbnailmode = cSecurity::toInteger(getEffectiveSetting('backend', 'thumbnailmode', 100));
    }
}

if (in_array($thumbnailmode, $resultsPerPageOptions)) {
    $numpics = $thumbnailmode;
} else {
    $thumbnailmode = 100;
    $numpics = 15;
}

$currentuser->setUserProperty('upload_folder_thumbnailmode', md5('search_results_num_per_page'), $thumbnailmode);

$list2->setResultsPerPage(cSecurity::toInteger($numpics));

$rownum = 0;

arsort($files, SORT_NUMERIC);

foreach ($files as $idupl => $rating) {
    $upl = new cApiUpload($idupl);

    $filename = $upl->get('filename');
    $dirname = $upl->get('dirname');
    $fullDirname = $clientsUploadPath . $upl->get('dirname');

    $filesize = $upl->get('size');
    if ($filesize == 0 && cFileHandler::exists($fullDirname . $filename)) {
        $filesize = filesize($fullDirname . $filename);
        $upl->set('size', $filesize);
        $upl->store();
    }

    $fileType = cString::toLowerCase(cFileHandler::getExtension($filename));
    $list2->setData($rownum, $dirname . $filename, $filename, $dirname, $filesize, $fileType, $rating / 10, $dirname . $filename);

    $rownum++;
}

if ($rownum == 0) {
    $page->displayWarning(i18n("No files found"));
    $page->abortRendering();
    $page->render();
    return;
}

$list2->sort($sortby, ($sortmode == 'ASC' ? SORT_ASC : SORT_DESC));

if ($startpage > $list2->getNumPages()) {
    $startpage = $list2->getNumPages();
}

$list2->setListStart($startpage);

// Create scroller
if ($list2->getCurrentPage() > 1) {
    $prevpage = '<a href="javascript:void(0)" class="invert_hover" data-action="go_to_page" data-page="' . ($list2->getCurrentPage() - 1) . '">' . i18n("Previous Page") . '</a>';
} else {
    $prevpage = '&nbsp;';
}

if ($list2->getCurrentPage() < $list2->getNumPages()) {
    $nextpage = '<a href="javascript:void(0)" class="invert_hover" data-action="go_to_page" data-page="' . ($list2->getCurrentPage() + 1) . '">' . i18n("Next Page") . '</a>';
} else {
    $nextpage = '&nbsp;';
}

$paging_form = '';
if ($list2->getNumPages() > 1) {
    $num_pages = $list2->getNumPages();

    $select = new cHTMLSelectElement('start_page');
    $options = [];
    for ($i = 1; $i <= $num_pages; $i++) {
        $options[$i] = cSecurity::toString($i);
    }
    $select->autoFill($options)
        ->setDefault($startpage)
        ->setAttribute('data-action-change', 'change_start_page');

    $paging_form .= $select->render();
} else {
    $paging_form = '1';
}

$curpage = $paging_form . ' / ' . $list2->getNumPages();

$scroller = $prevpage . $nextpage;

$output = $list2->output(true);
$output = str_replace('-C-SCROLLLEFT-', $prevpage, $output);
$output = str_replace('-C-SCROLLRIGHT-', $nextpage, $output);
$output = str_replace('-C-PAGE-', i18n("Page") . ' ' . $curpage, $output);

$select = new cHTMLSelectElement('thumbnailmode');
$select->setClass('align_middle mgl3');
$options = [];
foreach ($resultsPerPageOptions as $value) {
    $options[$value] = cSecurity::toString($value);
}
$select->autoFill($options);
$select->setDefault($thumbnailmode);

$button = cHTMLButton::image('images/submit.gif', i18n('Search'), ['class' => 'con_img_button align_middle mgl3']);
$topbar = $select->render() . $button;

$output = str_replace('-C-FILESPERPAGE-', $topbar, $output);

$form = new cHTMLForm('upl_file_list');
$form->setClass('upl_files_overview');
$form->setVar('appendparameters', $appendparameters);
$form->setVar('area', $area);
$form->setVar('frame', $frame);
$form->setVar('searchfor', $searchfor);
$form->setVar('sortby', $sortby);
$form->setVar('sortmode', $sortmode);
$form->setVar('startpage', $startpage);
$form->setVar('thumbnailmode', $thumbnailmode);
// Table with (preview) images
$form->appendContent($output);

$page->addStyle($sess->url('includes/upl_files_overview.css'));
$page->addScript($sess->url('includes/upl_files_overview.js'));

$jsCode = '
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        // Instantiate upload files overview component
        new Con.UplFilesOverview({
            rootSelector: ".upl_files_overview",
            filesPerPageSelector: "select[name=thumbnailmode]",
            filesCheckBoxSelector: "input[name=\'fdelete[]\']",
            text_close: "' . i18n("Click to close") . '",
            text_delete_question: "' . i18n('Are you sure you want to delete the selected files?') . '",
        });
    });
})(Con, Con.$);
</script>
';
$form->appendContent($jsCode);

$page->set('s', 'FORM', $form->render());
$page->render();
