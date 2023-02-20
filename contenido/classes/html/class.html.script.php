<?php

/**
 * This file contains the cHTMLScript class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLScript class represents a script.
 *
 * @todo Should set attribute type="text/javascript" by default or depending on
 *       doctype!
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLScript extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML script element.
     */
    public function __construct() {
        parent::__construct();
        $this->_tag = 'script';
    }

    /**
     * Renders a script tag to reference an external script.
     *
     * @param string $src The src (path) value for the script
     * @param array $attributes Attributes to set, `type="text/javascript"`
     *      will be set by default.
     * @return string
     */
    public static function external(string $src, array $attributes = []): string
    {
        $script = new self();

        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text/javascript';
        }
        $attributes = array_merge($attributes, [
            'src' => $src,
        ]);

        return $script->setAttributes($attributes)->toHtml();
    }

}
