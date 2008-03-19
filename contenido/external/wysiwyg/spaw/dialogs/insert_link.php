<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Insert Link dialog | Dialog Template
// ================================================
// Modified: Martin Horwath, horwath@opensa.org
// SPAW1.0.3 for Contenido 4.3.2.1, 2003-10-08 v0.1
// ================================================

// include wysiwyg config
    include_once (dirname(__FILE__) . '/../../../../includes/startup.php');
	cInclude("includes", "functions.con.php");
	cInclude("includes" ,"functions.api.php");
   include $cfg["path"]["wysiwyg"].'config/spaw_control.config.php'; // CONTENIDO
   $db2 = new DB_Contenido;
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
  <title><?php echo i18n("Insert Link");?></title>
  <style type="text/css">
    html, body, button, div, input, select, table { font-family: MS Shell Dlg; font-size: 8pt; }
    body { margin: 0px; background: threedface; color: windowtext; }
  </style>
  <SCRIPT language="JavaScript" src="utils.js"></SCRIPT>
  <SCRIPT language="JavaScript">
    function _KeyStrokes() {
	  if (event.keyCode == 27) { window.close(); return; }
    }

    function Init() {
      document.body.onkeypress = _KeyStrokes;

      var smyLink = window.dialogArguments; // get data

      if (smyLink) { // set the selects

        document.all.txt_link.value = smyLink.Href;
        if (!smyLink.Href.indexOf("front_content.php")) { // set if not internal link
          document.all.txt_name.value = smyLink.Href;
        }
        if (smyLink.Target) { // if target selected set it
          document.all.txt_target.value = smyLink.Target;
        }
      }

      resizeDialogToContent();
    }

    function Set() {

      function tempObj(){
        this.Href = '';
        this.Target = '';
      }


      myLink = new tempObj();
      myLink.Href = document.all.txt_link.value;
      myLink.Target =  document.all.txt_target.value;

      if (myLink.Href != '') {
        window.returnValue = myLink; // set return value
      } else {
        window.returnValue = null; // set return value
      }
      window.close(); // close dialog

    }

    function Cancel() {
      window.returnValue = false; // set return value
      window.close(); // close dialog
    }

    function change(pos) {
      document.all.txt_link.value = pos.value;
    }

  </SCRIPT>
  </head>
  <body onload="Init();">
  <form method="post" name="set">
  <table cellpadding="0" cellspacing="5" border="0"><tr>
  <td width="300" valign="top">
  <fieldset><legend>Hyperlink Information</legend>
    <table cellpadding="0" cellspacing="5" border="0">
      <tr>
        <td><?php echo i18n("Internal link");?> :</td>
        <td><?php

echo "<select style=\"width: 100%\" ID=\"txt_name\" SIZE=\"1\" onchange=\"change(this)\" onClick=\"change(this)\" tabIndex=1>";
echo "<option value=\"\">".i18n("Please choose")."</option>";
//echo "<option value='front_content.php?idcat=$link&client=$client&lang=$lang'>$spacer".$db->f("name")."</option>";

$sql = "SELECT
          *
        FROM
          ".$cfg["tab"]["cat_tree"]." AS a,
          ".$cfg["tab"]["cat_lang"]." AS b,
          ".$cfg["tab"]["cat"]." AS c
        WHERE
          a.idcat = b.idcat AND
          c.idcat = a.idcat AND
          c.idclient = '".$client."' AND
          b.idlang = '".$lang."'
        ORDER BY
          a.idtree";

$db->query($sql);

echo "<option value=\"\">-- ".i18n("None")." --</option>";

