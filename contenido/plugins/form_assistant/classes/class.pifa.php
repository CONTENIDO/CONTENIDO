<?php

/**
 * This file the helper class for the plugin.
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Helper class for this plugin.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class Pifa
{

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
    public static function getName()
    {
        return self::$_name;
    }

    /**
     * Return path to this plugins folder.
     *
     * @return string
     */
    public static function getPath()
    {
        $cfg = cRegistry::getConfig();

        $path = cRegistry::getBackendPath() . $cfg['path']['plugins'];
        $path .= self::$_name . '/';

        return $path;
    }

    /**
     * Return URL to this plugins' folder.
     *
     * @return string
     */
    public static function getUrl()
    {
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
    public static function i18n($key)
    {
        $trans = i18n($key, self::$_name);
        return $trans;
    }

    /**
     *
     * @param string $level
     * @param string $note
     *
     * @return string
     */
    public static function getNote($level, $note)
    {
        $note = self::i18n($note);
        $notification = new cGuiNotification();
        return $notification->returnNotification($level, $note);
    }

    /**
     *
     * @param string $note
     *
     * @return string
     */
    public static function getError($note)
    {
        return self::getNote(cGuiNotification::LEVEL_ERROR, $note);
    }

    /**
     *
     * @param Exception $e
     *
     * @throws cInvalidArgumentException
     */
    public static function logException(Exception $e)
    {
        if (getSystemProperty('debug', 'debug_for_plugins') == 'true') {
            $cfg = cRegistry::getConfig();

            $log = new cLog(cLogWriter::factory('file', [
                'destination' => $cfg['path']['contenido_logs'] . 'errorlog.txt'
            ]));

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
    public static function displayException(Exception $e, $showTrace = false)
    {
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
    public static function notifyException(Exception $e)
    {
        $cGuiNotification = new cGuiNotification();
        $level = cGuiNotification::LEVEL_ERROR;
        $message = $e->getMessage();

        return $cGuiNotification->returnNotification($level, $message);
    }

    /**
     * Returns array of extension classes that subclass the given $parentClass.
     *
     * @param string $parentClass
     * @return array
     * @throws PifaException
     */
    public static function getExtensionClasses($parentClass)
    {
        // ignore if extensions folder is missing
        if (false === ($handle = cDirHandler::read(self::getPath() . 'extensions/'))) {
            return [];
        }

        $extensionClasses = [];
        foreach ($handle as $file) {
            // skip files that don't match regex
            $matches = [];
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

            $extensionClasses[] = [
                'value' => $optionClass,
                'label' => $optionClass
            ];
        }

        return $extensionClasses;
    }

    /**
     * Returns array of client templates that adhere to the naming
     * convention cms_pifaform_FOOBAR.tpl where FOOBAR is any character but a
     * dot.
     *
     * @param string $re
     *
     * @return array
     * @throws PifaException
     */
    public static function getTemplates($re = '/cms_pifaform_[^\.]+\.tpl/')
    {
        $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());

        // ignore if template folder is missing
        if (false === ($handle = cDirHandler::read($clientConfig['template']['path']))) {
            return [];
        }

        $templates = [];
        foreach ($handle as $file) {

            // skip folders
            if (true === is_dir($file)) {
                continue;
            }

            // skip files that don't match regex
            $matches = [];
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

            $templates[] = [
                'value' => $file,
                'label' => $file
            ];
        }

        return $templates;
    }

    /**
     * Translates a camel case string into a string with underscores
     * (e.g.
     * firstName -&gt; first_name)
     *
     * @see https://www.paulferrett.com/2009/php-camel-case-functions/
     * @param string $str String in camel case format
     * @return string $str Translated into underscore format
     */
    public static function fromCamelCase($str)
    {
        $str[0] = cString::toLowerCase($str[0]);
        return preg_replace_callback('/([A-Z])/', function ($c) {
            return '_' . cString::toLowerCase($c[1]);
        }, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g.
     * first_name -&gt; firstName)
     *
     * @see https://www.paulferrett.com/2009/php-camel-case-functions/
     * @param string $str String in underscore format
     * @param bool $capitalise_first_char If true, capitalise the first
     *        char in $str
     * @return string $str translated into camel caps
     */
    public static function toCamelCase($str, $capitalise_first_char = false)
    {
        if ($capitalise_first_char) {
            $str[0] = cString::toUpperCase($str[0]);
        }
        return preg_replace_callback('/_([a-z])/', function ($c) {
            return cString::toUpperCase($c[1]);
        }, $str);
    }

    /**
     * Translates a string with underscores into camel case (e.g.
     * first_name -&gt; firstName)
     *
     * @see https://www.paulferrett.com/2009/php-camel-case-functions/
     *
     * @param bool $force
     *
     * @return string $str translated into camel caps
     */
    public static function getTimestampSetting($force = false)
    {
        if (is_null(self::$_timestampSetting) || $force) {
            self::$_timestampSetting = getEffectiveSetting('pifa', 'timestamp', self::TIMESTAMP_ALWAYS);
            if (!in_array(self::$_timestampSetting, [
                self::TIMESTAMP_NEVER,
                self::TIMESTAMP_BYFORM,
                self::TIMESTAMP_ALWAYS
            ])) {
                self::$_timestampSetting = self::TIMESTAMP_ALWAYS;
            }
        }
        return self::$_timestampSetting;
    }

    /**
     * Determine if page is called via HTTPS.
     *
     * @return bool
     */
    public static function isHttps()
    {
        $isHttps = false;
        $isHttps |= 443 === $_SERVER['SERVER_PORT'];
        $isHttps |= array_key_exists('HTTP_X_SSL_CIPHER', $_SERVER);
        return $isHttps;
    }
}
