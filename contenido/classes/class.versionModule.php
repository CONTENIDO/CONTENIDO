<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Super class for revision
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.0
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release >= 4.8.8
 *
 * {@internal
 *   created 2008-08-12
 *   $Id$
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
 die('Illegal call');
}

 class VersionModule extends Version {
    /**
    * Type of modul
    * @access public
    */
    public $sModType;

   /**
    * The class versionStyle object constructor, initializes class variables
    *
    * @param string $iIdMod The name of style file
    * @param array  $aCfg
    * @param array  $aCfgClient
    * @param object $oDB
    * @param integer $iClient
    * @param object $sArea
    * @param object $iFrame
    *
    * @return void its only initialize class members
    */
     public function __construct($iIdMod, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame){
//         Set globals in main class
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);

//         folder layout
         $this->sType = "module";

         $this->iIdentity = $iIdMod;

        $this->prune();

         $this->initRevisions();

        $this->_storeModuleInformation();
     }

    protected function _storeModuleInformation() {
        $iIdMod = Contenido_Security::toInteger($this->iIdentity);

        $oModule = new cApiModule($iIdMod);

        // create body node of XML file
         $this->setData("Name",             $oModule->getField('name'));
         $this->setData("Type",            $oModule->getField('type'));
         $this->setData("Error",         $oModule->getField('error'));
         $this->setData("Description",     $oModule->getField('description'));
         $this->setData("Deletable",     $oModule->getField('deletable'));
         $this->setData("Template",         $oModule->getField('template'));
         $this->setData("Static",         $oModule->getField('static'));
         $this->setData("PackageGuid",     $oModule->getField('package_guid'));
         $this->setData("PackageData",     $oModule->getField('package_data'));

        // retrieve module code from files
        $oModuleHandler = new Contenido_Module_Handler($iIdMod);
        $this->setData("CodeOutput", $oModuleHandler->readOutput());
        $this->setData("CodeInput", $oModuleHandler->readInput());
    }

   /**
    * This function read an xml file nodes
    *
    * @param string $sPath Path to file
    *
    * @return array returns array width this four nodes
    */
    public function initXmlReader($sPath) {
        $aResult = array();
        if($sPath !=""){
                // Output this xml file
            $sXML = simplexml_load_file($sPath);

            if ($sXML) {
                foreach ($sXML->body as $oBodyValues) {
                    //    if choose xml file read value an set it
                    $aResult["name"] = $oBodyValues->Name;
                    $aResult["desc"] = $oBodyValues->Description;
                    $aResult["code_input"] = $oBodyValues->CodeInput;
                    $aResult["code_output"] = $oBodyValues->CodeOutput;
                }

            }
        }

        return $aResult;
     } // end of function

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file list an subnavigation.
     * This is neccessary, if filenames where changed, when a history entry is restored
     *
     * @param integer $iIdClient id of client which contains this file
     * @param string $sArea name of CONTENIDO area in which this procedure should be done
     * @param integer $iIdLayout Id of layout to highlight
     * @param object $sess CONTENIDO session object
     *
     * @return string  - Javascript for refrehing frames
     */
    public function renderReloadScript($sArea, $iIdModule, $sess) {
        $sReloadScript = "<script type=\"text/javascript\">
                 var left_bottom = top.content.left.left_bottom;

                 if(left_bottom){
                    var href = '".$sess->url("main.php?area=$sArea&frame=2&idmod=$iIdModule")."';
                    left_bottom.location.href = href;
                 }

                 </script>";
        return $sReloadScript;
    }
}
?>