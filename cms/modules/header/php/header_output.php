<?php
/**
 * Description: Display Header with CONTENIDO Logo, an image and the current (navigation-) location.
 * If no image is selected, the default one will be used.
 *
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-04-07
 *   $Id$
 * }}
 */

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new Template();
}

// get start idcat
$iIdcatStart = (int) getEffectiveSetting('navigation', 'idcat-home', '1');

try {
    // get headline
    $oConCat = new Contenido_Category($db, $cfg);
    $oConCat->load($idcat, true, $lang);
    $sImgEdit = "CMS_IMAGE[1]";
    $sImgSrc = "CMS_IMG[1]";

    $sHeadline = $iIdcatStart != intval($idcat)
                 ? $oConCat->getCategoryLanguage()->getName()
                 : mi18n("Willkommen!");
    $sCssStyle = '';
    if ($contenido && $edit) {
        echo '<div id="modHeaderImgEdit">'.$sImgEdit.'</div>';
        $sCssStyle = ' style="background-image:url('.$sImgSrc.');"';
    } else {
        $sCssStyle = ' style="background-image:url('.$sImgSrc.');"';
    }
    $tpl->reset();
    $tpl->set('s', 'css-style', $sCssStyle);
    $tpl->set('s', 'url', 'front_content.php');
    $tpl->set('s', 'title', mi18n("Zur CONTENIDO Homepage"));
    $tpl->set('s', 'headline', $sHeadline);
    $tpl->generate('templates/header.html');
} catch (InvalidArgumentException $eI) {
    echo 'Some error occured: ' . $eI->getMessage() . ': ' . $eI->getFile() . ' at line '.$eI->getLine() . ' ('.$eI->getTraceAsString().')';
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}

?>