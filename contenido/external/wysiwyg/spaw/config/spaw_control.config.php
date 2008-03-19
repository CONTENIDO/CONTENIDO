<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Configuration file for CONTENIDO
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// Modified: Martin Horwath, horwath@opensa.org
// SPAW1.0.3 for Contenido 4.4.x, 2003-11-24 v0.2
// ================================================

include_once (dirname(__FILE__) . '/../../../../includes/startup.php');

cInclude("all_wysiwyg", 'spaw/class/lang.class.php'); // CONTENIDO
cInclude("includes", "functions.i18n.php");
cInclude("classes", "class.user.php");
cInclude("includes", "functions.general.php");

  i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

   if ($cfgClient["set"] != "set") // CONTENIDO
   {
      $db = new DB_Contenido;
      rereadClients();
   }

// directory where spaw files are located
$spaw_root = $cfg['path']['all_wysiwyg'] ."spaw/";
$spaw_dir = $cfg['path']['all_wysiwyg_html'] ."spaw/";
$spaw_base_url = $cfgClient[$client]["path"]["htmlpath"].$cfgClient[$client]["upload"];

$spaw_default_toolbars = 'default';
$spaw_default_theme = 'default';
$langs = i18nGetAvailableLanguages(); // CONTENIDO
$spaw_default_lang = $langs[$belang][4]; // CONTENIDO
$spaw_default_css_stylesheet = $spaw_dir.'wysiwyg.css';

// add javascript inline or via separate file
$spaw_inline_js = false;

// use active toolbar (reflecting current style) or static
$spaw_active_toolbar = true;

// spaw configuration - CONTENIDO
$toolbar_mode = getEffectiveSetting("wysiwyg","spaw-toolbar-mode");

if ($toolbar_mode == false)
{
   $toolbar_mode = "default";
}

$spaw_theme = getEffectiveSetting("wysiwyg","spaw-theme");

if ($spaw_theme == false)
{
   $spaw_theme = "contenido";
}

switch ($type)
{
   case "CMS_HTML":
         $editorheight = getEffectiveSetting("wysiwyg","spaw-height-html");
         break;
   case "CMS_HTMLHEAD":
         $editorheight = getEffectiveSetting("wysiwyg","spaw-height-head");
         break;
   default:
         $editorheight = getEffectiveSetting("wysiwyg","spaw-height");
         break;
}

if (!is_numeric($editorheight))
{
   $editorheight = 350;
}

$editorwidth = getEffectiveSetting("wysiwyg","spaw-width");

if ($editorwidth == false)
{
   $editorwidth = '100%';
}

$css_stylesheet = getEffectiveSetting("wysiwyg","spaw-stylesheet-file");

if ($css_stylesheet == false)
{
   $css_stylesheet = "";
} else {
    $css_stylesheet = $cfgClient[$client]["htmlpath"]["frontend"].$css_stylesheet;
}


$styles = getEffectiveSetting("wysiwyg","spaw-styles");

if ($styles == false && $css_stylesheet == "")
{
    // standard settings
    $spaw_dropdown_data['style']['default'] = 'Normal';
    $spaw_dropdown_data['style']['style1'] = 'Style No1';
    $spaw_dropdown_data['style']['style2'] = 'Style No2';
} else {

    if ($styles != false) // check if any styles are defined
    {
        $styles = explode(";",urldecode($styles));
        if (is_array($styles))
        {
            foreach ($styles as $style) // if there are more values
            {
               $spaw_dropdown_data['style'][$style] = $style;
            }
        } else {
            $spaw_dropdown_data['style'][$styles] = $styles; // for one value
        }
    }

    if ($css_stylesheet != "") // get styles from defined stylesheet file
    {
        $styles = file ($css_stylesheet);
        if ($styles) {
            foreach ($styles as $style) {
                if (preg_match("/\.([^\s:,{]*)/i", $style, $style_result)) {
                    // matches all .class in stylesheet, double entries are not possible
                   $spaw_dropdown_data['style'][trim($style_result[1])] = trim($style_result[1]);
                }
            }
            asort($spaw_dropdown_data['style']); // sort styles alphabetically
        } else {
            // stylesheet does not exist
        }
    }
} 

$spaw_dropdown_data['font']['Arial, Helvetica, Verdana, Sans Serif'] = 'Arial';
$spaw_dropdown_data['font']['Courier, Courier New'] = 'Courier';
$spaw_dropdown_data['font']['Tahoma, Verdana, Arial, Helvetica, Sans Serif'] = 'Tahoma';
$spaw_dropdown_data['font']['Times New Roman, Times, Serif'] = 'Times';
$spaw_dropdown_data['font']['Verdana, Tahoma, Arial, Helvetica, Sans Serif'] = 'Verdana';

$spaw_dropdown_data['fontsize']['1'] = '1';
$spaw_dropdown_data['fontsize']['2'] = '2';
$spaw_dropdown_data['fontsize']['3'] = '3';
$spaw_dropdown_data['fontsize']['4'] = '4';
$spaw_dropdown_data['fontsize']['5'] = '5';
$spaw_dropdown_data['fontsize']['6'] = '6';

$spaw_dropdown_data['paragraph']['<P>'] = 'Normal';
$spaw_dropdown_data['paragraph']['<H1>'] = 'Heading 1';
$spaw_dropdown_data['paragraph']['<H2>'] = 'Heading 2';
$spaw_dropdown_data['paragraph']['<H3>'] = 'Heading 3';
$spaw_dropdown_data['paragraph']['<H4>'] = 'Heading 4';
$spaw_dropdown_data['paragraph']['<H5>'] = 'Heading 5';
$spaw_dropdown_data['paragraph']['<H6>'] = 'Heading 6';

// extentions for image files
$spaw_valid_imgs = "'gif', 'jpg', 'jpeg', 'png'"; // Part of SQL Query

$spaw_debug = "Debug:<br>spaw_root:".$spaw_root."<br>spaw_base_url:".$spaw_base_url."<br>spaw_dir:".$spaw_dir
?>