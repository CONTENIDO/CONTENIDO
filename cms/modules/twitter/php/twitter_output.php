<?php

$tpl = new Template();
$tplFollowButton = new Template();
$tplTwitts= new Template();
$tplLableTranslation = new Template();
$tplTweets = new Template();

$twitterName = "CMS_VALUE[0]";
$twitterLook = "CMS_VALUE[1]";
$showTwitts = "CMS_VALUE[2]";
$countTwitts = "CMS_VALUE[3]";
$showFollowButton = "CMS_VALUE[4]";

$showShareButton = "CMS_VALUE[5]";
$defaultText = "CMS_VALUE[6]";
$urlToShare = "CMS_VALUE[7]";
$showCount = "CMS_VALUE[8]";


#echo "values: ".$twitterName.$twitterLook.$showTwitts.$countTwitts.$showFollowButton;




 
           

$tplLableTranslation->set("s","LESS_MINUTES", mi18n("vor weniger als einer Minute"));
$tplLableTranslation->set("s","ONE_MINUTES", mi18n("vor einer Minute"));
$tplLableTranslation->set("s","MORE_MINUTES", mi18n("vor %d Minuten"));
$tplLableTranslation->set("s","ONE_HOURS", mi18n("vor einer Stunde"));
$tplLableTranslation->set("s","MORE_HOURS", mi18n("vor %d Stunden"));
$tplLableTranslation->set("s","ONE_DAY", mi18n("vor einem Tag"));
$tplLableTranslation->set("s","MORE_DAYS", mi18n("vor %d Tagen"));

$tplTwitts->set("s", "USER_NAME", $twitterName);
$tplTwitts->set("s", "COUNT", $countTwitts);


        
switch($twitterLook) {

    case "small":
          
        //show twitter follow button
        if($showFollowButton) {
            $tplFollowButton->set("s", "ALIGN",'left');
            $tplFollowButton->set("s", "LANG", 'de');
            $tplFollowButton->set("s","TWITTER_NAME",$twitterName);
            $tpl->set("s","FOLLOW_BUTTON",$tplFollowButton->generate('follow_button.html',true));
        }else 
            $tpl->set("s","FOLLOW_BUTTON", '');

        //show twitts 
        if($showTwitts)
            $tpl->set("s","TWITTER_TWITTS",$tplTwitts->generate("twitter_twitts_small.html",true));
        else 
            $tpl->set("s","TWITTER_TWITTS",'');
        
        $tpl->set("s","LABEL_TRANSLATIONS",$tplLableTranslation->generate('label_translations.html',true));
        $tpl->generate('twitter_small.html');
    break;
    case "big":
    
        if($showFollowButton) {
            $tplFollowButton->set("s", "ALIGN",'left');
            $tplFollowButton->set("s", "LANG", 'de');
            $tplFollowButton->set("s","TWITTER_NAME",$twitterName);
            $tpl->set("s","FOLLOW_BUTTON",$tplFollowButton->generate('follow_button.html',true));
        }else 
            $tpl->set("s","FOLLOW_BUTTON", '');
            
        //show twitts 
        if($showTwitts)
            $tpl->set("s","TWITTER_TWITTS",$tplTwitts->generate("twitter_twitts_big.html",true));
        else 
            $tpl->set("s","TWITTER_TWITTS",'');
        
               
        $tpl->set("s","LABEL_TRANSLATIONS",$tplLableTranslation->generate('label_translations.html',true));
        $tpl->generate('twitter.html');
    break;

}
if($showShareButton) {
    if($showCount){
        $tplTweets->set("s", "SHOW_COUNT",'');
    } else {
        $tplTweets->set("s", "SHOW_COUNT",' data-count="none"');
    }
    $tplTweets->set("s", "DEFAULT_TEXT",$defaultText);
    $tplTweets->set("s", "URL_TO_SHARE",$urlToShare);
    $tplTweets->generate('tweets.html'); 
} 




?>