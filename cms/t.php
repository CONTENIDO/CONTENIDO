
<!DOCTYPE html>

<!-- test layout -->

<html lang="de">

<head>
    <base href="http://contenido.localhost/cms/">

    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1" />

    <title><?php
        $cCurrentModule = 10;
        $cCurrentContainer = 10;
        ?><?php
        /**
         * This module handles the content of the title element.
         *
         * @package    Module
         * @subpackage HeadTitle
         * @author dominik.ziegler@4fb.de
         * @copyright  four for business AG <www.4fb.de>
         * @license    https://www.contenido.org/license/LIZENZ.txt
         * @link       https://www.4fb.de
         * @link       https://www.contenido.org
         */

        $breadcrumb = array();

        // get category path
        $helper = cCategoryHelper::getInstance();
        foreach ($helper->getCategoryPath(cRegistry::getCategoryId(), 1) as $categoryLang) {
            $breadcrumb[] = $categoryLang->get('name');
        }

        // load current article information
        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId(cRegistry::getArticleId(), cRegistry::getLanguageId());
        $headline = strip_tags($article->getContent('CMS_HTMLHEAD', 1));

        // append headline of article if existing
        if ($headline != '') {
            $breadcrumb[] = $headline;
        }

        if ($headline === '') {
            $breadcrumb[] = conHtmlSpecialChars(mi18n("STARTPAGE"));
        }

        array_shift($breadcrumb);

        if (count($breadcrumb) > 0) {
            echo implode(' - ', $breadcrumb);
        }

        ?></title>

    <link rel="stylesheet" type="text/css" href="css/reset.css" />

    <meta name="robots" content="index, follow" />
    <meta name="generator" content="CMS CONTENIDO" />
    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
    <meta name="author" content="Systemadministrator" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />

    <!-- template.con_editcontent.html -->
    <!-- @TODO Similar to template.con_content_list.html, merge them -->

    <!-- JS -->
    <script type="text/javascript" src="http://contenido.localhost/contenido/scripts/jquery/jquery.js"></script>
    <script type="text/javascript" src="http://contenido.localhost/contenido/scripts/jquery/jquery-ui.js"></script>
    <script type="text/javascript" src="http://contenido.localhost/contenido/scripts/contenido.js"></script>
    <script type="text/javascript" src="http://contenido.localhost/contenido/scripts/general.js?v=c027a03b03f184f2d7d7f0d866bd9a55"></script>
    <script>
        (function(Con, $) {
            Con.sid = "iasl6qjnml4svau8u4efhq8j2l";
            $.extend(Con.cfg, {
                urlBackend: "http://contenido.localhost/contenido/",
                urlHelp: "#",
                belang: "de_DE",
                frame: 0
            });
        })(Con, Con.$);
    </script>
    <script type="text/javascript" src="http://contenido.localhost/contenido/scripts/startup.js?v=606f61b17b52c6f86295e3608e6f0ee4"></script>
    <script type="text/javascript" src="http://contenido.localhost/contenido/scripts/jquery/plugins/atooltip.jquery.js?v=606f61b17b52c6f86295e3608e6f0ee4"></script>
    <!-- /JS -->

    <script>
        var id = 'c_5';
        if ('undefined' !== typeof(Con)) {
            Con.markSubmenuItem(id);
        } else {
            // Contenido backend but with frozen article
            // Check if submenuItem is existing and mark it
            if (parent.parent.frames.right.frames.right_top.document.getElementById(id)) {
                menuItem = parent.parent.frames.right.frames.right_top.document.getElementById(id).getElementsByTagName('a')[0];
                // load the new tab now
                parent.parent.frames.right.frames.right_top.Con.Subnav.clicked(menuItem, true);
            }
        }
    </script>

    <link rel="stylesheet" type="text/css" href="http://contenido.localhost/contenido/external/wysiwyg/tinymce4/contenido/css/con_tiny.css">
    <link rel="stylesheet" type="text/css" href="http://contenido.localhost/contenido/styles/includes/con_editcontent.css">

    <!-- tinyMCE -->

    <!-- tinyMCE -->
    <script type="text/javascript" src="http://contenido.localhost/contenido/external/wysiwyg/tinymce4/tinymce/js/tinymce/tinymce.min.js"></script><script src="http://contenido.localhost/contenido/external/wysiwyg/tinymce4/contenido/js/con_tiny.js" type="text/javascript"></script><script src="http://contenido.localhost/contenido/external/wysiwyg/tinymce4/tinymce/js/tinymce/tinymce.min.js" type="text/javascript"></script>

    <script>
        (function(Con, $) {

            // Configuration of tiny, when tiny is opened set event which stores original
            // content to Con.Tiny.editDataOrg
            var wysiwygSettings = {"CMS_HTML":{"tinymce4_full":{"toolbar1":"cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen","toolbar2":"link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code","toolbar3":"table | formatselect fontselect fontsizeselect | consave conclose","plugins":"charmap code table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor"},"tinymce4_inline":{"toolbar1":"bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | confullscreen | consave conclose","toolbar2":"","toolbar3":"","plugins":"conclose confullscreen media table textcolor"},"tinymce4_fullscreen":{"toolbar1":"cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen","toolbar2":"link unlink anchor image media | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code","toolbar3":"table | formatselect fontselect fontsizeselect | consave conclose","plugins":"charmap code conclose table hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor"},"article_url_suffix":"front_content.php?idart=91","selector":".CMS_HTML","content_css":"http:\/\/contenido.localhost\/cms\/css\/style_tiny.css","theme":"modern","remove_script_host":false,"urlconverter_callback":"Con.Tiny.customURLConverterCallback","pagebreak_separator":"<!-- my page break -->","remove_linebreaks":false,"convert_urls":false,"relative_urls":false,"language":"de","document_base_url":"http:\/\/contenido.localhost\/cms\/","cleanup_callback":"","width":"100%","height":"210px","directionality":"ltr","plugin_insertdate_dateFormat":"%Y-%m-%d","plugin_insertdate_timeFormat":"%H:%M:%S","inline":true,"toolbar1":"bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | confullscreen | consave conclose","toolbar2":"","toolbar3":"","plugins":"conclose confullscreen media table textcolor","valid_elements":"a[name|href|target|title],strong\/b[class],em\/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hriframe[src|width|height],object[data|width|height|type],audio[controls|src],source[src|type],script[src],video[width|height|poster|controls]","extended_valid_elements":"form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]","menubar":false,"fullscreen_settings":{"tinymce4_full":{"toolbar1":"cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen","toolbar2":"link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code","toolbar3":"table | formatselect fontselect fontsizeselect | consave conclose","plugins":"charmap code table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor"},"tinymce4_inline":{"toolbar1":"bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | confullscreen | consave conclose","toolbar2":"","toolbar3":"","plugins":"conclose confullscreen media table textcolor"},"tinymce4_fullscreen":{"toolbar1":"cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen","toolbar2":"link unlink anchor image media | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code","toolbar3":"table | formatselect fontselect fontsizeselect | consave conclose","plugins":"charmap code conclose table hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor"},"article_url_suffix":"front_content.php?idart=91","selector":".CMS_HTML","content_css":"http:\/\/contenido.localhost\/cms\/css\/style_tiny.css","theme":"modern","remove_script_host":false,"urlconverter_callback":"Con.Tiny.customURLConverterCallback","pagebreak_separator":"<!-- my page break -->","remove_linebreaks":false,"convert_urls":false,"relative_urls":false,"language":"de","document_base_url":"http:\/\/contenido.localhost\/cms\/","cleanup_callback":"","width":"100%","height":"210px","directionality":"ltr","plugin_insertdate_dateFormat":"%Y-%m-%d","plugin_insertdate_timeFormat":"%H:%M:%S","inline":false,"toolbar1":"cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen","toolbar2":"link unlink anchor image media | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code","toolbar3":"table | formatselect fontselect fontsizeselect | consave conclose","plugins":"charmap code conclose table hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor","valid_elements":"a[name|href|target|title],strong\/b[class],em\/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hriframe[src|width|height],object[data|width|height|type],audio[controls|src],source[src|type],script[src],video[width|height|poster|controls]","extended_valid_elements":"form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]","menubar":true}},"CMS_HTMLHEAD":{"article_url_suffix":"front_content.php?idart=91","selector":".CMS_HTMLHEAD","content_css":"http:\/\/contenido.localhost\/cms\/css\/style_tiny.css","theme":"modern","remove_script_host":false,"urlconverter_callback":"Con.Tiny.customURLConverterCallback","pagebreak_separator":"<!-- my page break -->","remove_linebreaks":false,"convert_urls":false,"relative_urls":false,"language":"de","document_base_url":"http:\/\/contenido.localhost\/cms\/","cleanup_callback":"","width":"100%","height":"210px","directionality":"ltr","plugin_insertdate_dateFormat":"%Y-%m-%d","plugin_insertdate_timeFormat":"%H:%M:%S","menubar":false,"inline":true,"toolbar1":"undo redo | consave conclose","toolbar2":"","toolbar3":"","plugins":"conclose","valid_elements":"a[name|href|target|title],strong\/b[class],em\/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hriframe[src|width|height],object[data|width|height|type],audio[controls|src],source[src|type],script[src],video[width|height|poster|controls]","extended_valid_elements":"form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]","fullscreen_settings":{"article_url_suffix":"front_content.php?idart=91","selector":".CMS_HTMLHEAD","content_css":"http:\/\/contenido.localhost\/cms\/css\/style_tiny.css","theme":"modern","remove_script_host":false,"urlconverter_callback":"Con.Tiny.customURLConverterCallback","pagebreak_separator":"<!-- my page break -->","remove_linebreaks":false,"convert_urls":false,"relative_urls":false,"language":"de","document_base_url":"http:\/\/contenido.localhost\/cms\/","cleanup_callback":"","width":"100%","height":"210px","directionality":"ltr","plugin_insertdate_dateFormat":"%Y-%m-%d","plugin_insertdate_timeFormat":"%H:%M:%S","menubar":true,"inline":false,"toolbar1":"undo redo | consave conclose","toolbar2":"","toolbar3":"","plugins":"conclose","valid_elements":"a[name|href|target|title],strong\/b[class],em\/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hriframe[src|width|height],object[data|width|height|type],audio[controls|src],source[src|type],script[src],video[width|height|poster|controls]","extended_valid_elements":"form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]"}}};

            // Initialize/setup TinyMCE editor
            Con.Tiny.tinymceInit(tinymce, wysiwygSettings, {
                saveTitle: 'Editor schließen und Änderungen speichern',
                saveImage: 'http://contenido.localhost/contenido/images/but_save_tiny.gif',
                closeTitle: 'Editor schließen',
                closeImage: 'http://contenido.localhost/contenido/images/but_close_tiny.gif',
                useTiny: '1',
                backendUrl: 'http://contenido.localhost/contenido/'
            });

            $(function() {
                // Initialize CONTENIDO tiny module
                Con.Tiny.init({
                    fileUrl: 'http://contenido.localhost/contenido/frameset.php?area=upl&contenido=iasl6qjnml4svau8u4efhq8j2l&appendparameters=filebrowser',
                    imageUrl: 'http://contenido.localhost/contenido/frameset.php?area=upl&contenido=iasl6qjnml4svau8u4efhq8j2l&appendparameters=imagebrowser',
                    mediaUrl: 'http://contenido.localhost/contenido/frameset.php?area=upl&contenido=iasl6qjnml4svau8u4efhq8j2l&appendparameters=imagebrowser',
                    frontendPath: 'http://contenido.localhost/cms/',
                    txtQuestion: 'Sie haben ungesicherte Änderungen.',
                    idartlang: '138',
                    settings: wysiwygSettings
                });

                // Bind to some events like contenteditables click or window unload
                Con.Tiny.bindEvents({useTiny: '1'});
            });

        })(Con, Con.$);
    </script>
    <!-- /template.con_editcontent.html -->

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>

