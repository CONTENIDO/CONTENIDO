<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Include file for editiing content of type CMS_SWF
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
 *
 *   $Id: include.CMS_SWF.php 775 2008-09-03 16:30:44Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ($doedit == "1") {
    conSaveContentEntry ($idartlang, "CMS_SWF", $typenr, $CMS_SWF);
    conMakeArticleIndex ($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("location:".$sess->url($cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client"));
}

?>
<html>
<head>
<title>contenido</title>
<link rel="stylesheet" type="text/css" href="<?php print $cfg["path"]["contenido_fullhtml"] . $cfg["path"]["styles"] ?>contenido.css">
</HEAD>
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
        echo "  <INPUT type=hidden name=idcat value=\"$idcat\">";
        echo "  <INPUT type=hidden name=idartlang value=\"$idartlang\">";
        echo "<INPUT type=hidden name=doedit value=1>";
        echo "  <INPUT type=hidden name=action value=\"10\">";
        echo "  <INPUT type=hidden name=type value=\"$type\">";
        echo "<INPUT type=hidden name=changeview value=\"edit\">";

        echo "  <TABLE cellpadding=$cellpadding cellspacing=$cellpadding border=0>";

        echo "  <TR><TD valign=\"top\" class=\"text_medium\" nowrap>&nbsp;".$typenr.".&nbsp;".$a_description["CMS_SWF"][$typenr].":&nbsp;</TD><TD class=content>";
                echo "<SELECT name=CMS_SWF SIZE=1>";
                echo "<option value=0>-- ".i18n("None")." --</option>";
                
                $sql = "SELECT idupl, dirname, filename FROM ".$cfg["tab"]["upl"]." WHERE idclient='".Contenido_Security::toInteger($client)."' AND filetype = 'swf' ORDER BY filename";

                $db->query($sql);

                while ( $db->next_record() ) {

                    if ( $db->f("idupl") != $a_content['CMS_SWF'][$typenr] ) {

                        echo "<option value=\"".$db->f("idupl")."\">".$db->f("dirname").$db->f("filename")."</option>";
                        
                    } else {

                        echo "<option value=\"".$db->f("idupl")."\" selected=\"selected\">".$db->f("dirname").$db->f("filename")."</option>";
                        
                    }
                }

                echo "</SELECT>";
        echo "  </TD></TR>";

        $tmp_area = "con_editcontent";
        
               echo "  <TR valign=top><TD colspan=2><br>
                      <a href=".$sess->url($cfgClient[$client]["path"]["htmlpath"]."front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&idartlang=$idartlang")."><img src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_cancel.gif\" border=0></a>
                      <INPUT type=image name=submit value=editcontent src=\"".$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif\" border=0>
                      </TD></TR>";

        echo "  </TABLE>
                      </FORM>";

?>
</td></tr></table>
</body>
</HTML>