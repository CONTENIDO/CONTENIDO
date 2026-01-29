<?php

/**
 * AMR Content expert controller class
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
 * Content expert controller for expert settings/actions.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteExpertController extends PiModRewriteControllerAbstract
{

    /**
     * @var string Path to the restrictive htaccess file
     */
    protected $htaccessRestrictive = '';

    /**
     * @var string Path to the simple htaccess file
     */
    protected $htaccessSimple = '';

    /**
     * Initializer method sets the paths to htaccess files
     */
    public function init()
    {
        $this->view->content_before = '';

        $pluginPath = $this->cfg['path']['contenido'] . $this->cfg['path']['plugins'] . 'mod_rewrite/';
        $this->htaccessRestrictive = $pluginPath . 'files/htaccess_restrictive.txt';
        $this->htaccessSimple = $pluginPath . 'files/htaccess_simple.txt';
    }

    /**
     * Index action
     */
    public function indexAction()
    {
    }

    /**
     * Copy htaccess action
     */
    public function copyHtaccessAction()
    {
        $type = $this->getRequestParam('htaccesstype');
        $copy = $this->getRequestParam('copy');

        if ($type != 'restrictive' && $type != 'simple') {
            return;
        } elseif ($copy != 'contenido' && $copy != 'cms') {
            return;
        }

        $aInfo = $this->getProperty('htaccessInfo');

        if ($aInfo['has_htaccess']) {
            $this->view->content_before = $this->renderNotification(
                'warning',
                i18n('.htaccess already exists at CONTENIDO-/or client directory, so it is not copied.', $this->pluginName)
            );
            return;
        }

        if ($type === 'restrictive') {
            $source = $this->htaccessRestrictive;
        } else {
            $source = $this->htaccessSimple;
        }

        if ($copy === 'contenido') {
            $dest = $aInfo['contenido_full_path'] . '.htaccess';
        } else {
            $dest = $aInfo['client_full_path'] . '.htaccess';
        }

        if (!cFileHandler::copy($source, $dest)) {
            $this->view->content_before = $this->renderNotification(
                'warning',
                sprintf(
                    i18n('.htaccess could not copy from <strong>%s</strong> to <strong>%s</strong>! Perhaps the target directory has not the required rights to write files at your webserver.', $this->pluginName),
                    $source,
                    $dest
                )
            );
            return;
        }

        $msg = sprintf(
            i18n('.htaccess are successfully copied to %s', $this->pluginName),
            str_replace('.htaccess', '', $dest)
        );
        $this->view->content_before = $this->renderNotification('info', $msg);
    }

    /**
     * Download htaccess action
     *
     * @throws cInvalidArgumentException|cException
     */
    public function downloadHtaccessAction()
    {
        $type = $this->getRequestParam('htaccesstype');

        if ($type != 'restrictive' && $type != 'simple') {
            return;
        }

        if ($type == 'restrictive') {
            $source = $this->htaccessRestrictive;
        } else {
            $source = $this->htaccessSimple;
        }

        $this->view->content = cFileHandler::read($source);

        header('Content-Type: text/plain');
        header('Etag: ' . md5(mt_rand()));
        header('Content-Disposition: attachment; filename="' . $type . '.htaccess"');
        $this->render('{CONTENT}');
    }

    /**
     * Reset aliases action
     *
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function resetAction()
    {
        // recreate all aliases
        PiModRewrite::recreateAliases();
        $this->view->content_before = $this->renderNotification(
            'info',
            i18n('All aliases have been reset.', $this->pluginName)
        );
    }

    /**
     * Reset only empty aliases action
     *
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public function resetEmptyAction()
    {
        // recreate only empty aliases
        PiModRewrite::recreateAliases(true);
        $this->view->content_before = $this->renderNotification(
            'info',
            i18n('Only empty aliases have been reset.', $this->pluginName)
        );
    }

}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteExpertController} instead
 */
class ModRewrite_ContentExpertController extends PiModRewriteExpertController
{}
