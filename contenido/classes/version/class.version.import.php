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
 * @package CONTENIDO Backend Classes
 * @version 1.0.0
 * @author Bilal Arslan, Timo Trautmann
 * @copyright four for business AG <info@contenido.org>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release >= 4.8.8
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cVersionImport extends cVersion {

    /**
     * The class versionImport object constructor, initializes class variables
     *
     * @param string $iIdMod The name of style file
     * @param array $aCfg
     * @param array $aCfgClient
     * @param object $oDB
     * @param integer $iClient
     * @param object $sArea
     * @param object $iFrame
     *
     * @return void its only initialize class members
     * @deprecated [2012-07-02] Do not use this class!
     */
    public function __construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame) {
        cDeprecated("Do not use this class!");
    }

    /**
     * Creats xml files from table mod_history if exists any rows.
     * After create a version it will be delete the current row.
     * If no rows any available, it will be drop the table mod_history.
     *
     * @return void
     * @deprecated [2012-07-02] Do not use this method!
     */
    public function CreateHistoryVersion() {
        cDeprecated("Do not use this method!");
    }

    /**
     * Function reads rows variables from table con_mod and init with the class
     * members.
     *
     * @return void
     * @deprecated [2012-07-02] Do not use this method!
     */
    private function getModuleHistoryTable() {
        cDeprecated("Do not use this method!");
    }

    /**
     * Set with the body nodes of xml file
     *
     * @return void
     * @deprecated [2012-07-02] Do not use this method!
     */
    private function createBodyXML() {
        cDeprecated("Do not use this method!");
    }

    /**
     * Get all rows in tabel mod_con_history
     *
     * @return integer count of rows
     * @deprecated [2012-07-02] Do not use this method!
     */
    private function getRows() {
        cDeprecated("Do not use this method!");
    }

    /**
     * Drops table if table exists
     *
     * @return void
     * @deprecated [2012-07-02] Do not use this method!
     */
    public function dropTable() {
        cDeprecated("Do not use this method!");
    }

    /**
     * Deletes the row wich id of mod_history
     *
     * @return void
     * @deprecated [2012-07-02] Do not use this method!
     */
    public function deleteRows($iModHistory) {
        cDeprecated("Do not use this method!");
    }

}
