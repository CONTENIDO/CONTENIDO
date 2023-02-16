<?php
/**
 * This file contains the Frontend user editor.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cAuth $auth
 * @var cPermission $perm
 * @var cSession $sess
 * @var array $cfg
 * @var string $area
 * @var int $client
 * @var int $lang
 * @var int $frame
 */

cInclude("includes", "functions.con.php"); // For conDeleteArt and conCopyArt

// Initialization
$oPage = new cGuiPage("newsletter_edit_message", "newsletter");
$oClientLang = new cApiClientLanguage(false, $client, $lang);

// Ensure to have numeric newsletter id
$requestIdNewsletter = cSecurity::toInteger($_REQUEST['idnewsletter'] ?? '0');

// Include plugins
if (cHasPlugins('newsletters')) {
    cIncludePlugins('newsletters');
}

$action = $action ?? '';

// Exec actions
$oNewsletter = new Newsletter();
if ($requestIdNewsletter > 0) {
    $oNewsletter->loadByPrimaryKey($requestIdNewsletter);
}

if (true === $oNewsletter->isLoaded() && $oNewsletter->get("idclient") == $client && $oNewsletter->get("idlang") == $lang) {
    // Check and set values
    $requestSelTemplate = cSecurity::toInteger($_REQUEST['selTemplate'] ?? '0');
    $requestTxtMessage = $_REQUEST['txtMessage'] ?? '';

    if ($requestSelTemplate < 0) {
        $requestSelTemplate = 0;
    }

    // Saving message changes; note, that if a user doesn't have the right to save the
    // text message he may still have the right to change the html article. To prevent
    // changing the html article, give the user only read access right for the newsletter
    // article category - the article will be shown also, if he doesn't have any rights at all...
    if ($action == "news_save" && $perm->have_perm_area_action("news", $action)) {

        // Don't use $area! Changing e.g. \' back to ' (magic_quotes)

        $sMessage = cSecurity::unescapeDB($requestTxtMessage);
        $oNewsletter->set("message", $sMessage);

        if ($oNewsletter->get("template_idart") != $requestSelTemplate) {
            if ($oNewsletter->get("idart") > 0) {
                // Template has been changed: Delete old article
                // (this discards the current html content as it deletes the
                // existing newsletter article)
                conDeleteArt($oNewsletter->get("idart"));
                $iIDArt = 0;
            }

            if ($requestSelTemplate > 0) {
                // Template has been changed, but specified: Store template
                // article as new newsletter article
                $iIDArt = conCopyArticle($requestSelTemplate, $oClientLang->getProperty("newsletter", "html_newsletter_idcat"), sprintf(i18n("Newsletter: %s", 'newsletter'), $oNewsletter->get("name")));
                // Article has to be online for sending...
                conMakeOnline($iIDArt, $lang);
            }

            $oNewsletter->set("idart", $iIDArt);
            $oNewsletter->set("template_idart", $requestSelTemplate);
        }

        $oNewsletter->store();
        $oPage->displayOk(i18n("Saved changes successfully!", 'newsletter'));
    } elseif ($oNewsletter->get("idart") > 0) {
        // Check, if html message article and template article are still
        // available
        $oArticles = new cApiArticleLanguageCollection();
        $oArticles->setWhere("idlang", $lang);
        $oArticles->setWhere("idart", $oNewsletter->get("idart"));
        $oArticles->query();

        if ($oArticles->count() == 0) {
            // Ups, article lost, reset idart and template_idart for newsletter
            $oPage->displayError(sprintf(i18n("The html newsletter article has been deleted (idart: %s), the html message is lost", 'newsletter'), $oNewsletter->get("idart"))) . "<br>";

            $oNewsletter->set("idart", 0);
            $oNewsletter->set("template_idart", 0);
            $oNewsletter->store();
        } else {
            $oArticles->resetQuery();
            $oArticles->setWhere("idlang", $lang);
            $oArticles->setWhere("idart", $oNewsletter->get("template_idart"));
            $oArticles->query();

            if ($oArticles->count() == 0) {
                // Ups, template has been deleted: Restore from current
                // newsletter message article
                $oPage->displayWarning(i18n("The html newsletter template article has been deleted, it has been restored using the html message article of this newsletter", 'newsletter')) . "<br>";

                $iIDArt = conCopyArticle($oNewsletter->get("idart"), $oClientLang->getProperty("newsletter", "html_template_idcat"), sprintf(i18n("%s (Template restored)", 'newsletter'), $oNewsletter->get("name")));
                $oNewsletter->set("template_idart", $iIDArt);
                $oNewsletter->store();
            }
        }
    }

    $oForm = new cGuiTableForm("frmNewsletterMsg");
    $oForm->setVar("frame", $frame);
    $oForm->setVar("area", $area);
    $oForm->setVar("action", "news_save");
    $oForm->setVar("idnewsletter", $requestIdNewsletter);

    $oForm->addHeader(sprintf(i18n("Edit newsletter message (%s)", 'newsletter'), $oNewsletter->get("name")));
    $oForm->add(i18n("Subject", 'newsletter'), $oNewsletter->get("subject"));

    $sTagInfoText = '<a href="javascript:void(0)" data-action="toggle_tag_info" data-toggle-id="idTagInfoText"><strong>' . i18n("Tag information", 'newsletter') . '</strong></a>' . '<div id="idTagInfoText" style="display: none"><br><b>' . i18n("Special message tags (will be replaced when sending)", 'newsletter') . ':</b><br>' . 'MAIL_NAME: ' . i18n("Name of the recipient", 'newsletter') . '<br>' . 'MAIL_DATE: ' . i18n("Date, when the mail has been sent", 'newsletter') . '<br>' . 'MAIL_TIME: ' . i18n("Time, when the mail has been sent", 'newsletter') . '<br>' . 'MAIL_NUMBER: ' . i18n("Number of recipients", 'newsletter') . '<br>'.
            'MAIL_UNSUBSCRIBE: ' .
             i18n("Link to unsubscribe", 'newsletter') . '<br />' . 'MAIL_STOP: ' . i18n("Link to pause the subscription", 'newsletter') . '<br />' . 'MAIL_GOON: ' . i18n("Link to resume the subscription", 'newsletter');

    $sTagInfoHTML = '<a href="javascript:void(0)" data-action="toggle_tag_info" data-toggle-id="idTagInfoHTML"><strong>' . i18n("Tag information", 'newsletter') . '</strong></a>' . '<div id="idTagInfoHTML" style="display: none"><br><b>' . i18n("Special message tags (will be replaced when sending, {..} = optional)", 'newsletter') . ":</b><br>" . '[mail name="name" type="text"]{text}MAIL_NAME{text}[/mail]: ' . i18n("Name of the recipient", 'newsletter') . "<br>" . '[mail name="date" type="text"]{text}MAIL_DATE{text}[/mail]: ' . i18n("Date, when the mail has been sent", 'newsletter') . "<br>" . '[mail name="time" type="text"]{text}MAIL_TIME{text}[/mail]: ' . i18n("Time, when the mail has been sent", 'newsletter') . "<br>" . '[mail name="number" type="text"]{text}MAIL_NUMBER{text}[/mail]: ' . i18n("Number of recipients", 'newsletter') . "<br>".
            '[mail name="unsubscribe" type="link" {text="' .
            i18n("Link text", 'newsletter') . '" }]{text}MAIL_UNSUBSCRIBE{text}[/mail]: ' . i18n("Link to unsubscribe", 'newsletter') . "<br>" . '[mail name="stop" type="link" {text="' . i18n("Link text", 'newsletter') . '" }]{text}MAIL_STOP{text}[/mail]: ' . i18n("Link to pause the subscription", 'newsletter') . "<br>" . '[mail name="goon" type="link" {text="' . i18n("Link text", 'newsletter') . '" }]{text}MAIL_GOON{text}[/mail]: ' . i18n("Link to resume the subscription", 'newsletter');

    // Mention plugin interface
    if (getSystemProperty("newsletter", "newsletter-recipients-plugin") == "true") {
        $sTagInfoText .= "<br><br><strong>" . i18n("Additional message tags from recipients plugins:", 'newsletter') . "</strong><br>";
        $sTagInfoHTML .= "<br><br><strong>" . i18n("Additional message tags from recipients plugins:", 'newsletter') . "</strong><br>";

        if (cHasPlugins('recipients')) {
            cIncludePlugins('recipients');
            foreach ($cfg['plugins']['recipients'] as $plugin) {
                if (function_exists('recipients_' . $plugin . '_wantedVariables')) {
                    $aPluginVars = call_user_func('recipients_' . $plugin . '_wantedVariables');

                    foreach ($aPluginVars as $sPluginVar) {
                        $sTagInfoText .= 'MAIL_' . cString::toUpperCase($sPluginVar) . '<br>';
                        $sTagInfoHTML .= '[mail name="' . cString::toLowerCase($sPluginVar) . '" type="text"][/mail]<br>';
                    }
                }
            }
        }
    } else {
        // -> Property available in system settings
        setSystemProperty("newsletter", "newsletter-recipients-plugin", "false");
    }
    $sTagInfoText .= "</div>";
    $sTagInfoHTML .= "</div>";

    $iTplIDArt = 0; // Used later for on change event
    if ($oNewsletter->get("type") == "html") {
        $iTplIDArt = $oNewsletter->get("template_idart");
        $oSelTemplate = new cHTMLSelectElement("selTemplate");
        $oSelTemplate->setClass('text_medium')
            ->setAttribute("data-action-change", "template_change");
        $aOptions = [
            "idcat" => $oClientLang->getProperty("newsletter", "html_template_idcat"),
            "start" => true,
            "offline" => true,
            "order" => "title"
        ];
        $oTemplateArticles = new cArticleCollector($aOptions);

        $aItems = [];
        $aItems[] = [
            0,
            i18n("-- none --", 'newsletter')
        ];
        while ($oArticle = $oTemplateArticles->nextArticle()) {
            $aItems[] = [
                $oArticle->get("idart"),
                $oArticle->get("title")
            ];
        }

        $oSelTemplate->autoFill($aItems);
        $oSelTemplate->setDefault($iTplIDArt);
        unset($aItems, $oArticles, $oTemplateArticles);

        $oForm->add(i18n("HTML Template", 'newsletter'), $oSelTemplate->render() . "&nbsp;" . i18n("Note, that changing the template discards the current html message content", 'newsletter'));

        if ($iTplIDArt != 0) {
            $sFrameSrc = "main.php?area=con_editcontent&action=con_editart&changeview=edit&idart=" . $oNewsletter->get("idart") . "&idcat=" . $oClientLang->getProperty("newsletter", "html_newsletter_idcat") . "&lang=" . $lang . "&contenido=" . $sess->id;
//            $sFrameSrc = "main.php?area=con_editcontent&action=con_editart&changeview=edit&idart=13&idcat=5&lang=1&contenido=" . $sess->id;

            $oForm->add(i18n("HTML Message", 'newsletter'), '<iframe width="100%" height="600" src="' . $sFrameSrc . '"></iframe><br>' . $sTagInfoHTML);
        } else {
            // Add a real note, that a template has to be specified
            $oPage->displayWarning(i18n("Newsletter type has been set to HTML/text, please remember to select an html template", 'newsletter')) . "<br>";

            $oForm->add(i18n("HTML Message", 'newsletter'), i18n("Please choose a template first", 'newsletter'));
        }
    }

    $oTxtMessage = new cHTMLTextarea("txtMessage", $oNewsletter->get("message"), 80, 20);
    $oForm->add(i18n("Text Message", 'newsletter'), $oTxtMessage->render() . "<br>" . $sTagInfoText);

    $sExecScript = '
    <script type="text/javascript">
    (function(Con, $) {
        // If html newsletter template selection has changed, ask user
        // if he/she may like to save this change (e.g. to get a html
        // newsletter immediately)
        function actionTemplateChange($select) {
            var iOriginalTplIDArt = ' . $iTplIDArt . ';
    
            if (iOriginalTplIDArt !== parseInt($select.val(), 10)) {
                if (iOriginalTplIDArt === 0) {
                    // Everything fine: Just selecting a template for the first time
                    submitForm();
                } else {
                    // You may lose information, warn!
                    var msg = "' . i18n("HTML template has been changed. Do you like to save now to apply changes?<br><br><b>Note, that existing HTML newsletter content will get lost!</b>", 'newsletter') . '";
                    Con.showConfirmation(msg, submitForm);
                }
            }
        }

        function submitForm() {
            $("form[name=\'frmNewsletterMsg\']").submit();
        }

        $("form [data-action-change]").live("change", function () {
            var $element = $(this),
                action = $element.data("action-change");
            if (action === "template_change") {
                actionTemplateChange($element);
            }
        });

        $("form [data-action]").live("click", function () {
            var $element = $(this),
                action = $element.data("action");
            if (action === "toggle_tag_info") {
                // Doggle tag info text
                var selector = "#" + $element.data("toggle-id");
                $(selector).slideToggle();
            }
        });
    })(Con, Con.$);
    </script>';
    $oPage->setContent([$oForm, $sExecScript]);
}

$oPage->render();
