<?php

/**
 * Description: Article Include Output
 *
 * @package    Module
 * @subpackage ContentArticleInclude
 * @author     Willi Man
 * @author     alexander.scheider
 * @author     frederic.schneider
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

/**
 * @var int $cCurrentContainer
 */

// Init vars and objects
$curContainerId = $cCurrentContainer;
$languageId = cRegistry::getLanguageId();
$clientId = cRegistry::getClientId();
$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$tpl = cSmartyFrontend::getInstance();
$saved = false;
// Template config id
$CiCMS_Var = 'C' . $curContainerId . 'CMS_VAR';
$postData = [];

// Save send configuration
if (isset($_POST['categoryselect_' . $curContainerId]) && (isset($_POST['articleselect_' . $curContainerId]) || isset($_POST['articleselect_ajax_' . $curContainerId])) && cRegistry::isBackendEditMode()) {

    $containerConfColl = new cApiContainerConfigurationCollection();
    $containerConf = new cApiContainerConfiguration();
    $artLang = new cApiArticleLanguage(cRegistry::getArticleLanguageId());

    $catId = $_POST['categoryselect_' . $curContainerId];
    $catArtId = $_POST['articleselect_' . $curContainerId];
    $artId = $_POST['articleselect_ajax_' . $curContainerId];

    // Check if idart is send which loaded through ajax
    if (isset($artId)) {
        $cApiCatArt = new cApiCategoryArticle();
        $cApiCatArt->loadByMany([
            "idart" => $artId,
            "idcat" => $catId
        ]);

        if ($cApiCatArt->isLoaded()) {
            $catArtId = $cApiCatArt->get("idcatart");
        }
    }

    // Define data to save
    $postData = [
        $CiCMS_Var => [
            1 => $catId,
            2 => $catArtId
        ]
    ];

    $tplCfgId = $artLang->get("idtplcfg");

    // If no specific category is for this article selected, use standard category layout
    if (!$tplCfgId) {
        $catLang = new cApiCategoryLanguage();
        $catLang->loadByCategoryIdAndLanguageId(cRegistry::getCategoryId(), $languageId);
        $tplCfgId = $catLang->get("idtplcfg");
    }

    // Check values and create container value
    $containerData = [];
    if (isset($postData[$CiCMS_Var]) && is_array($postData[$CiCMS_Var])) {
        $containerData[$curContainerId] = '';
        foreach ($postData[$CiCMS_Var] as $key => $value) {
            $containerData[$curContainerId] = cApiContainerConfiguration::addContainerValue($containerData[$curContainerId], $key, $value);
        }
    }

    // Update/insert in container_conf
    if (count($containerData) > 0) {
        // Insert new containers
        foreach ($containerData as $col => $val) {
            // Check config already exists in db if yes update otherwise create
            $containerConf->loadByMany([
                "idtplcfg" => $tplCfgId,
                "number" => $col
            ]);

            if ($containerConf->isLoaded()) {
                $containerConf->set("container", $val);
                $containerConf->store();
            } else {
                $containerConfColl->create($tplCfgId, $col, $val);
            }

            $saved = true;
        }
    }
}

// Get settings for values
if ($saved === true) {
    $cms_idcat = $postData[$CiCMS_Var][1] ?? '';
    $cms_idcatart = $postData[$CiCMS_Var][2] ?? '';
} else {
    $cms_idcat = "CMS_VALUE[1]";
    $cms_idcatart = "CMS_VALUE[2]";
}

// Check data
$cms_idcat =  cSecurity::toInteger($cms_idcat);
$cms_idcatart =  cSecurity::toInteger($cms_idcatart);

// Create article select
$selectElement = new cHTMLSelectElement("articleselect_" . $curContainerId, "", "articleselect_" . $curContainerId);
$defOptionElement = new cHTMLOptionElement(mi18n("PLEASE_CHOOSE_LABEL"), 0);
$selectElement->addOptionElement(0, $defOptionElement);

if ($cms_idcat != "0" && cString::getStringLength($cms_idcat) > 0) {
    $sql = "
        SELECT
            a.title AS title, b.idcatart AS idcatart
        FROM
            `%s` AS a, `%s` AS b
        WHERE
            b.idcat = %d AND a.idart = b.idart AND a.idlang = %d
    ";

    $db->query($sql, $cfg["tab"]["art_lang"], $cfg["tab"]["cat_art"], $cms_idcat, $languageId);
    $i = 1;
    while ($db->nextRecord()) {
        $selectedCatArtId = $db->f('idcatart');
        $title = $db->f('title');

        if ($cms_idcatart != $selectedCatArtId) {
            $optionElement = new cHTMLOptionElement($title, $selectedCatArtId);
        } else {
            $optionElement = new cHTMLOptionElement($title, $selectedCatArtId, true);
        }
        $selectElement->addOptionElement($i, $optionElement);

        $i++;
    }
}

