<?php

/**
 * AMR Content controller class
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content controller for general settings.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewrite_ContentController extends ModRewrite_ControllerAbstract
{

    /**
     * Index action
     */
    public function indexAction()
    {
        // donut
    }

    /**
     * Save settings action
     *
     * @throws cInvalidArgumentException|cException
     */
    public function saveAction()
    {
        $bDebug = $this->getProperty('bDebug');
        $aSeparator = $this->getProperty('aSeparator');
        $aWordSeparator = $this->getProperty('aWordSeparator');
        $routingSeparator = $this->getProperty('routingSeparator');

        $isError = false;
        $mrCfg = [];

        $request = count($_POST) > 0 ? $_POST : $_GET;
        mr_requestCleanup($request);

        // use mod_rewrite
        if (mr_arrayValue($request, 'use') == 1) {
            $this->view->use_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['use'] = 1;
        } else {
            $this->view->use_chk = '';
            $mrCfg['mod_rewrite']['use'] = 0;
        }

        // root dir
        if (mr_arrayValue($request, 'rootdir', '') !== '') {
            if (!preg_match('/^[a-zA-Z0-9\-_\/\.]*$/', $request['rootdir'])) {
                $this->view->rootdir_error = $this->renderNotification(
                    'error',
                    i18n('The root directory has a invalid format, allowed are the chars [a-zA-Z0-9\-_\/\.]', $this->pluginName)
                );
                $isError = true;
            } elseif (!is_dir($_SERVER['DOCUMENT_ROOT'] . $request['rootdir'])) {
                if (mr_arrayValue($request, 'checkrootdir') == 1) {
                    // root dir check is enabled, this results in an error
                    $this->view->rootdir_error = $this->renderNotification(
                        'error',
                        sprintf(
                            i18n('The specified directory "%s" does not exists', $this->pluginName),
                            $_SERVER['DOCUMENT_ROOT'] . $request['rootdir']
                        )
                    );
                    $isError = true;
                } else {
                    // root dir check ist disabled, take over the setting and output a warning.
                    $this->view->rootdir_error = $this->renderNotification(
                        'warning',
                        sprintf(
                            i18n('The specified directory "%s" does not exists in DOCUMENT_ROOT "%s". this could happen, if clients DOCUMENT_ROOT differs from CONTENIDO backends DOCUMENT_ROOT. However, the setting will be taken over because of disabled check.', $this->pluginName),
                            $request['rootdir'],
                            $_SERVER['DOCUMENT_ROOT']
                        )                    );
                }
            }
            $this->view->rootdir = conHtmlentities($request['rootdir']);
            $mrCfg['mod_rewrite']['rootdir'] = $request['rootdir'];
        }

        // root dir check
        if (mr_arrayValue($request, 'checkrootdir') == 1) {
            $this->view->checkrootdir_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['checkrootdir'] = 1;
        } else {
            $this->view->checkrootdir_chk = '';
            $mrCfg['mod_rewrite']['checkrootdir'] = 0;
        }

        // start from root
        if (mr_arrayValue($request, 'startfromroot') == 1) {
            $this->view->startfromroot_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['startfromroot'] = 1;
        } else {
            $this->view->startfromroot_chk = '';
            $mrCfg['mod_rewrite']['startfromroot'] = 0;
        }

        // prevent duplicated content
        if (mr_arrayValue($request, 'prevent_duplicated_content') == 1) {
            $this->view->prevent_duplicated_content_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['prevent_duplicated_content'] = 1;
        } else {
            $this->view->prevent_duplicated_content_chk = '';
            $mrCfg['mod_rewrite']['prevent_duplicated_content'] = 0;
        }

        // language settings
        if (mr_arrayValue($request, 'use_language') == 1) {
            $this->view->use_language_chk = ' checked="checked"';
            $this->view->use_language_name_disabled = '';
            $mrCfg['mod_rewrite']['use_language'] = 1;
            if (mr_arrayValue($request, 'use_language_name') == 1) {
                $this->view->use_language_name_chk = ' checked="checked"';
                $mrCfg['mod_rewrite']['use_language_name'] = 1;
            } else {
                $this->view->use_language_name_chk = '';
                $mrCfg['mod_rewrite']['use_language_name'] = 0;
            }
        } else {
            $this->view->use_language_chk = '';
            $this->view->use_language_name_chk = '';
            $this->view->use_language_name_disabled = ' disabled="disabled"';
            $mrCfg['mod_rewrite']['use_language'] = 0;
            $mrCfg['mod_rewrite']['use_language_name'] = 0;
        }

        // client settings
        if (mr_arrayValue($request, 'use_client') == 1) {
            $this->view->use_client_chk = ' checked="checked"';
            $this->view->use_client_name_disabled = '';
            $mrCfg['mod_rewrite']['use_client'] = 1;
            if (mr_arrayValue($request, 'use_client_name') == 1) {
                $this->view->use_client_name_chk = ' checked="checked"';
                $mrCfg['mod_rewrite']['use_client_name'] = 1;
            } else {
                $this->view->use_client_name_chk = '';
                $mrCfg['mod_rewrite']['use_client_name'] = 0;
            }
        } else {
            $this->view->use_client_chk = '';
            $this->view->use_client_name_chk = '';
            $this->view->use_client_name_disabled = ' disabled="disabled"';
            $mrCfg['mod_rewrite']['use_client'] = 0;
            $mrCfg['mod_rewrite']['use_client_name'] = 0;
        }

        // use lowercase uri
        if (mr_arrayValue($request, 'use_lowercase_uri') == 1) {
            $this->view->use_lowercase_uri_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['use_lowercase_uri'] = 1;
        } else {
            $this->view->use_lowercase_uri_chk = '';
            $mrCfg['mod_rewrite']['use_lowercase_uri'] = 0;
        }

        $this->view->category_separator_attrib = '';
        $this->view->category_word_separator_attrib = '';
        $this->view->article_separator_attrib = '';
        $this->view->article_word_separator_attrib = '';

        $separatorPattern = $aSeparator['pattern'];
        $separatorInfo = $aSeparator['info'];

        $wordSeparatorPattern = $aSeparator['pattern'];
        $wordSeparatorInfo = $aSeparator['info'];

        $categorySeperator = mr_arrayValue($request, 'category_seperator', '');
        $categoryWordSeperator = mr_arrayValue($request, 'category_word_seperator', '');
        $articleSeperator = mr_arrayValue($request, 'article_seperator', '');
        $articleWordSeperator = mr_arrayValue($request, 'article_word_seperator', '');

        // category separator
        if ($categorySeperator == '') {
            $this->view->category_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Please specify separator (%s) for category', $this->pluginName),
                    $separatorInfo
                )
            );
            $isError = true;
        } elseif (!preg_match($separatorPattern, $categorySeperator)) {
            $this->view->category_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Invalid separator for category, allowed one of following characters: %s', $this->pluginName),
                    $separatorInfo
                )
            );
            $isError = true;

            // category word separator
        } elseif ($categoryWordSeperator == '') {
            $sMsg = sprintf(
                i18n('Please specify separator (%s) for category words', $this->pluginName),
                $wordSeparatorInfo
            );
            $this->view->category_word_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Please specify separator (%s) for category words', $this->pluginName),
                    $wordSeparatorInfo
                )
            );
            $isError = true;
        } elseif (!preg_match($wordSeparatorPattern, $categoryWordSeperator)) {
            $this->view->category_word_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Invalid separator for category words, allowed one of following characters: %s', $this->pluginName),
                    $wordSeparatorInfo
                )
            );
            $isError = true;

            // article separator
        } elseif ($articleSeperator == '') {
            $this->view->article_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Please specify separator (%s) for article', $this->pluginName),
                    $separatorInfo
                )
            );
            $isError = true;
        } elseif (!preg_match($separatorPattern, $articleSeperator)) {
            $this->view->article_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Invalid separator for article, allowed is one of following characters: %s', $this->pluginName),
                    $separatorInfo
                )
            );
            $isError = true;

            // article word separator
        } elseif ($articleWordSeperator == '') {
            $this->view->article_word_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Please specify separator (%s) for article words', $this->pluginName),
                    $wordSeparatorInfo
                )
            );
            $isError = true;
        } elseif (!preg_match($wordSeparatorPattern, $articleWordSeperator)) {
            $this->view->article_word_separator_error = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Invalid separator for article words, allowed is one of following characters: %s', $this->pluginName),
                    $wordSeparatorInfo
                )
            );
            $isError = true;

            // category_seperator - category_word_seperator
        } elseif ($categorySeperator == $categoryWordSeperator) {
            $this->view->category_separator_error = $this->renderNotification(
                'error',
                i18n('Separator for category and category words must not be identical', $this->pluginName)
            );
            $isError = true;
            // category_seperator - article_word_seperator
        } elseif ($categorySeperator == $articleWordSeperator) {
            $this->view->category_separator_error = $this->renderNotification(
                'error',
                i18n('Separator for category and article words must not be identical', $this->pluginName)
            );
            $isError = true;
            // article_seperator - article_word_seperator
        } elseif ($articleSeperator == $articleWordSeperator) {
            $this->view->article_separator_error = $this->renderNotification(
                'error',
                i18n('Separator for category-article and article words must not be identical', $this->pluginName)
            );
            $isError = true;
        }

        $this->view->category_separator = conHtmlentities($categorySeperator);
        $mrCfg['mod_rewrite']['category_seperator'] = $categorySeperator;
        $this->view->category_word_separator = conHtmlentities($categoryWordSeperator);
        $mrCfg['mod_rewrite']['category_word_seperator'] = $categoryWordSeperator;
        $this->view->article_separator = conHtmlentities($articleSeperator);
        $mrCfg['mod_rewrite']['article_seperator'] = $articleSeperator;
        $this->view->article_word_separator = conHtmlentities($articleWordSeperator);
        $mrCfg['mod_rewrite']['article_word_seperator'] = $articleWordSeperator;

        // file extension
        if (mr_arrayValue($request, 'file_extension', '') !== '') {
            if (!preg_match('/^\.([a-zA-Z0-9\-_\/])*$/', $request['file_extension'])) {
                $this->view->file_extension_error = $this->renderNotification(
                    'error',
                    i18n('The file extension has a invalid format, allowed are the chars \.([a-zA-Z0-9\-_\/])', $this->pluginName)
            );
                $isError = true;
            }
            $this->view->file_extension = conHtmlentities($request['file_extension']);
            $mrCfg['mod_rewrite']['file_extension'] = $request['file_extension'];
        } else {
            $this->view->file_extension = '.html';
            $mrCfg['mod_rewrite']['file_extension'] = '.html';
        }

        // category resolve min percentage
        if (isset($request['category_resolve_min_percentage'])) {
            if (!is_numeric($request['category_resolve_min_percentage'])) {
                $this->view->category_resolve_min_percentage_error = $this->renderNotification(
                    'error',
                    i18n('Value has to be numeric.', $this->pluginName)
                );
                $isError = true;
            } elseif ($request['category_resolve_min_percentage'] < 0 || $request['category_resolve_min_percentage'] > 100) {
                $this->view->category_resolve_min_percentage_error = $this->renderNotification(
                    'error',
                    i18n('Value has to be between 0 an 100.', $this->pluginName)
                );
                $isError = true;
            }
            $this->view->category_resolve_min_percentage = $request['category_resolve_min_percentage'];
            $mrCfg['mod_rewrite']['category_resolve_min_percentage'] = $request['category_resolve_min_percentage'];
        } else {
            $this->view->category_resolve_min_percentage = '75';
            $mrCfg['mod_rewrite']['category_resolve_min_percentage'] = '75';
        }

        // add start article name to the url
        if (mr_arrayValue($request, 'add_startart_name_to_url') == 1) {
            $this->view->add_startart_name_to_url_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['add_startart_name_to_url'] = 1;
            if (mr_arrayValue($request, 'add_startart_name_to_url', '') !== '') {
                if (!preg_match('/^[a-zA-Z0-9\-_\/\.]*$/', $request['default_startart_name'])) {
                    $this->view->add_startart_name_to_url_error = $this->renderNotification(
                        'error',
                        i18n('The article name has a invalid format, allowed are the chars /^[a-zA-Z0-9\-_\/\.]*$/', $this->pluginName)                    );
                    $isError = true;
                }
                $this->view->default_startart_name = conHtmlentities($request['default_startart_name']);
                $mrCfg['mod_rewrite']['default_startart_name'] = $request['default_startart_name'];
            } else {
                $this->view->default_startart_name = '';
                $mrCfg['mod_rewrite']['default_startart_name'] = '';
            }
        } else {
            $this->view->add_startart_name_to_url_chk = '';
            $mrCfg['mod_rewrite']['add_startart_name_to_url'] = 0;
            $this->view->default_startart_name = '';
            $mrCfg['mod_rewrite']['default_startart_name'] = '';
        }

        // rewrite urls at
        if (mr_arrayValue($request, 'rewrite_urls_at') == 'congeneratecode') {
            $this->view->rewrite_urls_at_congeneratecode_chk = ' checked="checked"';
            $this->view->rewrite_urls_at_front_content_output_chk = '';
            $mrCfg['mod_rewrite']['rewrite_urls_at_congeneratecode'] = 1;
            $mrCfg['mod_rewrite']['rewrite_urls_at_front_content_output'] = 0;
        } else {
            $this->view->rewrite_urls_at_congeneratecode_chk = '';
            $this->view->rewrite_urls_at_front_content_output_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['rewrite_urls_at_congeneratecode'] = 0;
            $mrCfg['mod_rewrite']['rewrite_urls_at_front_content_output'] = 1;
        }

        // routing
        if (isset($request['rewrite_routing'])) {
            $aRouting = [];
            $items = explode("\n", $request['rewrite_routing']);
            foreach ($items as $p => $v) {
                $routingDef = explode($routingSeparator, $v);
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
            $this->view->rewrite_routing = conHtmlentities($request['rewrite_routing']);
            $mrCfg['mod_rewrite']['routing'] = $aRouting;
        } else {
            $this->view->rewrite_routing = '';
            $mrCfg['mod_rewrite']['routing'] = [];
        }

        // redirect invalid article to errorsite
        if (isset($request['redirect_invalid_article_to_errorsite'])) {
            $this->view->redirect_invalid_article_to_errorsite_chk = ' checked="checked"';
            $mrCfg['mod_rewrite']['redirect_invalid_article_to_errorsite'] = 1;
        } else {
            $this->view->redirect_invalid_article_to_errorsite_chk = '';
            $mrCfg['mod_rewrite']['redirect_invalid_article_to_errorsite'] = 0;
        }

        if ($isError) {
            $this->view->content_before = $this->renderNotification(
                'error',
                i18n('Please check your input', $this->pluginName)
            );
            return;
        }

        if ($bDebug) {
            echo $this->renderNotification('info', 'Debug');
            echo '<pre class="example">';
            print_r($mrCfg['mod_rewrite']);
            echo '</pre>';
            echo $this->renderNotification(
                'info',
                i18n('Configuration has <b>not</b> been saved, because of enabled debugging', $this->pluginName)
            );
            return;
        }

        $bSeparatorModified = $this->isSeparatorModified($mrCfg['mod_rewrite']);

        if (mr_setConfiguration($this->clientId, $mrCfg)) {
            if ($bSeparatorModified) {
                mr_loadConfiguration($this->clientId, true);
            }
            $this->view->content_before = $this->renderNotification(
                'info',
                i18n('Configuration has been saved', $this->pluginName)
            );
        } else {
            $this->view->content_before = $this->renderNotification(
                'error',
                sprintf(
                    i18n('Configuration could not saved. Please check write permissions for %s ', $this->pluginName),
                    mr_getConfigurationFilePath($this->clientId)
                )
            );
        }
    }

    /**
     * Checks if any separator setting is modified or not.
     *
     * @param array $newCfg New configuration send by requests.
     */
    protected function isSeparatorModified(array $newCfg): bool
    {
        $aCfg = ModRewrite::getConfig();

        if ($aCfg['category_seperator'] != $newCfg['category_seperator']) {
            return true;
        } elseif ($aCfg['category_word_seperator'] != $newCfg['category_word_seperator']) {
            return true;
        } elseif ($aCfg['article_seperator'] != $newCfg['article_seperator']) {
            return true;
        } elseif ($aCfg['article_word_seperator'] != $newCfg['article_word_seperator']) {
            return true;
        }
        return false;
    }

}
