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
     * Return the raw settings of a content type
     *
     * @param string $contentTypeName Content type name
     * @param int $id ID of the content type
     * @param array $contentTypes Content type array
     * @return mixed
     */
    protected function _getRawSettings($contentTypeName, $id, array $contentTypes) {
        if (!isset($contentTypes[$contentTypeName][$id])) {
            $idArtLang = cRegistry::getArticleLanguageId();
            // get the idtype of the content type
            $typeItem = new cApiType();
            $typeItem->loadByType($contentTypeName);
            $idtype = $typeItem->get('idtype');
            // first load the appropriate content entry in order to get the
            // settings
            $content = new cApiContent();
            $content->loadByMany(array(
                'idartlang' => $idArtLang,
                'idtype' => $idtype,
                'typeid' => $id
            ));
            return $content->get('value');
        } else {
            return $contentTypes[$contentTypeName][$id];
        }
    }

    /**
     * Generates the code for the action buttons (save and cancel).
     *
     * @return string - the encoded code for the action buttons
     */
    protected function _generateActionCode() {
        $template = new cTemplate();

        $code = $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_action.html', true);

        return $this->_encodeForOutput($code);
    }

}