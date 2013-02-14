<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 * Helper class for this plugin.
 *
 * @author marcus.gnass
 */
class Pifa {

    /**
     * name of this plugin
     *
     * @var string
     */
    private static $_name = 'form_assistant';

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
        return i18n($key, self::$_name);
    }

    /**
     *
     * @param unknown_type $level
     * @param unknown_type $note
     */
    public static function getNote($level, $note) {
        $note = self::i18n($note);
        $notification = new cGuiNotification();
        return $notification->returnNotification($level, $note);
    }

    /**
     *
     * @param unknown_type $note
     */
    public static function getError($note) {
        return self::getNote(cGuiNotification::LEVEL_ERROR, $note);
    }

    /**
     *
     * @param Exception $e
     */
    public static function logException(Exception $e) {

        $cfg = cRegistry::getConfig();

        $log = new cLog(cLogWriter::factory('file', array(
            'destination' => $cfg['path']['contenido_logs'] . 'errorlog.txt'
        )), cLog::ERR);

        $log->err($e->getMessage());
        $log->err($e->getTraceAsString());

    }

    /**
     * TODO build method to display erro & info box and just call it from here
     *
     * @param Exception $e
     * @param bool $showTrace if trace should be displayed too
     */
    public static function displayException(Exception $e, $showTrace = false) {

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
        // echo '<strong>Exception</strong>';
        echo $e->getMessage();
        if (true === $showTrace) {
            echo '<pre style="overflow: auto">';
            echo htmlentities($e->getTraceAsString());
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
     */
    public static function getExtensionClasses($regex) {

        // ignore if extensions folder is missing
        if (false !== $dh = opendir(Pifa::getPath() . 'extensions/')) {
            return array();
        }

        $extensionClasses = array();
        while (false !== $file = readdir($dh)) {

            // skip folders
            if (true === is_dir($file)) {
                continue;
            }

            // skip files that don't match regex
            $matches = array();
            $matchCount = preg_match($regex, $file, $matches);

            if (false === $matchCount) {
                // REGEX failure ... just call Mr. T!
                throw new PifaException('REGEX failure');
            } else if (0 === $matchCount) {
                // some other file .. just skip it
                continue;
            } else {
                // this is a proper
                $optionClass = Pifa::toCamelCase($matches[1], true);
                $extensionClasses[] = array(
                    'value' => $optionClass,
                    'label' => $optionClass
                );
            }
        }

        return $extensionClasses;

    }

    /**
     */
    public static function afterLoadPlugins() {

        // return;
        if (!isset($_GET['securimage'])) {
            return;
        }

        $e = error_get_last();

        $img = new Securimage(array(
            'image_height' => 45,
            'image_width' => 45 * M_E,
            'perturbation' => 0,
            'num_lines' => 3,
            'session_name' => cRegistry::getClientId() . 'frontend'
        ));

        $img->show();

    }

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

}

// define plugin path
$cfg['plugins'][Pifa::getName()] = $cfg['path']['contenido'] . 'plugins/' . Pifa::getName() . DIRECTORY_SEPARATOR;

// define template names
// $cfg['templates']['form_left_bottom'] = $cfg['plugins']['form'] .
// 'templates/template.left_bottom.html';
$cfg['templates']['pifa_right_bottom_form'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_form.tpl';
$cfg['templates']['pifa_right_bottom_fields'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_fields.tpl';
$cfg['templates']['pifa_right_bottom_data'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.right_bottom_data.tpl';
$cfg['templates']['pifa_ajax_field_form'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.ajax_field_form.tpl';
$cfg['templates']['pifa_ajax_field_row'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.ajax_field_row.tpl';
$cfg['templates']['pifa_ajax_option_row'] = $cfg['plugins'][Pifa::getName()] . 'templates/template.ajax_option_row.tpl';

// define table names
$cfg['tab']['pifa_form'] = $cfg['sql']['sqlprefix'] . '_pifa_form';
$cfg['tab']['pifa_field'] = $cfg['sql']['sqlprefix'] . '_pifa_field';

// include CONTENIDO classes
cInclude('classes', 'class.ui.php');

// include plugin classes
plugin_include(Pifa::getName(), 'classes/class.pifa.gui.php');
plugin_include(Pifa::getName(), 'classes/class.pifa.form.php');
plugin_include(Pifa::getName(), 'classes/class.pifa.field.php');
plugin_include(Pifa::getName(), 'classes/class.pifa.form_post_helper.php');
plugin_include(Pifa::getName(), 'classes/class.pifa.ajax_handler.php');
plugin_include(Pifa::getName(), 'classes/class.pifa.exceptions.php');
plugin_include(Pifa::getName(), 'securimage/securimage.php');

// define chain functions
$cecRegistry = cRegistry::getCecRegistry();
$cecRegistry->addChainFunction('Contenido.Frontend.AfterLoadPlugins', 'Pifa::afterLoadPlugins');

?>