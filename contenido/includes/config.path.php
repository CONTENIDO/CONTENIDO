<?php
/*****************************************
* File      :   $RCSfile: config.path.php,v $
* Project   :   Contenido
* Descr     :   Contenido Path Configurations
*
* Created   :   24.02.2004
* Modified  :   $Date: 2006/03/16 10:52:50 $
*
* © four for business AG, www.4fb.de
*
* $Id: config.path.php,v 1.9 2006/03/16 10:52:50 timo.hummel Exp $
******************************************/

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */
   
$cfg['path']['contenido_html']          = '../contenido/';

$cfg['path']['statfile']                = 'statfile/';
$cfg['path']['includes']                = 'includes/';

$cfg['path']['xml']                     = 'xml/';
$cfg['path']['images']                  = 'images/';
$cfg['path']['classes']                 = 'classes/';

$cfg["path"]["cronjobs"]				= 'cronjobs/';
$cfg['path']['scripts']                 = 'scripts/';
$cfg['path']['styles']                  = 'styles/';
$cfg["path"]['plugins']				    = 'plugins/';

$cfg['path']['locale']                  = 'locale/';
$cfg['path']['temp']                  	= 'temp/';
$cfg['path']['external']                = 'external/';

$cfg['path']['frontendtemplate']        = 'external/frontend/';
$cfg['path']['templates']               = 'templates/standard/';
$cfg['path']['xml']                     = 'xml/';

$cfg['path']['repository']				= $cfg["path"]['plugins'] . 'repository/';
$cfg['path']['modules']					= 'modules/';

?>