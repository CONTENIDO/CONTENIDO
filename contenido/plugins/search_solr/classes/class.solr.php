<?php

/**
 *
 * @package Plugin
 * @subpackage SearchSolr
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Helper class for this plugin.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class Solr {

    /**
     * name of this plugin
     *
     * @var string
     */
    private static $_name = 'search_solr';

    /**
     * @param mixed $whatever
     * @param null  $file
     * @param null  $line
     *
     * @throws cInvalidArgumentException
     */
    public static function log($whatever, $file = NULL, $line = NULL) {
        static $start = 0;
        if (0 == $start) {
            $start = microtime(true);
        }
        $delta = microtime(true) - $start;

        // create name of logfile
        $cfg = cRegistry::getConfig();
        $filename = $cfg['path']['contenido_logs'] . 'errorlog.txt';

        // extend message with optional prefix
        $prefix = number_format($delta * 1000, 0) . 'ms: ';
        if (NULL !== $file) {
            $prefix .= $file;
            if (NULL !== $line) {
                $prefix .= ':' . $line;
            }
            $prefix .= ' ';
        }

        // log message
        $log = new cLog(cLogWriter::factory('file', [
            'destination' => $filename
        ]));
        $log->info($prefix . $whatever);
    }

    /**
     * @param Exception $e
     *
     * @throws cInvalidArgumentException
     */
    public static function logException(Exception $e) {
        $cfg = cRegistry::getConfig();

        $log = new cLog(cLogWriter::factory('file', [
            'destination' => $cfg['path']['contenido_logs'] . 'errorlog.txt'
        ]));

        $log->err($e->getMessage());
        $log->err($e->getTraceAsString());
    }

    /**
     * Return the plugin name.
     *
     * @return string
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
     *
     * @param string $key
     * @return string
     */
    public static function i18n($key) {
        try {
            $trans = i18n($key, self::$_name);
        } catch (cException $e) {
            $trans = $key;
        }

        return $trans;
    }

    /**
     * Returns array of options used to create a SolClient object.
     *
     * The option values are read from system or client settings.
     * Required settings are solr/hostname, solr/port, solr/path.
     *
     * @param $idclient
     * @param $idlang
     *
     * @return array
     * @throws cDbException
     * @throws cException
     */
    public static function getClientOptions($idclient, $idlang) {
        $options = [];

        // Boolean value indicating whether to connect in secure mode.
        $options['secure'] = (bool)cEffectiveSetting::get('solr', 'secure');

        // Required. The hostname for the Solr server.
        $options['hostname'] = cEffectiveSetting::get('solr', 'hostname');

        // Required. The port number.
        $options['port'] = cEffectiveSetting::get('solr', 'port');

        // Required. The path to solr.
        $options['path'] = cEffectiveSetting::get('solr', 'path');

        // The name of the response writer e.g. xml, phpnative.
        $options['wt'] = cEffectiveSetting::get('solr', 'wt');

        // Required. The username used for HTTP Authentication, if any.
        $options['login'] = cEffectiveSetting::get('solr', 'login');

        // Required. The HTTP Authentication password.
        $options['password'] = cEffectiveSetting::get('solr', 'password');

        // The hostname for the proxy server, if any.
        $options['proxy_host'] = cEffectiveSetting::get('solr', 'proxy_host');

        // The proxy port.
        $options['proxy_port'] = cEffectiveSetting::get('solr', 'proxy_port');

        // The proxy username.
        $options['proxy_login'] = cEffectiveSetting::get('solr', 'proxy_login');

        // The proxy password.
        $options['proxy_password'] = cEffectiveSetting::get('solr', 'proxy_password');

        // This is maximum time in seconds allowed for the http data transfer
        // operation. Default is 30 seconds.
        $options['timeout'] = cEffectiveSetting::get('solr', 'timeout');

        // File name to a PEM-formatted file containing the private key +
        // private certificate (concatenated in that order).
        // Please note the if the ssl_cert file only contains the private
        // certificate, you have to specify a separate ssl_key file.
        $options['ssl_cert'] = cEffectiveSetting::get('solr', 'ssl_cert');

        // File name to a PEM-formatted private key file only.
        $options['ssl_key'] = cEffectiveSetting::get('solr', 'ssl_key');

        // Password for private key.
        // The ssl_keypassword option is required if the ssl_cert or ssl_key
        // options are set.
        $options['ssl_keypassword'] = cEffectiveSetting::get('solr', 'ssl_keypassword');

        // Name of file holding one or more CA certificates to verify peer with.
        $options['ssl_cainfo'] = cEffectiveSetting::get('solr', 'ssl_cainfo');

        // Name of directory holding multiple CA certificates to verify peer with.
        $options['ssl_capath'] = cEffectiveSetting::get('solr', 'ssl_capath');

        // remove unset options (TODO could be done via array_filter too)
        foreach ($options as $key => $value) {
            if (0 == cString::getStringLength(trim($value))) {
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * Check if required options exist.
     * Required settings are solr/hostname, solr/port, solr/path.
     *
     * @param array $options
     * @throws SolrWarning when required options don't exist
     */
    public static function validateClientOptions(array $options) {
        $valid = true;
        $valid &= array_key_exists('hostname', $options);
        $valid &= array_key_exists('port', $options);
        $valid &= array_key_exists('path', $options);

        // login & password are optional!
        // $valid &= array_key_exists('login', $options);
        // $valid &= array_key_exists('password', $options);

        if (!$valid) {
            throw new SolrWarning(Solr::i18n('WARNING_INVALID_CLIENT_OPTIONS'));
        }
    }

    /**
     * TODO build method to display error & info box and just call it from here
     *
     * @param Exception $e
     * @param bool $showTrace if trace should be displayed too
     */
    public static function displayException(Exception $e, $showTrace = false) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

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
}
