<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Include file for editiing content of type CMS_TEXT
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
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2011-06-24, Rusmir Jusufovic loactioni: external/backendedit/front_content.php and not cms/front_content.php
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if(isset($area) && $area == 'con_content_list'){
	$tmp_area = $area;
	$href = "&action=10&idartlang=".$idartlang."&frame=4"."&lang=".$lang;
	$path1 = $cfg['path']['contenido_fullhtml']."main.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client".$href;
	$path2 = $path1;
	$inputHTML = "        <input type=hidden name=area value=\"$area\">\n".
				"        <input type=hidden name=frame value=\"4\">\n".
				"        <input type=hidden name=client value=\"$client\">\n";
} else {
	$tmp_area = 'con_editcontent';
	$path1 = $cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client";
	$path2 = $cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&lang=$lang";
	$inputHTML = "";
}

if ($doedit == "1") {
	conSaveContentEntry ($idartlang, "CMS_TEXT", $typenr, $CMS_TEXT);
	conMakeArticleIndex ($idartlang, $idart);
	conGenerateCodeForArtInAllCategories($idart);
	header( "location:".$sess->url($path1)."");
}
header("Content-Type: text/html; charset={$encoding[$lang]}");
?>
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
        
        echo "  <FORM name=\"editcontent\" method=\"post\" action=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php\">";
        $sess->hidden_session();
        echo "  <INPUT type=hidden name=lang value=\"$lang\">";
        echo "  <INPUT type=hidden name=typenr value=\"$typenr\">";
        echo "  <INPUT type=hidden name=idart value=\"$idart\">";
        echo "  <INPUT type=hidden name=action value=\"10\">";
        echo "  <INPUT type=hidden name=type value=\"$type\">";
        echo "<INPUT type=hidden name=doedit value=1>";        
        echo "  <INPUT type=hidden name=idcat value=\"$idcat\">";
        echo "  <INPUT type=hidden name=idartlang value=\"$idartlang\">";
        echo "<INPUT type=hidden name=changeview value=\"edit\">";
        echo $inputHTML;
        echo "  <TABLE cellpadding=$cellpadding cellspacing=$cellpadding border=0>";
        echo "  <TR><TD valign=top class=text_medium>&nbsp;".$typenr.".&nbsp;".$a_description[$type][$typenr].":&nbsp;</TD><TD class=content>";
        echo "  <TEXTAREA name=CMS_TEXT ROWS=15 COLS=90>".urldecode($a_content[$type][$typenr])."</TEXTAREA>";
        echo "  </TD></TR>";
        echo "  <TR valign=top><TD colspan=2><br>
                      <a href=".$sess->url($path2)."><img src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_cancel.gif\" border=0></a>
                      <INPUT type=image name=submit value=editcontent src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif\" border=0>
                      </TD></TR>";

        echo "  </TABLE>
                      </FORM>";

?>

</td></tr></table>
</body>
</HTML>