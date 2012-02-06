<?php
    $tpl = new Template();
    
    //url
    $url = "CMS_VALUE[0]";
    //like button or like box
    $facebookPlugin = "CMS_VALUE[1]";
    //layout standard, button_count , box_count
    $likeButtonLayout = "CMS_VALUE[2]";
    //show faces of fans
    $showFaces = "CMS_VALUE[3]";
    //width of the box
    $width = "CMS_VALUE[4]";
    //height of the box
    $height = "CMS_VALUE[6]";
    //language de_De en_US
    $locale = "CMS_VALUE[5]";
    
    if($showFaces != "true")
        $showFaces = "false";
        
    
    $tpl->set("s", "SHOW_FACES", $showFaces);
    $tpl->set("s", "LOCALE", $locale);
    $tpl->set("s", "WIDTH", $width);
    $tpl->set("s", "HEIGHT", $height);
    $tpl->set("s", "LAYOUT", $likeButtonLayout);
    
    switch($facebookPlugin) {
    
        case 'like_button':
             $tpl->set("s", "URL", urlencode($url));
             $tpl->generate("facebook_like_button.html");
        break;
    
        case 'like_box':
             $tpl->set("s", "URL",$url);
            $tpl->generate("facebook_like_box.html");
        break;
    
    
    }

?>