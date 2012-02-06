<?php


/***********************************************
* Bildergalerie Output
*
* Author      :     Timo A. Hummel
* Copyright   :     four for business AG
* Created     :     30-09-2005
* Modified    :     10-04-2008 by Bilal Arslan added new Bottom Navigation and change style
* Modified    :     22-04-2009 by Timo Trautmann, fixed parse error bug, when picture gallery has only one page
************************************************/

cInclude("includes", "functions.api.images.php");

/* Gallery variables */
$bRecursive= false;

/* mi18n variables */
$sSeeImage= mi18n("Bildvorschau");
$sDownImage= mi18n("Bild herunterladen");

$sPath= "CMS_VALUE[5]";
if ($sPath == '') {
    $sPath= $cfgClient[$client]["path"]["frontend"] . $cfgClient[$client]["upl"]["frontendpath"] . "bildergalerie/";
} else {
    $sPath= $cfgClient[$client]["path"]["frontend"] . $cfgClient[$client]["upl"]["frontendpath"] . "CMS_VALUE[5]";
}

$iRows= "CMS_VALUE[3]";

if ($iRows == 0) {
    $iRows= 2;
}

$iColumns= "CMS_VALUE[2]";

if ($iColumns == 0) {
    $iColumns= 2;
}

$start= $_REQUEST['start'];

if (isset ($start) && $start != "") {
    $iCurrentPage= $start;
} else {
    $iCurrentPage= 1;
    $start= 1;
}

$iWidth= "CMS_VALUE[0]";
$iHeight= "CMS_VALUE[1]";

if ($iWidth == 0) {
    $iWidth= 300;
}

if ($iHeight == 0) {
    $iHeight= 300;
}

$iDetailWidth= "CMS_VALUE[4]";

if ($iDetailWidth == 0) {
    $iDetailWidth= 300;
}

$aValidExtensions= array (
    "jpg",
    "jpeg",
    "gif",
    "png"
);

$iImagesPerPage= $iRows * $iColumns;

