<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Creates a page to be displayed in the right bottom frame.
 *
 * secure
 * hostname
 * port
 * path
 * wt
 * login
 * password
 * proxy_host
 * proxy_port
 * proxy_login
 * proxy_password
 * timeout
 * ssl_cert
 * ssl_key
 * ssl_keypassword
 * ssl_cainfo
 * ssl_capath
 *
 * @author marcus.gnass
 */
class SolrRightBottomPage extends cGuiPage {

    /**
     *
     * @var array
     */
    private $_clientOptions;

    /**
     * @global string $action to be performed
     */
    public function __construct() {

        global $action;

        parent::__construct('right_bottom', Solr::getName());

        $this->addStyle('smoothness/jquery-ui-1.8.20.custom.css');
        $this->addStyle('right_bottom.css');
        $this->addScript('right_bottom.js');

        $this->set('s', 'CONTENIDO', cRegistry::getBackendSessionId());

        $this->set('s', 'I18N_CLIENT_OPTIONS', Solr::i18n('CLIENT_OPTIONS'));
        $this->set('s', 'I18N_OPTION', Solr::i18n('OPTION'));
        $this->set('s', 'I18N_VALUE', Solr::i18n('VALUE'));
        $this->set('s', 'I18N_DESCRIPTION', Solr::i18n('DESCRIPTION'));
        $this->set('s', 'I18N_DESCR_HOSTNAME', Solr::i18n('DESCR_HOSTNAME'));
        $this->set('s', 'I18N_DESCR_PORT', Solr::i18n('DESCR_PORT'));
        $this->set('s', 'I18N_DESCR_PATH', Solr::i18n('DESCR_PATH'));
        $this->set('s', 'I18N_DESCR_LOGIN', Solr::i18n('DESCR_LOGIN'));
        $this->set('s', 'I18N_DESCR_PASSWORD', Solr::i18n('DESCR_PASSWORD'));
        $this->set('s', 'I18N_DESCR_SECURE', Solr::i18n('DESCR_SECURE'));
        $this->set('s', 'I18N_DESCR_TIMEOUT', Solr::i18n('DESCR_TIMEOUT'));
        $this->set('s', 'I18N_DESCR_WT', Solr::i18n('DESCR_WT'));
        $this->set('s', 'I18N_DESCR_PROXY_HOST', Solr::i18n('DESCR_PROXY_HOST'));
        $this->set('s', 'I18N_DESCR_PROXY_PORT', Solr::i18n('DESCR_PROXY_PORT'));
        $this->set('s', 'I18N_DESCR_PROXY_LOGIN', Solr::i18n('DESCR_PROXY_LOGIN'));
        $this->set('s', 'I18N_DESCR_PROXY_PASSWORD', Solr::i18n('DESCR_PROXY_PASSWORD'));
        $this->set('s', 'I18N_DESCR_SSL_CERT', Solr::i18n('DESCR_SSL_CERT'));
        $this->set('s', 'I18N_DESCR_SSL_KEY', Solr::i18n('DESCR_SSL_KEY'));
        $this->set('s', 'I18N_DESCR_SSL_KEYPASSWORD', Solr::i18n('DESCR_SSL_KEYPASSWORD'));
        $this->set('s', 'I18N_DESCR_SSL_CAINFO', Solr::i18n('DESCR_SSL_CAINFO'));
        $this->set('s', 'I18N_DESCR_SSL_CAPATH', Solr::i18n('DESCR_SSL_CAPATH'));
        $this->set('s', 'I18N_DESCR_RELOAD', Solr::i18n('DESCR_RELOAD'));
        $this->set('s', 'I18N_DESCR_REINDEX', Solr::i18n('DESCR_REINDEX'));
        $this->set('s', 'I18N_DESCR_DELETE', Solr::i18n('DESCR_DELETE'));

        // get client options
        $idclient = cRegistry::getClientId();
        $idlang = cRegistry::getLanguageId();
        $this->_clientOptions = Solr::getClientOptions($idclient, $idlang);
        $this->set('s', 'HOSTNAME', $this->_clientOptions['hostname']);
        $this->set('s', 'PORT', $this->_clientOptions['port']);
        $this->set('s', 'PATH', $this->_clientOptions['path']);
        $this->set('s', 'LOGIN', $this->_clientOptions['login']);
        $this->set('s', 'PASSWORD', $this->_clientOptions['password']);
        $this->set('s', 'SECURE', 'true' == $this->_clientOptions['secure'] ? 'checked="checked"' : '');
        $this->set('s', 'TIMEOUT', $this->_clientOptions['timeout']);
        $this->set('s', 'WT', $this->_clientOptions['wt']);
        $this->set('s', 'PROXY_HOST', $this->_clientOptions['proxy_host']);
        $this->set('s', 'PROXY_PORT', $this->_clientOptions['proxy_port']);
        $this->set('s', 'PROXY_LOGIN', $this->_clientOptions['proxy_login']);
        $this->set('s', 'PROXY_PASSWORD', $this->_clientOptions['proxy_password']);
        $this->set('s', 'SSL_CERT', $this->_clientOptions['ssl_cert']);
        $this->set('s', 'SSL_KEY', $this->_clientOptions['ssl_key']);
        $this->set('s', 'SSL_KEYPASSWORD', $this->_clientOptions['ssl_keypassword']);
        $this->set('s', 'SSL_CAINFO', $this->_clientOptions['ssl_cainfo']);
        $this->set('s', 'SSL_CAPATH', $this->_clientOptions['ssl_capath']);

        // dispatch action
        try {
            $this->_dispatch($action);

            // actions will be disabled if any required client option is missing
            try {
                Solr::validateClientOptions($this->_clientOptions);
                $validClientOptions = true;
            } catch (SolrWarning $e) {
                $validClientOptions = false;
            }
            $this->set('s', 'DISABLED_RELOAD', $validClientOptions ? '' : 'disabled="disabled"');
            $this->set('s', 'DISABLED_REINDEX', $validClientOptions ? '' : 'disabled="disabled"');
            $this->set('s', 'DISABLED_DELETE', $validClientOptions ? '' : 'disabled="disabled"');

        } catch (InvalidArgumentException $e) {
            $cGuiNotification = new cGuiNotification();
            $notification = $cGuiNotification->returnNotification(cGuiNotification::LEVEL_ERROR, $e->getMessage());
            $this->set('s', 'notification', $notification);
        }
    }

