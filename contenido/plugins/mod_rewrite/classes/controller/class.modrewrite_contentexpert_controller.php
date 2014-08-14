<?php
/**
 * AMR Content expert controller class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @version     SVN Revision $Rev:$
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');


/**
 * Content expert controller for expert settings/actions.
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
class ModRewrite_ContentExpertController extends ModRewrite_ControllerAbstract {

    /**
     * Path to restrictive htaccess file
     * @var string
     */
    protected $_htaccessRestrictive = '';

    /**
     * Path to simple htaccess file
     * @var string
     */
    protected $_htaccessSimple = '';

    /**
     * Initializer method, sets the paths to htaccess files
     */
    public function init() {
        $this->_oView->content_before = '';

        $pluginPath = $this->_cfg['path']['contenido'] . $this->_cfg['path']['plugins'] . 'mod_rewrite/';
        $this->_htaccessRestrictive = $pluginPath . 'files/htaccess_restrictive.txt';
        $this->_htaccessSimple = $pluginPath . 'files/htaccess_simple.txt';
    }

    /**
     * Index action
     */
    public function indexAction() {

    }

    /**
     * Copy htaccess action
     */
    public function copyHtaccessAction() {
        $type = $this->_getParam('htaccesstype');
        $copy = $this->_getParam('copy');

        if ($type != 'restrictive' && $type != 'simple') {
            return;
        } elseif ($copy != 'contenido' && $copy != 'cms') {
            return;
        }

        $aInfo = $this->getProperty('htaccessInfo');

        if ($aInfo['has_htaccess']) {
            $this->_oView->content_before = $this->_notifyBox('warning', 'Die .htaccess existiert bereits im CONTENIDO-/oder Mandantenverzeichnis, daher wird es nicht kopiert');
            return;
        }

        if ($type == 'restrictive') {
            $source = $this->_htaccessRestrictive;
        } else {
            $source = $this->_htaccessSimple;
        }

        if ($copy == 'contenido') {
            $dest = $aInfo['contenido_full_path'] . '.htaccess';
        } else {
            $dest = $aInfo['client_full_path'] . '.htaccess';
        }

        if (!$result = @copy($source, $dest)) {
            $this->_oView->content_before = $this->_notifyBox('warning', 'Die .htaccess-Datei konnte nicht von <strong>' . $source . '</strong> nach <strong>' . $dest . '</strong> kopiert werden! Ggfls. hat der Ziel-Ordner nicht die notwendigen Schreibrechte auf dem Webserver.');
            return;
        }

        $msg = 'Die .htaccess wurde erfolgreich nach ' . str_replace('.htaccess', '', $dest) . ' kopiert';
        $this->_oView->content_before = $this->_notifyBox('info', $msg);
    }

    /**
     * Download htaccess action
     */
    public function downloadHtaccessAction() {
        $type = $this->_getParam('htaccesstype');

        if ($type != 'restrictive' && $type != 'simple') {
            return;
        }

        if ($type == 'restrictive') {
            $source = $this->_htaccessRestrictive;
        } else {
            $source = $this->_htaccessSimple;
        }

        $this->_oView->content = cFileHandler::read($source);

        header('Content-Type: text/plain');
        header('Etag: ' . md5(mt_rand()));
        header('Content-Disposition: attachment; filename="' . $type . '.htaccess"');
        $this->render('{CONTENT}');
    }

    /**
     * Reset aliases action
     */
    public function resetAction() {
        // recreate all aliases
        ModRewrite::recreateAliases(false);
        $this->_oView->content_before = $this->_notifyBox('info', 'Alle Aliase wurden zur&uuml;ckgesetzt');
    }

    /**
     * Reset only empty aliases action
     */
    public function resetEmptyAction() {
        // recreate only empty aliases
        ModRewrite::recreateAliases(true);
        $this->_oView->content_before = $this->_notifyBox('info', 'Nur leere Aliase wurden zur&uuml;ckgesetzt');
    }

}
