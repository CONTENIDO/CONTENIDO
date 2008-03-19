<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Image library dialog | Dialog Template
// ================================================
// Modified: Martin Horwath, horwath@opensa.org
// SPAW1.0.3 for Contenido 4.3.2.1, 2003-10-08 v0.1
// ================================================

// include wysiwyg config
    include_once (dirname(__FILE__) . '/../../../../includes/startup.php');
    include ($cfg["path"]["wysiwyg"]."config/spaw_control.config.php");


$theme = empty($_POST['theme'])?(empty($_GET['theme'])?$spaw_default_theme:$_GET['theme']):$_POST['theme'];
$theme_path = $spaw_dir.'lib/themes/'.$theme.'/';

$l = new SPAW_Lang(empty($_POST['lang'])?$_GET['lang']:$_POST['lang']);
$l->setBlock('image_insert');

// get directorys
$db = new DB_Contenido;
$sql = "SELECT
          *
        FROM
          ".$cfg["tab"]["upl"]."
        WHERE
          idclient='$client' AND
          filetype IN ($spaw_valid_imgs)
        GROUP BY
          dirname
        ORDER BY
          dirname,filename";

$db->query($sql);

$spaw_imglibs = array(); // get directory informations
$path = $cfgClient[$client]["upl"]["path"];
$www = $spaw_base_url;

while ($db->next_record())
{
  $tmp_arr = array(	'name' => $db->f("dirname"));
  array_push($spaw_imglibs, $tmp_arr);
}

$value_found = false;
// callback function for preventing listing of non-library directory
function is_array_value($value, $key, $imglib)
{
  global $value_found;
  //  echo $value.'-'.$imglib.'<br>';
  if (is_array($value)) array_walk($value, 'is_array_value',$imglib);
  if ($value == $imglib){
    $value_found=true;
  }
}
array_walk($spaw_imglibs, 'is_array_value',$imglib);

function libOptions($arr, $prefix = '', $sel = '')
{
  global $lib;
  $buffer = '';

  foreach($arr as $vlib) {
  	  if ($sel == '')
  	  {
  	  	$sel = $vlib['name'];
  	  	$lib = $sel;
  	  }

    $buffer .= '<option value="'.$vlib['name'].'"'.(($vlib['name'] == $sel)?' selected':'').'>'.$prefix.$vlib['name'].'</option>'.'\n        ';
  }
  return $buffer;
}
if (!$value_found || empty($img))
{
  $img = $spaw_imglibs[0]['path'];
}
$lib_Options = libOptions($spaw_imglibs,'',$lib);

$d = @dir($path.$lib);
if ($d) {
  $arrayFiles = array();
  while (false !== ($entry = $d->read())) {
    if (is_file($path.$lib.$entry)) {
 	  $arrayFiles[] =  $entry;
    }
  }
  $d->close();
  
  sort($arrayFiles);
  
  $lib_Files = '';
  for ($i = 0; $i < count($arrayFiles); $i++)
  {
      $lib_Files .= '<option value="'.$arrayFiles[$i].'"'.(($arrayFiles[$i] == $img)?' selected':'').'>'.$arrayFiles[$i].'</option></option>'.'\n        ';
  }
  
} else {
  $errors[] = $l->m('error_no_dir');
}

// $lib_Options, $lib_Files
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
  <title><?php echo $l->m('title')?></title>
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

      window.name = 'imglibrary';

      resizeDialogToContent();
    }

    function selectClick()
    {
      if (document.libbrowser.lib.selectedIndex>=0 && document.libbrowser.img.selectedIndex>=0)
      {
        window.returnValue = '<?php echo $www?>' + document.libbrowser.lib.options[document.libbrowser.lib.selectedIndex].value + document.libbrowser.img.options[document.libbrowser.img.selectedIndex].value;
        window.close();
      }
      else
      {
        alert('<?php echo $l->m('error').': '.$l->m('error_no_image')?>');
      }
    }

    function Cancel() {
      window.close(); // close dialog
    }

  </SCRIPT>
  </head>
  <body onload="Init();">
  <form name="libbrowser" method="post" action="img_library.php" target="imglibrary">
    <input type="hidden" name="theme" value="<?php echo $theme?>">
    <input type="hidden" name="lang" value="<?php echo $l->lang?>">
    <input type="hidden" name="belang" value="<?php echo $belang?>">
    <input type="hidden" name="client" value="<?php echo $client?>">

  <table cellpadding="0" cellspacing="5" border="0"><tr>
    <td valign="top">
      <fieldset><legend>Browser</legend>
      <table cellpadding="0" cellspacing="5" border="0"><tr>
        <td><?php echo $l->m('library')?></td></tr><tr>
        <td><select name="lib" size="1" class="input" onChange="libbrowser.submit();">
        <?php echo $lib_Options?>
        </select></td>
      </tr><tr>
        <td><?php echo $l->m('images')?></td></tr><tr>
        <td><select name="img" size="15" class="input" style="width: 300px;" onchange="if (this.selectedIndex >=0) imgpreview.location.href ='<?php echo $www.$lib?>' + this.options[this.selectedIndex].value;" ondblclick="selectClick();">
        <?php echo $lib_Files?>
        </select></td>
      </tr></table>
      </fieldset>
    </td>
    <td valign="top">
      <fieldset><legend><?php echo $l->m('preview')?></legend>
      <table cellpadding="0" cellspacing="5" border="0"><tr>
        <td><iframe name="imgpreview" src="" style="width: 400px; height: 200px;" scrolling="Auto" marginheight="0" marginwidth="0" frameborder="0"></iframe></td>
      </tr></table>
      </fieldset>
    <table cellpadding="0" cellspacing="5" border="0">
      <tr>
        <td><BUTTON ID=btnOK onclick="selectClick();" style="width: 7em; height: 2.2em;" tabIndex=40><?php echo i18n("OK");?></BUTTON></td>
        <td><BUTTON ID=btnCancel onClick="Cancel()" style="width: 7em; height: 2.2em;" tabIndex=45><?php echo i18n("Cancel");?></BUTTON></td>
      </tr>
    </table>
    </td>
  </tr></table>
  </form>
  </body>
</html>
