<?php
/**
 * This file contains the cContentTypeAbstractTabbed class.
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract content type for content types which are edited in a tabbed popup.
 *
 * @package Core
 * @subpackage ContentType
 */
abstract class cContentTypeAbstractTabbed extends cContentTypeAbstract {

    /**
     * Generates the encoded code for the tab menu.
     *
     * @param array $tabs associative array mapping the tab IDs to the tab names
     * @return string - the encoded code for the tab menu
     */
    protected function _generateTabMenuCode(array $tabs) {
        $template = new cTemplate();

        // iterate over all tabs and set dynamic template placeholder for each
        foreach ($tabs as $id => $name) {
            $template->set('d', 'TAB_ID', $id);
            $template->set('d', 'TAB_NAME', $name);
            $template->next();
        }
        $code = $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tab_menu.html', true);

        return $this->_encodeForOutput($code);
    }

    /**
     * Generates the code for the action buttons (save and cancel).
     *
     * @return string - the encoded code for the action buttons
     */
    protected function _generateActionCode() {
        $template = new cTemplate();

        $template->set('s', 'CON_PATH', $this->_cfg['path']['contenido_fullhtml']);
        $code = $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_action.html', true);

        return $this->_encodeForOutput($code);
    }

}