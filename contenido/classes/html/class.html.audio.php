<?php

/**
 * This file contains the cHTMLAudio class.
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
 * cHTMLAudio class specifies sound content.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLAudio extends cHTMLContentElement {

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $content [optional]
     *         String or object with the contents
     * @param string $class [optional]
     *         the class of this element
     * @param string $id [optional]
     *         the ID of this element
     * @param string $src [optional]
     */
    public function __construct($content = '', $class = '', $id = '', $src = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'audio';
        $this->setSrc($src);
    }

    /**
     * Sets the src attribute of this element.
     *
     * @param string $src
     */
    public function setSrc($src) {
        $this->setAttribute('src', $src);
    }

    /**
     * Sets the autoplay attribute which specifies if the sound should be played
     * automatically.
     *
     * @param bool $autoplay
     */
    public function setAutoplay($autoplay) {
        if ($autoplay) {
            $this->setAttribute('autoplay', 'autoplay');
        } else {
            $this->removeAttribute('autoplay');
        }
    }

    /**
     * Sets the controls attribute which specifies if controls should be shown
     * in the player.
     *
     * @param bool $controls
     */
    public function setControls($controls) {
        if ($controls) {
            $this->setAttribute('controls', 'controls');
        } else {
            $this->removeAttribute('controls');
        }
    }

}
