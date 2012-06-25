<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Template Config Object
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.2
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2009-12-16, Dominik Ziegler, fixed sql table name
 *
 *   $Id$:
 * }}
 *
 */

/**
 * Object of a CONTENIDO template configuration
 *
 * Class to get template configurations for the current article or the current category
 *
 * Example (article configuration):
 *
 * $tplCfg = new templateConfig($idart); //get template configuration for current article
 * $exampleAr = $tplCfg->getData(20); //get all informations for container 20 for this template
 *
 * Example (category configuration):
 * $tplCfg = new templateConfig(); //leave empty cause you only want a category configuration
 * $tplCfg->getDataForIdcat ($idcat);
 * $exampleAr = $tplCfg->getData(20); //get all informations for container 20 for this template
 *
 * $exampleAr:
 * $exampleAr[0] => ""
 *
 * "0" specifies the CMS_VALUE set in modules
 * "" contains the configuration data
 *
 * @author Marco Jahn <marco.jahn@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 * @package Contenido_API
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class TemplateConfig
{
    /**
     * stores configuration data
     * @var array
     */
    var $data = array();

    /**
     * database object
     * @var object
     */
    var $db;

    /**
     * global config variables
     * @var array
     */
    var $cfg;

    /**
     * language id
     * @var int
     */
    var $lang;

    /**
     * client id
     * @var int
     */
    var $client;

    /**
     * constructor
     *
     * @return void
     */
    function templateConfig($idart = 0)
    {
        global $cfg, $lang, $client;

        $this->db = new DB_Contenido;

        $this->cfg = &$cfg;
        $this->lang = &$lang;
        $this->client = &$client;
        $this->idart = $idart;

        if ($idart != 0)
        {
            $idtplcfg = $this->_getTplCfgByArtId($idart);
            $this->data = $this->_getContainersByTplCfg($idtplcfg);
        }
    }

    /**
    * reset data array
    *
    * @return void
    */
    function resetData()
    {
        unset($this->data);
    }

    /**
     * get cms_values
     * returns false if no configuration was found
     *
     * @param integer $idcontainer id for the container which settings should be returned
     *
     * @return array array with the settings for each cms_value of the specified container
     */
    function getData ($idcontainer)
    {
        if ($this->data[$idcontainer])
        {
            $tmpVar = explode("&",trim($this->data[$idcontainer],"&"));
            foreach ($tmpVar as $string)
            {
                $tmpData = explode("=", $string);
                $tmpArray[$tmpData[0]] = urldecode($tmpData[1]);
            }
            return $tmpArray;
        }
        return false;
    }

    /**
     * get data
     */
    function getDataForIdcat ($idcat)
    {
        $idtplcfg = $this->_getTplCfgByCatId($idcat);
        $this->data = $this->_getContainersByTplCfg($idtplcfg);
    }

    /**
     * get values from template pre configuration
     * returns false if no pre configuration values were found
     *
     * @param integer $idart id of the article
     * @param integer $containerid id for the container of which the settings should be returned
     *
     * @return array containing pre configuration values
     */

    function getPreConfigurationValues ($idart,$containerid) {

        global $cfg;

        $idtplcfg = $this->_getTplCfgByArtId($idart);
        if ((!$idtplcfg) || ($idtplcfg==0)) {
            $idcat = $this->_getIdCatByIdArt($idart);
            $idtplcfg = $this->_getTplCfgByCatId($idcat);
        }

        if ($idtplcfg) {
            #Article or cat is assigned to a template
            $sql = "SELECT * FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
            $this->db->query($sql);
            if ($this->db->next_record()) {
                $idtpl = $this->db->f("idtpl");
                $sql = "SELECT * FROM ".$cfg["tab"]["tpl"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";
                $this->db->query($sql);
                if ($this->db->next_record()) {
                    $idtplcfg = $this->db->f("idtplcfg");
                    $this->_getContainersByTplCfg($idtplcfg);
                    $arrData = $this->getData ($containerid);
                    return $arrData;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
    * get template config id by article id
    * returns false if the article has no configuration
    *
    * returns the template configuration for the current article
    * if the article has not a template configuration it will return the configuration
    * for the current category
    *
    * @param integer $idart id of the article which configuration should be get
    *
    * @return string returns the template configuration
    */
   function _getTplCfgByArtId($idart)
   {
      $sql = "
         SELECT
            idtplcfg
         FROM ".$this->cfg['tab']['art_lang']."
            WHERE
                  idart='".Contenido_Security::toInteger($idart)."'
               AND
                  idlang='".Contenido_Security::toInteger($this->lang)."'";

      //query
      $this->db->query($sql);

      if (!$this->db->next_record())
      {
         return false;
      }

      if ($this->db->f("idtplcfg") != 0)
      {
         return $this->db->f("idtplcfg");
      }
      else
      {
         $idcat = $this->_getIdCatByIdArt($idart);
         return $this->_getTplCfgByCatId($idcat);
      }

   }

    /**
     * get category id by article id
     *
     * returns false if the specified article doesn't belong to a category
     *
     * @param integer $idart id of the current article
     *
     * @return int returns the idcat for the current article
     */

    function _getIdCatByIdArt($idart)
    {
        $sql = "SELECT idcat FROM ".$this->cfg['tab']['cat_art']." WHERE idart='".Contenido_Security::toInteger($idart)."' ORDER BY idcat ASC LIMIT 1";
        $this->db->query($sql);
        if ($this->db->next_record())
        {
            return $this->db->f("idcat");
        }
        return false;
    }

    /**
     * get template config id by category id
     * returns false if the category specified has not a template configuration
     *
     * @param integer $idcat id of the category which template config should be read out
     *
     * @return string template configuration for the selected category
     */
    function _getTplCfgByCatId ($idcat)
    {
        $sql = "SELECT idtplcfg FROM ".$this->cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'
                AND idlang='".Contenido_Security::toInteger($this->lang)."'";
        $this->db->query($sql);
        if ($this->db->next_record())
        {
            return $this->db->f("idtplcfg");
        }
        return false;
    }

    /**
     * get containers for a specified template configuration
     * returns false if no configuratin was found
     *
     * @param integer $idtplcfg id of the template
     *
     * @return array array with all containers and their values
     */
    function _getContainersByTplCfg($idtplcfg)
    {
        $sql = "
            SELECT
                number, container
            FROM ".$this->cfg['tab']['container_conf']."
                WHERE idtplcfg='".Contenido_Security::toInteger($idtplcfg)."'
            ORDER BY
                number ASC";
        $this->db->query($sql);

        if ($this->db->nf() == 0)
        { //nothing found
            return false;
        }
        else
        {
            //get all results
            while ($this->db->next_record())
            {
                $this->data[$this->db->f("number")] = $this->db->f("container");
            }

            //return data array
            return $this->data;
        }
    }
}

?>