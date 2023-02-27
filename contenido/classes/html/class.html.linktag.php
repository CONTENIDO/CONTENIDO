<?php

/**
 * This file contains the cHTMLLinkTag class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLLinkTag class represents a link tag.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLLinkTag extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML script element.
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'link';
        $this->_contentlessTag = true;
    }

    /**
     * Renders a link tag to reference an external stylesheet ressource.
     *
     * @since CONTENIDO 4.10.2
     * @param string $href The reference (path) to the stylesheet file
     * @param array $attributes Attributes to set, `rel="stylesheet"` and
     *      `type="text/css"` will be set by default.
     * @return string
     */
    public static function stylesheet(string $href, array $attributes = []): string
    {
        $link = new self();

        if (!isset($attributes['rel'])) {
            $attributes['rel'] = 'stylesheet';
        }
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text/css';
        }
        $attributes = array_merge($attributes, [
            'href' => $href,
        ]);

        return $link->setAttributes($attributes)->toHtml();
    }

}
