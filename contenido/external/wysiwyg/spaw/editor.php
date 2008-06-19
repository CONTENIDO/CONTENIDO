<?php

if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}

include_once (dirname(__FILE__) . '/../../../includes/startup.php');
include $cfg["path"]["all_wysiwyg"].'spaw/config/spaw_control.config.php';
include $cfg["path"]["all_wysiwyg"].'spaw/spaw_control.class.php';

/*
  $editor = new SPAW_Wysiwyg(
              $control_name='CMS_HTML', // control's name
              $value='',                  // initial value
              $lang='en',                 // language
              $mode = '',                 // toolbar mode
              $theme='',                  // theme (skin)
              $width='100%',              // width
              $height='300px',            // height
              $css_stylesheet='',         // css stylesheet file for content
              $dropdown_data=''           // data for dropdowns (style, font, etc.)
            );

$editor->show();*/

$currentuser = new User;
$currentuser->loadUserByUserID($auth->auth["uid"]);

$sw = new SPAW_Wysiwyg('CMS_HTML', $a_content[$type][$typenr], $spaw_default_lang,
                       $toolbar_mode, $spaw_theme, $editorwidth, $editorheight, $css_stylesheet);
if ($currentuser->getField("wysi") == 0)
{
	$sw->disabled = true;
} else {
	$sw->disabled = false;
}
                       
$sw->show();

?>
