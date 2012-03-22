<?php

$tpl = new Template();

//url to profile
$url = "CMS_VALUE[0]";
//small pictures or big
$look = "CMS_VALUE[1]";

//alt for bild
$name = "CMS_VALUE[2]";



$tpl->set("s", "NAME", $name);
$tpl->set("s", "URL" , $url);

if($look == "small")
    $tpl->generate("xing_small.html");
elseif($look == "big")
    $tpl->generate("xing_big.html");
else {
	$tpl->generate("xing_no_config.html");
}


?>