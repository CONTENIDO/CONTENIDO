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
    2, 10, 20, 50, 100, 200
];

$clientsUploadPath = $cfgClient[$client]['upl']['path'];
$clientsCachePath = $cfgClient[$client]['cache']['path'];
$clientsUploadUrlPath = $cfgClient[$client]['upl']['frontendpath'];
$clientsFrontendUrl = cRegistry::getFrontendUrl();

$appendparameters = $_REQUEST['appendparameters'] ?? '';
$searchfor        = cSecurity::escapeString($_REQUEST['searchfor'] ?? '');
$startpage        = cSecurity::toInteger($_REQUEST['startpage'] ?? '1');
$sortby           = cSecurity::escapeString($_REQUEST['sortby'] ?? '');
$sortmode         = cSecurity::escapeString($_REQUEST['sortmode'] ?? '');
$thumbnailmode    = cSecurity::escapeString($_REQUEST['thumbnailmode'] ?? '');

if ($startpage == '') {
    $startpage = 1;
}

if ($sortby == '') {
    $sortby = 7;
    $sortmode = 'DESC';
}

if (!in_array($sortmode, ['ASC', 'DESC'])) {
    $sortmode = 'DESC';
}

/**
 * Class UploadSearchResultList
 */
class UploadSearchResultList extends FrontendList {
    /**
     *
     * @var string
     */
    private $_pathData;

    /**
     *
     * @var string
     */
    private $_fileType;

    /**
     *
     * @var int
     */
    protected $_size;

    /**
     * Field converting facility.
     *
     * @see FrontendList::convert()
     *
     * @param int $field
     *         Field index
     * @param mixed $data
     *         Field value
     *
     * @return mixed
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function convert($field, $data) {
        global $appendparameters, $clientsUploadPath, $clientsUploadUrlPath, $clientsCachePath, $clientsFrontendUrl;

        $cfg = cRegistry::getConfig();
        $sess = cRegistry::getSession();

        if ($field == 5) {
            if ($data == '') {
                return i18n("None");
            }
        }
        if ($field == 4) {
            return humanReadableSize($data);
        }

        if ($field == 3) {
            if ($data == '') {
                return '&nbsp;';
            } else {
                return $data;
            }
        }

        if ($field == 2) {
            $vpath = str_replace($clientsUploadPath, '', $this->_pathData);
            $slashpos = cString::findLastPos($vpath, '/');
            if ($slashpos === false) {
                $file = $vpath;
                $path = '';
            } else {
                $path = cString::getPartOfString($vpath, 0, $slashpos + 1);
                $file = cString::getPartOfString($vpath, $slashpos + 1);
            }

            // Get rid of the slash hell...
            $subPath = trim(trim($path, '/') . '/' . $file, '/');

            if ($appendparameters == 'imagebrowser' || $appendparameters == 'filebrowser') {
                $fileUrlToAdd = $this->_getFileBrowserUrl($subPath);
                $title = i18n("Use file");
                $icon = '<img class="mgr5" src="' . $cfg['path']['images'] . '/but_ok.gif" alt="' . $title . '" title="' . $title . '" />';
                $mstr = '<a href="javascript:void(0)" data-action="add_file_from_browser" data-file="' . $fileUrlToAdd . '" title="' . $title . '">' . $icon . $data . '</a>';
            } elseif ('' !== $this->_fileType) {
                $markLeftPane = "Con.getFrame('left_bottom').upl.click(Con.getFrame('left_bottom').document.getElementById('$path'));";
                $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\');' . $markLeftPane . '">%s</a>';

                // Link to right_top first, so we can use history.back() in right_bottom!
                $mstr = sprintf(
                    $tmp_mstr,
                    'right_top',
                    $sess->url("main.php?area=upl&frame=3&path=$path&file=$file"),
                    'right_bottom',
                    $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$file"),
                    $data
                );
            } else {
                $markLeftPane = "Con.getFrame('left_bottom').upl.click(Con.getFrame('left_bottom').document.getElementById('$path'));";
                $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\');' . $markLeftPane . '">%s</a>';

                // Link to right_top first, so we can use history.back() in right_bottom!
                $mstr = sprintf(
                    $tmp_mstr,
                    'right_top',
                    $sess->url("main.php?area=upl&frame=3&path=$path&file=$file"),
                    'right_bottom',
                    $sess->url("main.php?area=upl&frame=4&path=$path$file/&file="),
                    $data
                );
            }
            return $mstr;
        }

        if ($field == 1) {
            $this->_pathData = $data;

            // If this file is an image, try to open
            $this->_fileType = cString::toLowerCase(cFileHandler::getExtension($data));
            switch ($this->_fileType) {
                case 'bmp':
                case 'gif':
                case 'iff':
                case 'jpeg':
                case 'jpg':
                case 'png':
                case 'tif':
                case 'tiff':
                case 'wbmp':
                case 'webp':
                case 'xbm':
                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    $sCacheName = basename($sCacheThumbnail);
                    $sFullPath = $clientsCachePath . $sCacheName;
                    if (cFileHandler::isFile($sFullPath)) {
                        $aDimensions = getimagesize($sFullPath);
                        $iWidth = $aDimensions[0];
                        $iHeight = $aDimensions[1];
                    } else {
                        $iWidth = 0;
                        $iHeight = 0;
                    }

                    if (cApiDbfs::isDbfs($data)) {
                        $href = $clientsFrontendUrl . 'dbfs.php?file=' . $data;
                    } else {
                        $href = $clientsFrontendUrl . $clientsUploadUrlPath . $data;
                    }
                    return '<a href="' . $href . '" data-action="zoom" data-action-mouseover="zoom">
                               <img class="hover" alt="" src="' . $sCacheThumbnail . '" data-width="' . $iWidth . '" data-height="' . $iHeight . '">
                               <img class="preview" alt="" src="' . $sCacheThumbnail . '">
                           </a>';
                case '':
                    // folder has empty filetype column value
                    return '<img class="hover_none" alt="" src="' . cRegistry::getBackendUrl() . 'images/grid_folder.gif' . '">';
                default:
                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    return '<img class="hover_none" alt="" src="' . $sCacheThumbnail . '">';
            }
        }

        return $data;
    }

    /**
     * @return int $size
     */
    public function getSize() {
        return $this->_size;
    }

    /**
     * @param int $size
     */
    public function setSize($size) {
        $this->_size = $size;
    }

    /**
     * Returns the url to the image/file to add to the wysiwyg editor.
     * Behaviour is configurable, see used effective setting.
     *
     * @param $subPath
     * @return string
     * @throws cDbException
     * @throws cException
     */
    protected function _getFileBrowserUrl($subPath) {
        global $appendparameters, $clientsUploadUrlPath, $clientsFrontendUrl;
        static $addWithFullUrl;

        if (!isset($addWithFullUrl)) {
            $addWithFullUrl = getEffectiveSetting($appendparameters, 'add_with_full_url', 'false');
            $addWithFullUrl = $addWithFullUrl === 'true';
        }
        if ($addWithFullUrl) {
            return $clientsFrontendUrl . $clientsUploadUrlPath . $subPath;
        } else {
            return $clientsUploadUrlPath . $subPath;
        }
    }

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
$list2 = new UploadSearchResultList($sStartWrapTpl, $sEndWrapTpl, $sItemWrapTpl);

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

$list2->setResultsPerPage($numpics);

$list2->setSize($thumbnailmode);

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
