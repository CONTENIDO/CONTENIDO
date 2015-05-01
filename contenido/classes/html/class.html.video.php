<?php

/**
 * This file contains the cHTMLVideo class.
 *
 * @package Core
 * @subpackage GUI_HTML
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
 * cHTMLVideo class represents a video.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLVideo extends cHTMLContentElement {

    /**
     * Constructor.
     *
     * @param mixed $content
     *         String or object with the contents
     * @param string $class
     *         the class of this element
     * @param string $id
     *         the ID of this element
     * @param string $src
     */
    public function __construct($content = '', $class = '', $id = '', $src = '') {
        parent::__construct($content, $class, $id);
        $this->_tag = 'video';
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

    /**
     * Specifies a link to a poster which is shown until the user plays or seeks
     * the video.
     *
     * @param string $poster
     */
    public function setPoster($poster) {
        $this->setAttribute('poster', $poster);
    }

}
