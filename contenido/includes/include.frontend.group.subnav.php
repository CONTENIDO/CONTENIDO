<?php
/*****************************************
* File      :   $RCSfile: include.frontend.group.subnav.php,v $
* Project   :   Contenido
* Descr     :   Custom subnavigation for the frontend groups
* Modified  :   $Date: 2006/01/20 17:35:56 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.frontend.group.subnav.php,v 1.5 2006/01/20 17:35:56 timo.hummel Exp $
******************************************/
if ( $_REQUEST['cfg'] ) { exit; }

if ( isset($_GET['idfrontendgroup']) )
{

    $caption = i18n("Overview");
    $tmp_area = "foo2";

    # Set template data
    $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS",     '');
    $tpl->set("d", "OPTIONS",   '');
    $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$area&frame=4&idfrontendgroup=$idfrontendgroup").'">'.$caption.'</a>');
    $tpl->next();
    
    if (is_array($cfg['plugins']['frontendlogic']))
    {
        foreach ($cfg['plugins']['frontendlogic'] as $plugin)
        {
        	cInclude("plugins", "frontendlogic/$plugin/".$plugin.".php");
        
        	$className = "frontendlogic_".$plugin;
    
    		if (class_exists($className))
    		{
	    		$class = new $className;
	    		
	        	$caption = $class->getFriendlyName();
	        	
	        	$tmp_area = "foo2";    	
	            $tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt);
	            $tpl->set("d", "CLASS",     '');
	            $tpl->set("d", "OPTIONS",   '');
	            $tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=frontendgroups_rights&frame=4&useplugin=$plugin&idfrontendgroup=$idfrontendgroup").'">'.$caption.'</a>');
	            $tpl->next();
    		}   	
        }
    } 
    
    
    
    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    # Generate the third
    # navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);

} else {

    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);

}

?>
