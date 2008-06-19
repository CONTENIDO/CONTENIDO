<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Colorpicker dialog | Dialog Template
// ================================================
// Modified: Martin Horwath, horwath@opensa.org
// SPAW1.0.3 for Contenido 4.3.2.1, 2003-10-08 v0.1
// ================================================

if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}

// include wysiwyg config
include_once (dirname(__FILE__) . '/../../../../includes/startup.php');
include ($cfg["path"]["wysiwyg"]."config/spaw_control.config.php");
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
  <title><?php echo i18n("Colorpicker");?></title>
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

      curColor = window.dialogArguments; // get data

      curColor = ValidateColor(curColor) || '000000';
      View(curColor); // set default color

      resizeDialogToContent();
    }

    var curColor; // passed color

    function View(color) { // preview color
      if ( color == '' ) {
        document.all.ColorPreview.style.backgroundColor = '';
        document.all.ColorHex.value = '';
      } else {
        document.all.ColorPreview.style.backgroundColor = '#' + color;
        document.all.ColorHex.value = '#' + color;
      }
    }

    function Set(string) { // select color
      if (string == '') {
        window.returnvalue = false;
        window.close();
      } else {
        color = ValidateColor(string);
        if (color == null) { // invalid color
          alert("Invalid color code: " + string);
        } else { // valid color
          View(color);                      // show selected color
          window.returnValue = '#' + color; // set return value
          window.close();                   // close dialog
        }
      }
    }

    function ValidateColor(string) {  // return valid color code
      string = string || '';
      string = string + "";
      string = string.toUpperCase();
      chars = '0123456789ABCDEF';
      out   = '';

      for (i=0; i<string.length; i++) { // remove invalid color chars
        schar = string.charAt(i);
        if (chars.indexOf(schar) != -1) { out += schar; }
      }

      if (out.length != 6) { return null; } // check length
      return out;
    }

</SCRIPT>
  </head>
  <body onload="Init();">
  <form method=get onSubmit="Set(document.all.ColorHex.value); return false;">

  <table border=0 cellspacing=0 cellpadding=4 width=100%>
    <tr>
      <td bgcolor="buttonface" valign=center><div style="background-color: #000000; padding: 1; height: 21px; width: 50px"><div id="ColorPreview" style="height: 100%; width: 100%"></div></div></td>
      <td bgcolor="buttonface" valign=center><input type="text" name="ColorHex" value="" size=15 style="font-size: 12px"></td>
      <td bgcolor="buttonface" width=100%><img border="0" src="transparentcolor.gif" onMouseOver=View('') onClick=Set('')></td>
    </tr>
  </table>

  <table bgcolor=0 cellspacing=1 cellpadding=0 style="cursor: hand;">
  <SCRIPT language="JavaScript">
  <!--
   var x = new Array('00','33','66','99','CC','FF');
   var y = new Array('000000','333333','666666','999999','CCCCCC','FFFFFF','FF0000','00FF00','0000FF','FFFF00','00FFFF','FF00FF');
   i=0;
   for (d=0;d<2;d++) {
     for (c=0;c<6;c++) {
       document.write('<tr><td width=10 height=10></td><td onClick=Set(\''+y[i]+'\') onMouseOver=View(\''+y[i]+'\') width=10 height=10 bgcolor=#'+y[i]+'></td><td width=10 height=10></td>');
       i++;
       for (b=0;b<3;b++) {
         for (a=0;a<6;a++) {
           r=x[b+3*d]+x[a]+x[c];
           document.write('<td width=10 height=10 onClick=Set(\''+r+'\') onMouseOver=View(\''+r+'\') bgcolor=#'+r+'></td>');
         }
       }
       document.write('</tr>');
     }
   }
  //-->
  </SCRIPT>
  </table>

  </form>
  </body>
</html>
