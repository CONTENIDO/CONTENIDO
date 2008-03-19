<?php


$sql = "SELECT code FROM ".$cfg["tab"]["lay"]." WHERE idlay='$idlay'";
$db->query($sql);

if (!$db->next_record())
{
	echo i18n("No such layout");	
} else {
	
	$code = $db->f("code");
	
	/* Insert base href */
	$base = '<base href="'.$cfgClient[$client]["path"]["htmlpath"].'">';
	$tags = $base;
	
	$code = str_replace("<head>", "<head>\n".$tags, $code);
	
	eval("?>\n".$code."\n<?php\n");
}

?>