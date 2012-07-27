<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This class provides validation methods for HTTP parameters (GET and POST).
 * Originally based on work of kummer and started by discussion in CONTENIDO forum this class
 * is a little bit "re-writed" for better interaction with CONTENIDO.
 * Thanks to Andreas Kummer (aka kummer) for this great idea!
 *
 * Requirements:
 * @con_php_req 5.0
 * @con_notice ToDo: Error page re-direction?
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.2
 * @author     Andreas Kummer, Holger Librenz
 * @copyright  atelierQ Kummer, four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 *
 * {@internal
 *   created 2008-02-06
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * This class is the extended version of excelent
 * code made by kummer.
 *
 * @deprecated [2012-07-03] This class was replaced by cRequestValidator
 * @version 1.0.1
 * @see http://contenido.org/forum/viewtopic.php?p=113492#113492
 */
class HttpInputValidator extends cRequestValidator {

    /**
     * Constructor
     *
     * Configuration path $sConfigPath is mandatory and has to contain the complete
     * path to configuration file with defined parameters.
     *
     * The class provides two modes: training and arcade.
     * Training mode only logs violations - if log path is given into log file otherwise
     * as comment into HTML output. Arcade mode is made for killing - every violation will
     * cause an hard exit!
     *
     * @deprecated [2012-07-03] This class was replaced by cRequestValidator
     * @param string $sConfigPath
     * @return HttpInputValidator
     */
    function HttpInputValidator($sConfigPath) {
        cDeprecated("This class was replaced by cRequestValidator");
        parent::__construct($sConfigPath);
    }

    /**
     * Print html comment or returns (depending on flag $bReturn) all POST params.
     *
     * @deprecated [2012-07-03] This class was replaced by cRequestValidator
     * @return string
     */
    function showPosts($bReturn = false) {
        cDeprecated("This class was replaced by cRequestValidator");
        return "dummy function";
    }

    /**
     * Checks POST param $sKey is unknown (result is null), known but invalid (result is false)
     * or it is known and valid (result is true).
     *
     * @deprecated [2012-07-03] This class was replaced by cRequestValidator
     * @param string $sKey
     * @return mixed
     */
    function isRegularPost($sKey) {
        cDeprecated("This class was replaced by cRequestValidator");
        return true;
    }

}

?>