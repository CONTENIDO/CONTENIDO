<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Include file for editing content of type CMS_HEAD
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-07
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-01-05, Timo Trautmann, add htmlspecialchars() instead of htmlentitydecode() because of UTF-8 Bug
 *
 *   $Id: include.CMS_HEAD.php 934 2009-01-05 10:32:56Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ( $doedit == "1" ) {
    conSaveContentEntry ($idartlang, "CMS_HEAD", $typenr, $CMS_HEAD);
    conMakeArticleIndex ($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("location:".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&action=con_editart&idart=$idart&idartlang=$idartlang&idcat=$idcat&changeview=edit&client=$client")."");
    
}
header("Content-Type: text/html; charset={$encoding[$lang]}");
?>
<html>
<head>
<title></title>
<link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"] . $cfg["path"]["styles"] ?>contenido.css">
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>">
</HEAD>
<BODY MARGINHEIGHT=0 MARGINWIDTH=0 LEFTMARGIN=0 TOPMARGIN=0>
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
        
        echo "  <FORM method=\"post\" action=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["includes"]."include.backendedit.php\">";
        $sess->hidden_session();
        echo "  <INPUT type=hidden name=lang value=\"$lang\">";
//        echo "  <INPUT type=hidden name=submit value=\"editcontent\">";
        echo "  <INPUT type=hidden name=typenr value=\"$typenr\">";
        echo "  <INPUT type=hidden name=idart value=\"$idart\">";
        echo "  <INPUT type=hidden name=idcat value=\"$idcat\">";
        echo "  <INPUT type=hidden name=idartlang value=\"$idartlang\">";
        echo "  <INPUT type=hidden name=action value=\"10\">";
        echo "  <INPUT type=hidden name=doedit value=1>";
        echo "  <INPUT type=hidden name=type value=\"$type\">";
        echo "<INPUT type=hidden name=changeview value=\"edit\">";

        echo "  <TABLE cellpadding=$cellpadding cellspacing=$cellpadding border=0>";

        if ($type == "CMS_HEAD") {
                echo "  <TR><TD valign=\"top\" class=text_medium>&nbsp;".$typenr.".&nbsp;".$a_description[$type][$typenr].":&nbsp;</TD><TD class=content>";
                echo "  <INPUT type=text name=\"CMS_HEAD\" VALUE=\"".htmlspecialchars(urldecode($a_content[$type][$typenr]))."\" SIZE=90>";
                echo "  </TD></TR>";
        }
        
        $tmp_area = "con_editcontent";
        
        echo "  <TR valign=top><TD colspan=2><br>
                      <a href=".$sess->url($cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&lang=$lang")."><img src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_cancel.gif\" border=0></a>
                      <INPUT type=image name=submit value=editcontent src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif\" border=0>
                      </TD></TR>";

        echo "  </TABLE>
                      </FORM>";

?>
</td></tr></table>
</body>
</HTML>
