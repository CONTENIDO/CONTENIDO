<?php

/**
 * Description: Article Include Output
 *
 * @package Module
 * @subpackage ContentArticleInclude
 * @version SVN Revision $Rev:$
 * @author Willi Man
 * @author alexander.scheider
 * @author frederic.schneider
 * @copyright four for business AG
 * @link http://www.4fb.de
 */


// Init vars and objects
$curContainerId = $cCurrentContainer;
$languageId = cRegistry::getLanguageId();
$clientId = cRegistry::getClientId();
$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$tpl = cSmartyFrontend::getInstance();
$saved = false;

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
        $cApiCatArt->loadByMany(array(
        		"idart" => $artId,
        		"idcat" => $catId
        ));

        if ($cApiCatArt->isLoaded()) {
            $catArtId = $cApiCatArt->get("idcatart");
        }
    }

    // Define data to save
    $postData = array('C' . $curContainerId . 'CMS_VAR' => array(
    				1 => $catId,
    				2 => $catArtId
    ));

    $tplCfgId = $artLang->get("idtplcfg");

    // If no specific category is for this article selected, use standard category layout
    if (!$tplCfgId) {
    	$catLang = new cApiCategoryLanguage($catId);
    	$tplCfgId = $catLang->get("idtplcfg");
    }

    // Template config id
    $CiCMS_Var = 'C' . $curContainerId . 'CMS_VAR';

    // Check values and create container value
    if (isset($postData[$CiCMS_Var]) && is_array($postData[$CiCMS_Var])) {

        if (!isset($containerData[$curContainerId])) {
            $containerData[$curContainerId] = '';
        }

        foreach ($postData[$CiCMS_Var] as $key => $value) {
            $containerData[$curContainerId] = cApiContainerConfiguration::addContainerValue($containerData[$curContainerId], $key, $value);
        }
    }

    // Update/insert in container_conf
    if (count($containerData) > 0) {

        // Insert new containers
        foreach ($containerData as $col => $val) {

            // Check config allready exists in db if yes update otherwise create
            $containerConf->loadByMany(array(
            		"idtplcfg" => $tplCfgId,
            		"number" => $col
            ));

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
if ($saved == true) {
    $cms_idcat = $postData['C' . $curContainerId . 'CMS_VAR'][1];
    $cms_idcatart = $postData['C' . $curContainerId . 'CMS_VAR'][2];
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

if ($cms_idcat != "0" && strlen($cms_idcat) > 0) {

    $sql = "
        SELECT
            a.title AS title,
            b.idcatart AS idcatart
        FROM
            " . $cfg["tab"]["art_lang"] . " AS a,
            " . $cfg["tab"]["cat_art"] . " AS b
            WHERE
            b.idcat = '" . $cms_idcat . "' AND
            a.idart = b.idart AND
            a.idlang = '" . $languageId . "'
	";

    $db->query($sql);
    $i = 1;
    while ($db->next_record()) {

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
$tpl->assign("articleSelect", $selectElement->toHTML());
$tpl->assign("ajaxUrl", cRegistry::getBackendUrl() ."ajaxmain.php");
$tpl->assign("articleIncludeSettingsLabel", mi18n("ARTICLE_INCLUDE_SETTINGS_LABEL"));
$tpl->assign("articleIncludeChooseCategoryLabel", mi18n("ARTICLE_INCLUDE_CHOOSE_CATEGORY_LABEL"));
$tpl->assign("articleIncludeChooseArticleLabel", mi18n("ARTICLE_INCLUDE_CHOOSE_ARTICLE_LABEL"));

// Display config only in backend mode
if (cRegistry::isBackendEditMode()) {
    $tpl->display("edit.tpl");
}

// Generate artice include code
if ($cms_idcat >= 0 && $cms_idcatart >= 0) {

    $isArticleAvailable = false;
    // Get idcat, idcatart, idart and lastmodified from the database
    $sql = "SELECT A.idart, A.idcat, A.createcode, A.idcatart, B.lastmodified
           FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["art_lang"] . " AS B
           WHERE
            A.idart = B.idart AND
            B.idlang = " . $languageId . " AND
            B.online = 1 AND ";

    if ($cms_idcatart == 0) {
        $sql .= "A.idcat = '" . $cms_idcat . "' ORDER BY B.lastmodified DESC";
    } else {
        $sql .= "A.idcatart = '" . $cms_idcatart . "'"; // Article specified
    }

    $db = cRegistry::getDb();
    $db->query($sql);

    if ($db->next_record()) {
        $isArticleAvailable = true;
        $includeCatArtId = $db->f("idcatart");
        $includeCatId = $db->f("idcat");
        $includeArtId = $db->f("idart");
        $createcode = $db->f("createcode");
        $lastmodified = $db->f("lastmodified");
    }

    $_bakArticleId = cRegistry::getArticleId();
    $_bakCategoryId = cRegistry::getCategoryId();
    $idart = $includeArtId;
    $idcat = $includeCatId;

    $db->free();

    // Check if category is online or protected
    $sql = "SELECT public, visible
           FROM " . $cfg["tab"]["cat_lang"] . "
           WHERE idcat = '" . $includeCatId . "' AND idlang = '" . $languageId . "'";

    $db->query($sql);
    $db->next_record();

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

        $posStart = strpos($code, "<!--start:content-->");
        $posEnd = strpos($code, "<!--end:content-->");
        $difflen = $posEnd - $posStart;

        $code = substr($code, $posStart, $difflen);

        echo $code;

        $idart = $_bakArticleId;
        $idcat = $_bakCategoryId;
    } else {
        echo "<!-- ERROR in module Article Include<pre>no code created for article to include!<br>idcat $cms_catid, idart $cms_artid, idlang $languageId, idclient $clientId</pre>-->";
    }
}

?>