<body>


<div id="page">

    <div id="content">
        <!--start:content-->
        <?php
        $cCurrentModule = 37;
        $cCurrentContainer = 110;
        ?><?php

        /**
         * description: contact (PIFA) form
         *
         * @package    Module
         * @subpackage FormContact
         * @author     marcus.gnass@4fb.de
         * @copyright  four for business AG <www.4fb.de>
         * @license    https://www.contenido.org/license/LIZENZ.txt
         * @link       https://www.4fb.de
         * @link       https://www.contenido.org
         */

        mi18n("REPLY_HEADLINE");
        mi18n("REPLY_TEXT");

        if (cRegistry::isBackendEditMode()) {
            echo '<label class="con_content_type_label">' . conHtmlSpecialChars(mi18n("LABEL_FORM_CONTACT")) . '</label>';
        }
        #echo "CMS_ PIFAFORM[1]";

        $foo = "<img id=\"cms_pifaform_1\" class=\"cms_abstract_img cms_pifaform_img\" alt=\"\" src=\"http://contenido.localhost/contenido/plugins/form_assistant/images/icon_form.png\">
<div style=\"display:none;\" id=\"cms_pifaform_1_settings\" class=\"cms_abstract cms_pifaform\">
    <div class=\"close\" id=\"pifaform_close\">
        <img src=\"http://contenido.localhost/contenido/images/but_cancel.gif\" alt=\"\">
    </div>
    <p class=\"head\">Formular</p><div id=\"pifaform_tabs\" class=\"tabs\">
    
    <div id=\"base\" class=\"base\">
        <div id=\"pifaform_panel_base_1\" class=\"pifaform_panel_base\" style=\"clear:both;\"><div><label for=\"pifaform_idform_1\">Formular &Uuml;berschrift</label><input name=\"pifaform_headline_1\" id=\"pifaform_headline_1\" value=\"Kontakt\" size=\"50\" type=\"text\" /></div><div><label for=\"pifaform_idform_1\">Formular</label><select name=\"pifaform_idform_1\" id=\"pifaform_idform_1\"><option value=\"\">keine</option><option value=\"1\" selected=\"selected\">contact</option><option value=\"3\">test</option><option value=\"5\">contact2</option></select></div><fieldset><legend>Klassen & Vorlagen</legend><div><label for=\"pifaform_module_1\">Module</label><select name=\"pifaform_module_1\" id=\"pifaform_module_1\"><option value=\"\">keine</option><option value=\"DefaultFormModule\" selected=\"selected\">DefaultFormModule</option></select></div><div><label for=\"pifaform_processor_1\">Prozessoren</label><select name=\"pifaform_processor_1\" id=\"pifaform_processor_1\"><option value=\"\">keine</option><option value=\"DefaultFormProcessor\" selected=\"selected\">DefaultFormProcessor</option><option value=\"MailedFormProcessor\">MailedFormProcessor</option></select></div><div><label for=\"pifaform_template_get_1\">Vorlagen &ndash; GET</label><select name=\"pifaform_template_get_1\" id=\"pifaform_template_get_1\"><option value=\"\">keine</option><option value=\"cms_pifaform_default_get.tpl\" selected=\"selected\">cms_pifaform_default_get.tpl</option></select></div><div><label for=\"pifaform_template_post_1\">Vorlagen &ndash; POST</label><select name=\"pifaform_template_post_1\" id=\"pifaform_template_post_1\"><option value=\"\">keine</option><option value=\"cms_pifaform_default_post.tpl\" selected=\"selected\">cms_pifaform_default_post.tpl</option></select></div></fieldset><fieldset><legend>Benutzer-Mail</legend><div><label for=\"pifaform_mail_client_template_1\">Vorlagen</label><select name=\"pifaform_mail_client_template_1\" id=\"pifaform_mail_client_template_1\"><option value=\"\">keine</option><option value=\"cms_pifaform_default_mail_client.tpl\" selected=\"selected\">cms_pifaform_default_mail_client.tpl</option></select></div><div><label for=\"pifaform_mail_client_from_email_1\">Absender-Adresse</label><input name=\"pifaform_mail_client_from_email_1\" id=\"pifaform_mail_client_from_email_1\" value=\"\" size=\"50\" type=\"text\" /></div><div><label for=\"pifaform_mail_client_from_name_1\">Absender-Name</label><input name=\"pifaform_mail_client_from_name_1\" id=\"pifaform_mail_client_from_name_1\" value=\"\" size=\"50\" type=\"text\" /></div><div><label for=\"pifaform_mail_client_reply_to_email_1\">Reply-to E-mail</label><select name=\"pifaform_mail_client_reply_to_email_1\" id=\"pifaform_mail_client_reply_to_email_1\"><option value=\"sender\">Absender-Adresse</option><option value=\"system\">Absender Email unter Systemeinstellungen</option></select></div><div><label for=\"pifaform_mail_client_subject_1\">Betreff</label><input name=\"pifaform_mail_client_subject_1\" id=\"pifaform_mail_client_subject_1\" value=\"\" size=\"50\" type=\"text\" /></div></fieldset><fieldset><legend>System-Mail</legend><div><label for=\"pifaform_mail_system_template_1\">Vorlagen</label><select name=\"pifaform_mail_system_template_1\" id=\"pifaform_mail_system_template_1\"><option value=\"\">keine</option><option value=\"cms_pifaform_default_mail_system.tpl\" selected=\"selected\">cms_pifaform_default_mail_system.tpl</option></select></div><div><label for=\"pifaform_mail_system_from_email_1\">Absender-Adresse</label><input name=\"pifaform_mail_system_from_email_1\" id=\"pifaform_mail_system_from_email_1\" value=\"\" size=\"50\" type=\"text\" /></div><div><label for=\"pifaform_mail_system_from_name_1\">Absender-Name</label><input name=\"pifaform_mail_system_from_name_1\" id=\"pifaform_mail_system_from_name_1\" value=\"\" size=\"50\" type=\"text\" /></div><div><label for=\"pifaform_mail_system_reply_to_email_1\">Reply-to E-mail</label><select name=\"pifaform_mail_system_reply_to_email_1\" id=\"pifaform_mail_system_reply_to_email_1\"><option value=\"sender\">Absender-Adresse</option><option value=\"system\">Absender Email unter Systemeinstellungen</option><option value=\"form\">Email in Formular</option></select></div><div><label for=\"pifaform_mail_system_recipient_email_1\">Empf&auml;nger Email</label><input name=\"pifaform_mail_system_recipient_email_1\" id=\"pifaform_mail_system_recipient_email_1\" value=\"\" size=\"50\" type=\"text\" /></div><div><label for=\"pifaform_mail_system_subject_1\">Betreff</label><input name=\"pifaform_mail_system_subject_1\" id=\"pifaform_mail_system_subject_1\" value=\"\" size=\"50\" type=\"text\" /></div></fieldset></div>
    </div>
    
