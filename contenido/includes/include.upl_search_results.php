<?php

/**
 * This file contains the backend page for search results in upload section.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cApiUser $currentuser
 * @var string $area
 * @var array $cfg
 * @var array $cfgClient
 * @var cSession $sess
 * @var int $client
 * @var int $frame
 */

cInclude('includes', 'api/functions.frontend.list.php');
cInclude('includes', 'functions.upl.php');
cInclude('includes', 'functions.file.php');

$appendparameters = $_REQUEST['appendparameters'] ?? '';
$sortby = $sortby ?? '';
$startpage = $startpage ?? '';
$thumbnailmode = $thumbnailmode ?? '';
$searchfor = $searchfor ?? '';

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
        global $appendparameters;

        $sess = cRegistry::getSession();
        $client = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();

        if ($field == 5) {
            if ($data == "") {
                return i18n("None");
            }
        }
        if ($field == 4) {
            return humanReadableSize($data);
        }

        if ($field == 3) {
            if ($data == "") {
                return "&nbsp;";
            } else {
                return $data;
            }
        }

        if ($field == 2) {
            // OK icon
            $icon = "<img src=\"images/but_ok.gif\" alt=\"\" />&nbsp;";

            $vpath = str_replace($cfgClient[$client]["upl"]["path"], "", $this->_pathData);
            $slashpos = cString::findLastPos($vpath, "/");
            if ($slashpos === false) {
                $file = $vpath;
            } else {
                $path = cString::getPartOfString($vpath, 0, $slashpos + 1);
                $file = cString::getPartOfString($vpath, $slashpos + 1);
            }

            if ($appendparameters == "imagebrowser" || $appendparameters == "filebrowser") {
                $mstr = '<a href="javascript:void(0)" onclick="javascript:Con.getFrame(\'left_top\').document.getElementById(\'selectedfile\').value= \'' . $cfgClient[$client]["upl"]["frontendpath"] . $path . $data . '\'; window.returnValue=\'' . $cfgClient[$client]["upl"]["frontendpath"] . $path . $data . '\'; window.close();">' . $icon . $data . '</a>';
            } elseif ('' !== $this->_fileType) {
                $markLeftPane = "Con.getFrame('left_bottom').upl.click(Con.getFrame('left_bottom').document.getElementById('$path'));";

                $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\');' . $markLeftPane . '">%s</a>';
                $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$file"), 'right_top', $sess->url("main.php?area=upl&frame=3&path=$path&file=$file"), $data);
            } else {
                $markLeftPane = "Con.getFrame('left_bottom').upl.click(Con.getFrame('left_bottom').document.getElementById('$path'));";

                $tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\');' . $markLeftPane . '">%s</a>';
                // concatenate path with folder name (file) for path parameter to access folder
                $mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=upl&frame=4&path=$path$file/&file="), 'right_top', $sess->url("main.php?area=upl&frame=3&path=$path&file=$file"), $data);
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
                    $frontendURL = cRegistry::getFrontendUrl();

                    $sCacheThumbnail = uplGetThumbnail($data, 150);
                    $sCacheName = basename($sCacheThumbnail);
                    $sFullPath = $cfgClient[$client]['cache']['path'] . $sCacheName;
                    if (cFileHandler::isFile($sFullPath)) {
                        $aDimensions = getimagesize($sFullPath);
                        $iWidth = $aDimensions[0];
                        $iHeight = $aDimensions[1];
                    } else {
                        $iWidth = 0;
                        $iHeight = 0;
                    }

                    if (cApiDbfs::isDbfs($data)) {
                        $href = $frontendURL . 'dbfs.php?file=' . $data;
                    } else {
                        $href = $frontendURL . $cfgClient[$client]['upload'] . $data;
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
}

if ($sortby == "") {
    $sortby = 7;
    $sortmode = "DESC";
}

if ($startpage == "") {
    $startpage = 1;
}

$thisfile = $sess->url("main.php?area=$area&frame=$frame&appendparameters=$appendparameters&searchfor=$searchfor&thumbnailmode=$thumbnailmode");
$scrollthisfile = $thisfile . "&sortmode=$sortmode&sortby=$sortby";

if ($sortby == 2 && $sortmode == "DESC") {
    $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=2&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 2) {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=2&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $fnsort = '<a class="gray" href="' . $thisfile . '&sortby=2&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Filename / Description") . '</a>';
    }
}

if ($sortby == 3 && $sortmode == "DESC") {
    $pathsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Path") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 3) {
        $pathsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Path") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $pathsort = '<a class="gray" href="' . $thisfile . '&sortby=3&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Path") . "</a>";
    }
}

