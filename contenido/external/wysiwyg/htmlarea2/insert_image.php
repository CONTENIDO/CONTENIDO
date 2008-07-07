<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    0.1
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
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}
?>
<!-- based on insimage.dlg -->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD W3 HTML 3.2//EN">
<HTML id=dlgImage STYLE="width: 40.1em; height: 18em">
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="MSThemeCompatible" content="Yes">
<TITLE>Insert Image</TITLE>
<style>
  html, body, button, div, input, select, fieldset { font-family: MS Shell Dlg; font-size: 8pt; position: absolute; };
</style>
<SCRIPT defer>

function _CloseOnEsc() {
  if (event.keyCode == 27) { window.close(); return; }
}

function _getTextRange(elm) {
  var r = elm.parentTextEdit.createTextRange();
  r.moveToElementText(elm);
  return r;
}

window.onerror = HandleError

function HandleError(message, url, line) {
  var str = "An error has occurred in this dialog." + "\n\n"
  + "Error: " + line + "\n" + message;
  alert(str);
  window.close();
  return true;
}

function Init() {
  var elmSelectedImage;
  var htmlSelectionControl = "Control";
  var globalDoc = window.dialogArguments;
  var grngMaster = globalDoc.selection.createRange();

  // event handlers
  document.body.onkeypress = _CloseOnEsc;
  btnOK.onclick = new Function("btnOKClick()");

  txtFileName.fImageLoaded = false;
  txtFileName.intImageWidth = 0;
  txtFileName.intImageHeight = 0;

  if (globalDoc.selection.type == htmlSelectionControl) {
    if (grngMaster.length == 1) {
      elmSelectedImage = grngMaster.item(0);
      if (elmSelectedImage.tagName == "IMG") {
        txtFileName.fImageLoaded = true;
        if (elmSelectedImage.src) {
          txtFileName.value          = elmSelectedImage.src.replace(/^[^*]*(\*\*\*)/, "$1");  // fix placeholder src values that editor converted to abs paths
          txtFileName.intImageHeight = elmSelectedImage.height;
          txtFileName.intImageWidth  = elmSelectedImage.width;
          txtVertical.value          = elmSelectedImage.vspace;
          txtHorizontal.value        = elmSelectedImage.hspace;
          txtAltText.value           = elmSelectedImage.alt;
          selAlignment.value         = elmSelectedImage.align;
        }
      }
    }
  }
  txtFileName.value = txtFileName.value || "http://";
  txtFileName.focus();
}

function _isValidNumber(txtBox) {
  var val = parseInt(txtBox);
  if (isNaN(val) || val < 0 || val > 999) { return false; }
  return true;
}

function btnOKClick() {
  var elmImage;
  var intAlignment;
  var htmlSelectionControl = "Control";
  var globalDoc = window.dialogArguments;
  var grngMaster = globalDoc.selection.createRange();

  // error checking

  if (!txtFileName.value || txtFileName.value == "http://") {
    alert("Image URL must be specified.");
    txtFileName.focus();
    return;
  }
  if (txtHorizontal.value && !_isValidNumber(txtHorizontal.value)) {
    alert("Horizontal spacing must be a number between 0 and 999.");
    txtHorizontal.focus();
    return;
  }

  if (txtVertical.value && !_isValidNumber(txtVertical.value)) {
    alert("Vertical spacing must be a number between 0 and 999.");
    txtVertical.focus();
    return;
  }

  // delete selected content and replace with image
  if (globalDoc.selection.type == htmlSelectionControl && !txtFileName.fImageLoaded) {
    grngMaster.execCommand('Delete');
    grngMaster = globalDoc.selection.createRange();
  }

  idstr = "\" id=\"556e697175657e537472696e67";     // new image creation ID
  if (!txtFileName.fImageLoaded) {
    grngMaster.execCommand("InsertImage", false, idstr);
    elmImage = globalDoc.all['556e697175657e537472696e67'];
    elmImage.removeAttribute("id");
    elmImage.removeAttribute("src");
    grngMaster.moveStart("character", -1);
  } else {
    elmImage = grngMaster.item(0);
    if (elmImage.src != txtFileName.value) {
      grngMaster.execCommand('Delete');
      grngMaster = globalDoc.selection.createRange();
      grngMaster.execCommand("InsertImage", false, idstr);
      elmImage = globalDoc.all['556e697175657e537472696e67'];
      elmImage.removeAttribute("id");
      elmImage.removeAttribute("src");
      grngMaster.moveStart("character", -1);
      txtFileName.fImageLoaded = false;
    }
    grngMaster = _getTextRange(elmImage);
  }

  if (txtFileName.fImageLoaded) {
    elmImage.style.width = txtFileName.intImageWidth;
    elmImage.style.height = txtFileName.intImageHeight;
  }

  if (txtFileName.value.length > 2040) {
    txtFileName.value = txtFileName.value.substring(0,2040);
  }

  elmImage.src = txtFileName.value;

  if (txtHorizontal.value != "") { elmImage.hspace = parseInt(txtHorizontal.value); }
  else                           { elmImage.hspace = 0; }

  if (txtVertical.value != "") { elmImage.vspace = parseInt(txtVertical.value); }
  else                         { elmImage.vspace = 0; }

  elmImage.alt = txtAltText.value;


  elmImage.style.float = selfloatAlignment.value;

  elmImage.align = selAlignment.value;
  elmImage.valign = selvAlignment.value;

  grngMaster.collapse(false);
  grngMaster.select();
  window.close();
}


