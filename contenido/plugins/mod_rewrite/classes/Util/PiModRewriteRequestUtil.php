<?php

declare(strict_types=1);

/**
 * AMR request utility class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      Advanced Mod Rewrite 2.1.0
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * AMR request utility class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteRequestUtil
{
    /**
     * A request cleanup function. Request data is always tainted and must be filtered.
     * Pass the array to clean up using several options.
     * Emulates array_walk_recursive().
     *
     * @param array|string|mixed $data Data to cleanup
     * @param ?array $options The default options array provides only the 'filter' key with several
     *      filter functions which are to execute as follows:
     *      <code>
     *      $options['filter'] = ['trim', 'myFilterFunc'];
     *      </code>
     *      If no filter functions are set, 'trim', 'strip_tags' and 'stripslashes'
     *      will be used by default.
     *      A user-defined function must accept the value as a parameter and must return
     *      the filtered parameter, e.g.
     * <code>
     * function myFilter($data) {
     *    // do what you want with the data, e.g., the cleanup of xss content
     *    return $data;
     * }
     * </code>
     * @return mixed Cleaned data
     */
    public static function cleanup(&$data, ?array $options = NULL)
    {
        if (!PiModRewriteUtil::arrayValue($options, 'filter')) {
            $options['filter'] = ['trim', 'strip_tags', 'stripslashes'];
        }

        if (is_array($data)) {
            foreach ($data as $p => $v) {
                $data[$p] = self::cleanup($v, $options);
            }
        } else {
            foreach ($options['filter'] as $filter) {
                if ($filter == 'trim') {
                    $data = trim($data);
                } elseif ($filter == 'strip_tags') {
                    $data = strip_tags($data);
                } elseif ($filter == 'stripslashes') {
                    $data = stripslashes($data);
                } elseif (function_exists($filter)) {
                    $data = call_user_func($filter, $data);
                }
            }
        }
        return $data;
    }

    /**
     * Minimalistic and simple way to get request variables.
     * Checks occurrence in $_GET, then in $_POST. Uses trim() and strip_tags() to pre clean data.
     *
     * @param string $key Name of var to get
     * @param mixed $default Default value to return
     * @return mixed The value
     */
    public static function getRequest(string $key, $default = NULL)
    {
        $cache = cRegistry::getAppVar('pluginModRewriteRequestCache', []);
        if (!isset($cache)) {
            $cache = [];
        }
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        if (isset($_GET[$key])) {
            $val = $_GET[$key];
        } elseif (isset($_POST[$key])) {
            $val = $_POST[$key];
        } else {
            $val = $default;
        }

        $cache[$key] = is_string($val) ? strip_tags(trim($val)) : '';
        cRegistry::setAppVar('pluginModRewriteRequestCache', $cache);

        return $cache[$key];
    }

    /**
     * Returns parameter from request, the order is:
     * - Return from $_GET, if found
     * - Return from $_POST, if found
     *
     * @param mixed $default The default value
     * @return mixed
     */
    public static function getRequestParam(string $key, $default = NULL)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } else {
            return $default;
        }
    }
}