if ($sortby == 4 && $sortmode == "DESC") {
    $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 4) {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Size") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $sizesort = '<a class="gray" href="' . $thisfile . '&sortby=4&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Size") . "</a>";
    }
}

if ($sortby == 5 && $sortmode == "DESC") {
    $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 5) {
        $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Type") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $typesort = '<a class="gray" href="' . $thisfile . '&sortby=5&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Type") . "</a>";
    }
}

if ($sortby == 6 && $sortmode == "DESC") {
    $srelevance = '<a class="gray" href="' . $thisfile . '&sortby=6&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Relevance") . '<img src="images/sort_down.gif" alt=""></a>';
} else {
    if ($sortby == 6) {
        $srelevance = '<a class="gray" href="' . $thisfile . '&sortby=6&sortmode=DESC&startpage=' . $startpage . '">' . i18n("Relevance") . '<img src="images/sort_up.gif" alt=""></a>';
    } else {
        $srelevance = '<a class="gray" href="' . $thisfile . '&sortby=6&sortmode=ASC&startpage=' . $startpage . '">' . i18n("Relevance") . "</a>";
    }
}

$sToolsRow = '<tr class="textg_medium">
                  <th colspan="6" id="cat_navbar">
                      <div class="toolsRight">' . i18n("Searched for:") . " " . $searchfor . '</div>
                  </th>
              </tr>';

// List wraps

$sSpacedRow = '<tr height="10">
                    <td colspan="6" class="empty_cell"></td>
               </tr>';

$pagerwrap = '<tr>
                <th colspan="6" id="cat_navbar" class="vAlignMiddle">
                    <div class="toolsRight">
                        <div class="vAlignMiddle">-C-SCROLLLEFT-</div>
                        <div class="vAlignMiddle">-C-PAGE-</div>
                        <div class="vAlignMiddle">-C-SCROLLRIGHT-</div>
                    </div>
                    ' . i18n("Files per Page") . ' -C-FILESPERPAGE-
                </th>
            </tr>';

$startwrap = '<table class="hoverbox generic">
                ' . $pagerwrap . $sSpacedRow . $sToolsRow . $sSpacedRow . '
               <tr>
                    <th>' . i18n("Preview") . '</th>
                    <th width="100%">' . $fnsort . '</th>
                    <th>' . $pathsort . '</th>
                    <th>' . $sizesort . '</th>
                    <th>' . $typesort . '</th>
                    <th>' . $srelevance . '</th>
                </tr>';
$itemwrap = '<tr data-list-item="{LIST_ITEM_POS}">
                    <td align="center">%s</td>
                    <td class="vAlignTop nowrap">%s</td>
                    <td class="vAlignTop nowrap">%s</td>
                    <td class="vAlignTop nowrap">%s</td>
                    <td class="vAlignTop nowrap">%s</td>
                    <td class="vAlignTop nowrap">%s</td>
                </tr>';
$endwrap = $sSpacedRow . $sToolsRow . $sSpacedRow . $pagerwrap . '</table>';

// Object initializing
$page = new cGuiPage("upl_search_results");
$list2 = new UploadSearchResultList($startwrap, $endwrap, $itemwrap);

$uploads = new cApiUploadCollection();

// Fetch data
$files = uplSearch($searchfor);

if ($thumbnailmode == '') {
    $current_mode = $currentuser->getUserProperty('upload_folder_thumbnailmode', md5('search_results_num_per_page'));
    if ($current_mode != '') {
        $thumbnailmode = $current_mode;
    } else {
        $thumbnailmode = getEffectiveSetting('backend', 'thumbnailmode', 100);
    }
}

switch ($thumbnailmode) {
    case 25:
        $numpics = 25;
        break;
    case 50:
        $numpics = 50;
        break;
    case 100:
        $numpics = 100;
        break;
    case 200:
        $numpics = 200;
        break;
    default:
        $thumbnailmode = 100;
        $numpics = 15;
        break;
}

$currentuser->setUserProperty('upload_folder_thumbnailmode', md5('search_results_num_per_page'), $thumbnailmode);

