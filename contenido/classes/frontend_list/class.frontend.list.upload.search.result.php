<?php

/**
 * This file contains the upload search result frontend list class.
 *
 * @since      CONTENIDO 4.10.2 - Class code extracted from `contenido/includes/include.upl_search_results.php`.
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * Class cFrontendListUploadSearchResult
 */
class cFrontendListUploadSearchResult extends cFrontendList
{

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
     * @inheritDoc
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function convert(int $field, $value)
    {
        global $appendparameters, $clientsUploadUrlPath, $clientsCachePath, $clientsFrontendUrl;
        global $clientsUploadPath;

        $cfg = cRegistry::getConfig();
        $sess = cRegistry::getSession();

        if ($field == 5) {
            if ($value == '') {
                return i18n("None");
            }
        }
        if ($field == 4) {
            return humanReadableSize($value);
        }

        if ($field == 3) {
            if ($value == '') {
                return '&nbsp;';
            } else {
                return $value;
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
                $mstr = '<a href="javascript:void(0)" data-action="add_file_from_browser" data-file="' . $fileUrlToAdd . '" title="' . $title . '">' . $icon . $value . '</a>';
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
                    $value
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
                    $value
                );
            }
            return $mstr;
        }

        if ($field == 1) {
            $this->_pathData = $value;

            // If this file is an image, try to open
            $this->_fileType = cString::toLowerCase(cFileHandler::getExtension($value));
            if (cFrontendListUpload::isImageFileType($this->_fileType)) {
                // Image thumbnail with link to show image in popup
                return cFrontendListUpload::getUploadImageLink((string) $value);
            } elseif ($this->_fileType == '')  {
                // Folder has empty filetype column value
                return '<img class="hover_none" alt="" src="' . cRegistry::getBackendUrl() . 'images/grid_folder.gif' . '">';
            } else {
                // Thumbnail for other file types
                $sCacheThumbnail = uplGetThumbnail($value, 150);
                return '<img class="hover_none" alt="" src="' . $sCacheThumbnail . '">';
            }
        }

        return $value;
    }

    /**
     * See {@see cFrontendListUpload::_getFileBrowserUrl()}
     */
    protected function _getFileBrowserUrl(string $subPath): string
    {
        return cFrontendListUpload::getFileBrowserUrl($subPath);
    }

}

/**
 * @deprecated [2024-02-04] Since 4.10.2, use {@see cFrontendListUploadSearchResult} instead!
 */
class UploadSearchResultList extends cFrontendListUploadSearchResult
{

    public function __construct(string $startWrap, string $endWrap, string $itemWrap)
    {
        cDeprecated("The class UploadSearchResultList is deprecated since CONTENIDO 4.10.2, use cFrontendListUploadSearchResult instead.");
        parent::__construct($startWrap, $endWrap, $itemWrap);
    }

}
