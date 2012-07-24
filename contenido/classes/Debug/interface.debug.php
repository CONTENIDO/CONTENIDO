<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Debug interface. Can be extended to a visible, invisible, logged, ...
 * Debugger
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.1.1
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
interface cDebugInterface {

    static public function getInstance();

    public function show($mVariable, $sVariableDescription = '', $bExit = false);

    public function add($mVariable, $sVariableDescription = '');

    public function reset();

    public function showAll();

    public function out($sText);

}

/**
 * @deprecated [2012-07-24] interface was renamed to cDebugInterface
 */
interface IDebug extends cDebugInterface {

}