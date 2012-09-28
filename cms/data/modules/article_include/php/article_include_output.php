<?php
/**
 * Article Include Output
 *
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 */

$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$cfgClient = cRegistry::getClientConfig();
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();

// Get current settings
$cmsIdcat = "CMS_VALUE[1]";
$cmsIdcatart = "CMS_VALUE[2]";

// Check data
$cmsIdcat = (int) $cmsIdcat;
$cmsIdcatart = (int) $cmsIdcatart;

if ($cmsIdcat >= 0 && $cmsIdcatart >= 0) {
    $articleAvailable = false;
    // Get idcat, idcatart, idart and lastmodified from the database
    $sql = "SELECT
                A.idart AS idart, A.idcat AS idcat, A.createcode AS createcode,
                A.idcatart AS idcatart, B.lastmodified AS lastmodified
            FROM
                " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["art_lang"] . " AS B
            WHERE
                A.idart = B.idart AND B.online = 1 AND ";

    if ($cmsIdcatart == 0) {
        // Only idcat specified, get latest article of category
        $sql .= "A.idcat = '" . $cmsIdcat . "' ORDER BY B.lastmodified DESC";
    } else {
        // Article specified
        $sql .= "A.idcatart = '" . $cmsIdcatart . "'";
    }
    $db->query($sql);

    if ($db->next_record()) {
        $articleAvailable = true;
        $iIDCatArt = $db->f('idcatart');
        $iIDCat = $db->f('idcat');
        $iIDArt = $db->f('idart');
        $createCode = $db->f('createcode');
        $modified = $db->f('lastmodified');
    }

   $db->free();

   // Check if category is online or protected
   $sql = "SELECT public, visible
           FROM " . $cfg['tab']['cat_lang'] . "
           WHERE idcat = '" . $iIDCat . "' AND idlang = '" . $lang . "'";

    $db->query($sql);
    $db->next_record();

    $isPublic  = $db->f('public');
    $isVisible = $db->f('visible');

    $db->free();

    // If the article is online and the according category is not protected and visible, include the article
    if ($articleAvailable && $isPublic == 1 && $isVisible == 1) {
        // Check, if code creation is necessary
        // Note, that createcode may be 0, but no code is available (all code for other languages will be deleted in
        // front_content, if code for one language will be created). This "bug" may be fixed in future releases.
        if ($createCode == 0) {
            $createCode = !cFileHandler::exists($cfgClient[$client]['code']['path'] . $client . "." . $lang . "." . $iIDCatArt . ".php");
        }

        // Create code if necessary
        if ($createCode) {
            cInclude('includes', 'functions.con.php');
            cInclude('includes', 'functions.tpl.php');
            cInclude('includes', 'functions.mod.php');
            conGenerateCode($iIDCat, $iIDArt, $lang, $client);
        }

        if (cFileHandler::exists($cfgClient[$client]['code']['path'] . $client . "." . $lang . "." . $iIDCatArt . ".php")) {
            $code = stripslashes(cFileHandler::read($cfgClient[$client]['code']['path'] . $client . "." . $lang . "." . $iIDCatArt . ".php"));
            ob_start();
            eval("?>\n" . $code . "\n<?php\n");
            $code = ob_get_contents();

            // Clean buffer
            ob_end_clean();

            $startPos = strpos($code, '<!--start:content-->');
            $endPos   = strpos($code, '<!--end:content-->');
            $diffLength  = $endPos - $startPos;

            $code = substr($code, $startPos, $diffLength);

            echo $code;
        } else {
            echo "<!-- ERROR in module Article Include<pre>no code created for article to include!<br>idcat $cmsIdcat, idcatart $cmsIdcatart, idlang $lang, idclient $client</pre>-->";
        }
    }
}

?>