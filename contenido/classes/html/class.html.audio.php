<?php

/**
 * This file contains the cHTMLAudio class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLAudio class specifies sound content.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLAudio extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $content [optional] String or object with the contents
     * @param string $class [optional] The class of this element
     * @param string $id [optional] The ID of this element
     * @param string $src [optional]
     */
    public function __construct($content = '', $class = '', $id = '', $src = '')
    {
        parent::__construct($content, $class, $id);
        $this->_tag = 'audio';
        $this->setSrc($src);
    }

    /**
     * Sets the src attribute of this element.
     *
     * @param string $src
     */
    public function setSrc($src): self
    {
        return $this->setAttribute('src', $src);
    }

    /**
     * Sets the autoplay attribute which specifies if the sound should be played automatically.
     */
    public function setAutoplay(bool $autoplay): self
    {
        if ($autoplay) {
            return $this->setAttribute('autoplay', 'autoplay');
        } else {
            return $this->removeAttribute('autoplay');
        }
    }

    /**
     * Sets the controls attribute which specifies if controls should be shown in the player.
     */
    public function setControls(bool $controls): self
    {
        if ($controls) {
            return $this->setAttribute('controls', 'controls');
        } else {
            return $this->removeAttribute('controls');
        }
    }

}
