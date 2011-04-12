<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Content controller
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2011-04-11
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_controller_abstract.php');


class ModRewrite_ContentController extends ModRewrite_ControllerAbstract
{

    public function init()
    {
        global $aSeparator, $aWordSeparator, $routingSeparator;
        $this->_aSeparator = $aSeparator;
        $this->_aWordSeparator = $aWordSeparator;
        $this->_routingSeparator = $routingSeparator;
    }

    public function indexAction()
    {
        // donut
    }

    public function saveAction()
    {
        $bError = false;

// TODO:  initialize $aMR

        $request = (count($_POST) > 0) ? $_POST : $_GET;
        mr_requestCleanup($request);

        // use mod_rewrite
        if (mr_arrayValue($request, 'use') == 1) {
            $this->_oView->use_chk = ' checked="checked"';
            $aMR['mod_rewrite']['use'] = 1;
        } else {
            $this->_oView->use_chk = '';
            $aMR['mod_rewrite']['use'] = 0;
        }

        // root dir
        if (mr_arrayValue($request, 'rootdir', '') !== '') {
            if (!preg_match('/^[a-zA-Z0-9\-_\/\.]*$/', $request['rootdir'])) {
                $this->_oView->rootdir_error = $this->_notifyBox('error', 'Das Rootverzeichnis hat ein ung&uuml;ltiges Format, erlaubt sind die Zeichen [a-zA-Z0-9\-_\/\.]');
                $bError = true;
            } elseif (!is_dir($_SERVER['DOCUMENT_ROOT'] . $request['rootdir'])) {

                if (mr_arrayValue($request, 'checkrootdir') == 1) {
                    // root dir check is enabled, this results in error
                    $this->_oView->rootdir_error = $this->_notifyBox('error', 'Das angegebene Verzeichnis "' . $_SERVER['DOCUMENT_ROOT'] . $request['rootdir'] . '" existiert nicht');
                    $bError = true;
                } else {
                    // root dir check ist disabled, take over the setting and output a warning.
                    $this->_oView->rootdir_error = $this->_notifyBox('warning', 'Das angegebene Verzeichnis "' . $request['rootdir'] . '" existiert nicht im aktuellen DOCUMENT_ROOT "' . $_SERVER['DOCUMENT_ROOT'] . '". Das kann vorkommen, wenn das DOCUMENT_ROOT des Clients vom Contenido Backend DOCUMENT_ROOT abweicht. Die Einstellung wird dennoch &uuml;bernommen, da die &Uuml;berpr&uuml;fung abgeschaltet wurde');
                }
            }
            $this->_oView->rootdir         = htmlentities($request['rootdir']);
            $aMR['mod_rewrite']['rootdir'] = $request['rootdir'];
        }

        // root dir check
        if (mr_arrayValue($request, 'checkrootdir') == 1) {
            $this->_oView->checkrootdir_chk = ' checked="checked"';
            $aMR['mod_rewrite']['checkrootdir'] = 1;
        } else {
            $this->_oView->checkrootdir_chk = '';
            $aMR['mod_rewrite']['checkrootdir'] = 0;
        }

        // start from root
        if (mr_arrayValue($request, 'startfromroot') == 1) {
            $this->_oView->startfromroot_chk     = ' checked="checked"';
            $aMR['mod_rewrite']['startfromroot'] = 1;
        } else {
            $this->_oView->startfromroot_chk     = '';
            $aMR['mod_rewrite']['startfromroot'] = 0;
        }

        // prevent duplicated content
        if (mr_arrayValue($request, 'prevent_duplicated_content') == 1) {
            $this->_oView->prevent_duplicated_content_chk     = ' checked="checked"';
            $aMR['mod_rewrite']['prevent_duplicated_content'] = 1;
        } else {
            $this->_oView->prevent_duplicated_content_chk     = '';
            $aMR['mod_rewrite']['prevent_duplicated_content'] = 0;
        }

        // language settings
        if (mr_arrayValue($request, 'use_language') == 1) {
            $this->_oView->use_language_chk           = ' checked="checked"';
            $this->_oView->use_language_name_disabled = '';
            $aMR['mod_rewrite']['use_language']  = 1;
            if (mr_arrayValue($request, 'use_language_name') == 1) {
                $this->_oView->use_language_name_chk     = ' checked="checked"';
                $aMR['mod_rewrite']['use_language_name'] = 1;
            } else {
                $this->_oView->use_language_name_chk     = '';
                $aMR['mod_rewrite']['use_language_name'] = 0;
            }
        } else {
            $this->_oView->use_language_chk           = '';
            $this->_oView->use_language_name_chk      = '';
            $this->_oView->use_language_name_disabled = ' disabled="disabled"';
            $aMR['mod_rewrite']['use_language']       = 0;
            $aMR['mod_rewrite']['use_language_name']  = 0;
        }

        // client settings
        if (mr_arrayValue($request, 'use_client') == 1) {
            $this->_oView->use_client_chk           = ' checked="checked"';
            $this->_oView->use_client_name_disabled = '';
            $aMR['mod_rewrite']['use_client']  = 1;
            if (mr_arrayValue($request, 'use_client_name') == 1) {
                $this->_oView->use_client_name_chk     = ' checked="checked"';
                $aMR['mod_rewrite']['use_client_name'] = 1;
            } else {
                $this->_oView->use_client_name_chk     = '';
                $aMR['mod_rewrite']['use_client_name'] = 0;
            }
        } else {
            $this->_oView->use_client_chk           = '';
            $this->_oView->use_client_name_chk      = '';
            $this->_oView->use_client_name_disabled = ' disabled="disabled"';
            $aMR['mod_rewrite']['use_client']       = 0;
            $aMR['mod_rewrite']['use_client_name']  = 0;
        }

        // use lowercase uri
        if (mr_arrayValue($request, 'use_lowercase_uri') == 1) {
            $this->_oView->use_lowercase_uri_chk     = ' checked="checked"';
            $aMR['mod_rewrite']['use_lowercase_uri'] = 1;
        } else {
            $this->_oView->use_lowercase_uri_chk     = '';
            $aMR['mod_rewrite']['use_lowercase_uri'] = 0;
        }

        $this->_oView->category_separator_attrib       = '';
        $this->_oView->category_word_separator_attrib  = '';
        $this->_oView->article_separator_attrib        = '';
        $this->_oView->article_word_separator_attrib   = '';

        $separatorPattern = $this->_aSeparator['pattern'];
        $separatorInfo    = $this->_aSeparator['info'];

        $wordSeparatorPattern = $this->_aSeparator['pattern'];
        $wordSeparatorInfo    = $this->_aSeparator['info'];

        $categorySeperator = mr_arrayValue($request, 'category_seperator', '');
        $categoryWordSeperator = mr_arrayValue($request, 'category_word_seperator', '');
        $articleSeperator = mr_arrayValue($request, 'article_seperator', '');
        $articleWordSeperator = mr_arrayValue($request, 'article_word_seperator', '');

        // category seperator
        if ($categorySeperator == '') {
            $this->_oView->category_separator_error = $this->_notifyBox('error', 'Bitte Trenner (' . $separatorInfo . ') f&uuml;r Kategorie angeben');
            $bError = true;
        } elseif (!preg_match($separatorPattern, $categorySeperator)) {
            $this->_oView->category_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Kategorie ist ung&uuml;ltig, erlaubt ist eines der Zeichen: ' . $separatorInfo);
            $bError = true;

        // category word seperator
        } elseif ($categoryWordSeperator == '') {
            $this->_oView->category_word_separator_error = $this->_notifyBox('error', 'Bitte Trenner (' . $wordSeparatorInfo . ') f&uuml;r Kategoriew&ouml;rter angeben');
            $bError = true;
        } elseif (!preg_match($wordSeparatorPattern, $categoryWordSeperator)) {
            $this->_oView->category_word_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Kategoriew&ouml;rter ist ung&uuml;ltig, erlaubt ist eines der Zeichen: ' . $wordSeparatorInfo);
            $bError = true;

        // article seperator
        } elseif ($articleSeperator == '') {
            $this->_oView->article_separator_error = $this->_notifyBox('error', 'Bitte Trenner (' . $separatorInfo . ') f&uuml;r Artikel angeben') . '<br>';
            $bError = true;
        } elseif (!preg_match($separatorPattern, $articleSeperator)) {
            $this->_oView->article_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Artikel ist ung&uuml;ltig, erlaubt ist eines der Zeichen: ' . $separatorInfo);
            $bError = true;

        // article word seperator
        } elseif ($articleWordSeperator == '') {
            $this->_oView->article_word_separator_error = $this->_notifyBox('error', 'Bitte Trenner (' . $wordSeparatorInfo . ') f&uuml;r Artikelw&ouml;rter angeben');
            $bError = true;
        } elseif (!preg_match($wordSeparatorPattern, $articleWordSeperator)) {
            $this->_oView->article_word_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Artikelw&ouml;rter ist ung&uuml;ltig, erlaubt ist eines der Zeichen: ' . $wordSeparatorInfo);
            $bError = true;

        // category_seperator - category_word_seperator
        } elseif ($categorySeperator == $categoryWordSeperator) {
            $this->_oView->category_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Kategorie und Kategoriew&ouml;rter d&uuml;rfen nicht identisch sein');
            $bError = true;
        // category_seperator - article_word_seperator
        } elseif ($categorySeperator == $articleWordSeperator) {
            $this->_oView->category_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Kategorie und Artikelw&ouml;rter d&uuml;rfen nicht identisch sein');
            $bError = true;
        // article_seperator - article_word_seperator
        } elseif ($articleSeperator == $articleWordSeperator) {
            $this->_oView->article_separator_error = $this->_notifyBox('error', 'Trenner f&uuml;r Kategorie-Artikel und Artikelw&ouml;rter d&uuml;rfen nicht identisch sein');
            $bError = true;
        }

        $this->_oView->category_separator              = htmlentities($categorySeperator);
        $aMR['mod_rewrite']['category_seperator']      = $categorySeperator;
        $this->_oView->category_word_separator         = htmlentities($categoryWordSeperator);
        $aMR['mod_rewrite']['category_word_seperator'] = $categoryWordSeperator;
        $this->_oView->article_separator               = htmlentities($articleSeperator);
        $aMR['mod_rewrite']['article_seperator']       = $articleSeperator;
        $this->_oView->article_word_separator          = htmlentities($articleWordSeperator);
        $aMR['mod_rewrite']['article_word_seperator']  = $articleWordSeperator;

        // file extension
        if (mr_arrayValue($request, 'file_extension', '') !== '') {
            if (!preg_match('/^\.([a-zA-Z0-9\-_\/])*$/', $request['file_extension'])) {
                $this->_oView->file_extension_error = $this->_notifyBox('error', 'Die Dateiendung hat ein ung&uuml;ltiges Format, erlaubt sind die Zeichen \.([a-zA-Z0-9\-_\/])');
                $bError = true;
            }
            $this->_oView->file_extension = htmlentities($request['file_extension']);
            $aMR['mod_rewrite']['file_extension'] = $request['file_extension'];
        } else {
            $this->_oView->file_extension = '.html';
            $aMR['mod_rewrite']['file_extension'] = '.html';
        }

        // category resolve min percentage
        if (isset($request['category_resolve_min_percentage'])) {
            if (!is_numeric($request['category_resolve_min_percentage'])) {
                $this->_oView->category_resolve_min_percentage_error = $this->_notifyBox('error', 'Wert muss numerisch sein.');
                $bError = true;
            } elseif ($request['category_resolve_min_percentage'] < 0 || $request['category_resolve_min_percentage'] > 100) {
                $this->_oView->category_resolve_min_percentage_error = $this->_notifyBox('error', 'Wert muss zwischen 0 und 100 sein.');
                $bError = true;
            }
            $this->_oView->category_resolve_min_percentage = $request['category_resolve_min_percentage'];
            $aMR['mod_rewrite']['category_resolve_min_percentage'] = $request['category_resolve_min_percentage'];
        } else {
            $this->_oView->category_resolve_min_percentage = '75';
            $aMR['mod_rewrite']['category_resolve_min_percentage'] = '75';
        }

        // add start article name to url
        if (mr_arrayValue($request, 'add_startart_name_to_url') == 1) {
            $this->_oView->add_startart_name_to_url_chk          = ' checked="checked"';
            $aMR['mod_rewrite']['add_startart_name_to_url'] = 1;
            if (mr_arrayValue($request, 'add_startart_name_to_url', '') !== '') {
                if (!preg_match('/^[a-zA-Z0-9\-_\/\.]*$/', $request['default_startart_name'])) {
                    $this->_oView->add_startart_name_to_url_error = $this->_notifyBox('error', 'Der Artikelname hat ein ung&uuml;ltiges Format, erlaubt sind die Zeichen /^[a-zA-Z0-9\-_\/\.]*$/');
                    $bError = true;
                }
                $this->_oView->default_startart_name         = htmlentities($request['default_startart_name']);
                $aMR['mod_rewrite']['default_startart_name'] = $request['default_startart_name'];
            } else {
                $this->_oView->default_startart_name         = '';
                $aMR['mod_rewrite']['default_startart_name'] = '';
            }
        } else {
            $this->_oView->add_startart_name_to_url_chk      = '';
            $aMR['mod_rewrite']['add_startart_name_to_url']  = 0;
            $this->_oView->default_startart_name             = '';
            $aMR['mod_rewrite']['default_startart_name']     = '';
        }

        // rewrite urls at
        if (mr_arrayValue($request, 'rewrite_urls_at') == 'congeneratecode') {
            $this->_oView->rewrite_urls_at_congeneratecode_chk          = ' checked="checked"';
            $this->_oView->rewrite_urls_at_front_content_output_chk     = '';
            $aMR['mod_rewrite']['rewrite_urls_at_congeneratecode']      = 1;
            $aMR['mod_rewrite']['rewrite_urls_at_front_content_output'] = 0;
        } else {
            $this->_oView->rewrite_urls_at_congeneratecode_chk          = '';
            $this->_oView->rewrite_urls_at_front_content_output_chk     = ' checked="checked"';
            $aMR['mod_rewrite']['rewrite_urls_at_congeneratecode']      = 0;
            $aMR['mod_rewrite']['rewrite_urls_at_front_content_output'] = 1;
        }

        // routing
        if (isset($request['rewrite_routing'])) {
            $aRouting = array();
            $items = explode("\n", $request['rewrite_routing']);
            foreach ($items as $p => $v) {
                $routingDef = explode($this->_routingSeparator, $v);
                if (count($routingDef) !== 2) {
                    continue;
                }
                $routingDef[0] = trim($routingDef[0]);
                $routingDef[1] = trim($routingDef[1]);
                if ($routingDef[0] == '') {
                    continue;
                }
                $aRouting[$routingDef[0]] = $routingDef[1];
            }
            $this->_oView->rewrite_routing = htmlentities($request['rewrite_routing']);
            $aMR['mod_rewrite']['routing'] = $aRouting;
        } else {
            $this->_oView->rewrite_routing = '';
            $aMR['mod_rewrite']['routing'] = array();
        }

        // redirect invalid article to errorsite
        if (isset($request['redirect_invalid_article_to_errorsite'])) {
            $this->_oView->redirect_invalid_article_to_errorsite_chk     = ' checked="checked"';
            $aMR['mod_rewrite']['redirect_invalid_article_to_errorsite'] = 1;
        } else {
            $this->_oView->redirect_invalid_article_to_errorsite_chk     = '';
            $aMR['mod_rewrite']['redirect_invalid_article_to_errorsite'] = 0;
        }

        if ($bError) {
            $this->_oView->content_before = $this->_notifyBox('error', 'Bitte &uuml;berpr&uuml;fen Sie ihre Eingaben');
            return;
        }

        if ($this->_bDebug == true) {
            echo $this->_notifyBox('info', 'Debug');
            echo '<pre class="example">';print_r($aMR['mod_rewrite']);echo '</pre>';
            echo $this->_notifyBox('info', 'Konfiguration wurde <b>nicht</b> gespeichert, weil debugging aktiv ist');
            return;
        }

        $bSeparatorModified = $this->_separatorModified($aMR['mod_rewrite']);

        if (mr_setConfiguration($this->_client, $aMR)) {
            $sMsg = 'Konfiguration wurde gespeichert';
            if ($bSeparatorModified) {
                mr_loadConfiguration($this->_client, true);
            }
            $this->_oView->content_before = $this->_notifyBox('info', $sMsg);
        } else {
            $this->_oView->content_before = $this->_notifyBox('error', 'Konfiguration konnte nicht gespeichert werden. &Uuml;berpr&uuml;fen Sie bitte die Schreibrechte f&uuml;r ' . $options['key']);
        }
    }


    protected function _separatorModified($aNewCfg)
    {
        $aCfg = ModRewrite::getConfig();

        if ($aCfg['category_seperator'] != $aNewCfg['category_seperator']) {
            return true;
        } elseif ($aCfg['category_word_seperator'] != $aNewCfg['category_word_seperator']) {
            return true;
        } elseif ($aCfg['article_seperator'] != $aNewCfg['article_seperator']) {
            return true;
        } elseif ($aCfg['article_word_seperator'] != $aNewCfg['article_word_seperator']) {
            return true;
        }
        return false;
    }

}
