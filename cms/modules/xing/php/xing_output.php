<?php
// profile url
$url = "CMS_VALUE[0]";

// big button or small button?
$look = "CMS_VALUE[1]";

// name of user
$name = "CMS_VALUE[2]";

if ($url != '' && $look != '') {
	$tpl = new Template();

	$tpl->set("s", "NAME", $name);
	$tpl->set("s", "URL" , $url);

	if ($look == "small") {
		$tpl->generate("xing_small.html");
	} elseif($look == "big") {
		$tpl->generate("xing_big.html");
	}
}
?>