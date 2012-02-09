<?php
    $tpl = new Template();
    
    //url
    $url = "CMS_VALUE[0]";
    //layout standard, small, medium, tall
    $buttonLayout = "CMS_VALUE[1]";
    //language de_De en_US
    $locale = "CMS_VALUE[2]";
    $showCount = "CMS_VALUE[3]";
    
if($buttonLayout=='standard'){
    $btLayout = '';
} else {
    $btLayout = 'size="'.$buttonLayout.'"';
}

    $tpl->set("s", "URL", urlencode($url));
    $tpl->set("s", "LOCALE", "{lang: '".$locale."'}");
    $tpl->set("s", "LAYOUT", $btLayout);
    if($showCount){
        $tpl->set("s", "SHOW_COUNT",'');
    } else {
        $tpl->set("s", "SHOW_COUNT",' count="false"');
    }
    $tpl->generate("google_plus.html");
 
?>