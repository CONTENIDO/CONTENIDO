<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id: insert_link.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {                                                                       
   die('Illegal call');
}


?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD W3 HTML 3.2//EN">
<html id=dlgImage style="width: 40.1em; height: 18em">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <meta http-equiv="MSThemeCompatible" content="Yes">
    <title>Insert Link</title>
    <style type="text/css">
        html, body, button, div, input, select, fieldset { font-family: MS Shell Dlg; font-size: 8pt; position: absolute; };
    </style>
    <SCRIPT language="javascript">
        function Set() {

            link    = document.all.txtextern.value;
            ziel    = document.all.txtziel.value;
            cla     = document.all.txtstyle.value;
            tmail   = document.all.txtmail.checked;
            img     = document.all.selectimg.value;

            if ( tmail == true ) {
                hr = "mailto:";
            } else {
                hr = "";
            }

            if ( cla != 0 ) {
                cla = 'class="' + cla + '"';
            } else {
                cla = "";
            }

            if ( img != 0 ){
               img = '<img src="' + img + '" border="0" alt=""></a>';
            } else {
               img = "";
            }

            targ = " target=\"" + ziel + "\"";

            ret = "<a " + cla + " href=\"" + hr + link + "\"" + targ + ">" + img;

            if ( link != '' ) {
                window.returnValue = ret;       // set return value
            } else {
                window.returnValue = 'none';        // set return value
            }

            window.close();                     // close dialog

        }

        function wechsel(pos) {
            document.all.txtextern.value = pos.value;
        }


    </SCRIPT>
</head>

<body id="bdy" style="background: threedface; color: windowtext;" scroll=no>
<?php

include ('../../../includes/config.php');
include ('../../../includes/cfg_sql.inc.php');
include ('../../../includes/functions.i18n.php');

$db     = new DB_Contenido;
$db2    = new DB_Contenido;

$lang   = ( is_numeric($_GET['lang']) )    ? $_GET['lang']    : 0;
$client = ( is_numeric($_GET['client']) )  ? $_GET['client']  : 0;

$sql = "SELECT idclient, frontendpath, htmlpath, errsite_cat, errsite_art FROM ".$cfg["tab"]["clients"];
$db->query($sql);

while ($db->next_record())
{
   rereadClients();
}

?>

<div id=divconName style="left: 0.98em; top: 1.2168em; width: 7em; height: 1.2168em; "><?php echo i18n("Internal link");?>:</div>
<?php

echo "<select ID=\"selecttxtName\" SIZE=1 style=\"left: 8.54em; top: 1.0647em; width: 21.5em;height: 2.1294em;\" onchange=\"wechsel(this)\" onClick=\"wechsel(this)\">";
echo "<option value=\"\" selected>".i18n("Please choose")."</option>";
echo "<option value='front_content.php?idcat=$link&client=$client&lang=$lang'>$spacer".$db->f("name")."</option>";

$sql = "SELECT a.idcat AS idcat, b.name AS name
			FROM
		 ".$cfg["tab"]["cat"]." AS a,
		 ".$cfg["tab"]["cat_lang"]." AS b,
		 ".$cfg["tab"]["cat_tree"]." AS c
		WHERE a.idclient = '". Contenido_Security::toInteger($client)."'
		  AND b.idlang = '". Contenido_Security::toInteger($lang)."'
		  AND b.idcat = a.idcat
          AND c.idcat = a.idcat ORDER BY c.idtree";

$db->query($sql);

$db2 = new DB_Contenido;

while ($db->next_record())
{
   $categories[$db->f("idcat")]["name"] = $db->f("name");
   
   $sql2 = "SELECT
			level
		   FROM
          ".$cfg["tab"]["cat_tree"]."
			WHERE
			idcat = '".$db->f("idcat")."'";
   $db2->query($sql2);

   if ($db2->next_record())
   {
   	$categories[$db->f("idcat")]["level"] = $db2->f("level");
   }
   
   $sql2 = "SELECT
			a.title AS title,
			b.idcatart AS idcatart
		   FROM
            ".$cfg["tab"]["art_lang"]." AS a,
            ".$cfg["tab"]["cat_art"]." AS b
			WHERE
			b.idcat = '".$db->f("idcat")."' AND
			a.idart = b.idart AND
			a.idlang = '". Contenido_Security::toInteger($lang)."'";

   $db2->query($sql2);
   #if ($db2->next_record())
   #{
   #	$categories[$db->f("idcat")]["articles"][$db2->f("idcatart")] = $db2->f("title");
   #}
   
    while ($db2->next_record())
    {
        $categories[$db->f("idcat")]["articles"][$db2->f("idcatart")] = $db2->f("title");
    }
   
    
}

