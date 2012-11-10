<?php
/**
 * Description: Twitter output
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

$tpl = new cTemplate();
$tplFollowButton = new cTemplate();
$tplTwitts= new cTemplate();
$tplLableTranslation = new cTemplate();
$tplTweets = new cTemplate();
$tpl2 = new cTemplate();

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

$tplLableTranslation->set('s', "LESS_MINUTES", mi18n("TIME_LESS_MINUTES"));
$tplLableTranslation->set('s', "ONE_MINUTES", mi18n("TIME_ONE_MINUTES"));
$tplLableTranslation->set('s', "MORE_MINUTES", mi18n("TIME_FORMATED_MINUTES"));
$tplLableTranslation->set('s', "ONE_HOURS", mi18n("TIME_ONE_HOURS"));
$tplLableTranslation->set('s', "MORE_HOURS", mi18n("TIME_MORE_HOURS"));
$tplLableTranslation->set('s', "ONE_DAY", mi18n("TIME_ONE_DAY"));
$tplLableTranslation->set('s', "MORE_DAYS", mi18n("TIME_FORMATED_DAYS"));

$tplTwitts->set('s', "USER_NAME", $twitterName);
$tplTwitts->set('s', "COUNT", $countTwitts);

$tplTwitts->set('s', "JS_FILE_TWITTER", $tpl2->generate('twitter.js', true));

switch ($twitterLook) {
    case "small":
        // Show twitter follow button
        if ($showFollowButton) {
            $tplFollowButton->set('s', "ALIGN", 'left');
            $tplFollowButton->set('s', "LANG", 'de');
            $tplFollowButton->set('s', "TWITTER_NAME", $twitterName);
            $tpl->set('s', "FOLLOW_BUTTON", $tplFollowButton->generate('follow_button.html', true));
        } else {
            $tpl->set('s', "FOLLOW_BUTTON", '');
        }

        // Show twitts
        if ($showTwitts) {
            $tpl->set('s', "TWITTER_TWITTS", $tplTwitts->generate("twitter_twitts_small.html", true));
        }  else {
            $tpl->set('s', "TWITTER_TWITTS", '');
        }

        $tpl->set('s', "LABEL_TRANSLATIONS",$tplLableTranslation->generate('label_translations.html',true));
        $tpl->generate('twitter_small.html');
        break;
    case "big":
        if ($showFollowButton) {
            $tplFollowButton->set('s',  "ALIGN",'left');
            $tplFollowButton->set('s',  "LANG", 'de');
            $tplFollowButton->set('s', "TWITTER_NAME",$twitterName);
            $tpl->set('s', "FOLLOW_BUTTON",$tplFollowButton->generate('follow_button.html', true));
        } else {
            $tpl->set('s', "FOLLOW_BUTTON", '');
        }

        // Show twitts
        if ($showTwitts) {
            $tpl->set('s', "TWITTER_TWITTS", $tplTwitts->generate("twitter_twitts_big.html", true));
        } else {
            $tpl->set('s', "TWITTER_TWITTS", '');
        }

        $tpl->set('s', "LABEL_TRANSLATIONS", $tplLableTranslation->generate('label_translations.html', true));
        $tpl->generate('twitter.html');
        break;
    default:
        $tpl->generate('twitter_no_config.html');
        break;
}

if ($showShareButton) {
    if ($showCount) {
        $tplTweets->set('s', "SHOW_COUNT", '');
    } else {
        $tplTweets->set('s', "SHOW_COUNT", ' data-count="none"');
    }
    $tplTweets->set('s', "DEFAULT_TEXT", $defaultText);
    $tplTweets->set('s', "URL_TO_SHARE", $urlToShare);
    $tplTweets->generate('tweets.html');
}

?>