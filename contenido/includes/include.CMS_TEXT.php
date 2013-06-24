<?php
/**
 * This file contains the editor page for content type CMS_TEXT.
 *
 * @package          Core
 * @subpackage       Backend_ContentType
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendUrl = cRegistry::getBackendUrl();

if (isset($area) && $area == 'con_content_list') {
    $tmp_area = $area;
    $href = "&action=10&idartlang=".$idartlang."&frame=4"."&lang=".$lang;
    $path1 = $backendUrl . "main.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client".$href;
    $path2 = $path1;
    $inputHTML = "    <input type=hidden name=area value=\"$area\">\n".
                 "    <input type=hidden name=frame value=\"4\">\n".
                 "    <input type=hidden name=client value=\"$client\">\n";
} else {
    $tmp_area = 'con_editcontent';
    $path1 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client";
    $path2 = $backendUrl . 'external/backendedit/' . "front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&lang=$lang&client=$client";
    $inputHTML = "";
}

if ($doedit == "1") {
    conSaveContentEntry($idartlang, "CMS_TEXT", $typenr, $CMS_TEXT);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("Location:".$sess->url($path1)."");
}
header("Content-Type: text/html; charset={$encoding[$lang]}");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title></title>
    <link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"] . $cfg["path"]["styles"] ?>contenido.css">
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>">
</head>
<body>
<table width="100%"  border=0 cellspacing="0" cellpadding="0" bgcolor="#ffffff">
  <tr>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="100%"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
  </tr>
  <tr>
    <td>

<?php
   getAvailableContentTypes($idartlang);

    echo "  <form name=\"editcontent\" method=\"post\" action=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php\">";
    echo "  <input type=hidden name=lang value=\"$lang\">";
    echo "  <input type=hidden name=typenr value=\"$typenr\">";
    echo "  <input type=hidden name=idart value=\"$idart\">";
    echo "  <input type=hidden name=action value=\"10\">";
    echo "  <input type=hidden name=type value=\"$type\">";
    echo "<input type=hidden name=doedit value=1>";
    echo "  <input type=hidden name=idcat value=\"$idcat\">";
    echo "  <input type=hidden name=idartlang value=\"$idartlang\">";
    echo "<input type=hidden name=changeview value=\"edit\">";
    echo $inputHTML;
    echo "  <table cellpadding=$cellpadding cellspacing=$cellpadding border=0>";
    echo "  <tr><td valign=top class=text_medium>&nbsp;".$typenr.".&nbsp;".$a_description[$type][$typenr].":&nbsp;</td><td class=content>";
    echo "  <textarea name=CMS_TEXT rows=15 cols=90>".$a_content[$type][$typenr]."</textarea>";
    echo "  </td></tr>";
    echo "  <tr valign=top><td colspan=2><br>
                  <a href=".$sess->url($path2)."><img src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_cancel.gif\" border=0></a>
                  <input type=image name=submit value=editcontent src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif\" border=0>
                  </td></tr>";

    echo "  </table>
                  </form>";

?>

</td></tr></table>
</body>
</html>