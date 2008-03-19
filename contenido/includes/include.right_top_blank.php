<?php

/******************************************
* File      :   include.default_right_top_blank.php
* Project   :   Contenido
* Descr     :   Builds empty third navigation
*               layer
*
* Author    :   Olaf Niemann
* Created   :   29.04.2003
* Modified  :   29.04.2003
*
* © four for business AG
******************************************/
if ( $_REQUEST['cfg'] ) { exit; }

include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);


?>
