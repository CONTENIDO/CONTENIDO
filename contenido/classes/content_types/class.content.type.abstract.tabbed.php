<?php

/**
 * This file contains the cContentTypeAbstractTabbed class.
 *
 * @package    Core
 * @subpackage ContentType
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract content type for content types which are edited in a tabbed popup.
 *
 * @package    Core
 * @subpackage ContentType
 */
abstract class cContentTypeAbstractTabbed extends cContentTypeAbstract
{

    /**
     * Generates the encoded code for the tab menu.
     *
     * @param array $tabs
     *         associative array mapping the tab IDs to the tab names
     *
     * @return string
     *         the encoded code for the tab menu
     * @throws cInvalidArgumentException
     */
    protected function _generateTabMenuCode(array $tabs): string
    {
        $template = new cTemplate();

        // iterate over all tabs and set dynamic template placeholder for each
        foreach ($tabs as $id => $name) {
            $template->set('d', 'TAB_ID', $id);
            $template->set('d', 'TAB_NAME', $name);
            $template->next();
        }
        $code = $template->generate(
            $this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tab_menu.html',
            true
        );

        return $this->_encodeForOutput($code);
    }

    /**
     * Return the raw settings of a content type
     *
     * @param string $contentTypeName
     *         Content type name (e.g. `CONTENT_TYPE`)
     * @param int $id
     *         Content id (e.g. the ID in `CONTENT_TYPE[ID]`)
     * @param array $contentTypes
     *         Content type array
     *
     * @return string
     * @throws cDbException
     * @throws cException
     */
    protected function _getRawSettings($contentTypeName, $id, array $contentTypes): string
    {
        $id = cSecurity::toInteger($id);
        if (!isset($contentTypes[$contentTypeName][$id])) {
            $idArtLang = cSecurity::toInteger(cRegistry::getArticleLanguageId());
            // Get the idtype of the content type and then the settings
            $typeItem = new cApiType();
            $typeItem->loadByType($contentTypeName);
            $idtype = cSecurity::toInteger($typeItem->get('idtype'));
            return $this->_getRawSettingsFromContent($idArtLang, $idtype, $id);
        } else {
            return cSecurity::toString($contentTypes[$contentTypeName][$id]);
        }
    }

    /**
     * Generates the code for the action buttons (save and cancel).
     *
     * @return string
     *         the encoded code for the action buttons
     * @throws cInvalidArgumentException
     */
    protected function _generateActionCode(): string
    {
        $template = new cTemplate();

        $code = $template->generate(
            $this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_action.html',
            true
        );

        return $this->_encodeForOutput($code);
    }

    /**
     * Returns the raw settings from content by article language id,
     * content type id, and content id.
     *
     * @param int $idArtLang Article language id
     * @param int $idType Content type id (e.g. id of `CONTENT_TYPE`)
     * @param int $typeId Content id (e.g. the ID in `CONTENT_TYPE[ID]`)
     * @return string
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    protected function _getRawSettingsFromContent(
        int $idArtLang, int $idType, int $typeId
    ): string
    {
        // Load the appropriate content entry in order to get the settings
        $content = new cApiContent();
        $content->loadByArticleLanguageIdTypeAndTypeId($idArtLang, $idType, $typeId);
        if ($content->isLoaded()) {
            return cSecurity::toString($content->get('value'));
        } else {
            return '';
        }
    }

}