while ( $db->next_record() ) {

  $spaces = "";

  for ($i=0; $i<$db->f("level"); $i++) {
    $spaces .= "&nbsp;&nbsp;";
  }

  if ($db->f("visible") == 0)
  {
  	$style = "font-weight: bold; color: #666666; background-color: #eeeeee;";
  } else {
  	$style = "font-weight: bold; background-color: #eeeeee;";
  }

  echo "<option style=\"$style\" value=\"front_content.php?idcat=".$db->f("idcat")."&lang=$lang&client=$client\">$spaces > ".$db->f("name")."</option>";

  if ($cfg["is_start_compatible"] == true)
  {
  $sql2 = " SELECT
		 		*
				FROM
			".$cfg["tab"]["cat_art"]." AS a,
			".$cfg["tab"]["art"]." AS b,
			".$cfg["tab"]["art_lang"]." AS c
				WHERE a.idcat = '".$db->f("idcat")."'
				 AND b.idart = a.idart AND c.idart = a.idart AND
				c.idlang = '".$lang."' AND b.idclient = '".$client."' ORDER BY a.is_start DESC, c.title ASC";
	
  } else {
   $sql2 = " SELECT
		 		*
				FROM
			".$cfg["tab"]["cat_art"]." AS a,
			".$cfg["tab"]["art"]." AS b,
			".$cfg["tab"]["art_lang"]." AS c
				WHERE a.idcat = '".$db->f("idcat")."'
				 AND b.idart = a.idart AND c.idart = a.idart AND
				c.idlang = '".$lang."' AND b.idclient = '".$client."' ORDER BY c.title ASC";
	 	
  }
  
  $db2->query($sql2);

	while ($db2->next_record())
	{
		  $tmp_title = $db2->f("title");

          if ( strlen($tmp_title) > 32 ) {
            $tmp_title = substr($tmp_title, 0, 32);
          }

      	$style = "";
      	
      	if ($cfg["is_start_compatible"] == true)
      	{
      		$is_start = $db2->f("is_start");
      	} else {
      		$is_start = isStartArticle($db2->f("idartlang"), $db2->f("idcat"), $lang);
      		
      		if ($is_start == true)
      		{
      			$is_start  = 1;
      		} else {
      			$is_start = 0;
      		}
      	}
        if ($is_start == 1 && $db2->f("online") == 0)
      	{
    	  	$style = "color: #ff0000";
      	}

      	if ($is_start == 1 && $db2->f("online") == 1)
      	{
      		$style = "color: #0000ff";
      	}
        if ($is_start == 0 && $db2->f("online") == 0)
      	{
    	  	$style = "color: #666666";
      	}


		echo "<option style=\"$style\" value=\"front_content.php?idcatart=".$db2->f("idcatart")."&lang=$lang&client=$client\">&nbsp;&nbsp;$spaces |&nbsp;&nbsp;".$tmp_title."</option>";
	}
  //
  //echo "<option value=\"front_content.php?idcatart=".$db->f("idcatart")."&lang=$lang&client=$client\">$spaces ".$db->f("name")."-".$tmp_title."</option>";
}

        echo "</SELECT>"; ?></td>
      </tr>
      <tr>
        <td><?php echo i18n("PDF");?> :</td>
        <td><?php

echo "<SELECT ID=\"selectpdf\" SIZE=\"1\" onchange=\"change(this)\" onClick=\"change(this)\" tabIndex=2>";
echo "<option value=\"\" selected>".i18n("Please choose")."</option>";

$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient = '".$client."' AND filetype = 'pdf' ORDER BY dirname, filename";
$db->query($sql);

while ($db->next_record())
{
  echo "<option value=\"".$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".capiStrTrimHard($db->f("description"),50)."]</option>";
}

        echo "</SELECT>"; ?></td>
      </tr>
<tr>
        <td><?php echo i18n("Media");?> :</td>
        <td><?php

echo "<SELECT ID=\"selectmedia\" SIZE=\"1\" onchange=\"change(this)\" onClick=\"change(this)\" tabIndex=3>";
echo "<option value=\"\" selected>".i18n("Please choose")."</option>";

$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient = '".$client."' ORDER BY dirname, filename";
$db->query($sql);

while ($db->next_record())
{
  echo "<option value=\"".$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".capiStrTrimHard($db->f("description"),50)."]</option>";
}

        echo "</SELECT>"; ?></td>
      </tr>
      <tr>
        <td><?php echo i18n("External link");?> :</td>
        <td><INPUT ID="txt_link" style="width:100%" type="text" onfocus="select()" tabIndex=3></td>
      </tr>
      <tr>
        <td><?php echo i18n("Target");?> :</td>
        <td><SELECT ID="txt_target" tabIndex=4>
              <option value=""><?php echo i18n("None");?></option>
              <option value="_self"><?php echo i18n("Open in same window");?></option>
              <option value="_blank"><?php echo i18n("Open in new window");?></option>
              <option value="_parent"><?php echo i18n("Open in parent frame");?></option>
              <option value="_top"><?php echo i18n("Open in top frame");?></option>
            </SELECT></td>
      </tr>
    </table>
  </fieldset></td>
  <td valign="top">
    <table cellpadding="0" cellspacing="5" border="0">
      <tr>
        <td><BUTTON ID=btnOK onClick="Set()" style="width: 7em; height: 2.2em;" tabIndex=40><?php echo i18n("OK");?></BUTTON></td>
      </tr>
      <tr>
        <td><BUTTON ID=btnCancel onClick="Cancel()" style="width: 7em; height: 2.2em;" tabIndex=45><?php echo i18n("Cancel");?></BUTTON></td>
      </tr>
    </table>
  </td></tr></table>
  </form>
  </body>
</html>
