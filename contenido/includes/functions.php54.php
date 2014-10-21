<?php
/**
 * This file contains fix functions for PHP 5.4 support (encoding related).
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler, Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (function_exists('conPhp54Check') == false) {

    function conPhp54Check() {
        if (!defined('CON_PHP54')) {
            define('CON_PHP54', version_compare(PHP_VERSION, '5.4.0', '<') ? 0 : 1);
        }

        return CON_PHP54;
    }

}

if (function_exists('conHtmlSpecialChars') == false) {

    function conHtmlSpecialChars($value, $flags = '', $encoding = '') {
        $isPhp54 = conPhp54Check();

		if ($encoding == '') {
			$encoding = cRegistry::getEncoding();
		}

        if ($isPhp54 == 1) {
            $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
            $encoding = ($encoding == '') ? 'UTF-8' : $encoding;
        } else {
            $flags = ($flags == '') ? ENT_COMPAT : $flags;
        }

        return htmlspecialchars($value, $flags, $encoding);
    }

}

if (function_exists('conHtmlEntityDecode') == false) {

    function conHtmlEntityDecode($value, $flags = '', $encoding = '') {
        $isPhp54 = conPhp54Check();

		if ($encoding == '') {
			$encoding = cRegistry::getEncoding();
		}

        if ($isPhp54 == 1) {
            $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
            $encoding = ($encoding == '') ? 'UTF-8' : $encoding;
        } else {
            $flags = ($flags == '') ? ENT_COMPAT : $flags;
        }

        return html_entity_decode($value, $flags, $encoding);
    }

}

if (function_exists('conHtmlentities') == false) {

    function conHtmlentities($value, $flags = '', $encoding = '') {
        $isPhp54 = conPhp54Check();

		if ($encoding == '') {
			$encoding = cRegistry::getEncoding();
		}

        if ($isPhp54 == 1) {
            $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
            $encoding = ($encoding == '') ? 'UTF-8' : $encoding;
        } else {
            $flags = ($flags == '') ? ENT_COMPAT : $flags;
        }

        return htmlentities($value, $flags, $encoding);
    }

}

if (function_exists('conGetHtmlTranslationTable') == false) {

    function conGetHtmlTranslationTable($table = '', $flags = '') {
        $isPhp54 = conPhp54Check();

        if ($isPhp54 == 1) {
            $table = ($table == '') ? HTML_SPECIALCHARS : $table;
            $flags = ($flags == '') ? ENT_COMPAT | ENT_HTML401 : $flags;
        } else {
            $flags = ($flags == '') ? ENT_COMPAT : $flags;
        }

        return get_html_translation_table($table, $flags);
    }

}
?>