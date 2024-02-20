<?php

/**
 * This file contains the trait for usage in debug visible classes.
 *
 * @package    Core
 * @subpackage Debug
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

trait cDebugVisibleTrait
{

    /**
     * Prepares debug item value for output as string representation.
     * Wraps the value with `<textarea>` or `<pre>` elements.
     *
     * @param mixed $value
     * @return string
     */
    protected function _prepareDumpValue($value): string
    {
        $bTextarea = false;
        $bPlainText = false;
        $sReturn = '';

        if (is_array($value)) {
            $bTextarea = sizeof($value) > 10;
        } elseif (is_object($value)) {
            $bTextarea = true;
        }

        if (is_string($value)) {
            if (preg_match('/<(.*)>/', $value)) {
                if (cString::getStringLength($value) > 40) {
                    $bTextarea = true;
                } else {
                    $bPlainText = true;
                    $value = conHtmlSpecialChars($value);
                }
            } else {
                $bPlainText = true;
            }
        }

        if ($bTextarea === true) {
            $sReturn .= '<textarea class="cms_debug_output" rows="14" cols="100">';
        } elseif ($bPlainText === true) {
            $sReturn .= '<pre class="cms_debug_output">';
        } else {
            $sReturn .= '<pre class="cms_debug_output">';
        }

        $sReturn .= $this->_dumpWithType($value);

        if ($bTextarea === true) {
            $sReturn .= '</textarea>';
        } elseif ($bPlainText === true) {
            $sReturn .= '</pre>';
        } else {
            $sReturn .= '</pre>';
        }

        return $sReturn;
    }

    /**
     * Prepares debug item value for output as string representation.
     *
     * @param mixed $value
     * @return string
     */
    protected function _preparePlainDumpValue($value): string
    {
        return $this->_dumpWithType($value);
    }

    protected function _getStyles(): string
    {
        static $stylesRendered;

        if (isset($stylesRendered)) {
            return '';
        }
        $stylesRendered = true;

        $cfg = cRegistry::getConfig();
        $tpl = new cTemplate();
        return $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['debug_styles'], true);
    }

    private function _dumpWithType($value): string
    {
        $type = gettype($value);
        switch ($type) {
            case 'boolean':
                return '(' . $type . '): ' . ($value ? 'true' : 'false');
            case 'double':
            case 'string':
            case 'integer':
                return '(' . $type . '): ' . $value;
            case 'NULL':
                return '(' . $type . '): NULL';
            case 'array':
                return '(' . $type . '): ' . print_r($value, true);
            case 'object':
                ob_start();
                var_dump($value);
                $content = ob_get_contents();
                ob_end_clean();
                return '(' . get_class($value) . '): ' . $content;
            case 'resource':
            case 'resource (closed)':
                ob_start();
                var_dump($value);
                $content = ob_get_contents();
                ob_end_clean();
                return '(' . get_resource_type($value) . '): ' . $content;
            default:
                ob_start();
                var_dump($value);
                $content = ob_get_contents();
                ob_end_clean();
                return '(unknown): ' . $content;
        }
    }

}