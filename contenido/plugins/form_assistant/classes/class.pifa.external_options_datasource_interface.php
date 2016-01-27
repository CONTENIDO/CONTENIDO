<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract class to read labels and values to be used for PIFA field options
 * from an external datasource.
 *
 * @author marcus.gnass
 */
abstract class PifaExternalOptionsDatasourceInterface {

    /**
     * Gets all option labels.
     *
     * @return array of labels to be used for PIFA field options
     */
    public abstract function getOptionLabels();

    /**
     * Gets all option values.
     *
     * @return array of values to be used for PIFA field options
     */
    public abstract function getOptionValues();
}
