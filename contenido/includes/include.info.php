<?php

/******************************************
* File      :   includes.mycontenido_lastarticles.php
* Project   :   Contenido
* Descr     :   Displays all last edited articles
*               of a category 
*
* Author    :   Timo A. Hummel
* Created   :   08.05.2003
* Modified  :   08.05.2003
*
* © four for business AG
*****************************************/


        # Generate template
		$tpl->reset();
		
		$message = sprintf(i18n("You can find many information and a community forum on the <a href=\"http://www.contenido.org\" target=\"_blank\">Contenido Portal</a>"));
		
		$tpl->set('s', 'VERSION', $cfg['version']);
		$tpl->set('s', 'PORTAL', $message); 
        $tpl->generate($cfg['path']['templates'] . $cfg['templates']['info']);
    


?>
