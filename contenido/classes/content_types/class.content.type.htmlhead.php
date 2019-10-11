<?php

/**
 * This file contains the cContentTypeHtmlhead class.
 *
 * @package    Core
 * @subpackage ContentType
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content type CMS_HTMLHEAD which lets the editor enter HTML with the help of a
 * WYSIWYG editor.
 *
 * @package    Core
 * @subpackage ContentType
 */
class cContentTypeHtmlhead extends cContentTypeHtml
{
    /**
     * Name of the content type.
     *
     * @var string
     */
    const CONTENT_TYPE = 'CMS_HTMLHEAD';
    /**
     * Prefix used for posted data.
     * Replaces the property $this->>_prefix.
     *
     * @var string
     */
    const PREFIX = 'htmlhead';
}
