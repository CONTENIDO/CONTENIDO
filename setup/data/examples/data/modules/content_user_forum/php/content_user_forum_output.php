<?php

/**
 * description:
 *
 * @package    Module
 * @subpackage ContentUserForum
 * @author     claus.schunk@4fb.de
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') or die('Illegal call');

if (!class_exists('ContentUserForumModule')) {
    cInclude('module', 'class.content_user_forum_module.php');
}

// Userforum administration
if (cRegistry::isBackendEditMode()) {
    echo "CMS_USERFORUM[2]";
}

// Generate instance
$userForumArticle = new ContentUserForumModule([
    'tpl' => cSmartyFrontend::getInstance(),
    'idart' => cSecurity::toInteger(cRegistry::getArticleId()),
    'idcat' => cSecurity::toInteger(cRegistry::getCategoryId()),
    'idlang' => cSecurity::toInteger(cRegistry::getLanguageId()),
    'collection' => new ArticleForumCollection(),
    'request' => $_REQUEST,
    'mi18n' => [
        'FEEDBACK' => mi18n("FEEDBACK"),
        'enterYourArticle' => mi18n("enterYourArticle"),
        'enterYourMail' => mi18n("enterYourMail"),
        'enterValidMail' => mi18n("enterValidMail"),
        'enterYourName' => mi18n("enterYourName"),
        'NEWENTRY' => mi18n("NEWENTRY"),
        'NEWENTRYTEXT' => mi18n("NEWENTRYTEXT"),
        'COMMENT' => mi18n("COMMENT"),
        'USER' => mi18n("USER"),
        'EMAIL' => mi18n("EMAIL"),
        'ARTICLE' => mi18n("ARTICLE"),
        'yourName' => mi18n("yourName"),
        'yourMailAddress' => mi18n("yourMailAddress"),
        'yourArticle' => mi18n("yourArticle"),
        'quote' => mi18n("quote"),
        'saveArticle' => mi18n("saveArticle"),
        'cancel' => mi18n("cancel"),
        'answerToQuote' => mi18n("answerToQuote"),
        'from' => mi18n("from"),
        'noCommentsYet' => mi18n("noCommentsYet"),
        'articles' => mi18n("articles"),
        'writeNewEntry' => mi18n("writeNewEntry"),
        'noPosibleInputForArticle' => mi18n("noPosibleInputForArticle"),
        'articlesLabel' => mi18n("articlesLabel"),
        'about' => mi18n("about"),
        'clock' => mi18n("clock"),
        'AM' => mi18n("AM"),
        'wroteAt' => mi18n("wroteAt"),
        'emailToAuthor' => mi18n("emailToAuthor"),
        'articleWasEditAt' => mi18n("articleWasEditAt"),
        'sameOpinion' => mi18n("sameOpinion"),
        'answers' => mi18n("answers"),
        'replyQuote' => mi18n("replyQuote"),
        'showHideArticles' => mi18n("showHideArticles"),
        'quoteFrom' => mi18n("quoteFrom"),
        'MODEMODETEXT' => mi18n("MODEMODETEXT"),
    ],
]);

// Renders module output
$userForumArticle->receiveData();

?>
