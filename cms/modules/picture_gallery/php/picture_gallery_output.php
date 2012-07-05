<?php
/**
 * Description: Picture gallery output
 *
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-09-30
 *   $Id$
 * }}
 */


cInclude('includes', 'functions.api.images.php');
cInclude('includes', 'functions.file.php');

// Gallery variables
$bRecursive = false;

// mi18n variables
$sSeeImage = mi18n("Bildvorschau");
$sDownImage = mi18n("Bild herunterladen");

$sPath = "CMS_VALUE[5]";
if ($sPath == '') {
    $sPath = $cfgClient[$client]['path']['frontend'] . $cfgClient[$client]['upl']['frontendpath'] . 'bildergalerie/';
} else {
    $sPath = $cfgClient[$client]['path']['frontend'] . $cfgClient[$client]['upl']['frontendpath'] . "CMS_VALUE[5]";
}

$iRows = (int) "CMS_VALUE[3]";
if ($iRows == 0) {
    $iRows = 2;
}

$iColumns = (int) "CMS_VALUE[2]";
if ($iColumns == 0) {
    $iColumns = 2;
}

$start = (int) $_REQUEST['start'];
if (isset($start) && $start != '') {
    $iCurrentPage = $start;
} else {
    $iCurrentPage = 1;
    $start = 1;
}

$iWidth = (int) "CMS_VALUE[0]";
if ($iWidth == 0) {
    $iWidth = 300;
}

$iHeight = (int) "CMS_VALUE[1]";
if ($iHeight == 0) {
    $iHeight = 300;
}

$iDetailWidth = (int) "CMS_VALUE[4]";
if ($iDetailWidth == 0) {
    $iDetailWidth = 300;
}

$aValidExtensions = array(
    'jpg',
    'jpeg',
    'gif',
    'png'
);

$iImagesPerPage = $iRows * $iColumns;

