<?php

/**
 *
 * @package    Plugin
 * @subpackage SIWECOS
 * @author     Fulai Zhang <fulai.zhang@4fb.de>
 * @copyright  four for business AG
 * @link       http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * SIWECOS form item collection class.
 * It's a kind of a model.
 *
 * @author Fulai Zhang <fulai.zhang@4fb.de>
 */
class SIWECOSCollection extends ItemCollection
{
    /**
     * SIWECOSCollection constructor.
     *
     * @param bool $where
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($where = false)
    {
        parent::__construct('con_pi_siwecos', 'idsiwecos');
        $this->_setItemClass('SIWECOS');
        if (false !== $where) {
            $this->select($where);
        }
    }

    /**
     * Get forms of given client in given language.
     *
     * @param $client
     * @param $lang
     *
     * @return array
     * @throws SIWECOSException
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function getByClientAndLang($client, $lang)
    {
        if (0 >= cSecurity::toInteger($client)) {
            $msg = SIWECOS::i18n('MISSING_CLIENT');
            throw new SIWECOSException($msg);
        }

        if (0 >= cSecurity::toInteger($lang)) {
            $msg = SIWECOS::i18n('MISSING_LANG');
            throw new SIWECOSException($msg);
        }

        return self::_getBy($client, $lang);
    }

    /**
     * Get forms according to given params.
     *
     * @param $client
     * @param $lang
     *
     * @return array
     * @throws cDbException
     */
    private static function _getBy($client, $lang)
    {
        global $idsiwecos;

        $db    = cRegistry::getDb();
        $forms = [];
        if ($idsiwecos) {
            $str = " idsiwecos = " . $idsiwecos . " AND ";
        }
        // update ranks of younger siblings
        $sql = "SELECT * FROM `con_pi_siwecos`
            WHERE " . $str . "
                idclient = " . cSecurity::toInteger($client) . "
                AND idlang = " . cSecurity::toInteger($lang) . "
            ;";
        $db->query($sql);
        while ($db->nextRecord()) {
            $forms[$db->f('idsiwecos')]['idsiwecos']   = $db->f('idsiwecos');
            $forms[$db->f('idsiwecos')]['domain']      = $db->f('domain');
            $forms[$db->f('idsiwecos')]['email']       = $db->f('email');
            $forms[$db->f('idsiwecos')]['userToken']   = $db->f('userToken');
            $forms[$db->f('idsiwecos')]['domainToken'] = $db->f('domainToken');
            $forms[$db->f('idsiwecos')]['dangerLevel'] = $db->f('dangerLevel');
            $forms[$db->f('idsiwecos')]['author']      = $db->f('author');
            $forms[$db->f('idsiwecos')]['created']     = $db->f('created');
        }

        return $forms;
    }
}

/**
 * Class SIWECOS
 */
class SIWECOS extends Item
{
    /**
     * name of this plugin
     *
     * @var string
     */
    private static $_name = 'siwecos';

    /**
     * SIWECOS constructor.
     *
     * @param bool $id
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct($id = false)
    {
        $cfg = cRegistry::getConfig();
        parent::__construct('con_pi_siwecos', 'idsiwecos');
        $this->setFilters([], []);
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * get pluginsname
     *
     * @return string
     */
    public static function getName()
    {
        return self::$_name;
    }

    /**
     * translate
     *
     * @param $key
     *
     * @return string
     * @throws cException
     */
    public static function i18n($key)
    {
        $trans = i18n($key, self::$_name);

        return $trans;
    }

    /**
     * @param Exception $e
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public static function logException(Exception $e)
    {
        if (getSystemProperty('debug', 'debug_for_plugins') == 'true') {
            $cfg = cRegistry::getConfig();

            $log = new cLog(cLogWriter::factory('file', [
                'destination' => $cfg['path']['contenido_logs'] . 'errorlog.txt',
            ]), cLog::ERR);

            $log->err($e->getMessage());
            $log->err($e->getTraceAsString());
        }
    }

    /**
     * Creates a notification widget in order to display an exception message in
     * backend.
     *
     * @param Exception $e
     *
     * @return string
     */
    public static function notifyException(Exception $e)
    {
        $cGuiNotification = new cGuiNotification();
        $level            = cGuiNotification::LEVEL_ERROR;
        $message          = $e->getMessage();

        return $cGuiNotification->returnNotification($level, $message);
    }

    /**
     * Deletes this form with all its fields and stored data.
     * The forms data table is also dropped.
     */
    public function delete()
    {
        global $idsiwecos;
        $cfg = cRegistry::getConfig();
        $db  = cRegistry::getDb();

        // delete form
        $sql = "DELETE FROM con_pi_siwecos
            WHERE
                idsiwecos = " . cSecurity::toInteger($idsiwecos) . "
            ;";
        if (false === $db->query($sql)) {
            $msg = SIWECOS::i18n('FORM_DELETE_ERROR');
            throw new SIWECOSException($msg);
        }
    }
}

/**
 * Base class for all SIWECOS related exceptions.
 *
 * @author fulai zhang <fulai.zhang@4fb.de>
 */
class SIWECOSException extends cException
{
}
