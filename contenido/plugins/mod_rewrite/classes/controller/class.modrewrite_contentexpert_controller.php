<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Content expert controller
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
 *   $Id: $:
 * }}
 * 
 */


defined('CON_FRAMEWORK') or die('Illegal call');


plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_controller_abstract.php');


class ModRewrite_ContentExpertController extends ModRewrite_ControllerAbstract
{
    protected $_htaccessRestrictive = '';
    protected $_htaccessSimple = '';

    public function init()
    {
        $this->_oView->content_before = '';
        
        $pluginPath = $this->_cfg['path']['contenido'] . $this->_cfg['path']['plugins'] . 'mod_rewrite/';
        $this->_htaccessRestrictive = $pluginPath . 'files/htaccess_restrictive.txt';
        $this->_htaccessSimple = $pluginPath . 'files/htaccess_simple.txt';
    }

    /**
     * Execute index action
     */
    public function indexAction()
    {
    }


    public function copyHtaccessAction()
    {
        $type = $this->_getParam('htaccesstype');
        $copy = $this->_getParam('copy');

        if ($type != 'restrictive' && $type != 'simple') {
            return;
        } elseif ($copy != 'contenido' && $copy != 'cms') {
            return;
        }

        $aInfo = $this->getProperty('htaccessInfo');

        if ($aInfo['has_htaccess']) {
            $this->_oView->content_before = $this->_notifyBox('info', 'Die .htaccess existiert bereits im Contenido-/ oder Mandantenverzeichnis, daher wird es nicht kopiert');
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
            $this->_oView->content_before = $this->_notifyBox('info', 'Die .htaccess konnte nicht von ' . $source . ' nach ' . $dest . ' kopiert werden!');
            return;
        }

        $msg = 'Die .htaccess wurde erfolgreich nach ' . str_replace('.htaccess', '', $dest) . ' kopiert';
        $this->_oView->content_before = $this->_notifyBox('info', $msg);
    }


    public function downloadHtaccessAction()
    {
        $type = $this->_getParam('htaccesstype');

        if ($type != 'restrictive' && $type != 'simple') {
            return;
        }

        if ($type == 'restrictive') {
            $source = $this->_htaccessRestrictive;
        } else {
            $source = $this->_htaccessSimple;
        }

        $this->_oView->content = file_get_contents($source);

        header('Content-Type: text/plain');
        header('Etag: ' . md5(mt_rand()));
        header('Content-Disposition: attachment; filename="' . $type . '.htaccess"');
        $this->render('{CONTENT}');
    }


    public function resetAction()
    {
        // recreate all aliases
        ModRewrite::recreateAliases(false);
        $this->_oView->content_before = $this->_notifyBox('info', 'Alle Aliase wurden zur&uuml;ckgesetzt');
    }


    public function resetEmptyAction()
    {
        // recreate only empty aliases
        ModRewrite::recreateAliases(true);
        $this->_oView->content_before = $this->_notifyBox('info', 'Nur leere Aliase wurden zur&uuml;ckgesetzt');
    }

}
