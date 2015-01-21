<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * TINYMCE 4 PHP Plugin interface
 * Passes template to caller with localisation applied
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * TINYMCE 4 Fileversion
 *
 * @package    CONTENIDO Backend Editor
 * @version    0.0.1
 * @author     Thomas Stauer
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
$contenido_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../../')) . '/';

if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
));

// include editor config/combat file
include(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config.php');

$db = cRegistry::getDb();

cInclude('includes', 'functions.lang.php');

$localiser = new TemplateLocaliser($_GET['localise'], $_GET['node']);
// $mediaList = new cTinyMCE4List($_GET['mode']);

class TemplateLocaliser {
    public function __construct($tmpl = null, $node = null) {
        // a template is mandatory
        if (false === isset($tmpl)
        || 0 === strlen($tmpl)) {
            return;
        }

        $result = '';
        // only localise pre-defined files
        switch ($tmpl) {
            case 'template.abbreviationdialog_tpl.html':
                $result = $this->_localiseAbbr($node);
                break;
            default:
                // just output an empty string for unknown template
        }
        
        echo $result;
    }

    private function _localiseAbbr($node) {
        $cfg = cRegistry::getConfig();
        
        $tmplPath = $cfg['path']['all_wysiwyg'] . 'tinymce4/contenido/templates/template.abbreviationdialog_tpl.html';
        
        $tmpl = new cTemplate();
        $tmpl->set('s', 'TITLE:', i18n('Title:'));
        $tmpl->set('s', 'ID:', i18n('ID:'));
        $tmpl->set('s', 'CLASS:', i18n('Class:'));
        $tmpl->set('s', 'STYLE:', i18n('Style:'));
        $tmpl->set('s', 'TEXT_DIRECTION:', i18n('Text Direction:'));
        $tmpl->set('s', 'LANGUAGE:', i18n('Language:'));
        
        $tmpl->set('s', 'NOT_SET', i18n('-- Not set --'));
        $tmpl->set('s', '(VALUE)', i18n('(value)'));
        $tmpl->set('s', 'LEFT_TO_RIGHT', i18n('Left to right'));
        $tmpl->set('s', 'RIGHT_TO_LEFT', i18n('Right to left'));
        
        $tmpl->set('s', 'INSERT', i18n('Insert'));
        $tmpl->set('s', 'UPDATE', i18n('Update'));
        $tmpl->set('s', 'REMOVE', i18n('Remove'));
        $tmpl->set('s', 'CANCEL', i18n('Cancel'));
        
        // pass node content to template
        $tmpl->set('s', 'SELECTION_VALUE', $node);
        
        
        $tmpl->generate($tmplPath);
    }

    // output the created list as JSON
    private function printList($list) {
        echo json_encode($list);
    }
}
?>