<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Main control class
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// Modified: Martin Horwath, horwath@opensa.org
// SPAW1.0.3 for Contenido 4.4.x, 2003-10-31 v0.2
// ================================================

if (!file_exists($spaw_root)) { die ("can't include external file");}

include $spaw_root.'class/toolbars.class.php';

// instance counter (static)
$spaw_wysiwyg_instCount = 0;

class SPAW_Wysiwyg {
  // controls name
  var $control_name;
  // value
  var $value;
  // holds control toolbar mode.
  var $mode;
  // editor dimensions;
  var $height;
  var $width;
  // language object
  var $lang;
  // theme (skin)
  var $theme;
  // editor stylesheet
  var $css_stylesheet;
  // toolbar dropdown data
  var $dropdown_data;
  // toolbars
  var $toolbars;
  // is disabled?
  var $disabled;  

  // constructor
  function SPAW_Wysiwyg($control_name='richeditor', $value='', $lang='', $mode = '',
              $theme='', $width='100%', $height='300px', $css_stylesheet='', $dropdown_data='')
  {
    global $spaw_dir;
    global $spaw_wysiwyg_instCount;
    global $spaw_default_theme;
    global $spaw_default_css_stylesheet;

    $spaw_wysiwyg_instCount++;

    $this->control_name = $control_name;
    $this->value = $value;
    $this->width = $width;
    $this->height = $height;
    if ($css_stylesheet == '')
    {
      $this->css_stylesheet = $spaw_default_css_stylesheet;
    }
    else
    {
      $this->css_stylesheet = $css_stylesheet;
    }
    $this->getLang($lang);
    if ($theme=='')
    {
      $this->theme = $spaw_default_theme;
    }
    else
    {
      $this->theme = $theme;
    }
    $this->mode = $mode;
    $this->dropdown_data = $dropdown_data;
    $this->getToolbar();
  }

  // sets _mode variable and fills toolbar items array
  function setMode($value) {
    $this->mode = $value;
  }
  // returns _mode value
  function getMode() {
    return($this->mode);
  }

  // set value/get value
  function setValue($value) {
    $this->value = $value;
  }
  function getValue() {
    return($this->value);
  }

  // set height/get height
  function setHeight($value) {
    $this->height = $value;
  }
  function getHeight() {
    return($this->height);
  }

  // set/get width
  function setWidth($value) {
    $this->width = $value;
  }
  function getWidth() {
    return($this->width);
  }

  // set/get css_stylesheet
  function setCssStyleSheet($value) {
    $this->css_stylesheet = $value;
  }
  function getCssStyleSheet() {
    return($this->css_stylesheet);
  }

  // outputs css and javascript code include
  function getCssScript($inline = false)
  {
    // static method... use only once per page
    global $spaw_dir;
    global $spaw_inline_js;
    global $spaw_root;
    global $spaw_active_toolbar;
    global $client; // CONTENIDO
    global $lang; // CONTENIDO
    global $belang; // CONTENIDO
    global $contenido;  // CONTENIDO

 if ($spaw_inline_js)
    {
      // inline javascript
      global $cfg; // CONTENIDO
      global $cfgClient; // CONTENIDO
      echo "<script language='JavaScript'>\n";
      echo "<!--\n";
      echo "var spaw_active_toolbar = ".($spaw_active_toolbar?"true":"false").";\n";
      include($spaw_root.'class/script.js.php');
      echo "//-->\n";
      echo "</script>\n";
    } else {
      // external javascript
      echo "<script language='JavaScript'>\n";
      echo "<!--\n";
      echo "var spaw_active_toolbar = ".($spaw_active_toolbar?"true":"false").";\n";
      echo "//-->\n";
      echo "</script>\n";
      echo '<script language="JavaScript" src="'.$spaw_dir.'spaw_script.js.php?client='.$client.'&lang='.$lang.'&belang='.$belang.'&contenido='.$contenido.'"></script>'."\n\n"; // CONTENIDO
    }
  }