// Set template data for backend configuration
$tpl->assign("id", $curContainerId);
$tpl->assign("backendUrl", cRegistry::getBackendUrl());
$tpl->assign("categorySelect", buildCategorySelect("categoryselect_" . $curContainerId, $cms_idcat));
$tpl->assign("articleSelect", $selectElement->toHtml());
$tpl->assign("ajaxUrl", cRegistry::getBackendUrl() ."ajaxmain.php");
$tpl->assign("articleIncludeSettingsLabel", mi18n("ARTICLE_INCLUDE_SETTINGS_LABEL"));
$tpl->assign("articleIncludeChooseCategoryLabel", mi18n("ARTICLE_INCLUDE_CHOOSE_CATEGORY_LABEL"));
$tpl->assign("articleIncludeChooseArticleLabel", mi18n("ARTICLE_INCLUDE_CHOOSE_ARTICLE_LABEL"));
$tpl->assign('label', mi18n("ARTICLE_INCLUDE_LABEL"));

// Display config only in backend mode
if (cRegistry::isBackendEditMode()) {
    $tpl->display("edit.tpl");
}

// Generate article include code
if ($cms_idcat >= 0 && $cms_idcatart >= 0) {

    $isArticleAvailable = false;
    $db = cRegistry::getDb();

    // Get idcat, idcatart, idart and lastmodified from the database
    $sql = "
        SELECT 
            A.idart, A.idcat, A.createcode, A.idcatart, B.lastmodified
        FROM
            `%s` AS A, `%s` AS B
        WHERE
            A.idart = B.idart AND B.idlang = %d AND B.online = 1 AND 
    ";

    if ($cms_idcatart == 0) {
        $sql .= "A.idcat = %d ORDER BY B.lastmodified DESC";
        $cmsFieldId = $cms_idcat;
    } else {
        $sql .= "A.idcatart = %d"; // Article specified
        $cmsFieldId = $cms_idcatart;
    }

    $db->query($sql, $cfg["tab"]["cat_art"], $cfg["tab"]["art_lang"], $languageId, $cmsFieldId);

    $includeCatId = 0;
    $includeArtId = 0;

    if ($db->nextRecord()) {
        $isArticleAvailable = true;
        $includeCatArtId = $db->f("idcatart");
        $includeCatId = $db->f("idcat");
        $includeArtId = $db->f("idart");
        $createcode = $db->f("createcode");
        $lastmodified = $db->f("lastmodified");
    }

    // Backup common article & category related global variables,
    // evaluated article code below may overwrite them.
    $_bakArticleId = cRegistry::getArticleId();
    $_bakArticleLangId = cRegistry::getArticleLanguageId();
    $_bakCategoryId = cRegistry::getCategoryId();
    $_bakCategoryLangId = cRegistry::getCategoryLanguageId();
    $_bakCategoryArticleId = cRegistry::getCategoryArticleId();

    $idart = $includeArtId;
    $idcat = $includeCatId;

    $db->free();

    // Check if category is online or protected
    $sql = "
        SELECT
            public, visible
        FROM
            `%s`
        WHERE
            idcat = %d AND idlang = %d
    ";

    $db->query($sql, $cfg["tab"]["cat_lang"], $includeCatId, $languageId);
    $db->nextRecord();

    $public = $db->f("public");
    $visible = $db->f("visible");

    $db->free();

    // If the article is online and the according category is not protected and
    // visible, include the article
    if ($isArticleAvailable && $public == 1 && $visible == 1) {
        // Check, if code creation is necessary
        // Note, that createcode may be 0, but no code is available (all
        // code for other languages will be deleted in
        // front_content, if code for one language will be created). This
        // "bug" may be fixed in future releases.
        global $edit;

        $tmpView = $edit;
        $edit = 0;
        $GLOBALS['edit'] = 0;

        $code = conGenerateCode($includeCatId, $includeArtId, $languageId, $clientId);

        ob_start();
        eval("?>
        " . $code . "
        <?php
         ");
        $code = ob_get_contents();

        // Clean buffer
        ob_end_clean();

        $edit = $tmpView;
        $GLOBALS['edit'] = $tmpView;

        $posStart = cString::findFirstPos($code, "<!--start:content-->");
        $posEnd = cString::findFirstPos($code, "<!--end:content-->");
        $diffLen = $posEnd - $posStart;

        $code = cString::getPartOfString($code, $posStart, $diffLen);

        echo $code;
    } else {
        echo "<!-- ERROR in module Article Include<pre>no code created for article to include!<br>idcat $cms_idcat, idcatart $cms_idcatart, idlang $languageId, idclient $clientId</pre>-->";
    }

    // Restore globals for current context
    $idart = $_bakArticleId;
    $idartlang = $_bakArticleLangId;
    $idcat = $_bakCategoryId;
    $idcatlang = $_bakCategoryLangId;
    $idcatart = $_bakCategoryArticleId;
}

?>