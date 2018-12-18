<?php

/**
 *
 * @package Plugin
 * @subpackage SearchSolr
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
class Solr {

    /**
     * name of this plugin
     *
     * @var string
     */
    private static $_name = 'search_solr';

    /**
     *
     * @param mixed $whatever
     */
    public static function log($whatever, $file = NULL, $line = NULL) {
        $msg = '';
        if ($whatever instanceof Exception) {
            $msg .= '========================' . PHP_EOL;
            $msg .= '=== LOGGED EXCEPTION ===' . PHP_EOL;
            $msg .= 'REFERER: ' . $_SERVER['HTTP_REFERER'] . PHP_EOL;
            $msg .= 'URI: ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
            $msg .= 'MSG: ' . $whatever->getMessage() . PHP_EOL;
            $msg .= 'TRACE: ' . $whatever->getTraceAsString() . PHP_EOL;
            $msg .= '========================';
        } else {
            $msg = $whatever;
        }

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
        $log = new cLog(cLogWriter::factory('file', array(
            'destination' => $filename
        )));
        $log->info($prefix . $whatever);
    }

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
     *
     * @param string $key
     * @return string
     */
    public static function i18n($key) {
        $trans = i18n($key, self::$_name);
        return $trans;
    }

    /**
     * Returns array of options used to create a SolClient object.
     *
     * The option values are read from system or client settings.
     * Required settings are solr/hostname, solr/port, solr/path.
     *
     * @return array
     */
    public static function getClientOptions($idclient, $idlang) {

        //$queryOption = 'getSystemProperty';
        $queryOption = 'getEffectiveSetting';

        $options = array();

        // Boolean value indicating whether or not to connect in secure mode.
        $options['secure'] = (bool) $queryOption('solr', 'secure');

        // Required. The hostname for the Solr server.
        $options['hostname'] = $queryOption('solr', 'hostname');

        // Required. The port number.
        $options['port'] = $queryOption('solr', 'port');

        // Required. The path to solr.
        $options['path'] = $queryOption('solr', 'path');

        // load path from clientLanguage, client or system
        $clientLanguage = new cApiClientLanguage();
        $clientLanguage->loadByMany(array(
            'idclient' => $idclient,
            'idlang' => $idlang
        ));
        $value = $clientLanguage->isLoaded() ? $clientLanguage->getProperty($type, $name) : false;
        if (false === $value) {
            $client = new cApiClient($idclient);
            $value = $client->isLoaded() ? $client->getProperty($type, $name) : false;
        }
        if (false === $value) {
            $value = getSystemProperty($type, $name);
        }

        // The name of the response writer e.g. xml, phpnative.
        $options['wt'] = $queryOption('solr', 'wt');

        // Required. The username used for HTTP Authentication, if any.
        $options['login'] = $queryOption('solr', 'login');

        // Required. The HTTP Authentication password.
        $options['password'] = $queryOption('solr', 'password');

        // The hostname for the proxy server, if any.
        $options['proxy_host'] = $queryOption('solr', 'proxy_host');

        // The proxy port.
        $options['proxy_port'] = $queryOption('solr', 'proxy_port');

        // The proxy username.
        $options['proxy_login'] = $queryOption('solr', 'proxy_login');

        // The proxy password.
        $options['proxy_password'] = $queryOption('solr', 'proxy_password');

        // This is maximum time in seconds allowed for the http data transfer
        // operation. Default is 30 seconds.
        $options['timeout'] = $queryOption('solr', 'timeout');

        // File name to a PEM-formatted file containing the private key +
        // private certificate (concatenated in that order).
        // Please note the if the ssl_cert file only contains the private
        // certificate, you have to specify a separate ssl_key file.
        $options['ssl_cert'] = $queryOption('solr', 'ssl_cert');

        // File name to a PEM-formatted private key file only.
        $options['ssl_key'] = $queryOption('solr', 'ssl_key');

        // Password for private key.
        // The ssl_keypassword option is required if the ssl_cert or ssl_key
        // options are set.
        $options['ssl_keypassword'] = $queryOption('solr', 'ssl_keypassword');

        // Name of file holding one or more CA certificates to verify peer with.
        $options['ssl_cainfo'] = $queryOption('solr', 'ssl_cainfo');

        // Name of directory holding multiple CA certificates to verify peer
        // with.
        $options['ssl_capath'] = $queryOption('solr', 'ssl_capath');

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

// define template names
$cfg['templates']['solr_right_bottom'] = $cfg['plugins'][Solr::getName()] . 'templates/template.right_bottom.tpl';

// include necessary sources, setup autoloader for plugin
$pluginClassPath = 'contenido/plugins/' . Solr::getName() . '/';
cAutoload::addClassmapConfig(array(
    'SolrIndexer' => $pluginClassPath . 'classes/class.solr_indexer.php',
    'SolrSearcherAbstract' => $pluginClassPath . 'classes/class.solr_searcher_abstract.php',
    'SolrSearcherSimple' => $pluginClassPath . 'classes/class.solr_searcher_simple.php',
    'SolrSearchModule' => $pluginClassPath . 'classes/class.solr_search_module.php',
    'SolrRightBottomPage' => $pluginClassPath . 'classes/class.solr.gui.php',
    'SolrException' => $pluginClassPath . 'classes/class.solr_exception.php',
    'SolrWarning' => $pluginClassPath . 'classes/class.solr_warning.php'
));
unset($pluginClassPath);

// == add chain functions
// reindex article after article properties are updated
cRegistry::getCecRegistry()->addChainFunction('Contenido.Action.con_saveart.AfterCall', 'SolrIndexer::handleStoringOfArticle');
// reindex article after any content entry is updated
cRegistry::getCecRegistry()->addChainFunction('Contenido.Content.AfterStore', 'SolrIndexer::handleStoringOfContentEntry');
