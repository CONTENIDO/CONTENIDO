<?php
/*****************************************
* File      :   $RCSfile$
* Project   :   Contenido
* Descr     :   Custom subnavigation for the newsletters
* Modified  :   $Date$
*
* © four for business AG, www.4fb.de
*
* $Id$
******************************************/
if ( $_REQUEST['cfg'] ) { exit; }

if (isset($_GET['idnewsletter']))
{
	# Set template data
	$sCaption = i18n("Edit");

	$tpl->set("d", "ID",		'c_'.$tpl->dyn_cnt);
	$tpl->set("d", "CLASS",	 	'');
	$tpl->set("d", "OPTIONS",   '');
	$tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=news&frame=4&idnewsletter=$idnewsletter").'">'.$sCaption.'</a>');
	$tpl->next();

	# Set template data
	$sCaption = i18n("Edit Message");

	$tpl->set("d", "ID",		'c_'.$tpl->dyn_cnt);
	$tpl->set("d", "CLASS",	 	'');
	$tpl->set("d", "OPTIONS",   '');
	$tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=news_edit&frame=4&idnewsletter=$idnewsletter").'">'.$sCaption.'</a>');
	$tpl->next();
	
	# Currently no plugin, as this code is a very specific one for frontend group security 
	/* if (is_array($cfg['plugins']['newsletterlogic']))
	{
		foreach ($cfg['plugins']['newsletterlogic'] as $plugin)
		{
			cInclude("plugins", "newsletterlogic/$plugin/".$plugin.".php");
		
			$className = "newsletterlogic_".$plugin;
	
			if (class_exists($className))
			{
				$class = new $className;
				
				$sCaption = $class->getFriendlyName();
				
				$tmp_area = "foo2";		
				$tpl->set("d", "ID",		'c_'.$tpl->dyn_cnt);
				$tpl->set("d", "CLASS",	 '');
				$tpl->set("d", "OPTIONS",   '');
				$tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=frontendgroups_rights&frame=4&useplugin=$plugin&idnewsletter=$idnewsletter").'">'.$sCaption.'</a>');
				$tpl->next();
			}   	
		}
	} */ 
	
	$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

	# Generate the third navigation layer
	$tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
} else {
	include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>