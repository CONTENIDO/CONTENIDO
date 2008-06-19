<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Image properties dialog
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// v.1.0, 2003-04-01
// ================================================

if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}

// include wysiwyg config
include_once (dirname(__FILE__) . '/../../../../includes/startup.php');
include ($cfg["path"]["wysiwyg"]."config/spaw_control.config.php");

$theme = empty($_GET['theme'])?$spaw_default_theme:$_GET['theme'];
$theme_path = $spaw_dir.'lib/themes/'.$theme.'/';

$l = new SPAW_Lang($_GET['lang']);
$l->setBlock('image_prop');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
  <title><?php echo $l->m('title')?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $l->getCharset()?>">
  <link rel="stylesheet" type="text/css" href="<?php echo $theme_path.'css/'?>dialog.css">
  <script language="javascript" src="utils.js"></script>

  <script language="javascript">
  <!--
  function Init() {
    var iProps = window.dialogArguments;
    if (iProps)
    {
      // set attribute values
      if (iProps.width) {
        img_prop.cwidth.value = iProps.width;
      }
      if (iProps.height) {
        img_prop.cheight.value = iProps.height;
      }

      setAlign(iProps.align);

      if (iProps.src) {
        img_prop.csrc.value = iProps.src;
      }
      if (iProps.alt) {
        img_prop.calt.value = iProps.alt;
      }
      if (iProps.border) {
        img_prop.cborder.value = iProps.border;
      }
      if (iProps.hspace) {
        img_prop.chspace.value = iProps.hspace;
      }
      if (iProps.vspace) {
        img_prop.cvspace.value = iProps.vspace;
      }
    }
    resizeDialogToContent();
  }

  function validateParams()
  {
    // check width and height
    if (isNaN(parseInt(img_prop.cwidth.value)) && img_prop.cwidth.value != '')
    {
      alert('<?php echo $l->m('error').': '.$l->m('error_width_nan')?>');
      img_prop.cwidth.focus();
      return false;
    }
    if (isNaN(parseInt(img_prop.cheight.value)) && img_prop.cheight.value != '')
    {
      alert('<?php echo $l->m('error').': '.$l->m('error_height_nan')?>');
      img_prop.cheight.focus();
      return false;
    }
    if (isNaN(parseInt(img_prop.cborder.value)) && img_prop.cborder.value != '')
    {
      alert('<?php echo $l->m('error').': '.$l->m('error_border_nan')?>');
      img_prop.cborder.focus();
      return false;
    }
    if (isNaN(parseInt(img_prop.chspace.value)) && img_prop.chspace.value != '')
    {
      alert('<?php echo $l->m('error').': '.$l->m('error_hspace_nan')?>');
      img_prop.chspace.focus();
      return false;
    }
    if (isNaN(parseInt(img_prop.cvspace.value)) && img_prop.cvspace.value != '')
    {
      alert('<?php echo $l->m('error').': '.$l->m('error_vspace_nan')?>');
      img_prop.cvspace.focus();
      return false;
    }

    return true;
  }

  function okClick() {
    // validate paramters
    if (validateParams())
    {
      var iProps = {};
      iProps.align = (img_prop.calign.value)?(img_prop.calign.value):'';
      iProps.width = (img_prop.cwidth.value)?(img_prop.cwidth.value):'';
      iProps.height = (img_prop.cheight.value)?(img_prop.cheight.value):'';
      iProps.border = (img_prop.cborder.value)?(img_prop.cborder.value):'';
      iProps.src = (img_prop.csrc.value)?(img_prop.csrc.value):'';
      iProps.alt = (img_prop.calt.value)?(img_prop.calt.value):'';
      iProps.hspace = (img_prop.chspace.value)?(img_prop.chspace.value):'';
      iProps.vspace = (img_prop.cvspace.value)?(img_prop.cvspace.value):'';

      window.returnValue = iProps;
      window.close();
    }
  }

  function cancelClick() {
    window.close();
  }


  function setAlign(alignment)
  {
    for (i=0; i<img_prop.calign.options.length; i++)
    {
      al = img_prop.calign.options.item(i);
      if (al.value == alignment.toLowerCase()) {
        img_prop.calign.selectedIndex = al.index;
      }
    }
  }

  //-->
  </script>
</head>

<body onLoad="Init()" dir="<?php echo $l->getDir();?>">
<table border="0" cellspacing="0" cellpadding="2" width="336">
<form name="img_prop">
<tr>
  <td><?php echo $l->m('source')?>:</td>
  <td colspan="3"><input type="text" name="csrc" class="input" size="32"></td>
</tr>
<tr>
  <td><?php echo $l->m('alt')?>:</td>
  <td colspan="3"><input type="text" name="calt" class="input" size="32"></td>
</tr>
<tr>
  <td><?php echo $l->m('align')?>:</td>
  <td align="left">
  <select name="calign" size="1" class="input">
    <option value=""></option>
    <option value="left"><?php echo $l->m('left')?></option>
    <option value="right"><?php echo $l->m('right')?></option>
    <option value="top"><?php echo $l->m('top')?></option>
    <option value="middle"><?php echo $l->m('middle')?></option>
    <option value="bottom"><?php echo $l->m('bottom')?></option>
    <option value="absmiddle"><?php echo $l->m('absmiddle')?></option>
    <option value="texttop"><?php echo $l->m('texttop')?></option>
    <option value="baseline"><?php echo $l->m('baseline')?></option>
  </select>
  </td>
  <td><?php echo $l->m('border')?>:</td>
  <td align="left"><input type="text" name="cborder" class="input_small"></td>
</tr>
<tr>
  <td><?php echo $l->m('width')?>:</td>
  <td nowrap>
    <input type="text" name="cwidth" size="3" maxlenght="3" class="input_small">
  </td>
  <td><?php echo $l->m('height')?>:</td>
  <td nowrap>
    <input type="text" name="cheight" size="3" maxlenght="3" class="input_small">
  </td>
</tr>
<tr>
  <td><?php echo $l->m('hspace')?>:</td>
  <td nowrap>
    <input type="text" name="chspace" size="3" maxlenght="3" class="input_small">
  </td>
  <td><?php echo $l->m('vspace')?>:</td>
  <td nowrap>
    <input type="text" name="cvspace" size="3" maxlenght="3" class="input_small">
  </td>
</tr>
<tr>
<td colspan="4" nowrap>
<hr width="100%">
</td>
</tr>
<tr>
<td colspan="4" align="right" valign="bottom" nowrap>
<input type="button" value="<?php echo $l->m('ok')?>" onClick="okClick()" class="bt">
<input type="button" value="<?php echo $l->m('cancel')?>" onClick="cancelClick()" class="bt">
</td>
</tr>
</form>
</table>

</body>
</html>
