<?php
/******************************************
* File      :   include.left_top_blank.php
* Project   :   Contenido
* Descr     :   Blank left top page
*
* Author    :   Jan Lengowski
* Created   :   21.01.2003
* Modified  :   21.01.2003
*
* © four for business AG
******************************************/

$tpl->reset();
$tpl->generate($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg['templates']['subnav_blank']);

?>

