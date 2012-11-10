<?php
$tpl = new cTemplate();

// get start idcat
$iIdcatStart = (int) getEffectiveSetting('navigation', 'idcat-home', '1');

try {
    // get headline
    if ($iIdcatStart != intval($idcat)) {
        $category = new cApiCategoryLanguage();
        $category->loadByCategoryIdAndLanguageId($idcat, $lang);

        $sHeadline = $category->get('name');
    } else {
        $sHeadline = mi18n("WELCOME");
    }

    $sImgEdit = "CMS_IMGEDITOR[1]";
    $sImgSrc = "CMS_IMG[1]";

    $sCssStyle = '';
    if ($contenido && $edit) {
        echo '<div id="modHeaderImgEdit">' . $sImgEdit . '</div>';
    }
    if ($sImgSrc != '') {
        $sCssStyle = ' style="background-image:url(' . $sImgSrc . ');"';
    }

    $tpl->set('s', 'css-style', $sCssStyle);
    $tpl->set('s', 'url', 'front_content.php');
    $tpl->set('s', 'title', mi18n("TXT_WELCOME"));
    $tpl->set('s', 'headline', $sHeadline);
    $tpl->generate('templates/header.html');
} catch (cInvalidArgumentException $eI) {
    echo 'Some error occured: ' . $eI->getMessage() . ': ' . $eI->getFile() . ' at line ' . $eI->getLine() . ' (' . $eI->getTraceAsString() . ')';
} catch (Exception $e) {
    echo 'Some error occured: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line ' . $e->getLine() . ' (' . $e->getTraceAsString() . ')';
}

?>