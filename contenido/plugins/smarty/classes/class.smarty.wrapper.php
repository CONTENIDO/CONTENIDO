<?php
/**
 * This file contains the wrapper class for smarty wrapper plugin.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 * @author Andreas Dieter
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for Integration of smarty.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 */
class cSmartyWrapper extends Smarty {

    public function __construct()
    {
        parent::__construct();
        $path = realpath(__DIR__ . '/../includes/smarty_plugins');
        $this->addPluginsDir($path);
    }

    /**
     * @see Smarty_Internal_TemplateBase::fetch()
     *
     * @param string $template   the resource handle of the template file or template object
     * @param mixed  $cache_id   cache id to be used with this template
     * @param mixed  $compile_id compile id to be used with this template
     * @param object $parent     next higher level of Smarty variables
     * @param bool   $display
     * @param bool   $merge_tpl_vars
     * @param bool   $no_output_filter
     *
     * @return mixed|string
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
     *
     * @see Smarty_Internal_TemplateBase::fetch()
     *
     * @param string $template   the resource handle of the template file or template object
     * @param mixed  $cache_id   cache id to be used with this template
     * @param mixed  $compile_id compile id to be used with this template
     * @param object $parent     next higher level of Smarty variables
     * @param bool $display
     * @param bool $merge_tpl_vars
     * @param bool $no_output_filter
     *
     * @return string
     */
    public function fetchGeneral($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
        $template = cRegistry::getFrontendPath() . 'templates/' . $template;

        return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
    }

    /**
     * @param string $template   the resource handle of the template file or template object
     * @param mixed  $cache_id   cache id to be used with this template
     * @param mixed  $compile_id compile id to be used with this template
     * @param object $parent     next higher level of Smarty variables
     */
    public function display($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL) {
        global $frontend_debug;

        if ($this->templateExists($template) === false) {
            $moduleId = (int) cRegistry::getCurrentModuleId();
            if ($moduleId > 0) {
                $module = new cModuleHandler($moduleId);
                $template = $module->getTemplatePath($template);
            }
        }

        // NOTE: We don't have $frontend_debug when Smarty runs in backend context!
        if (isset($frontend_debug['template_display']) && $frontend_debug['template_display']) {
            echo("<!-- SMARTY TEMPLATE " . $template . " -->");
        }

        parent::display($template, $cache_id, $compile_id, $parent);
    }

    /**
     * @see Smarty_Internal_TemplateBase::display()
     *
     * @param string $template   the resource handle of the template file or template object
     * @param mixed  $cache_id   cache id to be used with this template
     * @param mixed  $compile_id compile id to be used with this template
     * @param object $parent     next higher level of Smarty variables
     */
    public function displayGeneral($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL) {
        $this->fetchGeneral($template, $cache_id, $compile_id, $parent, true);
    }

    /**
     * Empty cache for a specific template
     *
     * @param string  $template_name template name
     * @param string  $cache_id      cache id
     * @param string  $compile_id    compile id
     * @param integer $exp_time      expiration time
     * @param string  $type          resource type
     * @return integer number of cache files deleted
     */
    public function clearCache($template_name, $cache_id = null, $compile_id = null, $exp_time = null, $type = null) {
        if ($this->templateExists($template_name) === false) {
            $moduleId = (int) cRegistry::getCurrentModuleId();
            if ($moduleId > 0) {
                $module = new cModuleHandler($moduleId);
                $template_name = $module->getTemplatePath($template_name);
            }
        }

        return parent::clearCache($template_name, $cache_id, $compile_id, $exp_time, $type);
    }

}