<?php
/**
 * Description: Twitter module
 *
 * @version    1.0.1
 * @author     konstantinos.katikak
 * @author     alexander.scheider@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 *
 */

//get smarty instance
$tpl = Contenido_SmartyWrapper::getInstance();

//get translations
$nameLabel = mi18n("TWITTERNAME");
$lookLabel = mi18n("LOOK");
$smallIconLabel = mi18n("SMALL");
$bigIconLabel = mi18n("BIG");
$showTweetsLabel = mi18n("SHOW_TWEETS");
$countTweetsLabel = mi18n("COUNT_TWEETS");
$showFollowButtonLabel = mi18n("SHOW_FOLLOW_BUTTON");
$showTweetButtonLabel = mi18n("SHOW_TWEET_BUTTON");
$defaultTextLabel = mi18n("DEFAULT_TEXT");
$urlToShareLabel = mi18n("URL_TO_SHARE");
$showCountLabel = mi18n("SHOW_COUNT");
$save = mi18n("SAVE");
$label_overview = mi18n("OVERVIEW");

//get id's
$idartlang = cRegistry::getArticleLanguageId();
$idlang = cRegistry::getLanguageId();
$idclient = cRegistry::getClientId();

//create article object
$art = new cApiArticleLanguage($idartlang);

//if post save values in db
if ('POST' === strtoupper($_SERVER['REQUEST_METHOD']) && $_POST['plugin_type'] == 'twitter') {
    conSaveContentEntry($idartlang, "CMS_HTML", 4000, $_POST['twitter_name']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4001, $_POST['look']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4002, $_POST['show_tweets']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4003, $_POST['count_tweets']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4004, $_POST['show_follow_button']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4005, $_POST['show_tweet_button']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4006, $_POST['default_text']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4007, $_POST['url_to_share']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4008, $_POST['show_count']);
}

//get saved content
$twitterName = strip_tags($art->getContent("CMS_HTML", 4000));
$look = strip_tags($art->getContent("CMS_HTML", 4001));
$show_tweets = strip_tags($art->getContent("CMS_HTML", 4002));
$count_tweets = strip_tags($art->getContent("CMS_HTML", 4003));
$show_follow_button = strip_tags($art->getContent("CMS_HTML", 4004));
$show_tweet_button = strip_tags($art->getContent("CMS_HTML", 4005));
$default_text = strip_tags($art->getContent("CMS_HTML", 4006));
$url_to_share = strip_tags($art->getContent("CMS_HTML", 4007));
$show_count = strip_tags($art->getContent("CMS_HTML", 4008));


//if backend mode set some values and display config tpl
if (cRegistry::isBackendEditMode()) {
    $tpl->assign('twitterName', $twitterName);
    $tpl->assign('look', $look);
    $tpl->assign('show_tweets', $show_tweets);
    $tpl->assign('count_tweets', $count_tweets);
    $tpl->assign('show_follow_button', $show_follow_button);
    $tpl->assign('show_tweet_button', $show_tweet_button);
    $tpl->assign('default_text', $default_text);
    $tpl->assign('url_to_share', $url_to_share);
    $tpl->assign('show_count', $show_count);
    $tpl->assign('nameLabel', $nameLabel);
    $tpl->assign('lookLabel', $lookLabel);
    $tpl->assign('smallIconLabel', $smallIconLabel);
    $tpl->assign('bigIconLabel', $bigIconLabel);
    $tpl->assign('showTweetsLabel', $showTweetsLabel);
    $tpl->assign('countTweetsLabel', $countTweetsLabel);
    $tpl->assign('showFollowButtonLabel', $showFollowButtonLabel);
    $tpl->assign('showTweetButtonLabel', $showTweetButtonLabel);
    $tpl->assign('defaultTextLabel', $defaultTextLabel);
    $tpl->assign('urlToShareLabel', $urlToShareLabel);
    $tpl->assign('showCountLabel', $showCountLabel);
    $tpl->assign('save', $save);
    $tpl->assign('label_overview', $label_overview);

    $tpl->display('twitter_config_view.tpl');
} else {

    $tpl->assign("LESS_MINUTES", mi18n("TIME_LESS_MINUTES"));
    $tpl->assign("ONE_MINUTES", mi18n("TIME_ONE_MINUTES"));
    $tpl->assign("MORE_MINUTES", mi18n("TIME_FORMATED_MINUTES"));
    $tpl->assign("ONE_HOURS", mi18n("TIME_ONE_HOURS"));
    $tpl->assign("MORE_HOURS", mi18n("TIME_MORE_HOURS"));
    $tpl->assign("ONE_DAY", mi18n("TIME_ONE_DAY"));
    $tpl->assign("MORE_DAYS", mi18n("TIME_FORMATED_DAYS"));

    $tpl->assign("USER_NAME", $twitterName);
    $tpl->assign("COUNT", $count_tweets);
    $tpl->assign("JS_FILE_TWITTER", $tpl->fetch('twitter.js'));

    switch ($look) {
        case "small":
            // Show twitter follow button
            if ($show_follow_button) {
                $tpl->assign("ALIGN", 'left');
                $tpl->assign("LANG", 'de');
                $tpl->assign("TWITTER_NAME", $twitterName);
                $tpl->assign("FOLLOW_BUTTON", $tpl->fetch('follow_button.tpl'));
            } else {
                $tpl->assign("FOLLOW_BUTTON", '');
            }

            // Show twitts
            if ($show_tweets) {
                $tpl->assign("TWITTER_TWITTS", $tpl->fetch("twitter_twitts_small.tpl"));
            } else {
                $tpl->assign("TWITTER_TWITTS", '');
            }

            $tpl->assign("LABEL_TRANSLATIONS", $tpl->fetch('label_translations.tpl'));
            $tpl->display('twitter_small.tpl');
            break;
        case "big":
            if ($show_follow_button) {
                $tpl->assign("ALIGN", 'left');
                $tpl->assign("LANG", 'de');
                $tpl->assign("TWITTER_NAME", $twitterName);
                $tpl->assign("FOLLOW_BUTTON", $tpl->fetch('follow_button.tpl'));
            } else {
                $tpl->assign("FOLLOW_BUTTON", '');
            }

            // Show twitts
            if ($show_tweets) {
                $tpl->assign("TWITTER_TWITTS", $tpl->fetch("twitter_twitts_big.tpl"));
            } else {
                $tpl->assign("TWITTER_TWITTS", '');
            }

            $tpl->assign("LABEL_TRANSLATIONS", $tpl->fetch('label_translations.tpl'));
            $tpl->display('twitter_big.tpl');
            break;
        default:
            $tpl->display('twitter_no_config.tpl');
            break;
    }

    if ($show_tweet_button) {
        if ($show_count) {
            $tpl->assign("SHOW_COUNT", '');
        } else {
            $tpl->assign("SHOW_COUNT", ' data-count="none"');
        }
        $tpl->assign("DEFAULT_TEXT", $default_text);
        $tpl->assign("URL_TO_SHARE", $url_to_share);
        $tpl->display('tweets.tpl');
    }
}

?>