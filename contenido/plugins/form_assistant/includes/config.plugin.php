<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Helper class for this plugin.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class Pifa {

    /**
     * These constants describe if forms have a timestamp.
     *
     * @var string
     */
    const TIMESTAMP_NEVER = 'never';

    const TIMESTAMP_BYFORM = 'byform';

    const TIMESTAMP_ALWAYS = 'always';

    /**
     * name of this plugin
     *
     * @var string
     */
    private static $_name = 'form_assistant';

    /**
     *
     * @var int
     */
    private static $_timestampSetting = NULL;

    /**
     */
    public static function getName() {
        return self::$_name;
    }

    /**
     * Return path to this plugins folder.
     *
     * @return string
     */
    public static function getPath() {
        $cfg = cRegistry::getConfig();

        $path = cRegistry::getBackendPath() . $cfg['path']['plugins'];
        $path .= self::$_name . '/';

        return $path;
    }

    /**
     * Return URL to this plugins folder.
     *
     * @return string
     */
    public static function getUrl() {
        $cfg = cRegistry::getConfig();

        $path = cRegistry::getBackendUrl() . $cfg['path']['plugins'];
        $path .= self::$_name . '/';

        return $path;
    }

    /**
     *
     * @param string $key
     * @return string
     */
    public static function i18n($key) {
        $trans = i18n($key, self::$_name);
        return $trans;
    }

    /**
     *
     * @param string $level
     * @param string $note
     */
    public static function getNote($level, $note) {
        $note = self::i18n($note);
        $notification = new cGuiNotification();
        return $notification->returnNotification($level, $note);
    }

    /**
     *
     * @param string $note
     */
    public static function getError($note) {
        return self::getNote(cGuiNotification::LEVEL_ERROR, $note);
    }

    /**
     *
     * @param Exception $e
     */
    public static function logException(Exception $e) {

    	if (getSystemProperty('debug', 'debug_for_plugins') == 'true') {
	        $cfg = cRegistry::getConfig();

	        $log = new cLog(cLogWriter::factory('file', array(
	            'destination' => $cfg['path']['contenido_logs'] . 'errorlog.txt'
	        )), cLog::ERR);

	        $log->err($e->getMessage());
	        $log->err($e->getTraceAsString());
    	}
    }

    /**
     * TODO build method to display erro & info box and just call it from here
     *
     * @param Exception $e
     * @param bool $showTrace if trace should be displayed too
     */
    public static function displayException(Exception $e, $showTrace = false) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 ' . self::i18n('INTERNAL_SERVER_ERROR'), true, 500);

        if (true) {
            // error box
            $class = "ui-state-error";
            $icon = "ui-icon-alert";
        } else {
            // info box
            $class = "ui-state-highlight";
            $icon = "ui-icon-info";
        }

        echo '<div class="ui-widget">';
        echo '<div class="' . $class . ' ui-corner-all">';
        echo '<p>';
        echo '<span class="ui-icon ' . $icon . '"></span>';
        echo $e->getMessage();
        if (true === $showTrace) {
            echo '<pre style="overflow: auto">';
            echo htmlentities($e->getTraceAsString(), ENT_COMPAT | ENT_HTML401, 'UTF-8');
            echo '</pre>';
        }
        echo '</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Creates a notification widget in order to display an exception message in
     * backend.
     *
     * @param Exception $e
     * @return string
     */
    public static function notifyException(Exception $e) {
        $cGuiNotification = new cGuiNotification();
        $level = cGuiNotification::LEVEL_ERROR;
        $message = $e->getMessage();

        return $cGuiNotification->returnNotification($level, $message);
    }

    /**
     * Returns array of extension classes that subclass the given $parentClass.
     *
     * @param string $parentClass
     * @throws PifaException
     * @return array
     */
    public static function getExtensionClasses($parentClass) {

        // ignore if extensions folder is missing
        if (false === ($handle = cDirHandler::read(self::getPath() . 'extensions/'))) {
            return array();
        }

        $extensionClasses = array();
        foreach ($handle as $file) {
            // skip files that don't match regex
            $matches = array();
            $matchCount = preg_match('/^class\.pifa\.([^\.]+)\.php$/', $file, $matches);

            // REGEX failure ... just call Mr. T!
            if (false === $matchCount) {
                $msg = self::i18n('EXTENSION_REGEX_ERROR');
                throw new PifaException($msg);
            }

            // some other file .. just skip it
            if (0 === $matchCount) {
                continue;
            }

            // this is a proper PHP class
            $optionClass = self::toCamelCase($matches[1], true);

            include_once(self::getPath() . 'extensions/' . $file);

            $reflection = new ReflectionClass($optionClass);
            if (false === $reflection->isSubclassOf($parentClass)) {
                continue;
            }

            $extensionClasses[] = array(
                'value' => $optionClass,
                'label' => $optionClass
            );
        }

        return $extensionClasses;
    }

    /**
     * Returns array of client templates that that adhere to the naming
     * convention cms_pifaform_FOOBAR.tpl where FOOBAR is any character but a
     * dot.
     *
     * @throws PifaException
     * @return array
     */
    public static function getTemplates($re = '/cms_pifaform_[^\.]+\.tpl/') {
        $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());

        // ignore if template folder is missing
        if (false === ($handle = cDirHandler::read($clientConfig['template']['path']))) {
            return array();
        }

        $templates = array();
        foreach ($handle as $file) {

            // skip folders
            if (true === is_dir($file)) {
                continue;
            }

            // skip files that don't match regex
            $matches = array();
            $matchCount = preg_match($re, $file, $matches);

            // REGEX failure ... just call Mr. T!
            if (false === $matchCount) {
                $msg = self::i18n('TEMPLATE_REGEX_ERROR');
                throw new PifaException($msg);
            }

            // some other file .. just skip it
            if (0 === $matchCount) {
                continue;
            }

            $templates[] = array(
                'value' => $file,
                'label' => $file
            );
        }

        return $templates;
    }

    // /**
    // */
    // public static function afterLoadPlugins() {

    // // return;
    // if (!isset($_GET['securimage'])) {
    // return;
    // }

    // $e = error_get_last();

    // $img = new Securimage(array(
    // 'image_height' => (int) getEffectiveSetting('pifa', 'captcha-image-height', 80),
    // 'image_width' => (int) getEffectiveSetting('pifa', 'captcha-image-width', 215),
    // 'perturbation' => (int) getEffectiveSetting('pifa', 'captcha-perturbation', 0),
    // 'num_lines' => (int) getEffectiveSetting('pifa', 'captcha-num-lines', 3),
    // 'session_name' => cRegistry::getClientId() . 'frontend'
    // ));

    // $img->show();
    // }

    /**
     * Translates a camel case string into a string with underscores
     * (e.g.
     * firstName -&gt; first_name)
     *
     * @see http://www.paulferrett.com/2009/php-camel-case-functions/
     * @param string $str String in camel case format
     * @return string $str Translated into underscore format
     */
    public static function fromCamelCase($str) {
        $str[0] = strtolower($str[0]);
        $func = create_function('$c', 'return "_" . strtolower($c[1]);');
        return preg_replace_callback('/([A-Z])/', $func, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g.
     * first_name -&gt; firstName)
     *
     * @see http://www.paulferrett.com/2009/php-camel-case-functions/
     * @param string $str String in underscore format
     * @param bool $capitalise_first_char If true, capitalise the first
     *        char in $str
     * @return string $str translated into camel caps
     */
    public static function toCamelCase($str, $capitalise_first_char = false) {
        if ($capitalise_first_char) {
            $str[0] = strtoupper($str[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g.
     * first_name -&gt; firstName)
     *
     * @see http://www.paulferrett.com/2009/php-camel-case-functions/
     * @param string $str String in underscore format
     * @param bool $capitalise_first_char If true, capitalise the first
     *        char in $str
     * @return string $str translated into camel caps
     */
    public static function getTimestampSetting($force = false) {
        if (is_null(self::$_timestampSetting) || $force) {
            self::$_timestampSetting = getEffectiveSetting('pifa', 'timestamp', self::TIMESTAMP_ALWAYS);
            if (!in_array(self::$_timestampSetting, array(
                self::TIMESTAMP_NEVER,
                self::TIMESTAMP_BYFORM,
                self::TIMESTAMP_ALWAYS
            ))) {
                self::$_timestampSetting = self::TIMESTAMP_ALWAYS;
            }
        }
        return self::$_timestampSetting;
    }

    /**
     * Determine if page is called via HTTPS.
     *
     * @return boolean
     */
    public static function isHttps() {
        $isHttps = false;
        $isHttps |= 443 === $_SERVER['SERVER_PORT'];
        $isHttps |= array_key_exists('HTTP_X_SSL_CIPHER', $_SERVER);
        return $isHttps;
    }
}

// define plugin path
$cfg['plugins'][Pifa::getName()] = Pifa::getPath();

// define template names
// $cfg['templates']['form_left_bottom'] = $cfg['plugins']['form'] .
// 'templates/template.left_bottom.html';
$cfg['templates']['pifa_right_bottom_form'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_form.tpl';
$cfg['templates']['pifa_right_bottom_fields'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_fields.tpl';
$cfg['templates']['pifa_right_bottom_data'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_data.tpl';
$cfg['templates']['pifa_right_bottom_export'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_export.tpl';
$cfg['templates']['pifa_right_bottom_import'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_import.tpl';
$cfg['templates']['pifa_ajax_field_form'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.ajax_field_form.tpl';
$cfg['templates']['pifa_ajax_field_row'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.ajax_field_row.tpl';
$cfg['templates']['pifa_ajax_option_row'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.ajax_option_row.tpl';

// define table names
$cfg['tab']['pifa_form'] = $cfg['sql']['sqlprefix'] . '_pifa_form';
$cfg['tab']['pifa_field'] = $cfg['sql']['sqlprefix'] . '_pifa_field';

// define action translations
global $lngAct;
$lngAct['form']['pifa_show_form'] = Pifa::i18n('pifa_show_form');
$lngAct['form']['pifa_store_form'] = Pifa::i18n('pifa_store_form');
$lngAct['form']['pifa_delete_form'] = Pifa::i18n('pifa_delete_form');
$lngAct['form_fields']['pifa_show_fields'] = Pifa::i18n('pifa_show_fields');
$lngAct['form_data']['pifa_show_data'] = Pifa::i18n('pifa_show_data');
$lngAct['form_import']['pifa_import_form'] = Pifa::i18n('pifa_import_form');
$lngAct['form_ajax']['pifa_export_form'] = Pifa::i18n('pifa_export_form');
$lngAct['form_ajax']['pifa_get_field_form'] = Pifa::i18n('pifa_get_field_form');
$lngAct['form_ajax']['pifa_post_field_form'] = Pifa::i18n('pifa_post_field_form');
$lngAct['form_ajax']['pifa_reorder_fields'] = Pifa::i18n('pifa_reorder_fields');
$lngAct['form_ajax']['pifa_export_data'] = Pifa::i18n('pifa_export_data');
$lngAct['form_ajax']['pifa_get_file'] = Pifa::i18n('pifa_get_file');
$lngAct['form_ajax']['pifa_delete_field'] = Pifa::i18n('pifa_delete_field');
$lngAct['form_ajax']['pifa_get_option_row'] = Pifa::i18n('pifa_get_option_row');

// include necessary sources, setup autoloader for plugin
// @todo Use config variables for $pluginClassPath below!
$pluginClassPath = 'contenido/plugins/' . Pifa::getName() . '/';
cAutoload::addClassmapConfig(array(
    'cContentTypePifaForm' => $pluginClassPath . 'classes/class.content.type.pifa_form.php',
    'PifaExternalOptionsDatasourceInterface' => $pluginClassPath . 'classes/class.pifa.external_options_datasource_interface.php',
    'PifaExporter' => $pluginClassPath . 'classes/class.pifa.exporter.php',
    'PifaImporter' => $pluginClassPath . 'classes/class.pifa.importer.php',
    'PifaLeftBottomPage' => $pluginClassPath . 'classes/class.pifa.gui.php',
    'PifaRightBottomFormPage' => $pluginClassPath . 'classes/class.pifa.gui.php',
    'PifaRightBottomFormFieldsPage' => $pluginClassPath . 'classes/class.pifa.gui.php',
    'PifaRightBottomFormDataPage' => $pluginClassPath . 'classes/class.pifa.gui.php',
    'PifaRightBottomFormExportPage' => $pluginClassPath . 'classes/class.pifa.gui.php',
    'PifaRightBottomFormImportPage' => $pluginClassPath . 'classes/class.pifa.gui.php',
    'PifaFormCollection' => $pluginClassPath . 'classes/class.pifa.form.php',
    'PifaForm' => $pluginClassPath . 'classes/class.pifa.form.php',
    'PifaFieldCollection' => $pluginClassPath . 'classes/class.pifa.field.php',
    'PifaField' => $pluginClassPath . 'classes/class.pifa.field.php',
    'PifaAbstractFormModule' => $pluginClassPath . 'classes/class.pifa.abstract_form_module.php',
    'PifaAbstractFormProcessor' => $pluginClassPath . 'classes/class.pifa.abstract_form_processor.php',
    'PifaAjaxHandler' => $pluginClassPath . 'classes/class.pifa.ajax_handler.php',
    'PifaException' => $pluginClassPath . 'classes/class.pifa.exceptions.php',
    'PifaDatabaseException' => $pluginClassPath . 'classes/class.pifa.exceptions.php',
    'PifaNotImplementedException' => $pluginClassPath . 'classes/class.pifa.exceptions.php',
    'PifaIllegalStateException' => $pluginClassPath . 'classes/class.pifa.exceptions.php',
    // 'Securimage' => $pluginClassPath . 'securimage/securimage.php',
    'PifaNotYetStoredException' => $pluginClassPath . 'classes/class.pifa.exceptions.php',
    'PifaValidationException' => $pluginClassPath . 'classes/class.pifa.exceptions.php',
    'PifaMailException' => $pluginClassPath . 'classes/class.pifa.exceptions.php'
));
unset($pluginClassPath);

// define chain functions
//cRegistry::getCecRegistry()->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'Pifa::afterLoadPlugins');
