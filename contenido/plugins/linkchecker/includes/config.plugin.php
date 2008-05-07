<?php
/******************************************************************************
Description 	: Linkchecker 2.0.1
Author      	: Frederic Schneider (4fb)
Urls        	: http://www.4fb.de
Create date 	: 2007-08-08
Modified    	: 2007-12-13
*******************************************************************************/

$plugin_name = "linkchecker";
$cfg['plugins']['linkchecker'] = $cfg['path']['contenido'] . "plugins/" . $plugin_name . "/";
$cfg['tab']['externlinks'] = $cfg['sql']['sqlprefix'].'_pi_externlinks';
$cfg['tab']['whitelist'] = $cfg['sql']['sqlprefix'].'_pi_linkwhitelist';

// Templates
$cfg['templates']['linkchecker_test'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test.html";
$cfg['templates']['linkchecker_test_errors'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_errors.html";
$cfg['templates']['linkchecker_test_errors_cat'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_errors_cat.html";
$cfg['templates']['linkchecker_test_nothing'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_test_nothing.html";
$cfg['templates']['linkchecker_noerrors'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_noerrors.html";
$cfg['templates']['linkchecker_whitelist'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_whitelist.html";
$cfg['templates']['linkchecker_whitelist_urls'] = $cfg['plugins']['linkchecker'] . "templates/standard/template.linkchecker_whitelist_urls.html";
?>