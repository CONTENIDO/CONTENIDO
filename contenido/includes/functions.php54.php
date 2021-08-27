<?php

/**
 * Originally, this file contained fixed functions for PHP 5.4 support (encoding related).
 * Update 2021-08-25: check for PHP-Version has been omitted. 
 * It is presumed that at least PHP7.x is running. 
 * Code alternatives for PHP below 5.4 have been deleted.
 *
 * @package Core
 * @subpackage Backend
 * @author Dominik Ziegler
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (function_exists('conHtmlSpecialChars') == false) {

    /**
     *
     * @param string $value
     * @param int|string $flags
     * @param string $encoding
     * @return string
     */
    function conHtmlSpecialChars($value, $flags = '', $encoding = '') {

        if ($encoding == '') {
            $encoding = cRegistry::getEncoding();
        }

        // consider case that encoding could not be determined
        if (empty($encoding)) {
            $encoding = null;
        }

        $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
        $encoding = ($encoding == '') ? 'UTF-8' : $encoding;

        return htmlspecialchars($value, $flags, $encoding);
    }

}

if (function_exists('conHtmlEntityDecode') == false) {

    /**
     *
     * @param string $value
     * @param int|string $flags
     * @param string $encoding
     * @return string
     */
    function conHtmlEntityDecode($value, $flags = '', $encoding = '') {

        if ($encoding == '') {
            $encoding = cRegistry::getEncoding();
        }

        $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
        $encoding = ($encoding == '') ? 'UTF-8' : $encoding;

        return html_entity_decode($value, $flags, $encoding);
    }

}

if (function_exists('conHtmlentities') == false) {

    /**
     *
     * @param string $value
     * @param int|string $flags
     * @param string $encoding
     * @return string
     */
    function conHtmlentities($value, $flags = '', $encoding = '') {

        if ($encoding == '') {
            $encoding = cRegistry::getEncoding();
        }

        $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
        $encoding = ($encoding == '') ? 'UTF-8' : $encoding;

        return htmlentities($value, $flags, $encoding);
    }

}

if (function_exists('conGetHtmlTranslationTable') == false) {
    /**
     *
     * @param string $table
     * @param string $flags
     *
     * @return array
     */
    function conGetHtmlTranslationTable($table = '', $flags = '') {

        $table = ($table == '') ? HTML_SPECIALCHARS : $table;
        $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;

        return get_html_translation_table($table, $flags);
    }

}