/*$sql = "SELECT
            *
        FROM
            ".$cfg["tab"]["cat_tree"]." AS a,
            ".$cfg["tab"]["cat_art"]." AS b,
            ".$cfg["tab"]["cat"]." AS c,
            ".$cfg["tab"]["cat_lang"]." AS d,
            ".$cfg["tab"]["art_lang"]." AS e
        WHERE
            a.idcat     = b.idcat AND
            b.idcat     = d.idcat AND
            d.idlang    = '".$lang."' AND
            b.idart     = e.idart AND
            c.idcat     = a.idcat AND
            c.idclient  = '".$client."'
        ORDER BY
            a.idtree";

$file = fopen("test.txt","w+");
fputs($file,$sql);
fclose($file);

$db->query($sql);*/

        if ($a_link_intern_value != 0) {
            echo "<option value=0>--- ".i18n("none")." ---</option>";
        } else {
            echo "<option value=0 selected>--- ".i18n("none")." ---</option>";
        }

        foreach ($categories as $idcat => $props)
        {

                $spaces = "&nbsp;&nbsp;";

                #$idcatart = $db->f("idcatart");

                for ($i=0; $i<$props["level"]; $i++) {
                    $spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                }

                if ( $idcatart != $a_link_intern_value ) {
                        echo '<option value="front_content.php?idcat='.$idcat.'&lang='.$lang.'&client='.$client.'" style="background-color:#EFEFEF">'.$spaces.">".$props["name"].'</option>';
                        //echo "<option value=\"".$db->f("idcatart")."\">$spaces ".$db->f("name")."---".$tmp_title."</option>";
                } else {
                        echo '<option selected="selected" value="front_content.php?idcat='.$idcat.'&lang='.$lang.'&client='.$client.'" style="background-color:#EFEFEF">'.$spaces.">".$props["name"].'</option>';
                        //echo "<option value=\"".$db->f("idcatart")."\" selected>$spaces ".$db->f("name")."---".$tmp_title."</option>";
                }
                
                foreach ($props["articles"] as $idcatart => $article)
                {
                	$tmp_title = $article;

                    for ($i=0; $i<$props["level"]; $i++) {
                        #$spaces .= "--";
                    }

                	if ( strlen($tmp_title) > 32 ) {
	                    $tmp_title = substr($tmp_title, 0, 32);
                	}

                	#$spaces .= ">";

                    if ( $idcatart != $a_link_intern_value ) {
                            echo '<option value="front_content.php?idcatart='.$idcatart.'&lang='.$lang.'&client='.$client.'">'.$spaces."&nbsp;&nbsp;".$article.'</option>';
                            //echo "<option value=\"".$db->f("idcatart")."\">$spaces ".$db->f("name")."---".$tmp_title."</option>";
                    } else {
                            echo '<option selected="selected" value="front_content.php?idcatart='.$idcatart.'&lang='.$lang.'&client='.$client.'">'.$spaces."&nbsp;&nbsp;".$article.'</option>';
                            //echo "<option value=\"".$db->f("idcatart")."\" selected>$spaces ".$db->f("name")."---".$tmp_title."</option>";
                    }
                }
        }

echo "</SELECT>";

?>
<DIV id=divconName style="left: 0.98em; top: 4.2168em; width: 7em; height: 1.2168em; ">PDF:</DIV>
<?php
echo "<SELECT ID=\"selectpdf\" SIZE=1 style=\"left: 8.54em; top: 4.0647em; width: 21.5em;height: 2.1294em;\" onchange=\"wechsel(this)\" onClick=\"wechsel(this)\">";
echo "<option value=\"\" selected>".i18n("Please choose")."</option>";

$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient = '". Contenido_Security::toInteger($client)."' AND filetype = 'pdf' ORDER BY dirname, filename";
$db->query($sql);

while ($db->next_record())
{
    echo "<option value=\"".$cfgClient[$client]["path"]["htmlpath"].$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
}

echo "</SELECT>";

?>

<DIV id=divFileName style="left: 0.98em; top: 7.1067em; width: 7em; height: 1.2168em; "><?php echo i18n("External link");?>:</DIV>
<INPUT ID="txtextern"  type="text" style="left: 8.54em; top: 6.8025em; width: 21.5em;height: 2.1294em; " tabIndex=10 onfocus="select()">

<DIV id=divAltText style="left: 0.98em; top: 10.1067em; width: 6.58em; height: 1.2168em; "><?php echo i18n("Target");?>:</DIV>
<SELECT ID="txtziel" style="left: 8.54em; top: 9.8em; width: 21.5em;height:10px;">
<option value="_self"><?php echo i18n("Open in same window");?></option>
<option value="_blank"><?php echo i18n("Open in new window");?></option>
</select>

<DIV id=divAltText style="left: 31.36em; top: 10.1067em; width: 6.58em; height: 1.2168em; "><?php echo i18n("E-Mail");?>:</DIV>
<input type="checkbox" ID="txtmail" name="hal" value="x" style="left: 34.54em; top: 9.8em;">


<DIV id=divAltText style="left: 0.98em; top: 13.1067em; width: 6.58em; height: 1.2168em; "><?php echo i18n("Style");?>:</DIV>
<SELECT ID="txtstyle" SIZE=1 style="left: 8.54em; top: 12.78em; width: 21.5em;height: 2.1294em;">
<option value="0"><?php echo i18n("Default");?></option>
<option value="mail"><?php echo i18n("Mail");?></option>
<option value="content"><?php echo i18n("Content");?></option>
</select>

<DIV id=divimgName style="left: 0.98em; top: 16.2168em; width: 6.58em; height: 1.2168em; "><?php echo i18n("Contenido image");?>:</DIV>
<?php

echo "<SELECT ID=\"selectimg\" SIZE=1 style=\"left: 8.54em; top: 16.2168em; width: 21.5em;height: 2.1294em;\">";
echo '<option value="0" selected="selected">'.i18n("Please choose").':</option>';

$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='". Contenido_Security::toInteger($client)."' AND filetype IN ('jpg','gif','png') ORDER BY dirname,filename";
$db->query($sql);

while ($db->next_record())
{
    echo "<option value=\"".$cfgClient[$client]["path"]["htmlpath"].$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
}

echo "</SELECT>";

?>

<BUTTON ID=btnOK onClick="Set()" style="left: 31.36em; top: 1.0647em; width: 7em; height: 2.2em; " tabIndex=40><?php echo i18n("OK");?>:</BUTTON>
<BUTTON ID=btnCancel style="left: 31.36em; top: 3.6504em; width: 7em; height: 2.2em; " type=reset tabIndex=45 onClick="window.returnValue = 'none';window.close();"><?php echo i18n("Cancel");?>:</BUTTON>



</BODY>
</HTML>
