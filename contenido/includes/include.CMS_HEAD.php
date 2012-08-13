<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Include file for editing content of type CMS_HEAD
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-05-07
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (isset($area) && $area == 'con_content_list') {
    $tmp_area = $area;
    $path1 = $cfg['path']['contenido_fullhtml'].'main.php?area=con_content_list&action=10&changeview=edit&idart='.$idart.'&idartlang='.$idartlang.
            '&idcat='.$idcat.'&client='.$client.'&lang='.$lang.'&frame=4&contenido='.$contenido;
    $path2 = $path1;
} else {
    $tmp_area = "con_editcontent";
    $path1 = $cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&action=con_editart&idart=$idart&idartlang=$idartlang&idcat=$idcat&changeview=edit&client=$client";
    $path2 = $cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&lang=$lang&client=$client";
}

if ($doedit == "1") {
    conSaveContentEntry($idartlang, "CMS_HEAD", $typenr, $CMS_HEAD);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("Location:".$sess->url($path1)."");
}
header("Content-Type: text/html; charset={$encoding[$lang]}");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title></title>
    <link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"] . $cfg["path"]["styles"] ?>contenido.css">
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>">
</head>
<body marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<table width="100%"  border=0 cellspacing="0" cellpadding="0">
  <tr>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="100%"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
    <td width="10" rowspan="4"><img src="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"] ?>spacer.gif" width="10" height="10"></td>
  </tr>
  <tr>
    <td>

<?php

    getAvailableContentTypes($idartlang);

    echo "  <form method=\"post\" action=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php\">";
    $sess->hidden_session();
    echo "  <input type=hidden name=lang value=\"$lang\">";
//        echo "  <input type=hidden name=submit value=\"editcontent\">";
    echo "  <input type=hidden name=typenr value=\"$typenr\">";
    echo "  <input type=hidden name=idart value=\"$idart\">";
    echo "  <input type=hidden name=idcat value=\"$idcat\">";
    echo "  <input type=hidden name=idartlang value=\"$idartlang\">";
    echo "  <input type=hidden name=action value=\"10\">";
    echo "  <input type=hidden name=doedit value=1>";
    echo "  <input type=hidden name=type value=\"$type\">";
    echo "<input type=hidden name=changeview value=\"edit\">";

    echo "  <table cellpadding=$cellpadding cellspacing=$cellpadding border=0>";

    if ($type == "CMS_HEAD") {
            echo "  <tr><td valign=\"top\" class=text_medium>&nbsp;".$typenr.".&nbsp;".$a_description[$type][$typenr].":&nbsp;</td><td class=content>";
            echo "  <input type=text name=\"CMS_HEAD\" value=\"".htmlspecialchars($a_content[$type][$typenr])."\" size=90>";
            echo "  </td></tr>";
    }

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
