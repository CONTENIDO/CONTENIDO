<?php
/**
 * $RCSfile$
 *
 * Description: Article Include Output
 *
 * @version 1.0.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2003-12-18
 *   $Id$
 * }}
 */

// Get current settings
$cms_idcat    = "CMS_VALUE[1]";
$cms_idcatart = "CMS_VALUE[2]";

$bDebug = false;

// Check data
$cms_idcat    = (int)$cms_idcat;
$cms_idcatart = (int)$cms_idcatart;

if ($bDebug) {
   echo "<pre> cat $cms_idcat catart $cms_idcatart</pre>";
}

if ($cms_idcat >= 0 && $cms_idcatart >= 0) {
    $bArticleAvailable = false;
    // Get idcat, idcatart, idart and lastmodified from the database
    $sql = "SELECT
                A.idart AS idart, A.idcat AS idcat, A.createcode AS createcode,
                A.idcatart AS idcatart, B.lastmodified AS lastmodified
            FROM
                ".$cfg["tab"]["cat_art"]." AS A, ".$cfg["tab"]["art_lang"]." AS B
            WHERE
                A.idart = B.idart AND B.online = 1 AND ";

    if ($cms_idcatart == 0) {
        $sql .= "A.idcat = '" . $cms_idcat . "' ORDER BY B.lastmodified DESC"; # Only idcat specified, get latest article of category
    } else {
        $sql .= "A.idcatart = '" . $cms_idcatart . "'"; # Article specified
    }
    if ($bDebug) {
        echo "<pre>" . print_r($sql, true) . "</pre>";
    }
    $db->query($sql);

    if ($db->next_record()) {
        $bArticleAvailable = true;
        $iIDCatArt   = $db->f("idcatart");
        $iIDCat      = $db->f("idcat");
        $iIDArt      = $db->f("idart");
        $iCreateCode = $db->f("createcode");
        $sModified   = $db->f("lastmodified");
    }

   $db->free();

   // Check if category is online or protected
   $sql = "SELECT public, visible
           FROM " . $cfg["tab"]["cat_lang"] . "
           WHERE idcat = '" . $iIDCat . "' AND idlang = '" . $lang . "'";
    if ($bDebug) {
        echo "<pre>" . print_r($sql, true) . "</pre>";
    }


    $db->query($sql);
    $db->next_record();

    $iPublic  = $db->f("public");
    $iVisible = $db->f("visible");

    $db->free();

    // Check if article is online
    // Not needed anymore, as only online articles are used
    /* $sql = "SELECT online FROM " . $cfg["tab"]["art_lang"] . "
            WHERE idart = '" . $cms_artid . "' AND idlang = '" . $lang . "'";
    if ($bDebug) {
        echo "<pre>" . print_r($sql, true) . "</pre>";
    }
    $db->query($sql);
    $db->next_record();

    $online = $db->f("online");

    $db->free(); */

    // If the article is online and the according category is not protected and visible, include the article
    if ($bArticleAvailable && $iPublic == 1 && $iVisible == 1) {
        // Check, if code creation is necessary
        // Note, that createcode may be 0, but no code is available (all code for other languages will be deleted in
        // front_content, if code for one language will be created). This "bug" may be fixed in future releases.
        if ($iCreateCode == 0) {
            $iCreateCode = !cFileHandler::exists($cfgClient[$client]['code_path'].$client.".".$lang.".".$iIDCatArt.".php");
        }

        // Create code if necessary
        if ($iCreateCode == 1) {
            cInclude('includes', 'functions.con.php');
            cInclude('includes', 'functions.tpl.php');
            cInclude('includes', 'functions.mod.php');
            conGenerateCode($iIDCat, $iIDArt, $lang, $client);
        }

        if (cFileHandler::exists($cfgClient[$client]['code_path'].$client.".".$lang.".".$iIDCatArt.".php")) {
            $sCode = stripslashes(cFileHandler::read($cfgClient[$client]['code_path'].$client.".".$lang.".".$iIDCatArt.".php"));
            ob_start();
            eval("?>\n" . $sCode . "\n<?php\n");
            $sCode = ob_get_contents();

            // Clean buffer
            ob_end_clean();

            $iStartPos = strpos($sCode, '<!--start:content-->');
            $iEndPos   = strpos($sCode, '<!--end:content-->');
            $iDiffLen  = $iEndPos - $iStartPos;

            $sCode = substr($sCode, $iStartPos, $iDiffLen);

            echo $sCode;
        } else {
            echo "<!-- ERROR in module Article Include<pre>no code created for article to include!<br>idcat $cms_catid, idart $cms_artid, idlang $lang, idclient $client</pre>-->";
        }
    }
}

?>