$list2->setResultsPerPage($numpics);

$list2->setSize($thumbnailmode);

$rownum = 0;
if (!is_array($files)) {
    $files = [];
}

arsort($files, SORT_NUMERIC);

foreach ($files as $idupl => $rating) {
    $upl = new cApiUpload($idupl);

    $filename = $upl->get('filename');
    $dirname = $upl->get('dirname');
    $fullDirname = $cfgClient[$client]["upl"]["path"] . $upl->get('dirname');

    $filesize = $upl->get('size');
    if ($filesize == 0 && cFileHandler::exists($fullDirname . $filename)) {
        $filesize = filesize($fullDirname . $filename);
        $upl->set('size', $filesize);
        $upl->store();
    }
    $description = $upl->get('description');

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

if ($sortmode == "ASC") {
    $list2->sort($sortby, SORT_ASC);
} else {
    $list2->sort($sortby, SORT_DESC);
}

if ($startpage < 1) {
    $startpage = 1;
}

if ($startpage > $list2->getNumPages()) {
    $startpage = $list2->getNumPages();
}

$list2->setListStart($startpage);

// Create scroller
if ($list2->getCurrentPage() > 1) {
    $prevpage = '<a href="' . $scrollthisfile . '&startpage=' . ($list2->getCurrentPage() - 1) . '" class="invert_hover">' . i18n("Previous Page") . '</a>';
} else {
    $nextpage = '&nbsp;';
}

if ($list2->getCurrentPage() < $list2->getNumPages()) {
    $nextpage = '<a href="' . $scrollthisfile . '&startpage=' . ($list2->getCurrentPage() + 1) . '" class="invert_hover">' . i18n("Next Page") . '</a>';
} else {
    $nextpage = '&nbsp;';
}

$paging_form = '';
if ($list2->getNumPages() > 1) {
    $num_pages = $list2->getNumPages();

    $paging_form .= "<script type=\"text/javascript\">
        function jumpToPage(select) {
            var pagenumber = select.selectedIndex + 1;
            url = '" . $sess->url("main.php?area=$area&frame=$frame&appendparameters=$appendparameters&searchfor=$searchfor&thumbnailmode=$thumbnailmode") . "';
            document.location.href = url + '&startpage=' + pagenumber;
        }
    </script>";
    $paging_form .= "<select name=\"start_page\" class=\"text_medium\" onChange=\"jumpToPage(this);\">";
    for ($i = 1; $i <= $num_pages; $i++) {
        if ($i == $startpage) {
            $selected = " selected";
        } else {
            $selected = "";
        }
        $paging_form .= "<option value=\"$i\"$selected>$i</option>";
    }

    $paging_form .= "</select>";
} else {
    $paging_form = "1";
}

$curpage = $paging_form . " / " . $list2->getNumPages();

$scroller = $prevpage . $nextpage;

$output = $list2->output(true);

$output = str_replace("-C-SCROLLLEFT-", $prevpage, $output);
$output = str_replace("-C-SCROLLRIGHT-", $nextpage, $output);
$output = str_replace("-C-PAGE-", i18n("Page") . " " . $curpage, $output);
$output = str_replace("-C-THUMBNAILMODE-", $thumbnailmode, $output);

$form = new cHTMLForm("upl_file_list");
$form->setClass('upl_files_overview');
$form->setVar("contenido", $sess->id);
$form->setVar("area", $area);
$form->setVar("frame", $frame);
$form->setVar("searchfor", $searchfor);
$form->setVar("sortmode", $sortmode);
$form->setVar("sortby", $sortby);
$form->setVar("startpage", $startpage);
$form->setVar("appendparameters", $appendparameters);

$select = new cHTMLSelectElement("thumbnailmode");
$select->setClass("vAlignMiddle tableElement");
$values = [
    25 => "25",
    50 => "50",
    100 => "100",
    200 => "200"
];
$select->autoFill($values);
$select->setDefault($thumbnailmode);

$topbar = $select->render() . '<input type="image" src="images/submit.gif" class="img_form_submit vAlignMiddle tableElement">';

$output = str_replace("-C-FILESPERPAGE-", $topbar, $output);

$page->addStyle($sess->url("includes/upl_files_overview.css"));
$page->addScript($sess->url("includes/upl_files_overview.js"));

$form->appendContent($output);

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


$page->set("s", "FORM", $form->render());
$page->render();
