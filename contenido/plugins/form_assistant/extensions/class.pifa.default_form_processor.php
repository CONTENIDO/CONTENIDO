<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 * Default implementation of the abstract form processor that implements no
 * postprocessing at all.
 *
 * @author marcus.gnass
 */
class DefaultFormProcessor extends PifaAbstractFormProcessor {

    /**
     *
     * @see PifaAbstractFormProcessor::_processReadData()
     */
    protected function _processReadData() {
    }

    /**
     *
     * @see PifaAbstractFormProcessor::_processValidatedData()
     */
    protected function _processValidatedData() {
    }

    /**
     *
     * @see PifaAbstractFormProcessor::_processStoredData()
     */
    protected function _processStoredData() {
    }

}