function wechsel(){

document.all.txtFileName.value=document.all.selecttxtName.value;
}



</SCRIPT>
</HEAD>



<BODY id=bdy onload="Init()" style="background: threedface; color: windowtext;" scroll=no>
<DIV id=divconName style="left: 0.98em; top: 1.2168em; width: 7em; height: 1.2168em; ">Contenido Image:</DIV>
<?php
include('../../../includes/config.php');
$db = new DB_Contenido;


echo "<SELECT ID=\"selecttxtName\" SIZE=1 onchange=\"wechsel()\" style=\"left: 8.54em; top: 1.0647em; width: 30.5em;height: 2.1294em;\">";
echo "<option value=\"http://\" selected>bitte auswählen</option>";
$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='". Contenido_Security::toInteger($client)."' AND filetype IN ('jpg','gif','png') ORDER BY dirname,filename";
$db->query($sql);

while ($db->next_record()) {

echo "<option value=\"".'upload/'.$db->f("dirname").$db->f("filename")."\">".$db->f("dirname").$db->f("filename")." [".$db->f("description")."]</option>";
}
echo "</SELECT>";

?>


<DIV id=divFileName style="left: 0.98em; top: 4.1067em; width: 8em; height: 1.2168em; ">Image URL:</DIV>
<INPUT ID="txtFileName"  type="text" style="left: 8.54em; top: 3.8025em; width: 30.5em;height: 2.1294em; " tabIndex=10 onfocus="select()">

<DIV id=divAltText style="left: 0.98em; top: 7.1067em; width: 6.58em; height: 1.2168em; ">Alternate Text:</DIV>
<INPUT type=text ID=txtAltText tabIndex=15 style="left: 8.54em; top: 6.8025em; width: 30.5em; height: 2.1294em; " onfocus="select()">

<FIELDSET id=fldLayout style="left: .9em; top: 10.1em; width: 17.08em; height: 10.6em;">
<LEGEND id=lgdLayout>Alignment</LEGEND>
</FIELDSET>

<FIELDSET id=fldSpacing style="left: 18.9em; top: 10.1em; width: 11em; height: 7.6em;">
<LEGEND id=lgdSpacing>Spacing</LEGEND>
</FIELDSET>

<DIV id=divAlign style="left: 1.82em; top: 12.126em; width: 4.76em; height: 1.2168em; ">Horizontal:</DIV>
<SELECT size=1 ID=selAlignment tabIndex=20 style="left: 10.36em; top: 11.8218em; width: 6.72em; height: 1.2168em; ">
<OPTION id=optNotSet value=""> Not set </OPTION>
<OPTION id=optLeft value=left> Left </OPTION>
<OPTION id=optRight value=right> Right </OPTION>
</SELECT>

<DIV id=divHoriz style="left: 19.88em; top: 12.126em; width: 4.76em; height: 1.2168em; ">Horizontal:</DIV>
<INPUT ID=txtHorizontal style="left: 24.92em; top: 11.8218em; width: 4.2em; height: 2.1294em; ime-mode: disabled;" type=text size=3 maxlength=3 value="" tabIndex=25 onfocus="select()">

<DIV id=divvAlign style="left: 1.82em; top: 15.0159em; width: 8.12em; height: 1.2168em; ">Vertical:</DIV>
<SELECT size=1 ID=selvAlignment tabIndex=21 style="left: 10.36em; top: 15.0159em; width: 6.72em; height: 1.2168em; ">
<OPTION id=optNotSet value=""> Not set </OPTION>
<OPTION id=optBottom value=bottom> Bottom </OPTION>
<OPTION id=optMiddle value=middle> Middle </OPTION>
<OPTION id=optTop value=top> Top </OPTION>
</SELECT>

<DIV id=divVert style="left: 19.88em; top: 15.0159em; width: 3.64em; height: 1.2168em; ">Vertical:</DIV>
<INPUT ID=txtVertical style="left: 24.92em; top: 14.5596em; width: 4.2em; height: 2.1294em; ime-mode: disabled;" type=text size=3 maxlength=3 value="" tabIndex=30 onfocus="select()">

<DIV id=divvAlign style="left: 1.82em; top: 18.0159em; width: 8.12em; height: 1.2168em; ">Float:</DIV>
<SELECT size=1 ID=selfloatAlignment tabIndex=21 style="left: 10.36em; top: 18.0159em; width: 6.72em; height: 1.2168em; ">
<OPTION id=optNotSet value=""> Not set </OPTION>
<OPTION id=optBottom value=right> Right </OPTION>
<OPTION id=optMiddle value=left> Left </OPTION>
<OPTION id=optTop value=top> Top </OPTION>
</SELECT>

<BUTTON ID=btnOK style="left: 31.36em; top: 11.0647em; width: 7em; height: 2.2em; " type=submit tabIndex=40>OK</BUTTON>
<BUTTON ID=btnCancel style="left: 31.36em; top: 14.6504em; width: 7em; height: 2.2em; " type=reset tabIndex=45 onClick="window.close();">Cancel</BUTTON>

</BODY>
</HTML>
