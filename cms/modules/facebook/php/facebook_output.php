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
   
    
    cApiPropertyCollection::reset();
    $propColl = new cApiPropertyCollection();
    $propColl->changeClient($client);
    
    $language = $propColl->getValue('idlang', $lang, 'language', 'code', '');
    $country =  $propColl->getValue('idlang', $lang, 'country', 'code', '');;
    
    $locale = $language ."_".strtoupper($country);
    
    
    if($showFaces != "true")
        $showFaces = "false";
        
    
    $tpl->set("s", "SHOW_FACES", $showFaces);
    $tpl->set("s", "LOCALE", $locale);
    $tpl->set("s", "WIDTH", $width);
    $tpl->set("s", "HEIGHT", $height);
    $tpl->set("s", "LAYOUT", $likeButtonLayout);
    
    $display = new Contenido_Notification();
    switch($facebookPlugin) {
    
        case 'like_button':
             $tpl->set("s", "URL", urlencode($url));
             $tpl->generate("facebook_like_button.html");
        break;
    
        case 'like_box':
             $tpl->set("s", "URL",$url);
            $tpl->generate("facebook_like_box.html");
        break;
        default:
    		$display->displayMessageBox(Contenido_Notification::LEVEL_ERROR, "Please configure facebook plugin!");
    
    }
    
 

?>