  // checks browser compatibility with the control
  function checkBrowser()
  {
    global $HTTP_SERVER_VARS;

    $browser = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
    // check if msie
    if (eregi("MSIE[^;]*",$browser,$msie))
    {
      // get version
      if (eregi("[0-9]+\.[0-9]+",$msie[0],$version))
      {
        // check version
        if ((float)$version[0]>=5.5)
        {
          // finally check if it's not opera impersonating ie
          if (!eregi("opera",$browser))
          {
            return true;
          }
        }
      }
    }
    return false;
  }

  // load language data
  function getLang($lang='')
  {
    $this->lang = new SPAW_Lang($lang);
  }
  // load toolbars
  function getToolbar()
  {
   $this->toolbars = new SPAW_Toolbars($this->lang,$this->control_name,$this->mode,$this->theme,$this->dropdown_data);
  }

  // returns html for wysiwyg control
  function getHtml()
  {
    global $spaw_dir;
    global $spaw_wysiwyg_instCount;
    global $spaw_active_toolbar;
    global $cfg, $client, $cfgClient;

    $n = $this->control_name;
    // todo: make more customizable

    $buf = '';
    if ($this->checkBrowser() == true && $this->disabled == false)
    {
      if ($spaw_wysiwyg_instCount == 1)
      {
        $this->getCssScript();
      }
      // theme based css file and javascript
      $buf.= '<script language="JavaScript" src="'.$spaw_dir.'lib/themes/'.$this->theme.'/js/toolbar.js.php"></script>';
      $buf.= '<link rel="stylesheet" type="text/css" href="'.$spaw_dir.'lib/themes/'.$this->theme.'/css/toolbar.css">';

      $buf.= "\n<script language=\"javascript\">\n<!--\n";
	  $buf.= "function SPAW_setContent_$n() {\n";
		
      $tmpstr = str_replace("\r\n","\n",$this->getValue());
      $tmpstr = str_replace("\r","\n",$tmpstr);

      $content = explode("\n",$tmpstr);
      $plus = "";
      foreach ($content as $line)
      {
        $buf.="document.all.".$n.".value ".$plus."=\"".str_replace('"','&quot;',str_replace("'","\'",$line))."\";\n";
        $plus = "+";
      }

      $buf.="document.all.".$n.".value = document.all.".$n.".value.replace(/&quot;/g,'\"');"."\n";

      /* Do some magic here:
       - Contenido stores relative paths
       - The MSIE DHTML Editor only supports absolute paths
       - As Contenido has relative paths, the MSIE Editor creates absolute paths out of the relative one
       - Replace the (wrong) generated path with the (correct) client path.
      */
      $buf.= $n."_rEdit.document.body.innerHTML = document.all.".$n.".value;"."\n";
      $buf.= "setTimeout('var s = ".$n."_rEdit.document.body.innerHTML; s=s.replace(/".str_replace('/', '\\\/',$cfg['path']['contenido_fullhtml'].$cfg['path']['includes'])."/g,\'".$cfgClient[$client]["path"]["htmlpath"]."\'); ".$n."_rEdit.document.body.innerHTML = s; document.all.".$n.".value = s;',0);"."\n";


      $buf.= 'setTimeout("SPAW_toggle_borders(\''.$n.'\',this[\''.$n.'_rEdit\'].document.body,null);",0);'."\n";
      $buf.= "}\n";

      $buf.= '//--></script>'."\n";

		/**
		 * Spaw reparser functionality
		 *
		 * autocorrection of link anchors (28.10.2004)
		 */
		$buf .= "<script language=\"javascript\">\n";
		$buf.= "function SPAW_reParseContent_$n() {\n";
	    $buf.= "setTimeout('var reparser = ".$n."_rEdit.document.body.innerHTML; Ausdruck = /http:(.*)#/gi; Ausdruck.exec(reparser); reparser=reparser.replace(Ausdruck, \"".$cfgClient[$client]["path"]["htmlpath"].'front_content.php?idart='.$_GET['idcat']."&idart=".$_GET['idart']."#'+RegExp.$2+'\"); ".$n."_rEdit.document.body.innerHTML = reparser; document.all.".$n.".value = reparser;',0);"."\n";
	    #$buf.= 'setTimeout("SPAW_toggle_borders(\''.$n.'\',this[\''.$n.'_rEdit\'].document.body,null);",0);'."\n";

		$buf .= "}\n</script>";

      $buf.= '<table border="0" cellspacing="0" cellpadding="0" width="'.$this->getWidth().'">';
      $buf.= '<tr>';

      $buf .= '<td id="SPAW_'.$n.'_toolbar_top_design" class="SPAW_'.$this->theme.'_toolbar" colspan="3">';
      $buf.= $this->toolbars->get('top');
      $buf .= '</td>';

      $buf .= '<td id="SPAW_'.$n.'_toolbar_top_html" class="SPAW_'.$this->theme.'_toolbar" colspan="3" style="display : none;">';
      $buf.= $this->toolbars->get('top','html');
      $buf .= '</td>';

      $buf .= '</tr>';

      $buf.= '<tr>';

      $buf.= '<td id="SPAW_'.$n.'_toolbar_left_design" valign="top" class="SPAW_'.$this->theme.'_toolbar" >';
      $buf.= $this->toolbars->get('left');
      $buf .= '</td>';

      $buf.= '<td id="SPAW_'.$n.'_toolbar_left_html" valign="top" class="SPAW_'.$this->theme.'_toolbar" style="display : none;">';
      $buf.= $this->toolbars->get('left','html');
      $buf .= '</td>';

      $buf .= '<td align="left" valign="top" width="100%">';

      //$buf.= '<input type="hidden" id="'.$n.'" name="'.$n.'">';
      $buf.= '<textarea id="'.$n.'" name="'.$n.'" style="width:100%; height:'.$this->getHeight().'; display:none;" class="SPAW_'.$this->theme.'_editarea"></textarea>';
      $buf.= '<input type="hidden" id="SPAW_'.$n.'_editor_mode" name="SPAW_'.$n.'_editor_mode" value="design">';
      $buf.= '<input type="hidden" id="SPAW_'.$n.'_lang" value="'.$this->lang->lang.'">';
      $buf.= '<input type="hidden" id="SPAW_'.$n.'_theme" value="'.$this->theme.'">';
      $buf.= '<input type="hidden" id="SPAW_'.$n.'_borders" value="on">';

  	  $buf.= '<iframe id="'.$n.'_rEdit" style="width:100%; height:'.$this->getHeight().'; direction:'.$this->lang->getDir().';" onLoad="SPAW_editorInit(\''.$n.'\',\''.htmlspecialchars($this->getCssStyleSheet()).'\',\''.$this->lang->getDir().'\');" class="SPAW_'.$this->theme.'_editarea" frameborder="no" style="direction : "></iframe><br>';

      // Removed Contenido

      $buf.= '</td>';

      $buf.= '<td id="SPAW_'.$n.'_toolbar_right_design" valign="top" class="SPAW_'.$this->theme.'_toolbar">';
      $buf.= $this->toolbars->get('right');
      $buf .= '</td>';

      $buf.= '<td id="SPAW_'.$n.'_toolbar_right_html" valign="top" class="SPAW_'.$this->theme.'_toolbar" style="display : none;">';
      $buf.= $this->toolbars->get('right','html');
      $buf .= '</td>';

      $buf.= '</tr>';
      $buf.= '<tr><td class="SPAW_'.$this->theme.'_toolbar"></td>';

      $buf .= '<td id="SPAW_'.$n.'_toolbar_bottom_design" class="SPAW_'.$this->theme.'_toolbar" width="100%">';
      $buf.= $this->toolbars->get('bottom');
      $buf .= '</td>';

      $buf .= '<td id="SPAW_'.$n.'_toolbar_bottom_html" class="SPAW_'.$this->theme.'_toolbar" width="100%" style="display : none;">';
      $buf.= $this->toolbars->get('bottom','html');
      $buf .= '</td>';

      $buf .= '<td class="SPAW_'.$this->theme.'_toolbar"></td></tr>';
      $buf.= '</table>';
    }
    else
    {
      // show simple text area
  	  $buf = '<textarea cols="160" rows="20" name="'.$n.'" style="width:'.$this->getWidth().'; height:'.$this->getHeight().'">'.$this->getValue().'</textarea>';
    }
    return $buf;
  }

  // outputs wysiwyg control
  function show()
  {
    echo $this->getHtml();
  }

}
?>