if ($_REQUEST['view'] == '') {
    /* Read all gallery files */
    $aGalleryFiles= scanDirectory($sPath, $bRecursive);

    if (is_array($aGalleryFiles)) {
        /* Filter out non-images */
        foreach ($aGalleryFiles as $key => $aGalleryFile) {
            $sExtension= strtolower(getFileExtension($aGalleryFile));

            if (!in_array($sExtension, $aValidExtensions)) {
                unset ($aGalleryFiles[$key]);
            }
        }

        /* Calculate effective variables */
        $iFileCount = count($aGalleryFiles);
        $iPages = ceil($iFileCount / $iImagesPerPage);

        $aImagesToDisplay = array_slice($aGalleryFiles, ($iCurrentPage -1) * $iImagesPerPage, $iImagesPerPage);

        $oImageTpl = new Template();
        $oGalleryTpl = new Template();
        $oEmptyImageTpl = new Template();

        $aRenderedImages = array ();

        $iRow = 0;
        $iImagesRendered = 0;
        $j = 1;
        foreach ($aImagesToDisplay as $sImageToDisplay) {

            $sDownloadImage = str_replace($cfgClient[$client]['path']['frontend'], '', $sImageToDisplay);

            /* Do Scaling */
            $sScaledImage= cApiImgScale($sImageToDisplay, $iWidth, $iHeight);

            $link= 'front_content.php?idcatart=' . $idcatart . '&amp;start=' . $_REQUEST['start'] . '&amp;view=' . urlencode(str_replace($cfgClient[$client]['path']['frontend'], '', $sImageToDisplay));

            $description= ig_getImageDescription($sImageToDisplay);
            if ($description == '') {
                $description= '&nbsp;';
            }

            $download_link= str_replace($cfgClient[$client]['path']['frontend'], $cfgClient[$client]['path']['htmlpath'], $sImageToDisplay);

            $download_size= ig_GetReadableFileSize($sImageToDisplay);

            $oImageTpl->reset();
            $oImageTpl->set("s", "FILE", $sScaledImage);
            $oImageTpl->set("s", "WIDTH", $iWidth);
            $oImageTpl->set("s", "HEIGHT", $iHeight);
            $oImageTpl->set("s", "LINK", $link);
            $oImageTpl->set("s", "DESCRIPTION", $description);
            $oImageTpl->set("s", "DOWNLOAD_LINK", $download_link);
            $oImageTpl->set("s", "DOWNLOAD_SIZE", $download_size);
            $oImageTpl->set("s", "DOWNLOAD_CAPTION", mi18n("Bild herunterladen"));
            $oImageTpl->set("s", "PREVIEW_CAPTION", mi18n("Bildvorschau"));
            $oImageTpl->set("s", "LINK_DOWN", $sDownloadImage); // a href
            $oImageTpl->set("s", "LINKDESCRIPTION", '');
            $oImageTpl->set("s", "SEE_IMAGE", $sSeeImage);
            $oImageTpl->set("s", "DOWN_IMAGE", $sDownImage);
            $oImageTpl->set("s", "", $sDownImage);

            #style links rechts
            $sStyle= "";
            $sStyle2 = '';

            if (($j % 2) == 0) {
                $sStyle= 'text-align:right';
                $sStyle2 = 'padding-left:65px';
            } else {
                $sStyle= 'text-align:left';
                $sStyle2 = '';
            }
            $j++;
            $oImageTpl->set("s", "style", $sStyle);
            $oImageTpl->set("s", "style_2", $sStyle2);


            $aRenderedImages[]= $oImageTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_image.html", true, false);

            $iImagesRendered++;

            if ($iImagesRendered == $iColumns) {
                $oGalleryTpl->set("d", "COLUMNS", implode("", $aRenderedImages));
                $oGalleryTpl->next();
                $iImagesRendered= 0;
                $aRenderedImages= array ();
            }
        }

        if (count($aRenderedImages) < $iColumns && count($aRenderedImages) > 0) {
            $iEmptyCells= $iColumns -count($aRenderedImages);

            $oEmptyImageTpl->set("s", "WIDTH", $iWidth);
            $oEmptyImageTpl->set("s", "HEIGHT", $iHeight);

            $sEmptyCells= str_repeat($oEmptyImageTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_empty.html", true, false), $iEmptyCells);

            $oGalleryTpl->set("d", "COLUMNS", implode("", $aRenderedImages) . $sEmptyCells);
            $oGalleryTpl->next();
        }

        //      Begin Navigation Bottom
        $aLinks= array ();

        if ($iCurrentPage == "")
            $iCurrentPage= 1;
        $sBack= $cfgClient[$client]["path"]["htmlpath"] . sprintf("front_content.php?idcatart=%s&amp;start=%s", $idcatart, $iCurrentPage -1);
        $sNext= $cfgClient[$client]["path"]["htmlpath"] . sprintf("front_content.php?idcatart=%s&amp;start=%s", $idcatart, $iCurrentPage +1);

        for ($i= 1; $i <= $iPages; $i++) {

            if ($i == $iCurrentPage) {
                $aAllLinks[$i]= $i;
            } else {
                $aAllLinks[$i]= $cfgClient[$client]["path"]["htmlpath"] . sprintf("front_content.php?idcatart=%s&amp;start=%s", $idcatart, $i);
            }

        }

        $sHtml= '<a href="%s" title="%s"> %s </a>'; // Template

        if ($iPages == 1) { // if pages count is = 1
            $oCurrenTpl= new Template();

            $oCurrenTpl->set("s", "Begin", '');
            $oCurrenTpl->set("s", "Body", '');
            $oCurrenTpl->set("s", "End", '');
            $aLinks[]= $oCurrenTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_link.html", true, false);
        }

        if ($iCurrentPage == 1 && $iPages > 1) { // current page=1

            $oTpl1= new Template();

            $sNextButton= sprintf($sHtml, $sNext, mi18n("vor"), mi18n("&nbsp;vor&nbsp;") . '<img src="images/link_pfeil_klein.gif">');
            $oTpl1->set("s", "Begin", '');

            foreach ($aAllLinks as $key => $value) {
                #echo '<br> value: '.$value;
                if (strlen($value) > 7) { // longer as url
                    $sNumber= sprintf($sHtml, $value, $key, $key);
                } else {
                    $sNumber= $key;
                }

                $oTpl1->set('d', 'Body', $sNumber);
                $oTpl1->next();
                $sNumber= "";
            }

            $oTpl1->set("s", "End", $sNextButton);
            $aLinks[]= $oTpl1->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_link.html", true, false);
        }

        if ($iCurrentPage > 1 && ($iPages - $iCurrentPage) != 0) { // body see all
            $oPreviousTpl= new Template();

            $sBackButton= sprintf($sHtml, $sBack, mi18n("zur&uuml;ck"), "<img src='images/link_pfeil_klein_links.gif'/>" . mi18n("&nbsp;zur&uuml;ck&nbsp;"));
            $sNextButton= sprintf($sHtml, $sNext, mi18n("vor"), mi18n("&nbsp;vor&nbsp;") . '<img src="images/link_pfeil_klein.gif">');

            $oPreviousTpl->set("s", "Begin", $sBackButton);

            foreach ($aAllLinks as $key => $value) {
                #filter current page
                if (strlen($value) > 7) { // longer as url
                    $sNumber= sprintf($sHtml, $value, $key, $key);
                } else {
                    $sNumber= $key;
                }

                $oPreviousTpl->set('d', 'Body', $sNumber);
                $oPreviousTpl->next();

            }

            $oPreviousTpl->set("s", "End", $sNextButton);

            $aLinks[]= $oPreviousTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_link.html", true, false);
        } else
            if ($iPages - $iCurrentPage == 0) { // this is end
                $oNextTpl= new Template();
                $oNextTpl->reset();
                $sBackButton= sprintf($sHtml, $sBack, mi18n("zur&uuml;ck"), "<img src='images/link_pfeil_klein_links.gif'/>" . mi18n("&nbsp;zur&uuml;ck&nbsp;"));
                $oNextTpl->set("s", "End", '');

                foreach ($aAllLinks as $key => $value) {
                    #filter current page
                    if (strlen($value) > 7) { // longer as url
                        $sNumber= sprintf($sHtml, $value, $key, $key);
                    } else {
                        $sNumber= $key;
                    }

                    $oNextTpl->set('d', 'Body', $sNumber);
                    $oNextTpl->next();
                }

                $oNextTpl->set("s", "Begin", $sBackButton);
                $aLinks[]= $oNextTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_link.html", true, false);

            }

        $oGalleryTpl->set("s", "NAVIGATION", implode("", $aLinks));
        $oGalleryTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery.html", false, false);
        $oGalleryTpl->reset(); // Navigation end
        unset ($aAllLinks);
    }
} else { // See only one Image
    $sImageToDisplay= $cfgClient[$client]['path']['frontend'] . $_REQUEST['view'];
    $sScaledImage= cApiImgScale($sImageToDisplay, $iDetailWidth, 1000);

    $description= ig_getImageDescription($sImageToDisplay);

    $download_link= str_replace($cfgClient[$client]['path']['frontend'], $cfgClient[$client]['path']['htmlpath'], $sImageToDisplay);

    $download_size= ig_GetReadableFileSize($sImageToDisplay);

    $oImageTpl= new Template();
    $oImageTpl->set("s", "IMG", $sScaledImage);
    $oImageTpl->set("s", "BACKLINK", 'front_content.php?idcat=' . $idcat . '&amp;idart=' . $idart . '&amp;start=' . $_REQUEST['start']);
    $oImageTpl->set("s", "BACKCAPTION", mi18n("&nbsp;zur&uuml;ck"));
    $oImageTpl->set("s", "DESCRIPTION", $description);
    $oImageTpl->set("s", "DOWNLOAD_LINK", $download_link);
    $oImageTpl->set("s", "DOWNLOAD_SIZE", $download_size);
    $oImageTpl->set("s", "DOWNLOAD_CAPTION", mi18n("Bild herunterladen&nbsp;"));

    $oImageTpl->generate($cfgClient[$client]["path"]["frontend"] . "templates/gallery_detail.html", false, false);
}

function ig_getImageDescription($idupl) {

    global $cfg, $cfgClient, $db, $client, $lang;

    $cApiClient= new cApiClient($client);
    $language_separator= $cApiClient->getProperty('language', 'separator');
    if ($language_separator == "") {
        //Sanity, if module used in client without set client setting
        $language_separator= "§§§";
        $cApiClient->setProperty('language', 'separator', $language_separator);
    }
    if (is_numeric($idupl)) {
        //ID is a number
        $query= "SELECT description FROM " . $cfg["tab"]["upl"] . " WHERE idupl = " . $idupl;
    } else {
        //ID is a string
        $path_parts= pathinfo($idupl);
        $upload= $cfgClient[$client]['upl']['frontendpath'];
        $len= strlen($upload);
        $pos= strpos($idupl, $upload);
        $dirname= substr($path_parts['dirname'], $pos + $len) . '/';
        $query= "SELECT description FROM " . $cfg["tab"]["upl"] . " WHERE (dirname = '" . $dirname . "') AND (filename='" . $path_parts['basename'] . "') AND (filetype='" . $path_parts['extension'] . "')";
    }
    $db->query($query);
    if ($db->next_record()) {
        return htmlspecialchars(urldecode($db->f("description")));
    } else {
        return '';
    }
}

function ig_GetReadableFileSize($path) {
    $filesize= filesize($path);
    $unit= "bytes";

    if ($filesize > 1024) {
        $filesize= ($filesize / 1024);
        $unit= "kB";
    }
    if ($filesize > 1024) {
        $filesize= ($filesize / 1024);
        $unit= "MB";
    }
    if ($filesize > 1024) {
        $filesize= ($filesize / 1024);
        $unit= "GB";
    }
    if ($filesize > 1024) {
        $filesize= ($filesize / 1024);
        $unit= "TB";
    }

    $filesize= round($filesize, 0);
    return $filesize . " " . $unit;
}
?>