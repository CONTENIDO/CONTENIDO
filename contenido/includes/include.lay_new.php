<?php

/******************************************
* File      :   include.lay_new.php
* Project   :   Contenido 
* Descr     :   Link f�r "neues Layout"
*
* Author    :   Olaf Niemann
* Created   :   27.03.2003
* Modified  :   27.03.2003
*
* � four for business AG
******************************************/

$tpl->reset();

$tpl->set('s', 'ACTION', '<div style="height:2em;"><a class="addfunction" target="right_bottom" href="'.$sess->url("main.php?area=lay_edit&frame=4&action=lay_new").'">'.i18n("New Layout").'</a></div>');

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['left_top']);

?>
