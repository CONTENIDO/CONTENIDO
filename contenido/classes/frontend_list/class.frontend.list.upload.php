<?php

/**
 * This file contains the upload frontend list class.
 *
 * @since      CONTENIDO 4.10.2 - Class code extracted from `contenido/includes/include.upl_files_overview.php`.
 * @package    Core
 * @subpackage Backend
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

class cFrontendListUpload extends cFrontendList
{

    /**
     * @var int
     */
    protected $_dataCount = 0;

    /**
     * @inheritDoc
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function convert(int $field, $value)
    {
        global $appendparameters, $clientsUploadUrlPath, $clientsCachePath, $clientsFrontendUrl;
        global $path, $startpage, $sortby, $sortmode, $thumbnailmode;

        $cfg = cRegistry::getConfig();
        $sess = cRegistry::getSession();

        if ($field == 4) {
            return humanReadableSize($value);
        }

        if ($field == 3) {
            // Get rid of the slash hell...
            $subPath = trim(trim($path, '/') . '/' . $value, '/');

            if ($appendparameters == 'imagebrowser' || $appendparameters == 'filebrowser') {
                $fileUrlToAdd = $this->_getFileBrowserUrl($subPath);
                $title = i18n("Use file");
                $icon = '<img class="mgr5" src="' . $cfg['path']['images'] . '/but_ok.gif" alt="' . $title . '" title="' . $title . '" />';
                $multiLink = '<a href="javascript:void(0)" data-action="add_file_from_browser" data-file="' . $fileUrlToAdd . '" title="' . $title . '">' . $icon . $value . '</a>';
            } else {
                $multiLink = '<a href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';

                // Link to right_top first, so we can use history.back() in right_bottom!
                $multiLink = sprintf(
                    $multiLink,
                    'right_top',
                    $sess->url("main.php?area=upl&frame=3&path=$path&file=$value"),
                    'right_bottom',
                    $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$value&appendparameters=$appendparameters&startpage=" . $startpage . "&sortby=" . $sortby . "&sortmode=" . $sortmode . "&thumbnailmode=" . $thumbnailmode),
                    $value
                );
            }
            return $multiLink;
        }

        if ($field == 5) {
            return uplGetFileTypeDescription($value);
        }

        if ($field == 2) {
            $fileType = cString::toLowerCase(cFileHandler::getExtension($value));
            if (self::isImageFileType($fileType)) {
                // Image thumbnail with link to show image in popup
                return self::getUploadImageLink((string) $value);
            } else {
                // Thumbnail for other file types
                $sCacheThumbnail = uplGetThumbnail($value, 150);
                return '<img class="hover_none" alt="" src="' . $sCacheThumbnail . '">';
            }
        }

        return $value;
    }

    /**
     * Sets the total count of data entries. This is needed for calculating the pages.
     * @param int $dataCount Total uploads count.
     */
    public function setDataCount(int $dataCount)
    {
        $this->_dataCount = $dataCount;
    }

    /**
     * Returns the number of pages.
     * If the data count variable is set it will be used instead counting the data array.
     * @return float|int
     */
    public function getNumPages(): int
    {
        if ($this->_dataCount > 0) {
            return (int) ceil($this->_dataCount / $this->_resultsPerPage);
        }

        return parent::getNumPages();
    }

    /**
     * @inheritDoc
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function output(bool $return = false)
    {
        // if the data count variable is not set, proceed with the previous logic
        if ($this->_dataCount === 0) {
            return parent::output($return);
        }

        // if the data count variable is set, display all contents from data array

        $output = $this->_startWrap;

        $count = count($this->_data);

        for ($i = 1; $i <= $count; $i++) {
            $currentPos = $i - 1;
            if (is_array($this->_data[$currentPos])) {
                $items = "";
                foreach ($this->_data[$currentPos] as $key => $value) {
                    $items .= ", '" . addslashes($this->convert($key, $value)) . "'";
                }

                $itemWrap = str_replace('{LIST_ITEM_POS}', $currentPos, $this->_itemWrap);
                $execute = '$output .= sprintf($itemWrap ' . $items . ');';
                eval($execute);
            }
        }

        $output .= $this->_endWrap;

        $output = stripslashes($output);

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    /**
     * Returns the url to the image/file to add to the wysiwyg editor.
     * Behaviour is configurable, see used effective setting.
     *
     * @param string $subPath
     * @return string
     * @throws cDbException
     * @throws cException
     */
    protected function _getFileBrowserUrl(string $subPath): string
    {
        return self::getFileBrowserUrl($subPath);
    }

    /**
     * See {@see cFrontendListUpload::_getFileBrowserUrl()}
     */
    public static function getFileBrowserUrl(string $subPath): string
    {
        global $appendparameters, $clientsUploadUrlPath, $clientsFrontendUrl;
        static $addWithFullUrl;

        if (cApiDbfs::isDbfs($subPath)) {
            $fileUrlToAdd = 'dbfs.php?file=' . $subPath;
        } else {
            $fileUrlToAdd = $clientsUploadUrlPath . $subPath;
        }

        if (!isset($addWithFullUrl)) {
            $addWithFullUrl = getEffectiveSetting($appendparameters, 'add_with_full_url', 'false');
            $addWithFullUrl = $addWithFullUrl === 'true';
        }
        if ($addWithFullUrl) {
            return $clientsFrontendUrl . $fileUrlToAdd;
        } else {
            return $fileUrlToAdd;
        }
    }

    /**
     * Checks if given file type is one of supported images file types.
     *
     * @param string $fileType
     * @return bool
     */
    public static function isImageFileType(string $fileType): bool
    {
        return in_array($fileType, [
            'bmp', 'gif', 'iff', 'jpeg', 'jpg', 'png', 'tif', 'tiff', 'wbmp', 'webp', 'xbm'
        ]);
    }

    /**
     * Builds the link to the upload image file.
     *
     * @param string $value
     * @return string
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public static function getUploadImageLink(string $value): string
    {
        global $clientsUploadUrlPath, $clientsCachePath, $clientsFrontendUrl;

        $sCacheThumbnail = uplGetThumbnail($value, 150);
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

        if (cApiDbfs::isDbfs($value)) {
            $href = $clientsFrontendUrl . 'dbfs.php?file=' . $value;
        } else {
            $href = $clientsFrontendUrl . $clientsUploadUrlPath . $value;
        }

        return '<a href="' . $href . '" data-action="zoom" data-action-mouseover="zoom">
                   <img class="hover" alt="" src="' . $sCacheThumbnail . '" data-width="' . $iWidth . '" data-height="' . $iHeight . '">
                   <img class="preview" alt="" src="' . $sCacheThumbnail . '">
               </a>';
    }

}

/**
 * @deprecated [2024-02-04] Since 4.10.2, use {@see cFrontendListUpload} instead!
 */
class UploadList extends cFrontendListUpload
{

    public function __construct(string $startWrap, string $endWrap, string $itemWrap)
    {
        cDeprecated("The class UploadList is deprecated since CONTENIDO 4.10.2, use cFrontendListUpload instead.");
        parent::__construct($startWrap, $endWrap, $itemWrap);
    }

}