    /**
     * Dispatches the given action.
     *
     * @param string $action to be executed
     * @throws InvalidArgumentException if the given action is unknown
     */
    protected function _dispatch($action) {
        global $area;

        // check for permission
        $perm = cRegistry::getPerm();
        if (!$perm->have_perm_area_action($area, $action)) {
            throw new IllegalStateException('no permissions');
        }

        if (NULL === $action) {
            $this->set('s', 'notification', '');
            $this->set('s', 'content', '');
            return;
        }

        // dispatch action
        try {
            switch ($action) {
                case 'store_client_options':
                    $this->set('s', 'notification', $this->_storeClientOptions());
                    break;
                case 'reload':
                    $this->set('s', 'notification', $this->_reload());
                    break;
                case 'reindex':
                    $this->set('s', 'notification', $this->_reindex());
                    break;
                case 'delete':
                    $this->set('s', 'notification', $this->_delete());
                    break;
                default:
                    throw new InvalidArgumentException('unknown action ' . $action);
            }
        } catch (Exception $e) {
            $notification = Solr::notifyException($e);
        }
    }

    /**
     *
     * @return string
     */
    private function _storeClientOptions() {
        $settings = 'secure,hostname,port,path,wt,login,password,timeout,';
        $settings .= 'proxy_host,proxy_port,proxy_login,proxy_password,';
        $settings .= 'ssl_cert,ssl_key,ssl_keypassword,ssl_cainfo,ssl_capath';
        foreach (explode(',', $settings) as $setting) {
            $value = $_POST[$setting];
            if (0 < strlen(trim($value))) {
                setSystemProperty('solr', $setting, $value);
            } else {
                // Solr system properties w/o values are not stored to prevent
                // property pollution
                deleteSystemProperty('solr', $setting);
            }
        }
        $cGuiNotification = new cGuiNotification();
        return $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, 'client options were stored');
    }

    /**
     * Call the relaod action.
     *
     * @return string
     */
    private function _reload() {

        // build URL
        // @see https://en.wikipedia.org/wiki/Basic_access_authentication
        $url = 'http://';
        $url .= $this->_clientOptions['login'] . ':' . $this->_clientOptions['password'] . '@';
        $url .= $this->_clientOptions['hostname'] . ':' . $this->_clientOptions['port'];
        $url .= '/solr/admin/cores?' . http_build_query(array(
                    'action' => 'RELOAD',
                    'core' => array_pop(explode('/', $this->_clientOptions['path']))
        ));

        // create curl resource
        $ch = curl_init();

        $data = false;
        if (false !== $ch) {

            $opt = array(
                // set url
                CURLOPT_URL => $url,
                // TRUE to reset the HTTP request method to GET.
                CURLOPT_HTTPGET => true,
                // The contents of the "User-Agent: " header to be used
                // in a HTTP request.
                CURLOPT_USERAGENT => 'CONTENIDO Solr Plugin v1.0',
                // The maximum number of milliseconds to allow cURL
                // functions to execute.
                CURLOPT_TIMEOUT_MS => 5000,
                // The number of milliseconds to wait while trying to
                // connect.
                CURLOPT_CONNECTTIMEOUT_MS => 5000,
                // return the transfer as a string
                CURLOPT_RETURNTRANSFER => 1,
                // TRUE to include the header in the output.
                CURLOPT_HEADER => false
            );

            curl_setopt_array($ch, $opt);

            // $data contains the output string
            $data = curl_exec($ch);

            // get curl info
            $curlInfo = curl_getinfo($ch);

            // close curl resource to free up system resources
            curl_close($ch);

            if (200 !== (int) $curlInfo['http_code']) {
                $msg = 'HTTP status code ' . $curlInfo['http_code'];
                // may contain an error message
                // $msg .= '\ndata: ' . $sData;
                $msg .= "\n(complete cUrl-Info:";
                foreach ($curlInfo as $key => $value) {
                    $msg .= "\n\t" . $key . ' => ' . $value;
                }
                $msg .= "\n)";

                throw new cException($msg);
            }
        }

        if (false === $data) {
            throw new cException('server did not answer');
        }

        $cGuiNotification = new cGuiNotification();
        return $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, 'core was reloaded');
    }

    /**
     *
     * @return string
     */
    private function _reindex() {
        $cfg = cRegistry::getConfig();

        $idclient = cRegistry::getClientId();
        $idclient = cSecurity::toInteger($idclient);

        $idlang = cRegistry::getLanguageId();
        $idlang = cSecurity::toInteger($idlang);

        // statement is not correct if articles are related to more than one category.
        $db = cRegistry::getDb();
        $db->query("-- SolrRightBottomPage->_reindex()
            SELECT
                art.idclient
                , art_lang.idlang
                , cat_art.idcat
                , cat_lang.idcatlang
                , art_lang.idart
                , art_lang.idartlang
            FROM
                `{$cfg['tab']['art_lang']}` AS art_lang
            INNER JOIN
                `{$cfg['tab']['art']}` AS art
            ON
                art_lang.idart = art.idart
            INNER JOIN
                `{$cfg['tab']['cat_art']}` AS cat_art
            ON
                art_lang.idart = cat_art.idart
            INNER JOIN
                `{$cfg['tab']['cat_lang']}` AS cat_lang
            ON
                cat_art.idcat = cat_lang.idcat
                AND art_lang.idlang = cat_lang.idlang
            WHERE
                art.idclient = $idclient
                -- AND art_lang.idlang = $idlang
            ORDER BY
                art_lang.idartlang
            ;");

        $articleIds = array();
        while ($db->nextRecord()) {
            array_push($articleIds, array(
                'idclient' => $db->f('idclient'),
                'idlang' => $db->f('idlang'),
                'idcat' => $db->f('idcat'),
                'idcatlang' => $db->f('idcatlang'),
                'idart' => $db->f('idart'),
                'idartlang' => $db->f('idartlang')
            ));
        }

        $indexer = new SolrIndexer($articleIds);
        $indexer->updateArticles();

        $cGuiNotification = new cGuiNotification();
        return $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, 'core was reindexed');
    }

    /**
     *
     * @return string
     */
    private function _delete() {

        $cfg = cRegistry::getConfig();

        // statement is not correct if articles are related to more than one category.
        $db = cRegistry::getDb();
        $db->query("-- SolrRightBottomPage->_delete()
            SELECT
                art.idclient
                , art_lang.idlang
                , cat_art.idcat
                , cat_lang.idcatlang
                , art_lang.idart
                , art_lang.idartlang
            FROM
                `{$cfg['tab']['art_lang']}` AS art_lang
            INNER JOIN
                `{$cfg['tab']['art']}` AS art
            ON
                art_lang.idart = art.idart
            INNER JOIN
                `{$cfg['tab']['cat_art']}` AS cat_art
            ON
                art_lang.idart = cat_art.idart
            INNER JOIN
                `{$cfg['tab']['cat_lang']}` AS cat_lang
            ON
                cat_art.idcat = cat_lang.idcat
                AND art_lang.idlang = cat_lang.idlang
            ORDER BY
                art_lang.idartlang
            ;");

        $articleIds = array();
        while ($db->nextRecord()) {
            array_push($articleIds, array(
                'idclient' => $db->f('idclient'),
                'idlang' => $db->f('idlang'),
                'idcat' => $db->f('idcat'),
                'idcatlang' => $db->f('idcatlang'),
                'idart' => $db->f('idart'),
                'idartlang' => $db->f('idartlang')
            ));
        }

        $indexer = new SolrIndexer($articleIds);
        $indexer->deleteArticles();

        $cGuiNotification = new cGuiNotification();
        return $cGuiNotification->returnNotification(cGuiNotification::LEVEL_INFO, 'core was deleted');
    }

}
