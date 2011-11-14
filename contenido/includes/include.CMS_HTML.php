<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Include file for editiing content of type CMS_HTML
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created  2003-05-07
 *   modified 2008-06-16, H. Librenz, Hotfix: check for illegal calls added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if ( $doedit == "1" || $doedit == "2" )
{
    conSaveContentEntry($idartlang, "CMS_HTML", $typenr, $CMS_HTML);
    conMakeArticleIndex ($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
}
if ( $doedit == "1" )
{
    header( "location:".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client")."");
}
header("Content-Type: text/html; charset={$encoding[$lang]}");
?>
<html>
<head>
<title></title>
<link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"].$cfg["path"]["styles"] ?>contenido.css">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>">
</head>

<body>
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

	echo "      <form method=\"post\" action=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php\">\n";
	echo "        ".$sess->hidden_session();
	echo "        <input type=hidden name=lang value=\"$lang\">\n";
	// echo "        <input type=hidden name=submit value=\"editcontent\">\n";
	echo "        <input type=hidden name=typenr value=\"$typenr\">\n";
	echo "        <input type=hidden name=idart value=\"$idart\">\n";
	echo "        <input type=hidden name=doedit value=1>\n";
	echo "        <input type=hidden name=action value=\"10\">\n";
	echo "        <input type=hidden name=type value=\"$type\">\n";
	echo "        <input type=hidden name=idcat value=\"$idcat\">\n";
	echo "        <input type=hidden name=idartlang value=\"$idartlang\">\n";
	echo "        <input type=hidden name=changeview value=\"edit\">\n";

	echo "        <table cellpadding=\"2\" width=\"100%\" cellspacing=\"0\" border=\"0\">\n";
	echo "          <tr>\n";
	echo "            <td valign=\"top\" class=\"text_medium\" nowrap>&nbsp;".$typenr.".&nbsp;".$a_description[$type][$typenr].":&nbsp;</td>\n";
	echo "          </tr>\n";
	echo "          <tr>\n";
	echo "            <td>";

	include ($cfg["path"]["wysiwyg"] . 'editor.php');

	echo "\n            </td>\n";
	echo "          </tr>\n";

	$tmp_area = "con_editcontent";
	echo "          <tr valign=\"top\">\n";
	echo "            <td colspan=\"2\"><br>\n";
	echo "              <a href=\"".$sess->url($cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&lang=$lang")."\"><img src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_cancel.gif\" border=\"0\"></a>\n";
	echo "              <input type=\"image\" name=\"save\" value=\"editcontent\" src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_refresh.gif\" border=\"0\" onclick=\"document.forms[0].doedit.value='2';document.forms[0].submit();\" alt=\"".i18n("Save without leaving the editor")."\" title=\"".i18n("Save without leaving the editor")."\" />\n";
	echo "              <input type=\"image\" name=\"submit\" value=\"editcontent\" src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif\" border=\"0\" alt=\"".i18n("Save and close editor")."\" title=\"".i18n("Save and close editor")."\" />\n";
	echo "            </td>\n";
	echo "          </tr>\n";
	echo "        </table>\n";
	echo "      </form>\n";
?>
    </td>
  </tr>
</table>
</body>
</html>