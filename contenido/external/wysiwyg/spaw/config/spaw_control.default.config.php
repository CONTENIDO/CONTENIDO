<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Configuration file
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// v.1.0, 2003-03-27
// ================================================

// directory where spaw files are located
$spaw_dir = '/spaw/';

// base url for images
$spaw_base_url = 'http://spaw/';

if (!ereg('/$', $HTTP_SERVER_VARS['DOCUMENT_ROOT']))
  $spaw_root = $HTTP_SERVER_VARS['DOCUMENT_ROOT'].$spaw_dir;
else
  $spaw_root = $HTTP_SERVER_VARS['DOCUMENT_ROOT'].substr($spaw_dir,1,strlen($spaw_dir)-1);


$spaw_default_toolbars = 'default';
$spaw_default_theme = 'default';
$spaw_default_lang = 'en';
$spaw_default_css_stylesheet = $spaw_dir.'wysiwyg.css';

// add javascript inline or via separate file
$spaw_inline_js = false;

// use active toolbar (reflecting current style) or static
$spaw_active_toolbar = true;

// default dropdown content
$spaw_dropdown_data['style']['default'] = 'Normal';

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

$spaw_dropdown_data['paragraph']['Normal'] = 'Normal';
$spaw_dropdown_data['paragraph']['Heading 1'] = 'Heading 1';
$spaw_dropdown_data['paragraph']['Heading 2'] = 'Heading 2';
$spaw_dropdown_data['paragraph']['Heading 3'] = 'Heading 3';
$spaw_dropdown_data['paragraph']['Heading 4'] = 'Heading 4';
$spaw_dropdown_data['paragraph']['Heading 5'] = 'Heading 5';
$spaw_dropdown_data['paragraph']['Heading 6'] = 'Heading 6';

// image library related config

// allowed extentions for uploaded image files
$spaw_valid_imgs = array('gif', 'jpg', 'jpeg', 'png');

// allow upload in image library
$spaw_upload_allowed = true;

// image libraries
$spaw_imglibs = array(
  array(
    'value'   => 'you/need/to/change/this/',
    'text'    => 'Not configured',
  ),
  array(
    'value'   => 'you/need/to/change/this/too/',
    'text'    => 'Not configured',
  ),
);


?>