if ($_REQUEST['view'] == '') {
    // Read all gallery files
    $aGalleryFiles = scanDirectory($sPath, $bRecursive);

    $aAllLinks = array();

    if (is_array($aGalleryFiles)) {
        // Filter out non-images
        foreach ($aGalleryFiles as $key => $aGalleryFile) {
            $sExtension = strtolower(getFileType($aGalleryFile));
            if (!in_array($sExtension, $aValidExtensions)) {
                unset($aGalleryFiles[$key]);
            }
        }

        // Calculate effective variables
        $iFileCount = count($aGalleryFiles);
        $iPages = ceil($iFileCount / $iImagesPerPage);

        $aImagesToDisplay = array_slice($aGalleryFiles, ($iCurrentPage - 1) * $iImagesPerPage, $iImagesPerPage);

        $oImageTpl = new Template();
        $oGalleryTpl = new Template();
        $oEmptyImageTpl = new Template();

        $aRenderedImages = array();

        $iImagesRendered = 0;
        foreach ($aImagesToDisplay as $sImageToDisplay) {
            // Do Scaling
            $sScaledImage = cApiImgScale($sImageToDisplay, $iWidth, $iHeight);
            $sScaledImageHtmlPath = str_replace($cfgClient[$client]['path']['htmlpath'], '', $sScaledImage);

            $link = 'front_content.php?idcatart=' . $idcatart . '&amp;start=' . $start . '&amp;view=' . urlencode(str_replace($cfgClient[$client]['path']['frontend'], '', $sImageToDisplay));

            $description = ig_getImageDescription($sImageToDisplay);
            if ($description == '') {
                $description = '&nbsp;';
            }

            $sDownloadLink = str_replace($cfgClient[$client]['path']['frontend'], '', $sImageToDisplay);
            $sDownloadSize = ig_GetReadableFileSize($sImageToDisplay);

            $oImageTpl->reset();
            $oImageTpl->set('s', 'FILE', $sScaledImageHtmlPath);
            $oImageTpl->set('s', 'WIDTH', $iWidth);
            $oImageTpl->set('s', 'HEIGHT', $iHeight);
            $oImageTpl->set('s', 'LINK', $link);
            $oImageTpl->set('s', 'DESCRIPTION', $description);
            $oImageTpl->set('s', 'DOWNLOAD_LINK', $sDownloadLink);
            $oImageTpl->set('s', 'DOWNLOAD_SIZE', $sDownloadSize);
            $oImageTpl->set('s', 'DOWNLOAD_CAPTION', mi18n("Bild herunterladen"));
            $oImageTpl->set('s', 'PREVIEW_CAPTION', mi18n("Bildvorschau"));
            $oImageTpl->set('s', 'LINKDESCRIPTION', '');
            $oImageTpl->set('s', 'SEE_IMAGE', $sSeeImage);
            $oImageTpl->set('s', 'DOWNLOAD_IMAGE', $sDownImage);

            // Style links rechts
            $sStyle = '';
            $sStyle2 = '';

            if ((($iImagesRendered + 1) % 2) == 0) {
                $sStyle = 'text-align:right';
                $sStyle2 = 'padding-left:65px';
            } else {
                $sStyle = 'text-align:left';
                $sStyle2 = '';
            }
            $oImageTpl->set('s', 'style', $sStyle);
            $oImageTpl->set('s', 'style_2', $sStyle2);

            $aRenderedImages[] = $oImageTpl->generate('gallery_image.html', true, false);

            $iImagesRendered++;

            if ($iImagesRendered == $iColumns) {
                $oGalleryTpl->set('d', 'COLUMNS', implode('', $aRenderedImages));
                $oGalleryTpl->next();
                $iImagesRendered = 0;
                $aRenderedImages = array();
            }
        }

        if (count($aRenderedImages) < $iColumns && count($aRenderedImages) > 0) {
            $iEmptyCells = $iColumns - count($aRenderedImages);

            $oEmptyImageTpl->set('s', 'WIDTH', $iWidth);
            $oEmptyImageTpl->set('s', 'HEIGHT', $iHeight);

            $sEmptyCells = str_repeat($oEmptyImageTpl->generate('gallery_empty.html', true, false), $iEmptyCells);

            $oGalleryTpl->set('d', 'COLUMNS', implode('', $aRenderedImages) . $sEmptyCells);
            $oGalleryTpl->next();
        }

        // Begin Navigation Bottom
        $aLinks = array();

        if ($iCurrentPage == '') {
            $iCurrentPage = 1;
        }
        $sBack = sprintf("front_content.php?idcatart=%s&amp;start=%s", $idcatart, $iCurrentPage - 1);
        $sNext = sprintf("front_content.php?idcatart=%s&amp;start=%s", $idcatart, $iCurrentPage + 1);

        for ($i = 1; $i <= $iPages; $i++) {
            if ($i == $iCurrentPage) {
                $aAllLinks[$i] = $i;
            } else {
                $aAllLinks[$i] = sprintf("front_content.php?idcatart=%s&amp;start=%s", $idcatart, $i);
            }
        }

        $sHtml = '<a href="%s" title="%s"> %s </a>'; // Template

        if ($iPages == 1) { // if pages count is = 1
            $oCurrenTpl = new Template();

            $oCurrenTpl->set('s', 'Begin', '');
            $oCurrenTpl->set('s', 'Body', '');
            $oCurrenTpl->set('s', 'End', '');
            $aLinks[] = $oCurrenTpl->generate('gallery_link.html', true, false);
        }

        if ($iCurrentPage == 1 && $iPages > 1) { // current page =1

            $oTpl1 = new Template();

            $sNextButton = sprintf($sHtml, $sNext, mi18n("vor"), mi18n("&nbsp;vor&nbsp;") . '<img src="images/link_pfeil_klein.gif" />');
            $oTpl1->set('s', 'Begin', '');

            foreach ($aAllLinks as $key => $value) {
                #echo '<br> value: '.$value;
                if (strlen($value) > 7) { // longer as url
                    $sNumber = sprintf($sHtml, $value, $key, $key);
                } else {
                    $sNumber = $key;
                }

                $oTpl1->set('d', 'Body', $sNumber);
                $oTpl1->next();
                $sNumber = '';
            }

            $oTpl1->set('s', 'End', $sNextButton);
            $aLinks[] = $oTpl1->generate('gallery_link.html', true, false);
        }

        if ($iCurrentPage > 1 && ($iPages - $iCurrentPage) != 0) { // body see all
            $oPreviousTpl = new Template();

            $sBackButton = sprintf($sHtml, $sBack, mi18n("zur&uuml;ck"), "<img src='images/link_pfeil_klein_links.gif' />" . mi18n("&nbsp;zur&uuml;ck&nbsp;"));
            $sNextButton = sprintf($sHtml, $sNext, mi18n("vor"), mi18n("&nbsp;vor&nbsp;") . '<img src="images/link_pfeil_klein.gif" />');

            $oPreviousTpl->set('s', 'Begin', $sBackButton);

            foreach ($aAllLinks as $key => $value) {
                // Filter current page
                if (strlen($value) > 7) { // longer as url
                    $sNumber = sprintf($sHtml, $value, $key, $key);
                } else {
                    $sNumber = $key;
                }

                $oPreviousTpl->set('d', 'Body', $sNumber);
                $oPreviousTpl->next();
            }

            $oPreviousTpl->set('s', 'End', $sNextButton);

            $aLinks[] = $oPreviousTpl->generate('gallery_link.html', true, false);
        } else if ($iPages - $iCurrentPage == 0) { // this is end
            $oNextTpl = new Template();
            $oNextTpl->reset();
            $sBackButton = sprintf($sHtml, $sBack, mi18n("zur&uuml;ck"), "<img src='images/link_pfeil_klein_links.gif' />" . mi18n("&nbsp;zur&uuml;ck&nbsp;"));
            $oNextTpl->set('s', 'End', '');

            foreach ($aAllLinks as $key => $value) {
                // Filter current page
                if (strlen($value) > 7) { // longer as url
                    $sNumber = sprintf($sHtml, $value, $key, $key);
                } else {
                    $sNumber = $key;
                }

                $oNextTpl->set('d', 'Body', $sNumber);
                $oNextTpl->next();
            }

            $oNextTpl->set('s', 'Begin', $sBackButton);
            $aLinks[] = $oNextTpl->generate('gallery_link.html', true, false);
        }

        $oGalleryTpl->set('s', 'NAVIGATION', implode('', $aLinks));
        $oGalleryTpl->generate('gallery.html', false, false);
        $oGalleryTpl->reset(); // Navigation end
        unset($aAllLinks);
    }
} else {
    // See only one Image
    $sImageToDisplay = $cfgClient[$client]['path']['frontend'] . $_REQUEST['view'];
    $sScaledImage = cApiImgScale($sImageToDisplay, $iDetailWidth, 1000);
    $sScaledImageHtmlPath = str_replace($cfgClient[$client]['path']['htmlpath'], '', $sScaledImage);

    $description = ig_getImageDescription($sImageToDisplay);

    $sDownloadLink = str_replace($cfgClient[$client]['path']['frontend'], '', $sImageToDisplay);
    $sDownloadSize = ig_GetReadableFileSize($sImageToDisplay);

    $oImageTpl = new Template();
    $oImageTpl->set('s', 'IMG', $sScaledImageHtmlPath);
    $oImageTpl->set('s', 'BACKLINK', 'front_content.php?idcat=' . $idcat . '&amp;idart=' . $idart . '&amp;start=' . $start);
    $oImageTpl->set('s', 'BACKCAPTION', mi18n("&nbsp;zur&uuml;ck"));
    $oImageTpl->set('s', 'DESCRIPTION', $description);
    $oImageTpl->set('s', 'DOWNLOAD_LINK', $sDownloadLink);
    $oImageTpl->set('s', 'DOWNLOAD_SIZE', $sDownloadSize);
    $oImageTpl->set('s', 'DOWNLOAD_CAPTION', mi18n("Bild herunterladen&nbsp;"));

    $oImageTpl->generate('gallery_detail.html', false, false);
}