</div><div class=\"toolbar\">
    <img class=\"save_settings\" alt=\"\" src=\"http://contenido.localhost/contenido/images/but_ok.gif\">
    <img class=\"cancel_settings\" alt=\"\" src=\"http://contenido.localhost/contenido/images/but_cancel.gif\">
</div>
<!-- template.cms_abstract_tabbed_edit_bottom.html -->
</div>

<script>
//<![CDATA[
    var initCallBack = function() {
        var contentTypeInstance = new Con.cContentTypePifaForm(
            '#cms_pifaform_1_settings',
            '#cms_pifaform_1',
            Con.cfg.urlBackend,
            'http://contenido.localhost/cms/',
            '138',
            '1',
            Array('pifaform_headline','pifaform_idform','pifaform_module','pifaform_processor','pifaform_template_get','pifaform_template_post','pifaform_mail_client_template','pifaform_mail_client_from_email','pifaform_mail_client_from_name','pifaform_mail_client_reply_to_email','pifaform_mail_client_subject','pifaform_mail_system_template','pifaform_mail_system_from_email','pifaform_mail_system_from_name','pifaform_mail_system_reply_to_email','pifaform_mail_system_recipient_email','pifaform_mail_system_subject'),
            'pifaform',
            Con.sid,
            {\"pifaform_headline\":\"Kontakt\",\"pifaform_idform\":\"1\",\"pifaform_module\":\"DefaultFormModule\",\"pifaform_processor\":\"DefaultFormProcessor\",\"pifaform_template_get\":\"cms_pifaform_default_get.tpl\",\"pifaform_template_post\":\"cms_pifaform_default_post.tpl\",\"pifaform_mail_client_template\":\"cms_pifaform_default_mail_client.tpl\",\"pifaform_mail_client_from_email\":\"\",\"pifaform_mail_client_from_name\":\"\",\"pifaform_mail_client_subject\":\"\",\"pifaform_mail_system_template\":\"cms_pifaform_default_mail_system.tpl\",\"pifaform_mail_system_from_email\":\"\",\"pifaform_mail_system_from_name\":\"\",\"pifaform_mail_system_recipient_email\":\"\",\"pifaform_mail_system_subject\":\"\"}
        );
        contentTypeInstance.initialise();
    };

    if (typeof Con.cContentTypePifaForm === 'undefined' || typeof Con.cContentTypeAbstractTabbed === 'undefined') {
        Con.Loader.get(
            [
                Con.cfg.urlBackend + 'scripts/content_types/cmsAbstractTabbed.js',
                'http://contenido.localhost/contenido/plugins/form_assistant/scripts/cmsPifaform.js'
            ],
            initCallBack
        );
    } else {
        initCallBack();
    }
    //]]>
</script>
<!-- /template.cms_abstract_tabbed_edit_bottom.html -->
" . (function (){ ob_start(); $form = new cContentTypePifaForm('<?xml version="1.0" encoding="utf-8"?>
<pifaform><headline><![CDATA[Kontakt]]></headline><idform><![CDATA[1]]></idform><module><![CDATA[DefaultFormModule]]></module><processor><![CDATA[DefaultFormProcessor]]></processor><template_get><![CDATA[cms_pifaform_default_get.tpl]]></template_get><template_post><![CDATA[cms_pifaform_default_post.tpl]]></template_post><mail_client_template><![CDATA[cms_pifaform_default_mail_client.tpl]]></mail_client_template><mail_client_from_email><![CDATA[]]></mail_client_from_email><mail_client_from_name><![CDATA[]]></mail_client_from_name><mail_client_subject><![CDATA[]]></mail_client_subject><mail_system_template><![CDATA[cms_pifaform_default_mail_system.tpl]]></mail_system_template><mail_system_from_email><![CDATA[]]></mail_system_from_email><mail_system_from_name><![CDATA[]]></mail_system_from_name><mail_system_recipient_email><![CDATA[]]></mail_system_recipient_email><mail_system_subject><![CDATA[]]></mail_system_subject></pifaform>
', 1, []);
                echo $form->buildCode(); $outputVar = ob_get_contents(); ob_end_clean(); return $outputVar; })() . "";

        echo $foo;

        ?>
        <?php
        $cCurrentModule = 0;
        $cCurrentContainer = 160;
        ?>
        <?php
        $cCurrentModule = 0;
        $cCurrentContainer = 170;
        ?>
        <!--end:content-->
    </div>

</div>


<form name="editcontent" method="post" action="http://contenido.localhost/contenido/external/backendedit/front_content.php?contenido=iasl6qjnml4svau8u4efhq8j2l&area=con_editcontent&idart=91&idcat=2&lang=1&action=20&client=1">
    <input type="hidden" name="changeview" value="edit">
    <input type="hidden" name="idArtLangVersion" value="">
    <input type="hidden" name="copyTo" value="">
    <input type="hidden" name="data" value="">
</form>

<form name="copyto" method="post" action="http://contenido.localhost/contenido/external/backendedit/front_content.php?contenido=iasl6qjnml4svau8u4efhq8j2l&area=con_editcontent&idart=91&idcat=2&lang=1&action=copyto&client=1">
    <input type="hidden" name="changeview" value="edit">
    <input type="hidden" name="idArtLangVersion" value="">
    <input type="hidden" name="data" value="">
</form>
</body>

</html>