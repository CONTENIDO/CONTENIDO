<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Abstract implementation of Contenido_UrlBuilder.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2008-02-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


abstract class Contenido_UrlBuilder
{
    /**
     * Holds final value of built URL
     * @var string
     */
    protected $sUrl; // needed in this context

    /**
     * Holds URL that is used as base for an absolute path, e.g. http://contenido.org/
     * @var string
     */
    protected $sHttpBasePath; // needed in this context

    /**
     * Implementation of Singleton.
     * It is meant to be an abstract function but not declared as abstract,
     * because PHP Strict Standards are against abstract static functions.
     * @throws  Exception  If child class has not implemented this function
     */
    public static function getInstance()
    {
        throw new Exception("Child class has to implement this function");
    }

    /**
     * Set http base path, e.g. http://contenido.org/
     * @return void
     */
    public function setHttpBasePath($sBasePath)
    {
        $this->sHttpBasePath = (string) $sBasePath;
    }

    /**
     * Return http base path, e.g. http://contenido.org/
     * @return  string
     */
    public function getHttpBasePath()
    {
        return $this->sHttpBasePath;
    }

    /**
     * Builds a URL in index-a-1.html style.
     * Index keys of $aParams will be used as "a", corresponding values as "1" in this sample.
     *
     * @param  array   $aParams
     * @param  bool    $bUseAbsolutePath
     * @param  string  $sSeparator
     * @return void
     * @throws InvalidArgumentException
     */
    abstract public function buildUrl(array $aParams, $bUseAbsolutePath = false);

    /**
     * Return built URL
     * @return string
     */
    public function getUrl()
    {
        return (string) $this->sUrl;
    }
}

?>