function ig_getImageDescription($idupl) {
    global $cfg, $cfgClient, $db, $client, $lang;

/*
    $cApiClient = new cApiClient($client);
    $language_separator = $cApiClient->getProperty('language', 'separator');
    if ($language_separator == "") {
        //Sanity, if module used in client without set client setting
        $language_separator = "§§§";
        $cApiClient->setProperty('language', 'separator', $language_separator);
    }
*/
    if (is_numeric($idupl)) {
        //ID is a number
        $query = "SELECT description FROM " . $cfg["tab"]["upl"] . " WHERE idupl = " . $idupl;
    } else {
        //ID is a string
        $path_parts = pathinfo($idupl);
        $upload = $cfgClient[$client]['upl']['frontendpath'];
        $len = strlen($upload);
        $pos = strpos($idupl, $upload);
        $dirname = substr($path_parts['dirname'], $pos + $len) . '/';
        $query = "SELECT description FROM " . $cfg["tab"]["upl"] . " WHERE (dirname = '" . $dirname . "') AND (filename ='" . $path_parts['basename'] . "') AND (filetype ='" . $path_parts['extension'] . "')";
    }
    $db->query($query);
    if ($db->next_record()) {
        return htmlspecialchars($db->f("description"));
    } else {
        return '';
    }
}

function ig_GetReadableFileSize($path) {
    $filesize = filesize($path);
    $unit = 'bytes';

    if ($filesize > 1024) {
        $filesize = ($filesize / 1024);
        $unit = 'kB';
    }
    if ($filesize > 1024) {
        $filesize = ($filesize / 1024);
        $unit = 'MB';
    }
    if ($filesize > 1024) {
        $filesize = ($filesize / 1024);
        $unit = 'GB';
    }
    if ($filesize > 1024) {
        $filesize = ($filesize / 1024);
        $unit = 'TB';
    }

    $filesize = round($filesize, 0);
    return $filesize . ' ' . $unit;
}
?>