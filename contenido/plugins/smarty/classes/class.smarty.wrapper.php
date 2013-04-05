<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Wrapper class for Integration of smarty
 *
 * Requirements:
 *
 *
 * @package    Contenido Template classes
 * @version    1.3.0
 * @author     Andreas Dieter
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since
 *
 * {@internal
 *     created     2010-07-22
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cSmartyWrapper extends Smarty {
    /**
     * @see Smarty_Internal_TemplateBase::fetch()
     */
    public function fetch($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
        if ($this->templateExists($template) === false) {
            $moduleId = (int) cRegistry::getCurrentModuleId();
            if ($moduleId > 0) {
                $module = new cModuleHandler($moduleId);
                $template = $module->getTemplatePath($template);
            }
        }

        return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
    }

    /**
     * @see Smarty_Internal_TemplateBase::fetch()
     */
    public function fetchGeneral($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
        $template = cRegistry::getFrontendPath() . 'templates/' . $template;

        return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
    }

    /**
     * @see Smarty_Internal_TemplateBase::display()
     */
    public function displayGeneral($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL) {
        $this->fetchGeneral($template, $cache_id, $compile_id, $parent, true);
    }

}