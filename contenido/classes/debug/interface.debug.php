<?php
/**
 * This file contains the debug interface.
 *
 * @package    Core
 * @subpackage Debug
 * @version    SVN Revision $Rev:$
 *
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Debug interface.
 * Can be extended to a visible, invisible, logged, ...
 *
 * @package Core
 * @subpackage Debug
 */
interface cDebugInterface {

    static public function getInstance();

    public function show($mVariable, $sVariableDescription = '', $bExit = false);

    public function add($mVariable, $sVariableDescription = '');

    public function reset();

    public function showAll();

    public function out($sText);

}
